<?php
declare(strict_types=1);

namespace OCA\TeamFolders\Listener;

use OCA\TeamFolders\BackgroundJob\RebuildJob;
use OCP\BackgroundJob\IJobList;
use OCP\Server;

/** NC34 still exposes password/expiration updates through legacy share hooks. */
final class LegacyShareHook {
    /** @param array<string,mixed> $parameters */
    public static function changed(array $parameters): void {
        Server::get(IJobList::class)->add(RebuildJob::class);
    }
}
