<?php

declare(strict_types=1);

namespace BitApps\FM\Tests;

use BitApps\FM\Http\Controllers\FileManagerController;
use BitApps\FM\Http\Middleware\FrontendAccessMiddleware;
use BitApps\FM\Plugin;
use BitApps\FM\Providers\AccessControlProvider;
use BitApps\FM\Providers\FileManager\FileRoot;
use BitApps\FM\Providers\MimeProvider;
use BitApps\FM\Providers\PermissionsProvider;
use BitApps\FM\Providers\PreferenceProvider;
use BitApps\FM\Vendor\BitApps\WPKit\Http\Request\Request;
use BitApps\FM\Views\Shortcode;
use elFinder;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use stdClass;
use WP_Mock;

/**
 * Covers the snippet-facing filters: bitapps_fm_can_access_frontend, bitapps_fm_can_run_command,
 * bitapps_fm_file_access, bitapps_fm_file_roots and bitapps_fm_toolbar_commands. Each must leave behaviour unchanged when
 * nothing is hooked, and be able to both grant and deny when something is.
 *
 * @internal
 *
 * @coversNothing
 */
#[AllowMockObjectsWithoutExpectations]
class ExtensionHooksTest extends TestCase
{
    public const VOLUME_PATH = '/vol/a.pdf';

    private const ADMIN_ROUTE = 'bitapps_fm_connector';

    private const FRONT_ROUTE = 'bitapps_fm_connector_front';

    private const ALL_COMMANDS = ['download', 'upload', 'rm'];

    private ReflectionProperty $pluginInstance;

    private stdClass $user;

    /**
     * Boots WP_Mock and a stand-in current user shared by every filter expectation.
     */
    protected function setUp(): void
    {
        WP_Mock::setUp();
        WP_Mock::userFunction('__', ['return_arg' => 0]);
        WP_Mock::userFunction('wp_sprintf', ['return_arg' => 0]);

        $this->pluginInstance = new ReflectionProperty(Plugin::class, '_instance');
        $this->user           = new stdClass();
    }

    /**
     * Resets the Plugin singleton, request globals and WP_Mock between tests.
     */
    protected function tearDown(): void
    {
        $this->pluginInstance->setValue(null, null);
        unset($_REQUEST['content'], $_REQUEST['action']);
        WP_Mock::tearDown();
    }

    /**
     * With no filter hooked, everyone may use the frontend file manager.
     */
    public function testFrontendAccessIsAllowedByDefault(): void
    {
        $this->assertTrue($this->permissions()->canAccessFrontend());
    }

    /**
     * The frontend-access filter can deny the current user.
     */
    public function testFrontendAccessFilterCanDeny(): void
    {
        WP_Mock::onFilter('bitapps_fm_can_access_frontend')->with(true, $this->user, PermissionsProvider::CONTEXT_FRONTEND)->reply(false);

        $this->assertFalse($this->permissions()->canAccessFrontend());
    }

    /**
     * The shortcode renders only an access notice when the frontend filter denies.
     */
    public function testShortcodeRendersNoticeWhenFrontendAccessDenied(): void
    {
        WP_Mock::onFilter('bitapps_fm_can_access_frontend')->with(true, $this->user, PermissionsProvider::CONTEXT_FRONTEND)->reply(false);
        $this->seedPlugin($this->permissions());

        $shortcode = (new ReflectionClass(Shortcode::class))->newInstanceWithoutConstructor();

        $this->assertSame(
            '<p class="bitapps-fm-access-denied">You don\'t have permission to access the file manager.</p>',
            $shortcode->shortCodeView()
        );
    }

    /**
     * A denied user is stopped on both connector routes unless the request is admin-area; context follows the route.
     */
    #[DataProvider('connectorRoutes')]
    public function testConnectorGateDeniesNonAdminAreaRequestsOnBothRoutes(string $route, string $context): void
    {
        $this->mockConnectorRequest($route, false);
        WP_Mock::onFilter('bitapps_fm_can_access_frontend')->with(true, $this->user, $context)->reply(false);
        WP_Mock::userFunction('wp_die', ['times' => 1]);

        $this->expectOutputString('{"error":"You don\'t have permission to access the file manager."}');
        $this->runConnectorGate();
    }

    /**
     * Admin-area connector requests bypass the frontend gate even when the filter denies.
     */
    public function testConnectorGateLetsAdminAreaRequestsThrough(): void
    {
        $this->mockConnectorRequest(self::ADMIN_ROUTE, true);
        WP_Mock::onFilter('bitapps_fm_can_access_frontend')
            ->with(true, $this->user, PermissionsProvider::CONTEXT_ADMIN)
            ->reply(false);
        WP_Mock::userFunction('wp_die', ['times' => 0]);

        $this->expectOutputString('');
        $this->assertTrue($this->runConnectorGate());
    }

    /**
     * Allowed users pass the connector gate on the frontend route.
     */
    public function testConnectorGateLetsAllowedUsersThrough(): void
    {
        $this->mockConnectorRequest(self::FRONT_ROUTE, false);
        WP_Mock::userFunction('wp_die', ['times' => 0]);

        $this->expectOutputString('');
        $this->assertTrue($this->runConnectorGate());
    }

    /**
     * requestContext() classifies admin-ajax by action and other requests by is_admin().
     */
    #[DataProvider('requestContexts')]
    public function testRequestContextClassifiesTheRequest(bool $ajax, bool $admin, string $action, string $expected): void
    {
        $_REQUEST['action'] = $action;
        WP_Mock::userFunction('wp_doing_ajax', ['return' => $ajax]);
        WP_Mock::userFunction('is_admin', ['return' => $admin]);
        WP_Mock::userFunction('sanitize_key', ['return_arg' => 0]);

        $this->assertSame($expected, $this->realPermissions()->requestContext());
    }

    /**
     * REST requests report the admin context even though is_admin() is false there.
     */
    public function testRequestContextTreatsRestRequestsAsAdmin(): void
    {
        $permissions = $this->getMockBuilder(PermissionsProvider::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isRestRequest'])
            ->getMock();
        $permissions->method('isRestRequest')->willReturn(true);
        WP_Mock::userFunction('wp_doing_ajax', ['return' => false]);
        WP_Mock::userFunction('is_admin', ['return' => false]);

        $this->assertSame(PermissionsProvider::CONTEXT_ADMIN, $permissions->requestContext());
    }

    /**
     * requestContext() is computed once per request, since control() reads it for every file.
     */
    public function testRequestContextIsMemoized(): void
    {
        WP_Mock::userFunction('wp_doing_ajax', ['return' => false, 'times' => 1]);
        WP_Mock::userFunction('is_admin', ['return' => true, 'times' => 1]);

        $permissions = $this->realPermissions();
        $permissions->requestContext();

        $this->assertSame(PermissionsProvider::CONTEXT_ADMIN, $permissions->requestContext());
    }

    /**
     * Without a filter the settings-based command decision is unchanged.
     */
    public function testCommandDecisionIsUnchangedWithoutFilter(): void
    {
        $this->assertNull($this->seedPlugin($this->permissions(['canRun' => true]))->checkPermission('upload'));
        $this->assertDenied($this->seedPlugin($this->permissions(['canRun' => false]))->checkPermission('upload'));
    }

    /**
     * The command filter can deny a permitted command and receives the normalized name and resolved paths.
     */
    public function testCommandFilterCanDenyAPermittedCommandWithNormalizedNameAndPaths(): void
    {
        WP_Mock::onFilter('bitapps_fm_can_run_command')
            ->with(true, 'download', [self::VOLUME_PATH], $this->user, PermissionsProvider::CONTEXT_FRONTEND)
            ->reply(false);

        $access = $this->seedPlugin($this->permissions(['canRun' => true]));

        $this->assertDenied($access->checkPermission('zipdl', ['targets' => ['h1']], $this->elfinder()));
    }

    /**
     * The command filter can grant on the connector's early check, which has no paths.
     */
    public function testCommandFilterCanGrantOnTheArglessConnectorBackstop(): void
    {
        WP_Mock::onFilter('bitapps_fm_can_run_command')->with(false, 'upload', [], $this->user, PermissionsProvider::CONTEXT_FRONTEND)->reply(true);

        $access = $this->seedPlugin($this->permissions(['canRun' => false]));

        $this->assertNull($access->checkPermission('upload'));
    }

    /**
     * Shared navigation commands such as ls go through the command filter too.
     */
    public function testCommandFilterSeesSharedNavigationCommands(): void
    {
        WP_Mock::onFilter('bitapps_fm_can_run_command')
            ->with(true, 'ls', [self::VOLUME_PATH], $this->user, PermissionsProvider::CONTEXT_FRONTEND)
            ->reply(false);

        $access = $this->seedPlugin($this->permissions(['hasRolePermission' => true]));

        $this->assertDenied($access->checkPermission('ls', ['target' => 'h1'], $this->elfinder()));
    }

    /**
     * A filter grant does not skip the JS content scan.
     */
    public function testGrantedCommandStillRunsTheUploadContentScan(): void
    {
        WP_Mock::onFilter('bitapps_fm_can_run_command')->with(false, 'edit', [], $this->user, PermissionsProvider::CONTEXT_FRONTEND)->reply(true);
        $_REQUEST['content'] = '<script>alert(1)</script>';

        $access = $this->seedPlugin($this->permissions(['canRun' => false]));

        $this->assertDenied($access->checkPermission('put'));
    }

    /**
     * Exempt navigation commands return early and are never filtered.
     */
    public function testExemptCommandsBypassTheCommandFilter(): void
    {
        foreach ([true, false] as $authorized) {
            WP_Mock::onFilter('bitapps_fm_can_run_command')->with($authorized, 'open', [], $this->user, PermissionsProvider::CONTEXT_FRONTEND)->reply(false);
        }

        $this->assertNull($this->seedPlugin($this->permissions())->checkPermission('open'));
    }

    /**
     * Without a filter control() applies the hidden (dot) file settings and defers everything else.
     */
    #[DataProvider('hiddenFileCases')]
    public function testHiddenFilePolicyWithoutFilter(
        string $attr,
        string $path,
        string $relPath,
        bool $showHidden,
        bool $hiddenAllowed,
        ?bool $expected
    ): void {
        $access = $this->fileAccessControl($showHidden, $hiddenAllowed);

        $this->assertSame($expected, $access->control($attr, $path, null, null, false, $relPath));
    }

    /**
     * The file-access filter runs for regular (non-dot) files.
     */
    public function testFileAccessFilterRunsForRegularFiles(): void
    {
        WP_Mock::onFilter('bitapps_fm_file_access')
            ->with(null, 'read', self::VOLUME_PATH, '/a.pdf', false, PermissionsProvider::CONTEXT_FRONTEND)
            ->reply(false);

        $this->assertFalse($this->fileAccessControl()->control('read', self::VOLUME_PATH, null, null, false, '/a.pdf'));
    }

    /**
     * The file-access filter can override the hidden-file policy.
     */
    public function testFileAccessFilterCanOverrideTheHiddenFilePolicy(): void
    {
        WP_Mock::onFilter('bitapps_fm_file_access')
            ->with(false, 'write', '/vol/.env', '/.env', false, PermissionsProvider::CONTEXT_FRONTEND)
            ->reply(true);

        $this->assertTrue($this->fileAccessControl()->control('write', '/vol/.env', null, null, false, '/.env'));
    }

    /**
     * End-to-end through getFileRoots(): with no filter the guest volume is kept and now gets the volume policy.
     */
    public function testGuestVolumeIsKeptAndPolicedWithoutFilter(): void
    {
        WP_Mock::userFunction('is_user_logged_in', ['return' => false]);
        WP_Mock::userFunction('current_user_can', ['return' => false]);
        WP_Mock::userFunction('is_ssl', ['return' => false]);
        WP_Mock::userFunction('trailingslashit', ['return' => '/srv/guest/']);
        WP_Mock::userFunction('home_url', ['return' => 'http://example.test/srv/guest/']);
        WP_Mock::userFunction('set_url_scheme', ['return_arg' => 0]);

        $access = $this->seedPlugin($this->permissions(['guestCommands' => ['download']]));

        $roots = (new FileManagerController())->getFileRoots();

        $this->assertCount(1, $roots);
        $this->assertSame('/srv/guest', $roots[0]->getOption('_path'));
        $this->assertSame(['upload', 'rm'], array_values($roots[0]->getOption('_disabled')));
        $this->assertPoliced($roots[0], $access);
    }

    /**
     * The dashboard volume keeps its MIME handling (all types for administrators) and gets the shared security policy.
     */
    #[DataProvider('dashboardRoles')]
    public function testDashboardVolumeKeepsMimeHandlingAndSecurityPolicy(
        string $role,
        bool $managesOptions,
        array $uploadAllow,
        $uploadMaxSize
    ): void {
        WP_Mock::userFunction('current_user_can', ['return' => $managesOptions]);

        $access = $this->seedPlugin($this->permissions(['dashboard' => true, 'role' => $role]));

        $roots = (new FileManagerController())->getFileRoots();

        $this->assertCount(1, $roots);
        $this->assertSame('/srv/site', $roots[0]->getOption('_path'));
        $this->assertSame([$access, 'control'], $roots[0]->getOption('_accessControl'));
        $this->assertSame([$access, 'validateName'], $roots[0]->getOption('_acceptedName'));
        $this->assertSame([], $roots[0]->getOption('_disabled'));
        $this->assertSame($uploadAllow, array_values($roots[0]->getOption('_uploadAllow')));
        $this->assertSame($uploadMaxSize, $roots[0]->getOption('_uploadMaxSize'));
        $this->assertSame($managesOptions, $roots[0]->getOption('_statOwner'));
    }

    /**
     * Covers the list-filter step directly: WP_Mock keys roots by object hash, so getFileRoots()' own root cannot be matched.
     */
    public function testFileRootsFilterReplacesVolumesAndDropsInvalidEntries(): void
    {
        WP_Mock::userFunction('_doing_it_wrong', ['times' => 1]);
        $pluginRoot  = new FileRoot('/srv/public', '', 'Public');
        $snippetRoot = new FileRoot('/outside/webroot/archief', '', 'Archief');

        WP_Mock::onFilter('bitapps_fm_file_roots')
            ->with([$pluginRoot], $this->user, PermissionsProvider::CONTEXT_FRONTEND)
            ->reply([$snippetRoot, 'not-a-root']);

        $this->assertSame([$snippetRoot], $this->filterFileRoots([$pluginRoot]));
    }

    /**
     * A non-array file_roots result is reported and yields no volumes.
     */
    public function testFileRootsFilterReturningNonArrayYieldsNoVolumes(): void
    {
        WP_Mock::userFunction('_doing_it_wrong', ['times' => 1]);
        $pluginRoot = new FileRoot('/srv/public', '', 'Public');

        WP_Mock::onFilter('bitapps_fm_file_roots')->with([$pluginRoot], $this->user, PermissionsProvider::CONTEXT_FRONTEND)->reply('nope');

        $this->assertSame([], $this->filterFileRoots([$pluginRoot]));
    }

    /**
     * Snippet-supplied volumes get the access-control policy and keep their own disabled commands.
     */
    public function testVolumePolicyAppliesAccessControlToSnippetRoots(): void
    {
        WP_Mock::userFunction('current_user_can', ['return' => false]);

        $access      = $this->seedPlugin($this->permissions());
        $snippetRoot = new FileRoot('/outside/webroot/archief', '', 'Archief');
        $snippetRoot->setDisabled(['rm']);

        $controller = new FileManagerController();
        $controller->setAllowedFileType($snippetRoot);
        (new ReflectionMethod($controller, 'applySecurityPolicy'))->invoke($controller, $snippetRoot);

        $this->assertPoliced($snippetRoot, $access);
        $this->assertSame(['rm'], $snippetRoot->getOption('_disabled'), 'snippet-set disabled commands must be kept');
    }

    /**
     * Without a filter the toolbar shows the user's enabled commands.
     */
    public function testToolbarCommandsAreUnchangedWithoutFilter(): void
    {
        $this->assertSame(['download', 'upload'], $this->toolbarPreferences(['download', 'upload'])->toolbarCommands());
    }

    /**
     * The toolbar filter can replace the command list; non-string entries are dropped and reported.
     */
    public function testToolbarFilterCanReplaceCommandsAndDropsNonStrings(): void
    {
        WP_Mock::userFunction('_doing_it_wrong', ['times' => 1]);
        WP_Mock::onFilter('bitapps_fm_toolbar_commands')
            ->with(['download'], $this->user, PermissionsProvider::CONTEXT_FRONTEND)
            ->reply(['upload', 42, 'download']);

        $this->assertSame(['upload', 'download'], $this->toolbarPreferences(['download'])->toolbarCommands());
    }

    /**
     * A non-array toolbar filter result is reported and shows no commands.
     */
    public function testToolbarFilterReturningNonArrayShowsNoCommands(): void
    {
        WP_Mock::userFunction('_doing_it_wrong', ['times' => 1]);
        WP_Mock::onFilter('bitapps_fm_toolbar_commands')->with(['download'], $this->user, PermissionsProvider::CONTEXT_FRONTEND)->reply('upload');

        $this->assertSame([], $this->toolbarPreferences(['download'])->toolbarCommands());
    }

    /**
     * finderOptions() feeds the filtered toolbar into the client's commands, disabled list and guest context menu.
     */
    public function testFinderOptionsUseFilteredToolbarCommands(): void
    {
        WP_Mock::onFilter('bitapps_fm_toolbar_commands')->with(['download'], $this->user, PermissionsProvider::CONTEXT_FRONTEND)->reply(['upload']);

        $options = $this->finderOptionsFor(['download']);

        $this->assertSame(['upload'], $options['commands']);
        $this->assertSame(['download', 'rm', 'dblclick'], $options['disabled']);
        $this->assertSame(['download'], $options['contextmenu']['files'], 'guest context menu must follow the filter');
    }

    /**
     * An empty filtered toolbar also empties the guest context menu.
     */
    public function testFinderOptionsGuestContextMenuIsEmptyWhenToolbarIsEmpty(): void
    {
        WP_Mock::onFilter('bitapps_fm_toolbar_commands')->with(['download'], $this->user, PermissionsProvider::CONTEXT_FRONTEND)->reply([]);

        $options = $this->finderOptionsFor(['download']);

        $this->assertSame([], $options['commands']);
        $this->assertSame([], $options['contextmenu']['files']);
    }

    /**
     * The `*` wildcard passes through to the client unchanged.
     */
    public function testFinderOptionsPassWildcardThrough(): void
    {
        $this->assertSame(['*'], $this->finderOptionsFor(['*'])['commands']);
    }

    /**
     * Both connector routes with the context each reports, keyed by name.
     *
     * @return array<string, array{string, string}>
     */
    public static function connectorRoutes(): array
    {
        return [
            'admin route'    => [self::ADMIN_ROUTE, PermissionsProvider::CONTEXT_ADMIN],
            'frontend route' => [self::FRONT_ROUTE, PermissionsProvider::CONTEXT_FRONTEND],
        ];
    }

    /**
     * Request shapes and the context each must report: is ajax, is_admin(), action.
     *
     * @return array<string, array{bool, bool, string, string}>
     */
    public static function requestContexts(): array
    {
        return [
            'ajax, shortcode action' => [true, true, self::FRONT_ROUTE, PermissionsProvider::CONTEXT_FRONTEND],
            'ajax, admin action'     => [true, true, self::ADMIN_ROUTE, PermissionsProvider::CONTEXT_ADMIN],
            'admin page'             => [false, true, '', PermissionsProvider::CONTEXT_ADMIN],
            'frontend page'          => [false, false, '', PermissionsProvider::CONTEXT_FRONTEND],
        ];
    }

    /**
     * Dashboard volume expectations for an administrator and a non-administrator.
     *
     * @return array<string, array{string, bool, string[], int|string}>
     */
    public static function dashboardRoles(): array
    {
        return [
            'administrator' => ['administrator', true, ['image', 'application'], 0],
            'editor'        => ['editor', false, ['image'], '5M'],
        ];
    }

    /**
     * Hidden-file policy cases: attr, path, relPath, show hidden, hidden writable, expected.
     *
     * @return array<string, array{string, string, string, bool, bool, ?bool}>
     */
    public static function hiddenFileCases(): array
    {
        return [
            'dotfile shown'              => ['hidden', '/vol/.env', '/.env', true, false, false],
            'dotfile hidden'             => ['hidden', '/vol/.env', '/.env', false, false, true],
            'dotfile writable'           => ['write', '/vol/.env', '/.env', false, true, true],
            'dotfile read-only'          => ['write', '/vol/.env', '/.env', false, false, false],
            'dotfile readable'           => ['read', '/vol/.env', '/.env', false, false, true],
            'dotfile lock left alone'    => ['locked', '/vol/.env', '/.env', false, false, null],
            'regular file left alone'    => ['hidden', '/vol/a.pdf', '/a.pdf', false, false, null],
            'dot-named root left alone'  => ['write', '/vol/.private', '/', false, false, null],
        ];
    }

    /**
     * Asserts elFinder's preventexec payload.
     *
     * @param mixed $result
     */
    private function assertDenied($result): void
    {
        $this->assertIsArray($result);
        $this->assertTrue($result['preventexec']);
        $this->assertArrayHasKey('error', $result['results']);
    }

    /**
     * Asserts a volume carries the plugin's access-control, name and MIME policy.
     */
    private function assertPoliced(FileRoot $root, AccessControlProvider $access): void
    {
        $this->assertSame([$access, 'control'], $root->getOption('_accessControl'));
        $this->assertSame([$access, 'validateName'], $root->getOption('_acceptedName'));
        $this->assertSame(['image'], array_values($root->getOption('_uploadAllow')));
        $this->assertSame('5M', $root->getOption('_uploadMaxSize'));
    }

    /**
     * Mocks a logged-in connector request on $route; $isAdmin decides the File Manager home capability.
     */
    private function mockConnectorRequest(string $route, bool $isAdmin): void
    {
        $_REQUEST['action'] = $route;
        WP_Mock::userFunction('wp_doing_ajax', ['return' => true]);
        WP_Mock::userFunction('sanitize_key', ['return_arg' => 0]);
        WP_Mock::userFunction('is_user_logged_in', ['return' => true]);
        WP_Mock::userFunction('current_user_can', ['return' => $isAdmin]);
        WP_Mock::userFunction('wp_json_encode', ['return' => static function ($data) {
            return json_encode($data);
        }]);

        $this->seedPlugin($this->realPermissions());
    }

    /**
     * PermissionsProvider with real request logic; only currentUser() is stubbed.
     */
    private function realPermissions(): PermissionsProvider
    {
        $permissions = $this->getMockBuilder(PermissionsProvider::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['currentUser'])
            ->getMock();
        $permissions->method('currentUser')->willReturn($this->user);

        return $permissions;
    }

    /**
     * Runs the frontend_access route middleware; true means the request may continue.
     *
     * @return mixed
     */
    private function runConnectorGate()
    {
        $request = (new ReflectionClass(Request::class))->newInstanceWithoutConstructor();

        return (new FrontendAccessMiddleware())->handle($request);
    }

    /**
     * Runs $roots through the file_roots list filter with getFileRoots()' FileRoot check.
     */
    private function filterFileRoots(array $roots): array
    {
        return $this->permissions()->applyListFilter(
            'file_roots',
            $roots,
            static function ($root) {
                return $root instanceof FileRoot;
            },
            FileRoot::class
        );
    }

    /**
     * Pins the Plugin singleton to the given permissions plus stub mimes/preferences/access control; returns the access control.
     */
    private function seedPlugin(PermissionsProvider $permissions): AccessControlProvider
    {
        $access = $this->getMockBuilder(AccessControlProvider::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $mimes = $this->getMockBuilder(MimeProvider::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getTypes'])
            ->getMock();
        $mimes->method('getTypes')->willReturn(['image', 'application']);

        $preferences = $this->getMockBuilder(PreferenceProvider::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRootPath', 'getRootUrl', 'getRootVolumeName'])
            ->getMock();
        $preferences->method('getRootPath')->willReturn('/srv/site');
        $preferences->method('getRootUrl')->willReturn('http://example.test/');
        $preferences->method('getRootVolumeName')->willReturn('Home');

        $plugin = (new ReflectionClass(Plugin::class))->newInstanceWithoutConstructor();
        (new ReflectionProperty(Plugin::class, '_container'))->setValue($plugin, [
            'permissions'    => $permissions,
            'access_control' => $access,
            'mimes'          => $mimes,
            'preferences'    => $preferences,
        ]);
        $this->pluginInstance->setValue(null, $plugin);

        return $access;
    }

    /**
     * AccessControlProvider with the given hidden-file settings (default: hidden and unwritable).
     */
    private function fileAccessControl(bool $showHidden = false, bool $hiddenAllowed = false): AccessControlProvider
    {
        $settings = $this->getMockBuilder(PreferenceProvider::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getVisibilityOfHiddenFile', 'isHiddenFolderAllowed'])
            ->getMock();
        $settings->method('getVisibilityOfHiddenFile')->willReturn($showHidden);
        $settings->method('isHiddenFolderAllowed')->willReturn($hiddenAllowed);

        $access           = $this->seedPlugin($this->permissions());
        $access->settings = $settings;

        return $access;
    }

    /**
     * PermissionsProvider stub; $opts: canRun, hasRolePermission, guestCommands, dashboard, role.
     */
    private function permissions(array $opts = []): PermissionsProvider
    {
        $mock = $this->getMockBuilder(PermissionsProvider::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'currentUser', 'currentUserCanRun', 'allCommands', 'commandLabel', 'getEnabledFileType',
                'getMaximumUploadSize', 'isCurrentRoleHasPermission', 'isCurrentUserHasPermission',
                'getGuestPermissions', 'getEnabledCommandsForPath', 'isRequestForAdminArea',
                'isDisabledForAdmin', 'currentUserRole', 'requestContext',
            ])
            ->getMock();

        $mock->method('currentUser')->willReturn($this->user);
        $mock->method('currentUserCanRun')->willReturn($opts['canRun'] ?? false);
        $mock->method('allCommands')->willReturn(self::ALL_COMMANDS);
        $mock->method('commandLabel')->willReturn('');
        $mock->method('getEnabledFileType')->willReturn(['image']);
        $mock->method('getMaximumUploadSize')->willReturn(5);
        $mock->method('isCurrentRoleHasPermission')->willReturn($opts['hasRolePermission'] ?? false);
        $mock->method('isCurrentUserHasPermission')->willReturn(false);
        $mock->method('getEnabledCommandsForPath')->willReturn(($opts['canRun'] ?? false) ? ['*'] : []);
        $mock->method('isRequestForAdminArea')->willReturn($opts['dashboard'] ?? false);
        $mock->method('isDisabledForAdmin')->willReturn($opts['dashboard'] ?? false);
        $mock->method('currentUserRole')->willReturn($opts['role'] ?? false);
        $mock->method('requestContext')->willReturn(PermissionsProvider::CONTEXT_FRONTEND);
        $mock->method('getGuestPermissions')->willReturn([
            'path'     => '/srv/guest',
            'commands' => $opts['guestCommands'] ?? [],
        ]);

        return $mock;
    }

    /**
     * PreferenceProvider whose permissions report $enabled as the user's commands; $extraMethods are stubbed too.
     */
    private function toolbarPreferences(array $enabled, array $extraMethods = []): PreferenceProvider
    {
        $permissions = $this->getMockBuilder(PermissionsProvider::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['currentUser', 'getEnabledCommand', 'allCommands', 'requestContext'])
            ->getMock();
        $permissions->method('currentUser')->willReturn($this->user);
        $permissions->method('requestContext')->willReturn(PermissionsProvider::CONTEXT_FRONTEND);
        $permissions->method('getEnabledCommand')->willReturn($enabled);
        $permissions->method('allCommands')->willReturn(self::ALL_COMMANDS);

        $preferences = $this->getMockBuilder(PreferenceProvider::class)
            ->disableOriginalConstructor()
            ->onlyMethods(array_merge(['permissions'], $extraMethods))
            ->getMock();
        $preferences->method('permissions')->willReturn($permissions);

        return $preferences;
    }

    /**
     * Guest finderOptions() for $enabled commands, with unrelated UI getters stubbed out.
     */
    private function finderOptionsFor(array $enabled): array
    {
        WP_Mock::userFunction('admin_url', ['return' => 'http://example.test/wp-admin/admin-ajax.php']);
        WP_Mock::userFunction('is_user_logged_in', ['return' => false]);

        return $this->toolbarPreferences($enabled, [
            'themes', 'getTheme', 'getLangCode', 'getWidth', 'getHeight', 'finderCommandsOptions',
            'getRememberLastDir', 'getClearHistoryOnReload', 'getViewType', 'getUiOptions', 'finderCdns',
        ])->finderOptions();
    }

    /**
     * elFinder whose volume resolves every hash to VOLUME_PATH.
     */
    private function elfinder(): elFinder
    {
        $volume = new class() {
            /**
             * Resolves any hash to the fixture path.
             *
             * @param mixed $hash
             */
            public function getPath($hash)
            {
                return ExtensionHooksTest::VOLUME_PATH;
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
