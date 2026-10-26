<?php

namespace BitApps\FM\Functions;

if (! \defined('ABSPATH')) {
    exit;
}

use BitApps\FM\Config;

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

/**
 * Flags a plugin filter callback that returned a value of the wrong type.
 *
 * @param string $hook     filter name
 * @param string $expected human-readable description of the expected return value
 */
function reportInvalidFilterReturn(string $hook, string $expected)
{
    _doing_it_wrong(
        esc_html($hook),
        // translators: 1: description of the expected return value, e.g. "an array"
        esc_html(wp_sprintf(__('Callbacks must return %s; invalid values are ignored.', 'file-manager'), $expected)),
        esc_html(Config::VERSION)
    );
}
