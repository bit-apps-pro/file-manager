<?php

declare(strict_types=1);

namespace BitApps\FM\Tests;

use BitApps\FM\Providers\PermissionsProvider;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use WP_Mock;

/**
 * Regression guard for the connector privilege-escalation fix: the admin-area
 * (site-root) volume must require the admin File Manager capability, not merely a
 * logged-in session on the connector action. A valid wp_rest nonce is held by every
 * logged-in user, so authorization has to come from a capability check.
 */
#[AllowMockObjectsWithoutExpectations]
class AdminAreaAuthorizationTest extends TestCase
{
    protected function setUp(): void
    {
        WP_Mock::setUp();
        WP_Mock::userFunction('sanitize_key', ['return_arg' => 0]);
        // Hooks::applyFilter($cap, $default) forwards to apply_filters; passthrough the default cap.
        WP_Mock::userFunction('apply_filters', ['return' => static fn($tag, $value) => $value]);
    }

    protected function tearDown(): void
    {
        unset($_REQUEST['action']);
        WP_Mock::tearDown();
    }

    private function permissions(): PermissionsProvider
    {
        return $this->getMockBuilder(PermissionsProvider::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
    }

    /**
     * Grant only the admin File Manager caps, so the test pins the *specific* capability
     * the gate must require — weakening it (e.g. to 'read') would flip these to false.
     */
    private function withCapability(bool $granted): void
    {
        $adminCaps = ['manage_options', 'bitapps_fm_can_access_home'];
        WP_Mock::userFunction('current_user_can', [
            'return' => static fn($cap) => $granted && \in_array($cap, $adminCaps, true),
        ]);
    }

    public function testLoggedInWithoutCapabilityIsNotAdminArea(): void
    {
        $_REQUEST['action'] = 'bitapps_fm_connector';
        WP_Mock::userFunction('is_user_logged_in', ['return' => true]);
        $this->withCapability(false);

        // The vulnerability: a Subscriber holding a wp_rest nonce must NOT be treated
        // as an admin-area request, or it is served the site-root dashboard volume.
        $this->assertFalse($this->permissions()->isRequestForAdminArea());
    }

    public function testLoggedInWithCapabilityIsAdminArea(): void
    {
        $_REQUEST['action'] = 'bitapps_fm_connector';
        WP_Mock::userFunction('is_user_logged_in', ['return' => true]);
        $this->withCapability(true);

        $this->assertTrue($this->permissions()->isRequestForAdminArea());
    }

    public function testWrongActionIsNotAdminAreaEvenWithCapability(): void
    {
        $_REQUEST['action'] = 'some_other_action';
        WP_Mock::userFunction('is_user_logged_in', ['return' => true]);
        $this->withCapability(true);

        $this->assertFalse($this->permissions()->isRequestForAdminArea());
    }

    public function testGuestIsNotAdminArea(): void
    {
        $_REQUEST['action'] = 'bitapps_fm_connector';
        WP_Mock::userFunction('is_user_logged_in', ['return' => false]);
        $this->withCapability(true);

        $this->assertFalse($this->permissions()->isRequestForAdminArea());
    }
}
