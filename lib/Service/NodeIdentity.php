<?php
declare(strict_types=1);

namespace OCA\TeamFolders\Service;

use OCP\Files\Node;

final class NodeIdentity {
    public static function storageId(Node $node): int {
        return (int)$node->getStorage()->getCache()->getNumericStorageId();
    }
}
