<?php

use BitApps\FM\Config;
use BitApps\FM\Vendor\BitApps\WPDatabase\Blueprint;
use BitApps\FM\Vendor\BitApps\WPDatabase\Connection;
use BitApps\FM\Vendor\BitApps\WPDatabase\Schema;
use BitApps\FM\Vendor\BitApps\WPKit\Migration\Migration;

if (! \defined('ABSPATH')) {
    exit;
}

final class BFMLogsTableMigration extends Migration
{
    public function up()
    {
        Schema::withPrefix(Connection::wpPrefix() . Config::VAR_PREFIX)->create(
            'logs',
            function (Blueprint $table) {
                $table->id();
                $table->int('user_id', 11);
                $table->string('command')->length(32);
                $table->longtext('details');
                $table->datetime('created_at');
            }
        );
    }

    public function down()
    {
        Schema::drop('logs');
    }
}
