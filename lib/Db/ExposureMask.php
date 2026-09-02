<?php
declare(strict_types=1);

namespace OCA\TeamFolders\Db;

use OCA\TeamFolders\Service\Exposure;

final class ExposureMask {
    public static function known(int $mask): int { return Exposure::normalize($mask); }
}
