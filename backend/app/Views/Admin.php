<?php

namespace BitApps\FM\Views;

if (! \defined('ABSPATH')) {
    exit;
}

use BitApps\FM\Config;

use function BitApps\FM\Functions\view;

use BitApps\FM\Plugin;

use BitApps\FM\Vendor\BitApps\WPKit\Hooks\Hooks;

use BitApps\FM\Vendor\BitApps\WPKit\Utils\Capabilities;

/**
 * The admin Layout and page handler class.
 */
class Admin
{
    private $_reviewBlock;

    public function __construct()
    {
        Hooks::addAction('in_admin_header', [$this, 'removeAdminNotices']);
        Hooks::addAction('admin_menu', [$this, 'sideBarMenuItem']);
        Hooks::addAction('admin_notices', [$this, 'adminNotice']);
        Hooks::removeAction('admin_notices', [Plugin::instance()->telemetryReport(), 'adminNotice']);
        Hooks::addAction('admin_enqueue_scripts', [$this, 'adminEnqueueAssets'], 20);
        Hooks::addFilter(Config::withPrefix('localized_script'), [$this, 'filterConfigVariable']);
        Hooks::addFilter('script_loader_tag', [$this, 'filterScriptTag'], 0, 3);

        /*
         * // For testing --
         * Hooks::addFilter('can_access_fm_home', [$this, 'filterCapabilityForHomeMenu']);
         */
    }

    /**
     * Register the admin left sidebar menu item.
     */
    public function sideBarMenuItem()
    {
        $menus = Hooks::applyFilter(Config::withPrefix('admin_sidebar_menu'), $this->sideBarMenus());
        global $submenu;
        foreach ($menus as $menu) {
            if (isset($menu['capability']) && Capabilities::check($menu['capability'])) {
                if ($menu['type'] == 'menu') {
                    add_menu_page(
                        $menu['title'],
                        $menu['name'],
                        $menu['capability'],
                        $menu['slug'],
                        $menu['callback'],
                        $menu['icon'],
                        $menu['position']
                    );
                } elseif (isset($menu['callback'])) {
                    add_submenu_page(
                        $menu['parent'],
                        $menu['title'],
                        $menu['name'],
                        $menu['capability'],
                        $menu['slug'],
                        $menu['callback']
                    );
                } else {
                    $submenu[$menu['parent']][] = [
                        $menu['name'],
                        $menu['capability'],
                        'admin.php?page=' . $menu['slug'],
                    ];
                }
            }
        }
    }

    public function removeAdminNotices()
    {
        // phpcs:disable
        global $plugin_page;
        if (empty($plugin_page) || strpos($plugin_page, Config::SLUG) === false) {
            return;
        }
        // phpcs:enable
        remove_all_actions('admin_notices');
        remove_all_actions('all_admin_notices');

        $this->adminNotice();
    }

    public function filterConfigVariable($config)
    {
        if (Capabilities::check('install_plugins')) {
            $config['sys_info'] = $this->getSystemInfo();
        }

        if ($adBanner = Config::adBanner()) {
            $config['adBanner'] = $adBanner;
        }

        return (array) $config + [
            'nonce'         => wp_create_nonce('wp_rest'),
            'apiBase'       => get_rest_url(null, '/file-manager/v1'),
            'baseURL'       => Config::get('ADMIN_URL') . 'admin.php?page=' . Config::SLUG . '#/home',
            'pluginSlug'    => Config::SLUG,
            'rootURL'       => Config::get('ROOT_URI'),
            'assetsURL'     => Config::get('ASSET_URI') . '/',
            'routePrefix'   => Config::VAR_PREFIX,
            'plugin_dir'    => Config::get('BASEDIR'),
            'plugin_url'    => Config::get('ROOT_URI'),
            'action'        => Config::withPrefix('connector'),
            'options'       => Plugin::instance()->preferences()->finderOptions(),
            'telemetry'     => ['tryPlugin' => Config::tryPlugin()],
        ];
    }

    public function enqueueAssets()
    {
        $preferences = Plugin::instance()->preferences();

        wp_enqueue_style(Config::SLUG . 'jquery-ui-css');
        wp_enqueue_style(Config::SLUG . 'jquery-ui-theme-css');
        wp_enqueue_style(Config::SLUG . 'elfinder-css');

        wp_enqueue_script(Config::SLUG . 'elfinder-script');
        wp_enqueue_script(Config::SLUG . 'elfinder-editor-script');
        wp_enqueue_script(Config::SLUG . 'elfinder-lang', $preferences->getLangUrl(), [Config::SLUG . 'elfinder-script']);

        $devPort = Config::devPort();
        if ($devPort > 0) {
            $devUrl = 'http://localhost:' . $devPort;
            wp_enqueue_script(
                Config::SLUG . '-MODULE-vite-client-helper',
                $devUrl . '/config/devHotModule.js',
                [],
                null
            );
            wp_enqueue_script(Config::SLUG . '-MODULE-vite-client', $devUrl . '/@vite/client', [], null);
            wp_enqueue_script(Config::SLUG . '-MODULE-index', $devUrl . '/main.tsx', [], null);
        } else {
            wp_enqueue_script(
                Config::SLUG . '-MODULE-main',
                Config::get('ASSET_JS_URI') . '/main.' . Config::VERSION . '.js',
                [Config::SLUG . 'elfinder-script'],
                Config::VERSION
            );

            wp_enqueue_style(
                Config::SLUG . '-style-main',
                Config::get('ASSET_JS_URI') . '/main.' . Config::VERSION . '.css'
            );
        }
    }

    public function adminEnqueueAssets()
    {
        $page = sanitize_text_field(wp_unslash($_GET['page'] ?? ''));
        if (empty($page) || strpos($page, Config::SLUG) === false) {
            return;
        }
        $this->enqueueAssets();
    }

    public function dashboard()
    {
        // phpcs:ignore
        echo "<div id='bit-fm-root'></div>";
    }

    public function homePage()
    {
        return view('admin.index');
    }

    public function settingsPage()
    {
        return view('admin.settings');
    }

    public function logsPage()
    {
        return view('admin.logs');
    }

    public function systemInfoPage()
    {
        return view('admin.utility');
    }

    public function permissionsPage()
    {
        return view('admin.permission_system');
    }

    /**
     * Adds admin notices to the admin page
     *
     * @return void
     * */
    public function adminNotice()
    {
        // DISALLOW_FILE_EDIT Macro checking
        if (\defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT) {
            ?>
            <div class='update-nag fm-error notice notice-success is-dismissible'>
                <b>DISALLOW_FILE_EDIT</b> <?php esc_html_e('is set to', 'file-manager'); ?>
                <b>TRUE</b>.
                <?php esc_html_e('You will not be able to edit files with', 'file-manager'); ?>
                 <a href='admin.php?page=file-manager#/settings'>Bit File Manager</a>.
                <?php esc_html_e('Please set', 'file-manager'); ?>
                <b>DISALLOW_FILE_EDIT</b> <?php esc_html_e('to', 'file-manager'); ?>
                <b>FALSE</b>
                in wp-config.php
            </div>
            <style>
                .fm-error {
                    border-left: 4px solid red;
                    display: block;
                }
            </style>
<?php
        }
    }

    public function filterCapabilityForHomeMenu()
    {
        if (Plugin::instance()->permissions()->isCurrentUserHasPermission()
        || Plugin::instance()->permissions()->isCurrentRoleHasPermission()
        ) {
            return Plugin::instance()->permissions()->currentUserRole();
        }

        return false;
    }

    /**
     * Modify script tags.
     *
     * @param string $html   script tag
     * @param mixed  $handle
     * @param mixed  $href
     *
     * @return string new script tag
     */
    public function filterScriptTag($html, $handle)
    {
        $newTag = $html;
        if (str_contains($handle, Config::SLUG . '-MODULE')) {
            $newTag = preg_replace('/<script /', '<script type="module" ', $newTag);
        }

        return $newTag;
    }

    private function getSystemInfo()
    {
        return [
            'currentMediaDir'    => esc_url(wp_upload_dir()['path']),
            'phpVersion'         => esc_html(PHP_VERSION),
            'iniPath'            => esc_html(php_ini_loaded_file()),
            'uploadMaxFilesize'  => esc_html(\ini_get('upload_max_filesize')),
            'postMaxSize'        => esc_html(\ini_get('post_max_size')),
            'memoryLimit'        => esc_html(\ini_get('memory_limit')),
            'maxExecutionTime'   => esc_html(\ini_get('max_execution_time')),
            'ua'                 => esc_html($_SERVER['HTTP_USER_AGENT']),
            'fileEditNotAllowed' => \defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT,
        ];
    }

    /**
     * Provides menus for wordpress admin sidebar.
     * should return an array of menus with the following structure:
     * [
     *   'type' => menu | submenu,
     *  'name' => 'Name of menu will shown in sidebar',
     *  'capability' => 'capability required to access menu',
     *  'slug' => 'slug of menu after ?page=',.
     *
     *  'title' => 'page title will be shown in browser title if type is menu',
     *  'callback' => 'function to call when menu is clicked',
     *  'icon' =>   'icon to display in menu if menu type is menu',
     *  'position' => 'position of menu in sidebar if menu type is menu',
     *
     * 'parent' => 'parent slug if submenu'
     * ]
     *
     * @return array<string,array{type:string,title:string,name:string,slug:string,callback:callable}>
     */
    private function sideBarMenus()
    {
        return [
            'Bit File Manager'   => [
                'type'       => 'menu',
                'title'      => __('Dashboard | Bit File Manager', 'file-manager'),
                'name'       => __('Bit File Manager', 'file-manager'),
                'capability' => Hooks::applyFilter('can_access_fm_home', 'manage_options'),// fm_capabilities
                'slug'       => Config::SLUG,
                'callback'   => [$this, 'dashboard'],
                'icon'       => 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 19 19"><path fill="currentColor" d="M5 8.13v-2.5q0-.82.88-.88H10q.3 0 .63.19l.18.31.31.38.26.3.12.13h5.38q.62.25.68.88v1.18l.19.07q.63.25.81.87v.5l-.12.5-.07.75-.12.63-.19 1.69-.25 2.18-.12.94-.13.63-.06.5q-.3.67-1.06.75H5.87l-.62-.2q-.31-.24-.44-.62v-.37l-.12-.82-.13-1-.43-3.43-.25-1.5v-.5l-.13-.31v-.13c0-.69.63-1.12 1.25-1.12m5.19-1.26-.13-.3v-.2L10 6.2l-.31-.25h-.31l-.44.12-1.44.32-.62.18-.38.07q-.25.13-.19.43v.25l.19.82h8.88l-.13-.44-.12-.57q0-.18-.2-.43l-.37-.07h-.19l-.24.07-.57.12-.75.19-.62.13-.06-.2q-.14-.3-.44-.3h-.31l-.44.12-.5.13Zm.94 2.94h-.26l-.56.13a3.3 3.3 0 0 0-.94 5.69q.75.62 1.7.62h.5a3.1 3.1 0 0 0 3-2.69v-.81a3.1 3.1 0 0 0-3-3h-.2Zm-.38 3.69.25-.31 1.31-1.25q0-.2.19-.19.25 0 .31.13.2.19.13.3l-.13.2-1.87 1.87-.25.13-.13-.07-.81-.81-.06-.12q-.12 0-.13-.2 0-.18.13-.24.06-.12.25-.13.18 0 .25.19Zm0 0"/></svg>'),
                'position'   => '2',
            ],
            'Home'               => [
                'parent'     => Config::SLUG,
                'type'       => 'submenu',
                'title'      => __('Dashboard | Bit File Manager', 'file-manager'),
                'name'       => __('Home', 'file-manager'),
                'capability' => Hooks::applyFilter('bitapps_fm_can_access_home', 'manage_options'),
                'slug'       => Config::SLUG . '#/home',
                'position'   => '2',
            ],
            'Logs'           => [
                'parent'     => Config::SLUG,
                'type'       => 'submenu',
                'name'       => __('Logs', 'file-manager'),

                'title'      => __('Logs | Bit File Manager', 'file-manager'),
                'capability' => Hooks::applyFilter('bitapps_fm_can_access_logs', 'install_plugins'),
                'slug'       => Config::SLUG . '#/logs',
            ],
            'Settings'           => [
                'parent'     => Config::SLUG,
                'type'       => 'submenu',
                'name'       => __('Settings', 'file-manager'),
                'title'      => __('Settings | Bit File Manager', 'file-manager'),
                'capability' => Hooks::applyFilter('bitapps_fm_can_change_settings', 'install_plugins'),
                'slug'       => Config::SLUG . '#/settings',
            ],
            'Permissions'        => [
                'parent'     => Config::SLUG,
                'type'       => 'submenu',
                'name'       => __('Permissions', 'file-manager'),
                'title'      => __(
                    'Permissions - Sets permission for specific user or by user role | Bit File Manager',
                    'file-manager'
                ),
                'capability' => Hooks::applyFilter('bitapps_fm_can_change_permissions', 'install_plugins'),
                'slug'       => Config::SLUG . '#/permissions',
            ],
            'Support' => [
                'parent'     => Config::SLUG,
                'type'       => 'submenu',
                'name'       => __('Support', 'file-manager'),
                'title'      => __(
                    'Support | Bit File Manager',
                    'file-manager'
                ),
                'capability' => Hooks::applyFilter('bitapps_fm_can_access_home', 'manage_options'),
                'slug'       => Config::SLUG . '#/support',
            ],
            'System Information' => [
                'parent'     => Config::SLUG,
                'type'       => 'submenu',
                'name'       => __('System Information', 'file-manager'),
                'title'      => __(
                    'System Information | Bit File Manager',
                    'file-manager'
                ),
                'capability' => Hooks::applyFilter('bitapps_fm_can_view_sys_info', 'install_plugins'),
                'slug'       => Config::SLUG . '#/system-info',
            ],
        ];
    }
}
