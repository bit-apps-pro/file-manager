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

    /** Navigation/utility commands must short-circuit as allowed (return null). */
    public function testNavigationAndUtilityCommandsAreExempt(): void
    {
        $access = $this->accessControl();

        // Independent oracle: a managed command must never leak into this list, or the
        // `*.pre` gate would be bypassed for it. Iterating the const itself would pass
        // tautologically, so pin it against a hardcoded expectation.
        $expected = ['open', 'search', 'subdirs', 'url', 'abort', 'callback'];
        $this->assertSame($expected, AccessControlProvider::EXEMPT_COMMANDS);

        foreach ($expected as $cmd) {
            $this->assertNull($access->checkPermission($cmd), $cmd . ' must be exempt');
        }
    }

    public function testManagedCommandsAreNotExempt(): void
    {
        foreach (['rm', 'put', 'get', 'upload', 'rename', 'chmod', 'mkdir', 'mkfile', 'extract', 'file', 'zipdl'] as $cmd) {
            $this->assertNotContains($cmd, AccessControlProvider::EXEMPT_COMMANDS, $cmd . ' must not be exempt');
        }
    }

    /**
     * `file` (read) must be gated only by the `*.pre` bind, never by the argless
     * connector pre-check — otherwise preview of an enabled file type by a user
     * without download permission is wrongly blocked before run() resolves the target.
     */
    public function testConnectorDoesNotEnforceFileButEnforcesDestructiveCommands(): void
    {
        $access = $this->accessControl();

        $this->assertFalse($access->isConnectorEnforceable('file'), 'file must defer to the *.pre bind');
        $this->assertFalse($access->isConnectorEnforceable(''), 'empty command is a no-op');
        $this->assertSame(['file'], AccessControlProvider::CONNECTOR_UNCHECKED_COMMANDS);

        foreach (['rm', 'put', 'get', 'upload', 'rename', 'chmod', 'mkdir', 'mkfile', 'extract', 'zipdl'] as $cmd) {
            $this->assertTrue($access->isConnectorEnforceable($cmd), $cmd . ' must be enforced at the connector');
        }
    }

    /** elFinder stub whose getVolume() resolves any hash to "/resolved/{hash}". */
    private function elfinderStub(): object
    {
        $volume = new class {
            public function getPath($hash)
            {
                return '/resolved/' . $hash;
            }
        };

        return new class($volume) {
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
    }

    public function testResolveInvolvedPathsCollectsTargetTargetsAndDst(): void
    {
        $access = $this->accessControl();

        $paths = $access->resolveInvolvedPaths(
            ['target' => 'h1', 'targets' => ['h2', 'h3'], 'dst' => 'h4'],
            $this->elfinderStub()
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

        $paths = $access->resolveInvolvedPaths(
            ['target' => 'h1', 'upload_path' => ['h2', 'h3']],
            $this->elfinderStub()
        );

        sort($paths);
        $this->assertSame(['/resolved/h1', '/resolved/h2', '/resolved/h3'], $paths);
    }
}
