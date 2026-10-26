<?php

declare(strict_types=1);

namespace BitApps\FM\Tests;

use BitApps\FM\Plugin;
use BitApps\FM\Providers\AccessControlProvider;
use BitApps\FM\Providers\PermissionsProvider;
use elFinder;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;
use WP_Mock;

/**
 * Locks the security property that makes excluding `file` from the connector
 * pre-check safe: the `*.pre` bind calls the SAME checkPermission() with full
 * elFinder args, where `file` (read/download) is authorized ONLY by the `download`
 * grant for the target path — regardless of the file's MIME type. There is no
 * MIME "escape hatch" (removed in the 6.9.x A-1 fix), so a user without the
 * download grant cannot read image/application files.
 * See AccessControlProvider::CONNECTOR_UNCHECKED_COMMANDS.
 */
#[AllowMockObjectsWithoutExpectations]
class FileCommandEscapeHatchTest extends TestCase
{
    private ReflectionProperty $pluginInstance;

    protected function setUp(): void
    {
        WP_Mock::setUp();
        WP_Mock::userFunction('__', ['return_arg' => 0]);

        $this->pluginInstance = new ReflectionProperty(Plugin::class, '_instance');
    }

    protected function tearDown(): void
    {
        $this->pluginInstance->setValue(null, null);
        WP_Mock::tearDown();
    }

    public function testFileWithEnabledTypeIsDeniedWithoutDownloadPermission(): void
    {
        // An image (enabled type) must NOT be readable without the download grant —
        // the removed MIME re-permit used to allow exactly this (A-1).
        $access = $this->accessControl($this->permissions(['image'], false));

        $result = $access->checkPermission('file', ['target' => 'h1'], $this->elfinder());

        $this->assertIsArray($result, 'file without the download grant must be denied even for an enabled type');
        $this->assertTrue($result['preventexec']);
        $this->assertArrayHasKey('error', $result['results']);
    }

    public function testFileWithDisallowedTypeIsDeniedWithPreventexec(): void
    {
        $access = $this->accessControl($this->permissions([], false));

        $result = $access->checkPermission('file', ['target' => 'h1'], $this->elfinder());

        $this->assertIsArray($result, 'a denied file command must return the preventexec payload');
        $this->assertTrue($result['preventexec']);
        $this->assertArrayHasKey('error', $result['results']);
    }

    public function testFileIsAllowedWithDownloadGrant(): void
    {
        // With the download grant the read is authorized — the grant is the only gate.
        $access = $this->accessControl($this->permissions(['image'], true));

        $this->assertNull(
            $access->checkPermission('file', ['target' => 'h1'], $this->elfinder()),
            'file must be allowed when the user holds the download grant'
        );
    }

    /** AccessControlProvider under test, with the Plugin singleton pinned to $permissions. */
    private function accessControl(PermissionsProvider $permissions): AccessControlProvider
    {
        // Plugin is final (unmockable); build it without its heavy constructor and seed
        // the container so Plugin::instance()->permissions() returns the mock.
        $plugin    = (new ReflectionClass(Plugin::class))->newInstanceWithoutConstructor();
        $container = new ReflectionProperty(Plugin::class, '_container');
        $container->setValue($plugin, ['permissions' => $permissions]);
        $this->pluginInstance->setValue(null, $plugin);

        return $this->getMockBuilder(AccessControlProvider::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
    }

    /** PermissionsProvider with the given download grant and enabled file types. */
    private function permissions(array $enabledFileTypes, bool $canDownload): PermissionsProvider
    {
        $mock = $this->getMockBuilder(PermissionsProvider::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['currentUserCanRun', 'allCommands', 'commandLabel', 'getEnabledFileType', 'requestContext'])
            ->getMock();

        $mock->method('currentUserCanRun')->willReturn($canDownload);
        $mock->method('allCommands')->willReturn([]);
        $mock->method('commandLabel')->willReturn('');
        $mock->method('getEnabledFileType')->willReturn($enabledFileTypes);
        $mock->method('requestContext')->willReturn(PermissionsProvider::CONTEXT_FRONTEND);

        return $mock;
    }

    /** elFinder whose volume resolves the target hash to a .png path. */
    private function elfinder(): elFinder
    {
        $volume = new class {
            public function getPath($hash)
            {
                return '/vol/pic.png';
            }
        };

        $mock = $this->getMockBuilder(elFinder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getVolume'])
            ->getMock();
        $mock->method('getVolume')->willReturn($volume);

        return $mock;
    }
}
