<?php
declare(strict_types=1);

namespace OCA\TeamFolders\Controller;

use OCA\TeamFolders\Service\ExposureIndex;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\IRootFolder;
use OCP\IRequest;
use OCP\IUserSession;

final class IndicatorController extends Controller {
    public function __construct(string $appName, IRequest $request, private IUserSession $session, private IRootFolder $root, private ExposureIndex $index) {
        parent::__construct($appName, $request);
    }

    #[NoAdminRequired]
    public function index(string $dir = '/'): JSONResponse {
        $user = $this->session->getUser();
        if ($user === null) return new JSONResponse(['error' => 'Not authenticated'], 401);
        // Folder::get enforces the current user's mount and ACL view.
        $folder = $this->root->getUserFolder($user->getUID())->get($dir);
        if (!$folder instanceof \OCP\Files\Folder) return new JSONResponse(['error' => 'Not a folder'], 400);
        return new JSONResponse(['directory' => $dir, 'items' => $this->index->describeVisibleNodes($folder->getDirectoryListing())]);
    }
}
