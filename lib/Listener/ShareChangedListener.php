<?php
declare(strict_types=1);

namespace OCA\TeamFolders\Listener;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Share\Events\ShareCreatedEvent;
use OCP\Share\Events\ShareDeletedEvent;
use OCA\TeamFolders\Service\Indexer;

/** Share events enqueue small idempotent repairs; no tree walk occurs in a request. */
final class ShareChangedListener implements IEventListener {
    public function __construct(private Indexer $indexer) {}
    public function handle(Event $event): void {
        if ($event instanceof ShareCreatedEvent) $this->indexer->indexShare($event->getShare());
        if ($event instanceof ShareDeletedEvent) $this->indexer->deleteShare($event->getShare()->getFullId());
    }
}
