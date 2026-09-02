<?php
declare(strict_types=1);

namespace OCA\TeamFolders\Listener;

use OCA\TeamFolders\Service\Indexer;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Events\Node\NodeDeletedEvent;
use OCP\Files\Events\Node\NodeRenamedEvent;

final class NodeChangedListener implements IEventListener {
    public function __construct(private Indexer $indexer) {}
    public function handle(Event $event): void {
        if ($event instanceof NodeRenamedEvent) $this->indexer->nodeMoved($event->getSource(), $event->getTarget());
        if ($event instanceof NodeDeletedEvent) $this->indexer->nodeDeleted($event->getNode());
    }
}
