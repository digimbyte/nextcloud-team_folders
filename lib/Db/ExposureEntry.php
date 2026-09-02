<?php
declare(strict_types=1);

namespace OCA\TeamFolders\Db;

use OCP\AppFramework\Db\Entity;

/** @method int getStorageId() @method int getNodeId() @method int getParentId() @method int getDirectMask() @method int getDescendantMask() @method int getDirty() @method int getIsFolder() */
final class ExposureEntry extends Entity {
    protected int $storageId = 0;
    protected int $nodeId = 0;
    protected int $parentId = 0;
    protected int $directMask = 0;
    protected int $descendantMask = 0;
    protected int $dirty = 1;
    protected int $generation = 0;
    protected int $updatedAt = 0;
    protected int $isFolder = 1;

    public function __construct() {
        $this->addType('storageId', 'integer');
        $this->addType('nodeId', 'integer');
        $this->addType('parentId', 'integer');
        $this->addType('directMask', 'integer');
        $this->addType('descendantMask', 'integer');
        $this->addType('dirty', 'integer');
        $this->addType('generation', 'integer');
        $this->addType('updatedAt', 'integer');
        $this->addType('isFolder', 'integer');
    }
}
