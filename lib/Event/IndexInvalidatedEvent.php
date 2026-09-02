<?php
declare(strict_types=1);

namespace OCA\TeamFolders\Event;

use OCP\EventDispatcher\Event;

final class IndexInvalidatedEvent extends Event {
    public function __construct(public readonly int $storageId, public readonly int $nodeId) { parent::__construct(); }
}
