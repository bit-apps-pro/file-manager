<?php

declare(strict_types=1);

namespace BitApps\FM\Tests;

use BitApps\FM\Providers\PermissionsProvider;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use WP_Mock;

#[AllowMockObjectsWithoutExpectations]
class EnabledCommandsForPathTest extends TestCase
{
    protected function setUp(): void
    {
        WP_Mock::setUp();
    }

    protected function tearDown(): void
    {
        WP_Mock::tearDown();
    }

    /** @return PermissionsProvider&\PHPUnit\Framework\MockObject\MockObject */
    private function provider(array $methods)
    {
        $mock = $this->getMockBuilder(PermissionsProvider::class)
            ->disableOriginalConstructor()
            ->onlyMethods(array_keys($methods))
            ->getMock();

        foreach ($methods as $name => $return) {
            $mock->method($name)->willReturn($return);
        }

        return $mock;
    }

    public function testLoggedOutReturnsGuestCommands(): void
    {
        WP_Mock::userFunction('is_user_logged_in', ['return' => false]);

        $provider = $this->provider([
            'getGuestPermissions'         => ['commands' => ['download'], 'path' => '/pub'],
            'isCurrentUserHasPermission'  => false,
            'permissionsForCurrentUser'   => ['commands' => [], 'path' => ''],
            'permissionsForCurrentRole'   => ['commands' => ['edit'], 'path' => '/role'],
            'currentUserID'               => 0,
        ]);

        $this->assertSame(['download'], $provider->getEnabledCommandsForPath('/pub/x'));
    }

    public function testPathUnderUserFolderReturnsUserCommands(): void
    {
        WP_Mock::userFunction('is_user_logged_in', ['return' => true]);

        $provider = $this->provider([
            'getGuestPermissions'        => ['commands' => [], 'path' => ''],
            'isCurrentUserHasPermission' => true,
            'permissionsForCurrentUser'  => ['commands' => ['download'], 'path' => __DIR__],
            'permissionsForCurrentRole'  => ['commands' => ['edit', 'upload'], 'path' => '/role'],
            'currentUserID'              => 7,
        ]);

        // __DIR__ exists so realpath() resolves; a child path is under it.
        $child = __DIR__ . '/child.txt';
        $this->assertSame(['download'], $provider->getEnabledCommandsForPath($child));
    }

    public function testPathOutsideUserFolderReturnsRoleCommands(): void
    {
        WP_Mock::userFunction('is_user_logged_in', ['return' => true]);

        $provider = $this->provider([
            'getGuestPermissions'        => ['commands' => [], 'path' => ''],
            'isCurrentUserHasPermission' => true,
            'permissionsForCurrentUser'  => ['commands' => ['download'], 'path' => __DIR__],
            'permissionsForCurrentRole'  => ['commands' => ['edit', 'upload'], 'path' => '/role'],
            'currentUserID'              => 7,
        ]);

        $this->assertSame(['edit', 'upload'], $provider->getEnabledCommandsForPath('/somewhere/else'));
    }

    public function testNoPerUserConfigReturnsRoleCommands(): void
    {
        WP_Mock::userFunction('is_user_logged_in', ['return' => true]);

        $provider = $this->provider([
            'getGuestPermissions'        => ['commands' => [], 'path' => ''],
            'isCurrentUserHasPermission' => false,
            'permissionsForCurrentUser'  => ['commands' => [], 'path' => ''],
            'permissionsForCurrentRole'  => ['commands' => ['edit'], 'path' => '/role'],
            'currentUserID'              => 7,
        ]);

        $this->assertSame(['edit'], $provider->getEnabledCommandsForPath(__DIR__ . '/x'));
    }

    public function testNonexistentChildUnderUserFolderStaysUser(): void
    {
        WP_Mock::userFunction('is_user_logged_in', ['return' => true]);

        $provider = $this->provider([
            'getGuestPermissions'        => ['commands' => [], 'path' => ''],
            'isCurrentUserHasPermission' => true,
            'permissionsForCurrentUser'  => ['commands' => ['download'], 'path' => __DIR__],
            'permissionsForCurrentRole'  => ['commands' => ['edit'], 'path' => '/role'],
        ]);

        // Nonexistent child of the real tests/ dir -> parent realpaths to __DIR__ -> under F.
        $this->assertSame(['download'], $provider->getEnabledCommandsForPath(__DIR__ . '/no-such-file.txt'));
    }

    public function testTraversalOutOfUserFolderFallsBackToRole(): void
    {
        WP_Mock::userFunction('is_user_logged_in', ['return' => true]);

        $provider = $this->provider([
            'getGuestPermissions'        => ['commands' => [], 'path' => ''],
            'isCurrentUserHasPermission' => true,
            'permissionsForCurrentUser'  => ['commands' => ['download'], 'path' => __DIR__],
            'permissionsForCurrentRole'  => ['commands' => ['edit'], 'path' => '/role'],
        ]);

        // Raw '..' path whose real parent (repo root, dirname(dirname(__DIR__))) is OUTSIDE F.
        $escaping = __DIR__ . '/../../no-such-file.txt';
        $this->assertSame(['edit'], $provider->getEnabledCommandsForPath($escaping));
    }
}
