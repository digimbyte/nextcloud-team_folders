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
     * @return array<string, array{solid:list<string>,ghost:list<string>}>
     */
    public function describeVisibleNodes(Folder $folder): array {
        $nodes = $folder->getDirectoryListing();
        $ancestorMasks = [];
        $current = $folder;
        while (true) {
            $storageId = NodeIdentity::storageId($current);
            $entry = $this->mapper->findRaw($storageId, $current->getId());
            $ancestorMasks[$storageId] = ($ancestorMasks[$storageId] ?? 0) | ($entry['direct_mask'] ?? 0);
            try { $current = $current->getParent(); } catch (\Throwable) { break; }
            if ($current->getPath() === '/') break;
        }
        $grouped = [];
        foreach ($nodes as $node) {
            if (!$node instanceof Folder) continue;
            $storageId = NodeIdentity::storageId($node);
            $grouped[$storageId][] = $node;
        }
        $result = [];
        foreach ($grouped as $storageId => $storageNodes) {
            $entries = $this->mapper->findForStorageAndNodes((int)$storageId, array_map(static fn(Node $n): int => $n->getId(), $storageNodes));
            foreach ($storageNodes as $node) {
                $entry = $entries[$node->getId()] ?? null;
                $solid = Exposure::normalize(($ancestorMasks[(int)$storageId] ?? 0) | ($entry?->getDirectMask() ?? 0));
                $ghost = Exposure::normalize(($entry?->getDescendantMask() ?? 0) & ~$solid);
                $result[$node->getName()] = [
                    'solid' => Exposure::labels($solid),
                    'ghost' => Exposure::labels($ghost),
                ];
            }
        }
        return $result;
    }
}
