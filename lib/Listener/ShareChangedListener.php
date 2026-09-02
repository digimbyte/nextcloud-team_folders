<?php
declare(strict_types=1);

namespace OCA\TeamFolders\Listener;

use OCP\BackgroundJob\IJobList;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Share\Events\BeforeShareDeletedEvent;
use OCP\Share\Events\ShareCreatedEvent;
use OCA\TeamFolders\BackgroundJob\ReindexNodeJob;

/** Share events enqueue small idempotent repairs; no tree walk occurs in a request. */
final class ShareChangedListener implements IEventListener {
    public function __construct(private IJobList $jobs) {}
    public function handle(Event $event): void {
        if (!$event instanceof ShareCreatedEvent && !$event instanceof BeforeShareDeletedEvent) return;
        $node = $event->getShare()->getNode();
        $this->jobs->add(ReindexNodeJob::class, ['storageId' => (int)$node->getStorage()->getId(), 'nodeId' => $node->getId()]);
    }
}
