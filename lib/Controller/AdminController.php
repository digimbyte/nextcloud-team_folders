<?php
declare(strict_types=1);
namespace OCA\TeamFolders\Controller;
use OCA\TeamFolders\BackgroundJob\RebuildJob;
use OCA\TeamFolders\Service\IndexStateService;
use OCA\TeamFolders\Settings\AdminSettings;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\AppFramework\Http\JSONResponse;
use OCP\BackgroundJob\IJobList;
use OCP\IRequest;
final class AdminController extends Controller {
    public function __construct(string $appName, IRequest $request, private IndexStateService $state, private IJobList $jobs) { parent::__construct($appName, $request); }
    #[AuthorizedAdminSetting(settings: AdminSettings::class)]
    public function status(): JSONResponse { return new JSONResponse($this->state->status()); }
    #[AuthorizedAdminSetting(settings: AdminSettings::class)]
    public function rebuild(): JSONResponse { $this->jobs->add(RebuildJob::class); return new JSONResponse(['queued' => true], 202); }
}
