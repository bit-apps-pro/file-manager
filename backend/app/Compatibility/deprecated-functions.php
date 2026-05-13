<?php
if (!function_exists('bfmActivate')) {
    function bfmActivate()
    {
        trigger_error('bfmActivate is deprecated since version 6.8.9, use bitapps_fm_activate instead', E_USER_DEPRECATED);

        return bitapps_fm_activate();
    }
}

if (!function_exists('bfmUninstall')) {
    function bfmUninstall()
    {
        trigger_error('bfmUninstall is deprecated since version 6.8.9, use bitapps_fm_uninstall instead', E_USER_DEPRECATED);

        return bitapps_fm_uninstall();
    }
}

if (!function_exists('bfmDeactivate')) {
    function bfmDeactivate()
    {
        trigger_error('bfmDeactivate is deprecated since version 6.8.9, use bitapps_fm_deactivate instead', E_USER_DEPRECATED);

        return bitapps_fm_deactivate();
    }
}

if (!function_exists('bfmLoaded')) {
    function bfmLoaded()
    {
        trigger_error('bfmLoaded is deprecated since version 6.8.9, use bitapps_fm_loaded instead', E_USER_DEPRECATED);

        return bitapps_fm_loaded();
    }
}