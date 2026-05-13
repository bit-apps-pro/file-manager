<?php

namespace BitApps\FM\Http\Requests\Permissions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


use BitApps\FM\Vendor\BitApps\WPKit\Http\Request\Request;
use BitApps\FM\Vendor\BitApps\WPKit\Utils\Capabilities;

class PermissionsGetRequest extends Request
{
    public function authorize()
    {
        return Capabilities::filter('bitapps_fm_can_view_settings', 'install_plugins');
    }
}
