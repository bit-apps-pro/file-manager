<?php

namespace BitApps\FM\Http\Requests\Log;

if (! \defined('ABSPATH')) {
    exit;
}

use BitApps\FM\Vendor\BitApps\WPKit\Http\Request\Request;
use BitApps\FM\Vendor\BitApps\WPKit\Utils\Capabilities;

class DeleteLogRequest extends Request
{
    public function authorize()
    {
        return Capabilities::filter('bitapps_fm_can_delete_log', 'manage_options');
    }

    public function rules()
    {
        return [
            'ids' => ['required','array']
        ];
    }
}
