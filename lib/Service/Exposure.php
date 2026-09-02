<?php
declare(strict_types=1);

namespace OCA\TeamFolders\Service;

/** Stable persisted bit assignments. Never renumber existing flags. */
final class Exposure {
    public const NONE = 0;
    public const PEOPLE = 1;   // user, group, circle or room
    public const LINK = 2;     // link/email share of any access level
    public const PUBLIC = 4;   // link/email share without password/identity gate
    public const FEDERATED = 8;
    public const OTHER = 16;

    public const KNOWN = self::PEOPLE | self::LINK | self::PUBLIC | self::FEDERATED | self::OTHER;

    public static function normalize(int $mask): int {
        $mask &= self::KNOWN;
        // Public exposure necessarily implies that a link exists.
        if (($mask & self::PUBLIC) !== 0) {
            $mask |= self::LINK;
        }
        return $mask;
    }

    /** @return list<string> */
    public static function labels(int $mask): array {
        $mask = self::normalize($mask);
        $labels = [];
        foreach ([self::PEOPLE => 'people', self::LINK => 'link', self::PUBLIC => 'public', self::FEDERATED => 'federated', self::OTHER => 'other'] as $flag => $label) {
            if (($mask & $flag) !== 0) {
                $labels[] = $label;
            }
        }
        return $labels;
    }
}
