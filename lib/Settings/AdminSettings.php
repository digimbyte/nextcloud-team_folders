<?php
declare(strict_types=1);
namespace OCA\TeamFolders\Settings;
use OCA\TeamFolders\Service\IndexStateService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Settings\ISettings;
final class AdminSettings implements ISettings {
    public function __construct(private IndexStateService $state) {}
    public function getForm(): TemplateResponse { return new TemplateResponse('team_folders', 'admin', ['status' => $this->state->status()]); }
    public function getSection(): string { return 'server'; }
    public function getPriority(): int { return 90; }
}
