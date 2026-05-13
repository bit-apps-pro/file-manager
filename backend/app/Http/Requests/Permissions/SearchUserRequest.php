<?php

namespace BitApps\FM\Http\Requests\Permissions;

if (! \defined('ABSPATH')) {
    exit;
}

use BitApps\FM\Vendor\BitApps\WPKit\Http\Request\Request;
use BitApps\FM\Vendor\BitApps\WPKit\Utils\Capabilities;

/**
 * @property string $search
 * @property int    $page
 */
class SearchUserRequest extends Request
{
    public function authorize()
    {
        return Capabilities::filter('bitapps_fm_can_search_user', 'list_users');
    }

    public function rules()
    {
        return [
            'search'               => ['sanitize:text'],
            'page'                 => ['sanitize:text', 'nullable','Integer']
        ];
    }
}
