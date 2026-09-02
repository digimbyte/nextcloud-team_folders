<?php
declare(strict_types=1);

namespace OCA\TeamFolders\Tests\Unit;

use OCA\TeamFolders\Service\Exposure;
use PHPUnit\Framework\TestCase;

final class ExposureTest extends TestCase {
    public function testPublicAlwaysIncludesLink(): void {
        self::assertSame(Exposure::LINK | Exposure::PUBLIC, Exposure::normalize(Exposure::PUBLIC));
        self::assertSame(['link', 'public'], Exposure::labels(Exposure::PUBLIC));
    }
    public function testUnknownBitsDoNotLeak(): void { self::assertSame([], Exposure::labels(1 << 20)); }
    public function testFlagsAreAdditive(): void { self::assertSame(['people', 'link', 'public'], Exposure::labels(Exposure::PEOPLE | Exposure::PUBLIC)); }
}
