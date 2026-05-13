<?php

if (! \defined('ABSPATH')) {
    exit;
}
/**
 * Plugin Name: Bit File Manager
 * Plugin URI: https://bitapps.pro/bit-file-manager
 * Author:     File Manager by Bit Form Team
 * Author URI:  https://bitapps.pro
 * Version: 6.8.8
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Text domain: file-manager
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Description: File Manager let you manage your file the way you like.
 *  You can upload, delete, copy, move, rename, compress, extract files.
 *  You don't need to worry about ftp. It is really simple and easy to use.
 * */
\define('BITAPPS_FM_MAIN_FILE', __FILE__);
\define('BITAPPS_FM_BASEDIR', plugin_dir_path(__FILE__));

require_once 'backend' . DIRECTORY_SEPARATOR . 'bootstrap.php';
