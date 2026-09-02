<?php
declare(strict_types=1);

namespace OCA\TeamFolders\AppInfo;

use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCA\TeamFolders\Listener\LoadFilesAssetsListener;
use OCA\TeamFolders\Listener\ShareChangedListener;
use OCA\TeamFolders\Listener\NodeChangedListener;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Share\Events\ShareCreatedEvent;
use OCP\Share\Events\ShareDeletedEvent;
use OCP\Files\Events\Node\NodeDeletedEvent;
use OCP\Files\Events\Node\NodeRenamedEvent;
use OCA\TeamFolders\Listener\LegacyShareHook;
use OCP\Share;
use OCP\Util;

final class Application extends App implements IBootstrap {
    public const APP_ID = 'team_folders';
    public function __construct() { parent::__construct(self::APP_ID); }
    public function register(IRegistrationContext $context): void {
        $context->registerEventListener(LoadAdditionalScriptsEvent::class, LoadFilesAssetsListener::class);
        $context->registerEventListener(ShareCreatedEvent::class, ShareChangedListener::class);
        $context->registerEventListener(ShareDeletedEvent::class, ShareChangedListener::class);
        $context->registerEventListener(NodeRenamedEvent::class, NodeChangedListener::class);
        $context->registerEventListener(NodeDeletedEvent::class, NodeChangedListener::class);
    }
    public function boot(IBootContext $context): void {
        Util::connectHook(Share::class, 'post_update_password', LegacyShareHook::class, 'changed');
        Util::connectHook(Share::class, 'post_set_expiration_date', LegacyShareHook::class, 'changed');
    }
}
