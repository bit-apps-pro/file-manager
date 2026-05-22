<?php

namespace BitApps\FM\Functions;

if (! \defined('ABSPATH')) {
    exit;
}

use BitApps\FM\Config;
use BitApps\FM\Plugin;

function view($path)
{
    $pathString = Config::get('VIEW_DIR');

    foreach (explode('.', $path) as $dir) {
        $pathString = $pathString . DIRECTORY_SEPARATOR . $dir;
    }

    $pathString = $pathString . '.php';

    if (file_exists($pathString) && is_file($pathString)) {
        include_once $pathString;
    }
}

function fileSystemAdapter()
{
    global $wp_filesystem;

    if (empty($wp_filesystem)) {
        require_once ABSPATH . '/wp-admin/includes/file.php';
        WP_Filesystem();
    }

    return $wp_filesystem;
}
