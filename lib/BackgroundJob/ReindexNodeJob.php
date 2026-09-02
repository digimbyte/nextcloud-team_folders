<?php
declare(strict_types=1);

namespace OCA\TeamFolders\BackgroundJob;

use OCA\TeamFolders\Service\Indexer;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;

final class ReindexNodeJob extends QueuedJob {
    public function __construct(ITimeFactory $time, private Indexer $indexer) { parent::__construct($time); }
    protected function run($argument): void { $this->indexer->reindexNodeAndAncestors((int)$argument['storageId'], (int)$argument['nodeId']); }
}
