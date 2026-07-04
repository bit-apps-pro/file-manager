<?php

declare(strict_types=1);

namespace BitApps\FM\Tests;

use BitApps\FM\Providers\AccessControlProvider;
use BitApps\FM\Providers\PermissionsProvider;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

// Partial mocks (disabled ctor, stubbed getters) carry no ->expects(); opt out of
// PHPUnit 13's "mock without expectations" notice so test output stays pristine.
#[AllowMockObjectsWithoutExpectations]
class CheckPermissionStrictTest extends TestCase
{
    /** @return AccessControlProvider */
    private function accessControl(): AccessControlProvider
    {
        // disableOriginalConstructor avoids Plugin::instance()->preferences() in ctor.
        return $this->getMockBuilder(AccessControlProvider::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
    }

    private function permissions(array $pathToCommands): PermissionsProvider
    {
        $mock = $this->getMockBuilder(PermissionsProvider::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getEnabledCommandsForPath'])
            ->getMock();

        $mock->method('getEnabledCommandsForPath')
            ->willReturnCallback(static function (string $path) use ($pathToCommands): array {
                return $pathToCommands[$path] ?? [];
            });

        return $mock;
    }

    public function testCommandAllowedWhenPresentForAllPaths(): void
    {
        $access = $this->accessControl();
        $perms  = $this->permissions(['/f/a.txt' => ['download', 'edit']]);

        $this->assertTrue($access->isCommandAllowedForPaths('edit', ['/f/a.txt'], $perms));
    }

    public function testCommandDeniedWhenMissingForAnyPath(): void
    {
        $access = $this->accessControl();
        $perms  = $this->permissions([
            '/f/a.txt'   => ['download'],       // per-user: download only
            '/other.txt' => ['edit', 'upload'], // role
        ]);

        // 'edit' allowed for /other.txt but NOT for /f/a.txt -> overall denied.
        $this->assertFalse($access->isCommandAllowedForPaths('edit', ['/f/a.txt', '/other.txt'], $perms));
    }

    public function testWildcardAllowsAnyCommand(): void
    {
        $access = $this->accessControl();
        $perms  = $this->permissions(['/x' => ['*']]);

        $this->assertTrue($access->isCommandAllowedForPaths('rm', ['/x'], $perms));
    }

    public function testEmptyPathsIsUnconstrained(): void
    {
        $access = $this->accessControl();
        $perms  = $this->permissions([]);

        $this->assertTrue($access->isCommandAllowedForPaths('rm', [], $perms));
    }

    public function testResolveInvolvedPathsCollectsTargetTargetsAndDst(): void
    {
        $access = $this->accessControl();

        $volume = new class {
            public function getPath($hash)
            {
                return '/resolved/' . $hash;
            }
        };
        $elfinder = new class($volume) {
            private $volume;
            public function __construct($volume)
            {
                $this->volume = $volume;
            }
            public function getVolume($hash)
            {
                return $this->volume;
            }
        };

        $paths = $access->resolveInvolvedPaths(
            ['target' => 'h1', 'targets' => ['h2', 'h3'], 'dst' => 'h4'],
            $elfinder
        );

        sort($paths);
        $this->assertSame(
            ['/resolved/h1', '/resolved/h2', '/resolved/h3', '/resolved/h4'],
            $paths
        );
    }

    public function testResolveInvolvedPathsIncludesUploadPathDestinations(): void
    {
        $access = $this->accessControl();

        $volume = new class {
            public function getPath($hash)
            {
                return '/resolved/' . $hash;
            }
        };
        $elfinder = new class($volume) {
            private $volume;
            public function __construct($volume)
            {
                $this->volume = $volume;
            }
            public function getVolume($hash)
            {
                return $this->volume;
            }
        };

        $paths = $access->resolveInvolvedPaths(
            ['target' => 'h1', 'upload_path' => ['h2', 'h3']],
            $elfinder
        );

        sort($paths);
        $this->assertSame(['/resolved/h1', '/resolved/h2', '/resolved/h3'], $paths);
    }
}
