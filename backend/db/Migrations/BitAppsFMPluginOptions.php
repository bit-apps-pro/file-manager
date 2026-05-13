<?php

use BitApps\FM\Config;
use BitApps\FM\Vendor\BitApps\WPDatabase\Connection;
use BitApps\FM\Vendor\BitApps\WPKit\Migration\Migration;

if (!\defined('ABSPATH')) {
    exit;
}

final class BFMPluginOptions extends Migration
{
    public function up()
    {
        Config::addOption('installed', time(), true);
        Config::updateOption('version', Config::VERSION_ID, true);
        Config::updateOption('db_version', Config::DB_VERSION, true);
    }

    public function down()
    {
        $pluginOptions = [
            Config::withPrefix('db_version'),
            Config::withPrefix('installed'),
            Config::withPrefix('version'),
            Config::withPrefix('preferences'),
            Config::withPrefix('permissions'),
            Config::withPrefix('log_deleted_at'),
        ];

        Connection::query(
            Connection::prepare(
                'DELETE FROM `' . Connection::wpPrefix() . 'options` WHERE option_name in ('
                    . implode(
                        ',',
                        array_map(
                            function () {
                                return '%s';
                            },
                            $pluginOptions
                        )
                    ) . ')',
                $pluginOptions
            )
        );
    }
}
