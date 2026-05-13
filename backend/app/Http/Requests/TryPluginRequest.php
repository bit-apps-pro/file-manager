<?php

namespace BitApps\FM\Http\Requests;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


use BitApps\FM\Vendor\BitApps\WPKit\Http\Request\Request;

class TryPluginRequest extends Request
{
    public function authorize()
    {
        return current_user_can('install_plugins');
    }

    public function rules()
    {
        return [
            'tryPlugin' => ['required','array']
        ];
    }
}
