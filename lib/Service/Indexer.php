<?php
declare(strict_types=1);

namespace OCA\TeamFolders\Service;

use OCA\TeamFolders\Db\ExposureEntryMapper;
use OCA\TeamFolders\Db\ShareFactMapper;
use OCP\Files\Folder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\IUserManager;
use OCP\Share\IManager;
use OCP\Share\IShare;
use Psr\Log\LoggerInterface;

final class Indexer {
    private const SHARE_TYPES = [0, 1, 2, 3, 4, 6, 7, 8, 9, 10, 12, 13, 15];

    public function __construct(
        private ExposureEntryMapper $entries, private ShareFactMapper $facts,
        private ShareClassifier $classifier, private IManager $shares,
        private IUserManager $users, private IndexStateService $state,
        private LoggerInterface $logger,
    ) {}

    public function indexShare(IShare $share, int $generation = 0, bool $failOnError = false): void {
        try {
            $node = $share->getNode();
            $mask = $this->classifier->classify($share);
            if ($mask === Exposure::NONE) { $this->deleteShare($share->getFullId()); return; }
            $now = time();
            $storageId = NodeIdentity::storageId($node);
            $this->ensureChain($node, $generation, $now);
            $this->facts->upsert($share->getFullId(), $storageId, $node->getId(), $mask, $share->getExpirationDate()?->getTimestamp(), $generation, $now);
            $this->recomputeFromNode($storageId, $node->getId());
            $this->state->touchStorage($storageId, 'ready', $generation, null);
        } catch (NotFoundException $e) {
            // Providers can temporarily retain a share after its node has gone.
            // It is invalid input, not a failed reconciliation generation.
            $this->deleteShare($share->getFullId());
        } catch (\Throwable $e) {
            $this->logger->warning('Unable to index share', ['exception' => $e]);
            if ($failOnError) throw $e;
        }
    }

    public function deleteShare(string $shareId): void {
        $location = $this->facts->findLocation($shareId);
        if ($location === null) return;
        $this->facts->delete($shareId);
        $this->recomputeFromNode($location['storage_id'], $location['node_id']);
    }

    public function nodeMoved(Node $source, Node $target): void {
        $oldStorage = NodeIdentity::storageId($source);
        $old = $this->entries->findRaw($oldStorage, $source->getId());
        if ($old === null) return;
        $this->ensureChain($target, 0, time());
        $this->recomputeFromNode($oldStorage, $old['parent_id']);
        $this->recomputeFromNode(NodeIdentity::storageId($target), $target->getId());
    }

    public function nodeDeleted(Node $node): void {
        $storageId = NodeIdentity::storageId($node);
        $row = $this->entries->findRaw($storageId, $node->getId());
        if ($row === null) return;
        $this->entries->deleteNode($storageId, $node->getId());
        $this->recomputeFromNode($storageId, $row['parent_id']);
    }

    /** @return array{generation:int,shares:int} */
    public function rebuild(): array {
        $generation = time();
        $indexed = 0;
        $this->state->begin($generation);
        try {
            $offset = 0;
            do {
                $batch = $this->users->search('', 100, $offset);
                foreach ($batch as $user) {
                    foreach (self::SHARE_TYPES as $type) {
                        if (!$this->shares->shareProviderExists($type)) continue;
                        $shareOffset = 0;
                        do {
                            $shareBatch = $this->shares->getSharesBy($user->getUID(), $type, null, true, 200, $shareOffset, true);
                            foreach ($shareBatch as $share) {
                                $this->indexShare($share, $generation, true);
                                $indexed++;
                            }
                            $shareOffset += count($shareBatch);
                        } while (count($shareBatch) === 200);
                    }
                }
                $offset += count($batch);
            } while (count($batch) === 100);
            $stale = $this->facts->locationsNotInGeneration($generation);
            $this->facts->deleteNotInGeneration($generation);
            foreach ($stale as $location) $this->recomputeFromNode($location['storage_id'], $location['node_id']);
            $this->state->complete($generation);
            return ['generation' => $generation, 'shares' => $indexed];
        } catch (\Throwable $e) {
            $this->state->fail($generation, $e->getMessage());
            throw $e;
        }
    }

    public function reindexNodeAndAncestors(int $storageId, int $nodeId): void { $this->recomputeFromNode($storageId, $nodeId); }
    public function repairDirtyBatch(int $limit): void { $this->rebuild(); }

    private function ensureChain(Node $node, int $generation, int $now): void {
        $current = $node;
        while (true) {
            $storageId = NodeIdentity::storageId($current);
            $parent = null;
            $parentId = 0;
            try {
                $parent = $current->getParent();
                if (NodeIdentity::storageId($parent) === $storageId) $parentId = $parent->getId();
            } catch (\Throwable) {}
            $this->entries->ensure($storageId, $current->getId(), $parentId, $current instanceof Folder, $generation, $now);
            if ($parentId === 0 || $parent === null) break;
            $current = $parent;
        }
    }

    private function recomputeFromNode(int $storageId, int $nodeId): void {
        $now = time();
        while ($nodeId !== 0) {
            $row = $this->entries->findRaw($storageId, $nodeId);
            if ($row === null) break;
            $direct = $this->facts->maskForNode($storageId, $nodeId, $now);
            $descendant = Exposure::normalize($this->entries->childMask($storageId, $nodeId));
            $changed = $direct !== $row['direct_mask'] || $descendant !== $row['descendant_mask'];
            $this->entries->updateMasks($storageId, $nodeId, $direct, $descendant, $now);
            if (!$changed) break;
            $nodeId = $row['parent_id'];
        }
    }
}
