<?php
declare(strict_types=1);

namespace OCA\TeamFolders\Listener;

use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Util;

/** @implements IEventListener<LoadAdditionalScriptsEvent> */
final class LoadFilesAssetsListener implements IEventListener {
    public function handle(Event $event): void {
        if (!$event instanceof LoadAdditionalScriptsEvent) return;
        Util::addScript('team_folders', 'team-folders-main');
        Util::addStyle('team_folders', 'indicators');
    }
}
