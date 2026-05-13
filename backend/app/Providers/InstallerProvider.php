<?php

namespace BitApps\FM\Providers;

if (! \defined('ABSPATH')) {
    exit;
}

use BitApps\FM\Config;
use BitApps\FM\Vendor\BitApps\WPKit\Installer;

class InstallerProvider
{
    private $_activateHook;

    private static $_uninstallHook;

    public function __construct()
    {
        $this->_activateHook   = Config::withPrefix('activate');
        self::$_uninstallHook  = Config::withPrefix('uninstall');
    }

    public function register()
    {
        $installer = new Installer(
            [
                'php'        => Config::REQUIRED_PHP_VERSION,
                'wp'         => Config::REQUIRED_WP_VERSION,
                'version'    => Config::VERSION,
                'oldVersion' => Config::getOption('version', '0.0'),
                'multisite'  => false,
                'basename'   => Config::get('BASENAME'),
            ],
            [
                'activate'  => $this->_activateHook,
                'uninstall' => self::$_uninstallHook,
            ],
            [

                'migration' => $this->migration(),
                'drop'      => $this->drop(),
            ]
        );
        $installer->register();
    }

    public static function migration()
    {
        $migrations = [
            'BFMLogsTableMigration',
            'BFMPluginOptions',
        ];

        return [
            'path'       => Config::get('BACKEND_DIR')
                . DIRECTORY_SEPARATOR
                . 'db'
                . DIRECTORY_SEPARATOR
                . 'Migrations'
                . DIRECTORY_SEPARATOR,
            'migrations' => $migrations,
        ];
    }

    public static function drop()
    {
        $migrations = [
            'BFMLogsTableMigration',
            'BFMPluginOptions',
        ];

        return [
            'path'       => Config::get('BACKEND_DIR')
                . DIRECTORY_SEPARATOR
                . 'db'
                . DIRECTORY_SEPARATOR
                . 'Migrations'
                . DIRECTORY_SEPARATOR,
            'migrations' => $migrations,
        ];
    }
}
