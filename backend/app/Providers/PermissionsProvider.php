<?php

namespace BitApps\FM\Providers;

use BitApps\FM\Config;
use BitApps\FM\Exception\PreCommandException;

use function BitApps\FM\Functions\fileSystemAdapter;

use BitApps\FM\Plugin;
use BitApps\FM\Vendor\BitApps\WPKit\Helpers\Arr;
use BitApps\FM\Vendor\BitApps\WPKit\Utils\Capabilities;

use WP_User;

\defined('ABSPATH') || exit();
class PermissionsProvider
{
    private const ALL_COMMANDS = [
        'download', // file, zipdl
        'cut',// only for frontend. send cmd as paste
        'copy',// only for frontend. send cmd as paste
        'edit', // put
        'rm', // rm
        'upload',// upload
        'duplicate', // duplicate
        'paste', // paste
        'mkfile',// mkfile
        'mkdir',// mkdir
        'rename', // rename
        'archive', // archive
        'extract',// extract
        'emailto', // client-only: open mailto link for selected file
    ];

    public $permissions;

    public $users;

    public $roles;

    /**
     * FileManager Instance
     *
     * @var WP_User
     */
    public $currentUser;

    /**
     * Dashboard preferences
     *
     * @var PreferenceProvider
     */
    private $_preferences;

    public function __construct()
    {
        global $wp_roles;
        $this->permissions = Config::getOption(
            'permissions',
            $this->defaultPermissions()
        );

        if (\array_key_exists('do_not_use_for_admin', $this->permissions)) {
            $this->permissions['do_not_use_for_admin'] = \boolval($this->permissions['do_not_use_for_admin']);
        }

        $this->_preferences = Plugin::instance()->preferences();
        $this->roles        = array_keys($wp_roles->roles);
    }

    public function refresh()
    {
        $this->permissions    = Config::getOption(
            'permissions',
            $this->defaultPermissions()
        );
    }

    public function allRoles()
    {
        return $this->roles;
    }

    /**
     * Returns all available users
     *
     * @return array<int, WP_User>
     */
    public function allUsers()
    {
        if (!isset($this->users)) {
            $this->users        = $this->mappedUsers();
        }

        return $this->users;
    }

    /**
     * Get user display name by id
     *
     * @param mixed $id
     *
     * @return string
     */
    public function getUserDisplayName($id)
    {
        if (!isset($this->users) && $id) {
            $users = $this->mappedUsers([$id]);
        } elseif (isset($this->users)) {
            $users = $this->users;
        } else {
            $users = [];
        }

        return isset($users[$id]) ? $users[$id]->display_name : 'guest';
    }

    public function allCommands()
    {
        return self::ALL_COMMANDS;
    }

    /**
     * Human-readable action phrase for a command, for user-facing errors.
     * Kept here so command names and their labels share one owner; labels can't
     * live on the ALL_COMMANDS const because __() can't run in a const context.
     *
     * @param string $cmd normalized elFinder command
     *
     * @return string empty when the command has no friendly label
     */
    public function commandLabel($cmd)
    {
        $labels = [
            'download'  => __('download files', 'file-manager'),
            'cut'       => __('move items', 'file-manager'),
            'copy'      => __('copy items', 'file-manager'),
            'edit'      => __('edit files', 'file-manager'),
            'rm'        => __('delete items', 'file-manager'),
            'upload'    => __('upload files', 'file-manager'),
            'duplicate' => __('duplicate items', 'file-manager'),
            'paste'     => __('paste items', 'file-manager'),
            'mkfile'    => __('create files', 'file-manager'),
            'mkdir'     => __('create folders', 'file-manager'),
            'rename'    => __('rename items', 'file-manager'),
            'archive'   => __('create archives', 'file-manager'),
            'extract'   => __('extract archives', 'file-manager'),
            'emailto'   => __('email files', 'file-manager'),
        ];

        return $labels[$cmd] ?? '';
    }

    public function defaultPermissions()
    {
        $permissions['do_not_use_for_admin']     = true;
        $permissions['fileType']                 = apply_filters(
            Config::withPrefix('filter_file_type'),
            ['image', 'application']
        );
        $permissions['file_size']                = 2;
        $permissions['folder_options']           = 'common'; // common | role | user
        $permissions['by_role']['administrator'] = [
            'commands' => $this->allCommands(),
            'path'     => Config::uploadBaseDir(),
        ];

        return $permissions;
    }

    public function getPath()
    {
        if ($this->isRequestForAdminArea() && $this->isDisabledForAdmin()) {
            return $this->_preferences->getRootPath();
        }

        $path = '';

        if (!is_user_logged_in()) {
            $path = $this->getGuestPermissions()['path'];
        } elseif ($this->isCurrentUserHasPermission()) {
            $path = $this->permissionsForCurrentUser()['path'];
        } else {
            $path = $this->permissionsForCurrentRole()['path'];
        }

        if (empty($path) || !is_readable($path)) {
            throw new PreCommandException(esc_html__('please check root folder for file manager, from file manager settings', 'file-manager'));
        }

        return $path;
    }

    public function getURL()
    {
        if ($this->isRequestForAdminArea() && $this->isDisabledForAdmin()) {
            return $this->_preferences->getRootUrl();
        }

        return Config::uploadBaseURL();
    }

    public function getVolumeAlias()
    {
        return $this->_preferences->getRootVolumeName();
    }

    public function getDefaultPublicRootPath()
    {
        return Config::uploadBaseDir();
    }

    public function getDefaultPublicRootURL()
    {
        return Config::uploadBaseURL();
    }

    public function getPublicRootPath()
    {
        return isset($this->permissions['root_folder'])
            ? stripslashes($this->permissions['root_folder'])
            : $this->getDefaultPublicRootPath();
    }

    public function getPublicRootURL()
    {
        return isset($this->permissions['root_folder_url'])
            ? stripslashes($this->permissions['root_folder_url'])
            : $this->getDefaultPublicRootURL();
    }

    public function getPublicRootPathByCriteria($criteria, $type)
    {
        $defaultPath = $this->getPublicRootPath();
        $rootPath    = wp_unslash($defaultPath) . DIRECTORY_SEPARATOR . "{$type}_{$criteria}";
        if (
            !fileSystemAdapter()->exists($rootPath)
            && fileSystemAdapter()->is_dir($defaultPath)
            && fileSystemAdapter()->is_writable($defaultPath)
        ) {
            wp_mkdir_p($rootPath);
        }

        if (!fileSystemAdapter()->exists($rootPath) || !fileSystemAdapter()->is_dir($rootPath) || !fileSystemAdapter()->is_readable($rootPath)) {
            $rootPath = '';
        }

        return $rootPath;
    }

    public function getPublicRootPathForUser($userName)
    {
        return $this->getPublicRootPathByCriteria($userName, 'user');
    }

    public function getPublicRootPathForRole($role)
    {
        return $this->getPublicRootPathByCriteria($role, 'role');
    }

    public function getPathByFolderOption()
    {
        switch ($this->getFolderOption()) {
            case 'role':
                return $this->getPublicRootPathForRole($this->currentUserRole());
            case 'user':
                return $this->getPublicRootPathForUser($this->currentUserName());
            default:
                return $this->getPublicRootPath();
        }
    }

    public function getByRole($role)
    {
        return $this->getPermissions('by_role', $role);
    }

    public function getByUser($userID)
    {
        return $this->getPermissions('by_user', $userID);
    }

    public function removeByUser(int $userID)
    {
        if (Arr::has($this->permissions, "by_user.{$userID}")) {
            unset($this->permissions['by_user'][$userID]);
        }

        return $this->updatePermissionSetting($this->permissions);
    }

    /**
     * Adds permission for a user
     *
     * @param int   $userID
     * @param array $permission {
     *                          Permission details
     *
     * @type string $path
     * @type array  $command
     *              }
     *
     * @return bool
     */
    public function addByUser(int $userID, array $permission)
    {
        if (!Arr::exists($this->permissions, 'by_user')) {
            $this->permissions['by_user'] = [];
        }

        $this->permissions['by_user'][$userID] = $permission;

        return $this->updatePermissionSetting($this->permissions);
    }

    public function getPermissions($type, $name)
    {
        $settings = [
            'commands' => [],
            'path'     => '',
        ];

        if (
            isset($this->permissions[$type])
            && \is_array($this->permissions[$type])
            && isset($this->permissions[$type][$name])
            && \is_array($this->permissions[$type][$name])
        ) {
            $settings['path'] = isset($this->permissions[$type][$name]['path'])
                ? $this->permissions[$type][$name]['path'] : $settings['path'];
            $settings['commands'] = isset($this->permissions[$type][$name]['commands'])
                && \is_array($this->permissions[$type][$name]['commands'])
                ? $this->permissions[$type][$name]['commands'] : $settings['commands'];
        }

        return $settings;
    }

    public static function isSubPath(string $path, string $boundary): bool
    {
        $path     = rtrim(str_replace('\\', '/', $path), '/');
        $boundary = rtrim(str_replace('\\', '/', $boundary), '/');

        if ($boundary === '') { // empty or separator-only boundary matches nothing
            return false;
        }

        return $path === $boundary || strpos($path, $boundary . '/') === 0;
    }

    /**
     * Canonicalize $path and return it only when it resolves inside $boundary.
     *
     * @return string|null resolved target path, or null when either side fails to
     *                     resolve or the target escapes the boundary
     */
    public static function realpathWithin(string $path, string $boundary): ?string
    {
        $boundaryReal = realpath($boundary);
        $target       = realpath($path);

        if ($boundaryReal === false || $target === false || !self::isSubPath($target, $boundaryReal)) {
            return null;
        }

        return $target;
    }

    public function getGuestPermissions()
    {
        $settings = [
            'commands' => [],
            'path'     => '',
        ];

        if (
            isset($this->permissions['guest'])
            && \is_array($this->permissions['guest'])
        ) {
            $settings['path'] = isset($this->permissions['guest']['path'])
                ? $this->permissions['guest']['path'] : $settings['path'];

            // commands in guest permission removed. this is for back compat
            if (
                isset($this->permissions['guest']['commands'])
            && \is_array($this->permissions['guest']['commands'])
            ) {
                $settings['commands'] =  $this->permissions['guest']['commands'];
            } elseif (
                \array_key_exists('can_download', $this->permissions['guest'])
                 && $this->permissions['guest']['can_download']
            ) {
                $settings['commands'] = ['download'];
            }
        }

        return $settings;
    }

    public function getEnabledFileType()
    {
        return isset($this->permissions['fileType'])
            ? $this->permissions['fileType'] : [];
    }

    public function getMaximumUploadSize()
    {
        return isset($this->permissions['file_size'])
            ? $this->permissions['file_size'] : 2;
    }

    public function isDisabledForAdmin()
    {
        return isset($this->permissions['do_not_use_for_admin'])
            && \boolval($this->permissions['do_not_use_for_admin']);
    }

    public function getFolderOption()
    {
        return isset($this->permissions['folder_options']) ? $this->permissions['folder_options'] : 'common';
    }

    public function isCommonFolderEnabled()
    {
        return isset($this->permissions['folder_options']) && $this->permissions['folder_options'] === 'common';
    }

    public function currentUser()
    {
        if (!isset($this->currentUser) && \function_exists('wp_get_current_user')) {
            $this->currentUser = wp_get_current_user();
        }

        return $this->currentUser;
    }

    public function currentUserRole()
    {
        if (!is_user_logged_in() || !$this->currentUser() instanceof WP_User) {
            return false;
        }

        return isset($this->currentUser()->roles[0]) ? $this->currentUser()->roles[0] : false;
    }

    public function currentUserID()
    {
        if (!$this->currentUser() instanceof WP_User) {
            return false;
        }

        return $this->currentUser()->ID;
    }

    public function currentUserName()
    {
        if (!$this->currentUser() instanceof WP_User) {
            return false;
        }

        return $this->currentUser()->user_login;
    }

    public function isCurrentUserHasPermission()
    {
        $hasPermission = true;

        if (empty($this->permissionsForCurrentUser()['commands'])) {
            $hasPermission = false;
        }

        return $hasPermission;
    }

    public function isCurrentRoleHasPermission()
    {
        $hasPermission = true;

        if (empty($this->permissionsForCurrentRole()['commands'])) {
            $hasPermission = false;
        }

        return $hasPermission;
    }

    public function permissionsForCurrentUser()
    {
        return $this->getByUser($this->currentUserID());
    }

    public function permissionsForCurrentRole()
    {
        return $this->getByRole($this->currentUserRole());
    }

    public function currentUserCanRun($command)
    {
        if (Capabilities::check('administrator') && $this->isDisabledForAdmin()) {
            return true;
        }

        $permission = false;
        if (
            \in_array($command, $this->permissionsForCurrentUser()['commands'])
        || \in_array($command, $this->permissionsForCurrentRole()['commands'])
        ) {
            $permission = true;
        }

        if (!is_user_logged_in() && \in_array($command, $this->getGuestPermissions()['commands'])) {
            $permission = true;
        }

        $cap = Config::VAR_PREFIX . 'user_can_' . $command;

        return Capabilities::filter($cap) || $permission;
    }

    public function getEnabledCommand()
    {
        if ($this->isRequestForAdminArea() && $this->isDisabledForAdmin()) {
            return ['*'];
        }

        if (!is_user_logged_in()) {
            $enabledCommands = $this->getGuestPermissions()['commands'];
        } elseif ($this->isCurrentUserHasPermission()) {
            $enabledCommands = $this->permissionsForCurrentUser()['commands'];
        } else {
            $enabledCommands = $this->permissionsForCurrentRole()['commands'];
        }

        return $enabledCommands;
    }

    public function getDisabledCommand()
    {
        if ($this->isRequestForAdminArea() && $this->isDisabledForAdmin()) {
            return [];
        }

        return $this->getDisabledCommandFor($this->getEnabledCommand());
    }

    public function isPathUnderUserPermission(string $absPath): bool
    {
        if (!is_user_logged_in() || !$this->isCurrentUserHasPermission()) {
            return false;
        }

        $userPath = $this->permissionsForCurrentUser()['path'] ?? null;
        if (!\is_string($userPath) || $userPath === '') {
            return false;
        }

        $boundary = realpath($userPath);
        if ($boundary === false) {
            return false;
        }

        $target = realpath($absPath);
        if ($target === false) {
            // Target does not exist yet (e.g. a create destination). Canonicalize via the
            // parent so a raw '..' cannot string-match the boundary; reject if the parent
            // itself does not resolve.
            $parent = realpath(\dirname($absPath));
            if ($parent === false) {
                return false;
            }
            $target = $parent . DIRECTORY_SEPARATOR . basename($absPath);
        }

        return self::isSubPath($target, $boundary);
    }

    public function getEnabledCommandsForPath(string $absPath): array
    {
        if (!is_user_logged_in()) {
            return $this->getGuestPermissions()['commands'];
        }

        if ($this->isPathUnderUserPermission($absPath)) {
            return $this->permissionsForCurrentUser()['commands'];
        }

        return $this->permissionsForCurrentRole()['commands'];
    }

    public function getEnabledCommandsUnion(): array
    {
        return array_values(array_unique(array_merge(
            $this->permissionsForCurrentUser()['commands'],
            $this->permissionsForCurrentRole()['commands']
        )));
    }

    public function getDisabledCommandFor(array $enabledCommands): array
    {
        $disabled = [];
        foreach ($this->allCommands() as $command) {
            if (!\in_array($command, $enabledCommands, true)) {
                $disabled[] = $command;
            }
        }

        return $disabled;
    }

    /**
     * Disabled-command hint for the Public volume. Least-restrictive (user ∪ role) so a
     * nested per-user folder never hides legitimate commands; the runtime gate enforces
     * the real per-path policy.
     */
    public function getPublicVolumeDisabledCommands(): array
    {
        return $this->getDisabledCommandFor($this->getEnabledCommandsUnion());
    }

    public function getRoleVolumeDisabledCommands(): array
    {
        return $this->getDisabledCommandFor($this->permissionsForCurrentRole()['commands']);
    }

    public function getUserVolumeDisabledCommands(): array
    {
        $commands = $this->isCurrentUserHasPermission()
            ? $this->permissionsForCurrentUser()['commands']
            : $this->permissionsForCurrentRole()['commands'];

        return $this->getDisabledCommandFor($commands);
    }

    public function updatePermissionSetting($permissions)
    {
        return Config::updateOption('permissions', $permissions, 'yes');
    }

    public function isRequestForAdminArea()
    {
        $action = '';

        if (isset($_REQUEST['action'])) {
            $action = sanitize_key($_REQUEST['action']);
        }

        return is_user_logged_in() && $action === 'bitapps_fm_connector';
    }

    public function isRequestForShortcode()
    {
        $action = '';

        if (isset($_REQUEST['action'])) {
            $action = sanitize_key($_REQUEST['action']);
        }

        return $action === Config::withPrefix('connector_front');
    }

    /**
     * Returns all users who are in the permission by_user list
     *
     * @return array<int, WP_User>
     */
    public function permittedUsers()
    {
        $allowedUsers = $this->permittedUserIds();

        return \count($allowedUsers) ? $this->mappedUsers($allowedUsers) : [];
    }

    /**
     * Returns all user's id who are in the permission by_user list
     *
     * @return array<int, int>
     */
    public function permittedUserIds()
    {
        $allowedUsers = [];

        if (isset($this->permissions['by_user']) && \is_array($this->permissions['by_user'])) {
            $allowedUsers = array_keys($this->permissions['by_user']);
        }

        return $allowedUsers;
    }

    /**
     * Returns all available users. Array Index will be user ID
     *
     * @param array $userIDs List of user ids to fetch
     *
     * @return array<int, WP_User>
     */
    public function mappedUsers(array $userIDs = [])
    {
        $query = ['fields' => ['ID', 'user_login', 'display_name']];

        if (\count($userIDs)) {
            $query['include'] = $userIDs;
        }

        $users          = get_users($query);
        $processedUsers = [];

        foreach ($users as $user) {
            $processedUsers[$user->ID] = $user;
        }

        return $processedUsers;
    }
}
