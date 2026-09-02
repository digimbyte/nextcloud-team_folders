<?php
declare(strict_types=1);

namespace OCA\TeamFolders\Service;

use OCA\TeamFolders\Db\ExposureEntryMapper;
use OCP\Files\Folder;
use OCP\Files\Node;

final class ExposureIndex {
    public function __construct(private ExposureEntryMapper $mapper) {}

    /**
     * Only accepts Nodes already resolved through the current user's Folder view.
     * This is the authorization boundary: never expose arbitrary indexed node IDs.
     * @param list<Node> $nodes
     * @return array<string, array{direct:list<string>,nested:list<string>,stale:bool}>
     */
    public function describeVisibleNodes(array $nodes): array {
        $grouped = [];
        foreach ($nodes as $node) {
            if (!$node instanceof Folder) continue;
            $storageId = (int)$node->getStorage()->getId();
            $grouped[$storageId][] = $node;
        }
        $result = [];
        foreach ($grouped as $storageId => $storageNodes) {
            $entries = $this->mapper->findForStorageAndNodes((int)$storageId, array_map(static fn(Node $n): int => $n->getId(), $storageNodes));
            foreach ($storageNodes as $node) {
                $entry = $entries[$node->getId()] ?? null;
                $result[$node->getName()] = [
                    'direct' => Exposure::labels($entry?->getDirectMask() ?? 0),
                    'nested' => Exposure::labels($entry?->getDescendantMask() ?? 0),
                    'stale' => $entry === null || $entry->getDirty() === 1,
                ];
            }
        }
        return $result;
    }
}
