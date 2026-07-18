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
 * elFinder args, where the isFileAllowedToOpen escape hatch allows preview of an
 * enabled file type but still denies a disallowed one — even without download
 * permission. See AccessControlProvider::CONNECTOR_UNCHECKED_COMMANDS.
 */
#[AllowMockObjectsWithoutExpectations]
class FileCommandEscapeHatchTest extends TestCase
{
    private ReflectionProperty $pluginInstance;

    protected function setUp(): void
    {
        WP_Mock::setUp();
        WP_Mock::userFunction('__', ['return_arg' => 0]);
        WP_Mock::userFunction('wp_basename', ['return' => 'pic.png']);
        WP_Mock::userFunction('wp_check_filetype_and_ext', [
            'return' => ['ext' => 'png', 'type' => 'image/png'],
        ]);

        $this->pluginInstance = new ReflectionProperty(Plugin::class, '_instance');
    }

    protected function tearDown(): void
    {
        $this->pluginInstance->setValue(null, null);
        WP_Mock::tearDown();
    }

    public function testFileWithEnabledTypeIsAllowedEvenWithoutDownloadPermission(): void
    {
        $access = $this->accessControl($this->permissions(['image']));

        $this->assertNull(
            $access->checkPermission('file', ['target' => 'h1'], $this->elfinder()),
            'enabled-type preview must be allowed by the escape hatch'
        );
    }

    public function testFileWithDisallowedTypeIsDeniedWithPreventexec(): void
    {
        $access = $this->accessControl($this->permissions([]));

        $result = $access->checkPermission('file', ['target' => 'h1'], $this->elfinder());

        $this->assertIsArray($result, 'a denied file command must return the preventexec payload');
        $this->assertTrue($result['preventexec']);
        $this->assertArrayHasKey('error', $result['results']);
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

    /** PermissionsProvider with download denied and the given enabled file types. */
    private function permissions(array $enabledFileTypes): PermissionsProvider
    {
        $mock = $this->getMockBuilder(PermissionsProvider::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['currentUserCanRun', 'allCommands', 'commandLabel', 'getEnabledFileType'])
            ->getMock();

        $mock->method('currentUserCanRun')->willReturn(false);
        $mock->method('allCommands')->willReturn([]);
        $mock->method('commandLabel')->willReturn('');
        $mock->method('getEnabledFileType')->willReturn($enabledFileTypes);

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
