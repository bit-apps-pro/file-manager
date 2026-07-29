<?php

// phpcs:disable WordPress.WP.AlternativeFunctions
if (! \defined('ABSPATH')) {
    exit;
}

use BitApps\FM\Config;

use function BitApps\FM\Functions\view;

use BitApps\FM\Providers\InstallerProvider;

function bitapps_fm_activate()
{
    include_once BITAPPS_FM_BASEDIR . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

    $installerProvider = new InstallerProvider();
    $installerProvider->register();

    do_action(Config::withPrefix('activate'));

    $baseDir      = Config::uploadBaseDir();
    $trashDir     = Config::getTrashDir();

    wp_mkdir_p($baseDir);
    wp_mkdir_p($baseDir . DIRECTORY_SEPARATOR . '.tmb');
    wp_mkdir_p($baseDir . DIRECTORY_SEPARATOR . '.tmp');
    wp_mkdir_p($trashDir);

    bitapps_fm_harden_dir($baseDir);
    bitapps_fm_harden_dir($baseDir . DIRECTORY_SEPARATOR . '.tmb');
    bitapps_fm_harden_dir($baseDir . DIRECTORY_SEPARATOR . '.tmp');
    bitapps_fm_harden_dir($trashDir);
}

/**
 * Drop a silence-stub index + a hardening .htaccess into a plugin-created upload dir.
 * These dirs live under wp-content/uploads (web-served), so deny direct PHP execution
 * and directory listing as defense-in-depth against a planted script — the .htaccess
 * applies recursively, so lazily-created user_/role_ subdirs are covered too.
 *
 * @param mixed $dir
 */
function bitapps_fm_harden_dir($dir)
{
    if (!\is_string($dir) || $dir === '' || !is_dir($dir)) {
        return;
    }

    $index = $dir . DIRECTORY_SEPARATOR . 'index.php';
    if (!file_exists($index)) {
        @file_put_contents($index, "<?php\n// Silence is golden.\n");
    }

    $htaccess = $dir . DIRECTORY_SEPARATOR . '.htaccess';
    if (!file_exists($htaccess)) {
        $rules = "Options -Indexes\n"
            . "<FilesMatch \"\\.(?i:php|phtml|phar|php[0-9]|pht)$\">\n"
            . "    <IfModule mod_authz_core.c>\n"
            . "        Require all denied\n"
            . "    </IfModule>\n"
            . "    <IfModule !mod_authz_core.c>\n"
            . "        Order allow,deny\n"
            . "        Deny from all\n"
            . "    </IfModule>\n"
            . "</FilesMatch>\n";
        @file_put_contents($htaccess, $rules);
    }
}

function bitapps_fm_uninstall()
{
    include_once BITAPPS_FM_BASEDIR . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
    $installerProvider = new InstallerProvider();
    $installerProvider->register();
    do_action(Config::withPrefix('uninstall'));
}

function bitapps_fm_deactivate()
{
    include_once BITAPPS_FM_BASEDIR . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

    $installerProvider = new InstallerProvider();
    $installerProvider->register();
    do_action(Config::withPrefix('deactivate'));
}

function bitapps_fm_loaded()
{
    /**
     * @deprecated since 6.8.9 Use bitapps_fm_loaded instead.
     */
    do_action('file_manager_init');
    do_action('bitapps_fm_loaded');

    // Autoload vendor files.
    if (!is_readable(BITAPPS_FM_BASEDIR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php')) {
        error_log('Failed to load File Manager. Cause: autoload does not exists');

        return;
    }

    include_once BITAPPS_FM_BASEDIR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
    include_once BITAPPS_FM_BASEDIR . 'backend' . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'common.php';
    // Initialize the plugin.
    BitApps\FM\Plugin::load();
}

// Simple function API to invoke the file manager about anywhere
if (!\function_exists('file_manager_frontend')) {
    function file_manager_frontend()
    {
        if (!is_user_logged_in()) {
            return;
        }

        view('finder');
    }
}
