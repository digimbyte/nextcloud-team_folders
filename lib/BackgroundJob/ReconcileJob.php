<?php
declare(strict_types=1);

namespace OCA\TeamFolders\BackgroundJob;

use OCA\TeamFolders\Service\Indexer;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;

final class ReconcileJob extends TimedJob {
    public function __construct(ITimeFactory $time, private Indexer $indexer) {
        parent::__construct($time);
        $this->setInterval(900);
        $this->setAllowParallelRuns(false);
    }
    protected function run($argument): void { $this->indexer->repairDirtyBatch(500); }
}
