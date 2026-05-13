<?php

namespace BitApps\FM;

if (! \defined('ABSPATH')) {
    exit;
}

use function BitApps\FM\Functions\fileSystemAdapter;
use function BitApps\FM\Functions\view;

use BitApps\FM\Http\Middleware\CapCheckerMiddleware;
use BitApps\FM\Http\Middleware\NonceCheckerMiddleware;

use BitApps\FM\Providers\AccessControlProvider;

use BitApps\FM\Providers\FileEditValidator;

use BitApps\FM\Providers\HookProvider;
use BitApps\FM\Providers\InstallerProvider;
use BitApps\FM\Providers\Logger;
use BitApps\FM\Providers\MediaSynchronizer;
use BitApps\FM\Providers\MimeProvider;
use BitApps\FM\Providers\PermissionsProvider;
use BitApps\FM\Providers\PreferenceProvider;
use BitApps\FM\Providers\VersionMigrationProvider;
use BitApps\FM\Vendor\BitApps\WPKit\Hooks\Hooks;
use BitApps\FM\Vendor\BitApps\WPKit\Http\RequestType;
use BitApps\FM\Vendor\BitApps\WPKit\Migration\MigrationHelper;
use BitApps\FM\Vendor\BitApps\WPTelemetry\Telemetry\Report\Report;

use BitApps\FM\Vendor\BitApps\WPTelemetry\Telemetry\Telemetry;
use BitApps\FM\Vendor\BitApps\WPTelemetry\Telemetry\TelemetryConfig;
use BitApps\FM\Views\Admin;
use BitApps\FM\Views\Shortcode;

final class Plugin
{
    /**
     * Main instance of the plugin.
     *
     * @var null|Plugin
     */
    private static $_instance;

    private $_registeredMiddleware = [];

    private $_container = [];

    /**
     * Initialize the Plugin with hooks.
     */
    public function __construct()
    {
        Hooks::addAction('init', [$this, 'registerProviders']);
        Hooks::addAction('admin_enqueue_scripts', [$this, 'registerAssets']);
        Hooks::addFilter('plugin_action_links_' . Config::get('BASENAME'), [$this, 'actionLinks']);
        Hooks::addAction('wp_dashboard_setup', [$this, 'addNoticeToDashBoard']);
        $this->setPhpIniVars();
        $this->uploadFolder();
    }

    public function middlewares()
    {
        return [
            'nonce' => NonceCheckerMiddleware::class,
            'cap'   => CapCheckerMiddleware::class,
        ];
    }

    public function getMiddleware($name)
    {
        if (isset($this->_registeredMiddleware[$name])) {
            return $this->_registeredMiddleware[$name];
        }

        $middlewares = $this->middlewares();
        if (isset($middlewares[$name])
         && class_exists($middlewares[$name])
         && method_exists($middlewares[$name], 'handle')) {
            $this->_registeredMiddleware[$name] = new $middlewares[$name]();
        } else {
            return false;
        }

        return $this->_registeredMiddleware[$name];
    }

    /**
     * Instantiate the Provider class.
     */
    public function registerProviders()
    {
        if (RequestType::is('admin')) {
            new Admin();
        }

        if (RequestType::is('frontend')) {
            new Shortcode();
        }

        new HookProvider();

        $this->initTelemetry();

        $this->_container['access_control']      = new AccessControlProvider();
        $this->_container['logger']              = new Logger();
        $this->_container['permissions']         = new PermissionsProvider();
        $this->_container['mimes']               = new MimeProvider(Config::getFinderDirectory() . DIRECTORY_SEPARATOR . 'php/mime.types');
        $this->_container['media_sync']          = new MediaSynchronizer();
        $this->_container['file_edit_validator'] = new FileEditValidator();
        $this->_container['preferences']         = new PreferenceProvider();

        $migrationProvider = new VersionMigrationProvider();
        $migrationProvider->migrate();
    }

    /**
     * Provide preferences
     *
     * @return PreferenceProvider
     */
    public function preferences()
    {
        if (!isset($this->_container['preferences'])) {
            $this->_container['preferences'] = new PreferenceProvider();
        }

        return $this->_container['preferences'];
    }

    /**
     * Provide php syntax checker
     *
     * @return FileEditValidator
     */
    public function fileEditValidator()
    {
        if (!isset($this->_container['file_edit_validator'])) {
            $this->_container['file_edit_validator'] = new FileEditValidator();
        }

        return $this->_container['file_edit_validator'];
    }

    /**
     * Provide media synchronizer
     *
     * @return MediaSynchronizer
     */
    public function mediaSyncs()
    {
        if (!isset($this->_container['media_sync'])) {
            $this->_container['media_sync'] = new MediaSynchronizer();
        }

        return $this->_container['media_sync'];
    }

    /**
     * Provide finder mime types
     *
     * @return MimeProvider
     */
    public function mimes()
    {
        if (!isset($this->_container['mimes'])) {
            $this->_container['mimes'] = new MimeProvider(Config::getFinderDirectory() . DIRECTORY_SEPARATOR . 'php/mime.types');
        }

        return $this->_container['mimes'];
    }

    /**
     * Provide stored permissions settings
     *
     * @return PermissionsProvider
     */
    public function permissions()
    {
        if (!isset($this->_container['permissions'])) {
            $this->_container['permissions'] = new PermissionsProvider();
        }

        return $this->_container['permissions'];
    }

    /**
     * Provide access control for file manager
     *
     * @return AccessControlProvider
     */
    public function accessControl()
    {
        if (!isset($this->_container['access_control'])) {
            $this->_container['access_control'] = new AccessControlProvider();
        }

        return $this->_container['access_control'];
    }

    /**
     * Provide Logger for finder
     *
     * @return Logger
     */
    public function logger()
    {
        if (!isset($this->_container['logger'])) {
            $this->_container['logger'] = new Logger();
        }

        return $this->_container['logger'];
    }

    public function get($name)
    {
        if (isset($this->_container[$name])) {
            return $this->_container[$name];
        }

        return false;
    }

    /**
     * Load the asset libraries.
     *
     * @param string $currentScreen top_level_page variable for current page
     */
    public function registerAssets($currentScreen = '')
    {
        $version = Config::VERSION;

        wp_register_script(
            Config::SLUG . 'finder-loader',
            Config::get('ASSET_JS_URI') . '/finder-loader.js',
            [Config::SLUG . 'elfinder-script', 'jquery'],
            $version
        );
        $this->registerFinderAssets(); // Loads all the assets necessary for elFinder
    }

    /**
     * Registers all the elfinder assets
     * */
    public function registerFinderAssets()
    {
        wp_register_style(
            Config::SLUG . 'jquery-ui-css',
            Config::get('ASSET_URI') . '/style/jquery-ui.min.css',
            [],
            Config::VERSION
        );

        wp_register_style(
            Config::SLUG . 'jquery-ui-theme-css',
            Config::get('ASSET_URI') . '/style/jquery-ui.theme.min.css',
            [],
            Config::VERSION
        );

        wp_register_style(
            Config::SLUG . 'elfinder-css',
            Config::getFinderUrl() . '/css/elfinder.min.css',
            [],
            Config::VERSION
        );

        // elFinder Scripts depends on jQuery UI core, selectable, draggable, droppable, resizable, dialog and slider.
        wp_register_script(
            Config::SLUG . 'elfinder-script',
            Config::getFinderUrl() . '/js/elfinder.min.js',
            ['jquery', 'jquery-ui-core', 'jquery-ui-selectable', 'jquery-ui-draggable', 'jquery-ui-droppable', 'jquery-ui-resizable', 'jquery-ui-dialog', 'jquery-ui-slider', 'jquery-ui-tabs']
        );

        wp_register_script(
            Config::SLUG . 'elfinder-editor-script',
            Config::getFinderUrl() . '/js/extras/editors.default.min.js',
            [Config::SLUG . 'elfinder-script']
        );

        wp_localize_script(
            Config::SLUG . 'elfinder-script',
            'bitapps_fm',
            $this->createConfigVariable()
        );
    }

    public function createConfigVariable()
    {
        return apply_filters(
            Config::withPrefix('localized_script'),
            [
                'ajaxURL'      => admin_url('admin-ajax.php'),
                'js_url'       => Config::getFinderUrl() . '/js/',
                'elfinder'     => Config::getFinderUrl(),
                'translations' => $this->loadTranslations(),
            ]
        );
    }

    /**
     * Load translations for frontend.
     *
     * @return array
     */
    public function loadTranslations()
    {
        $translations = [];
        if (get_locale() !== 'en_US' && file_exists(Config::get('BASEDIR') . '/languages/generatedString.php')) {
            include_once Config::get('BASEDIR') . '/languages/generatedString.php';
            $translations = $i18n_strings;
        }

        return $translations;
    }

    /**
     * Plugin action links.
     *
     * @param array $links Array of links
     *
     * @return array
     */
    public function actionLinks($links)
    {
        $linksToAdd = Config::get('PLUGIN_PAGE_LINKS');
        foreach ($linksToAdd as $link) {
            $links[] = '<a href="' . $link['url'] . '">' . $link['title'] . '</a>';
        }

        return $links;
    }

    public function addNoticeToDashBoard()
    {
        wp_add_dashboard_widget(
            'bitapps_notice',
            'Bit Apps',
            function () {
                view('admin.widget');
            },
            null,
            null,
            'normal',
            'high'
        );

        global $wp_meta_boxes;

        $metaBox = $wp_meta_boxes['dashboard']['normal']['high'];
        $notice  = [
            'bitapps_notice' => $metaBox['bitapps_notice'],
        ];

        $wp_meta_boxes['dashboard']['normal']['high'] = array_merge($notice, $metaBox);
    }

    /**
     * Retrieves the main instance of the plugin.
     *
     * @return Plugin plugin main instance
     */
    public static function instance()
    {
        return static::$_instance;
    }

    /**
     * Loads the plugin main instance and initializes it.
     *
     * @return bool True if the plugin main instance could be loaded, false otherwise
     */
    public static function load()
    {
        if (static::$_instance !== null) {
            return false;
        }

        static::$_instance = new static();
        if (version_compare(Config::getOption('version'), Config::VERSION_ID, '<')) {
            MigrationHelper::migrate(InstallerProvider::migration());
        }

        return true;
    }

    public function initTelemetry()
    {
        TelemetryConfig::setSlug(Config::SLUG);
        TelemetryConfig::setTitle(Config::TITLE);
        TelemetryConfig::setPrefix(Config::VAR_PREFIX);
        TelemetryConfig::setVersion(Config::VERSION);

        TelemetryConfig::setServerBaseUrl('https://wp-api.bitapps.pro/public/');

        TelemetryConfig::setTermsUrl('https://bitapps.pro/terms-of-service/');
        TelemetryConfig::setPolicyUrl('https://bitapps.pro/refund-policy/');

        $this->telemetryReport();
        Telemetry::feedback()->init();
    }

    /**
     * Telemetry Report Instance
     *
     * @return Report
     */
    public function telemetryReport()
    {
        if (!isset($this->_container['telemetry_report'])) {
            $telemetryReport                      = Telemetry::report();
            $this->_container['telemetry_report'] = $telemetryReport;
            $telemetryReport->init();
        }

        return $this->_container['telemetry_report'];
    }

    /**
     * Set the all necessary variables of php.ini file.
     * */
    protected function setPhpIniVars()
    {
        if (\defined('WP_DEBUG') && isset($_REQUEST['action']) && sanitize_text_field($_REQUEST['action']) === 'bitapps_fm_connector') {
            ini_set('post_max_size', '128M');
            ini_set('upload_max_filesize', '128M');
        }
    }

    /**
     * Checks if the upload folder is present. If not creates a upload folder.
     * */
    private function uploadFolder()
    {
        if (!is_dir(Config::uploadBaseDir())) {
            fileSystemAdapter()->mkdir(Config::uploadBaseDir(), 0755);
        }
    }
}
