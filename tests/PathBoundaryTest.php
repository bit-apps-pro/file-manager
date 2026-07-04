<?php

declare(strict_types=1);

namespace BitApps\FM\Tests;

use BitApps\FM\Providers\PermissionsProvider;
use PHPUnit\Framework\TestCase;

class PathBoundaryTest extends TestCase
{
    public function testExactPathIsUnderBoundary(): void
    {
        $this->assertTrue(PermissionsProvider::isSubPath('/var/www/f', '/var/www/f'));
    }

    public function testNestedChildIsUnderBoundary(): void
    {
        $this->assertTrue(PermissionsProvider::isSubPath('/var/www/f/sub/a.txt', '/var/www/f'));
    }

    public function testTrailingSeparatorIsIgnored(): void
    {
        $this->assertTrue(PermissionsProvider::isSubPath('/var/www/f/', '/var/www/f'));
        $this->assertTrue(PermissionsProvider::isSubPath('/var/www/f/sub', '/var/www/f/'));
    }

    public function testSiblingWithSharedPrefixIsNotUnderBoundary(): void
    {
        $this->assertFalse(PermissionsProvider::isSubPath('/var/www/foobar', '/var/www/foo'));
    }

    public function testUnrelatedPathIsNotUnderBoundary(): void
    {
        $this->assertFalse(PermissionsProvider::isSubPath('/etc/passwd', '/var/www/f'));
    }

    public function testEmptyBoundaryIsNeverUnder(): void
    {
        $this->assertFalse(PermissionsProvider::isSubPath('/var/www/f', ''));
    }
}
