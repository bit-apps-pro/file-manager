<?php

\defined('ABSPATH') or exit();

require_once 'app' . DIRECTORY_SEPARATOR . 'Compatibility' . DIRECTORY_SEPARATOR . 'Deprecated.php';
\BitApps\FM\Compatibility\Deprecated::init();
require_once 'functions' . DIRECTORY_SEPARATOR . 'global.php';

register_activation_hook(BITAPPS_FM_MAIN_FILE, 'bitapps_fm_activate');
register_deactivation_hook(BITAPPS_FM_MAIN_FILE, 'bitapps_fm_deactivate');
register_uninstall_hook(BITAPPS_FM_MAIN_FILE, 'bitapps_fm_uninstall');

add_action('plugins_loaded', 'bitapps_fm_loaded');
