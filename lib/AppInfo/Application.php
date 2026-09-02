<?php
declare(strict_types=1);

namespace OCA\TeamFolders\AppInfo;

use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCA\TeamFolders\Listener\LoadFilesAssetsListener;
use OCA\TeamFolders\Listener\ShareChangedListener;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Share\Events\BeforeShareDeletedEvent;
use OCP\Share\Events\ShareCreatedEvent;

final class Application extends App implements IBootstrap {
    public const APP_ID = 'team_folders';
    public function __construct() { parent::__construct(self::APP_ID); }
    public function register(IRegistrationContext $context): void {
        $context->registerEventListener(LoadAdditionalScriptsEvent::class, LoadFilesAssetsListener::class);
        $context->registerEventListener(ShareCreatedEvent::class, ShareChangedListener::class);
        $context->registerEventListener(BeforeShareDeletedEvent::class, ShareChangedListener::class);
    }
    public function boot(IBootContext $context): void {}
}
