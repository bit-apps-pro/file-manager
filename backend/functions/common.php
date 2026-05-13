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

function pr($obj)
{
    if (\defined('BITAPPS_DEV') && BITAPPS_DEV) {
        return;
    }

    echo '<pre>';
    print_r($obj);
    echo '</pre>';
}

function validatePath($path, $for = '')
{
    if (!class_exists('BitApps\FM\Plugin') || !\defined('ABSPATH')) {
        return;
    }

    if (current_user_can('install_plugins')) {
        return $path;
    }

    $realPath = trailingslashit(Plugin::instance()->preferences()->realPath($path));

    $error = null;

    if (strpos($realPath, ABSPATH) === false) {
        $error = 'Directory path must be within WordPress root directory. ' . $for . ' path: ' . $realPath;
    }

    if (!is_readable($realPath)) {
        $error = 'Directory is not readable or not exits. ' . $for . ' path: ' . $realPath;
    }

    if (!\is_null($error)) {
        view('admin.header'); ?>

        <div class='fm-container'>
            <div class='col-main col-main-permission-system'>
                <div class='gb-fm-row'>
                    <?php echo esc_html($error) ?>
                </div>
                <div class='gb-fm-row'>
                    <a style="background: #0073aa;padding: 12px;color: white;border-radius: 22px;text-decoration: none;" href="<?php echo esc_attr($_SERVER['REQUEST_URI']) ?>">Go Back</a>
                </div>
            </div><?php view('admin.sidebar'); ?>
        </div>
        <?php view('admin.footer');
        wp_die();
    }

    return $realPath;
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
