<?php

namespace BitApps\FM\Http\Requests\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


use BitApps\FM\Http\Rules\ValidateThemeRule;
use BitApps\FM\Vendor\BitApps\WPKit\Http\Request\Request;
use BitApps\FM\Vendor\BitApps\WPKit\Utils\Capabilities;

class ThemeUpdateRequest extends Request
{
    public function authorize()
    {
        return Capabilities::filter('bitapps_fm__can_change_theme');
    }

    public function rules()
    {
        return [
            'theme'  => ['required','string', ValidateThemeRule::class],
        ];
    }
}
