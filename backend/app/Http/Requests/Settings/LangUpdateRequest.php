<?php

namespace BitApps\FM\Http\Requests\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


use BitApps\FM\Http\Rules\ValidateLangRule;
use BitApps\FM\Vendor\BitApps\WPKit\Http\Request\Request;
use BitApps\FM\Vendor\BitApps\WPKit\Utils\Capabilities;

class LangUpdateRequest extends Request
{
    public function authorize()
    {
        return Capabilities::filter('bitapps_fm__can_change_language');
    }

    public function rules()
    {
        return [
            'lang'  => ['required','string', ValidateLangRule::class],
        ];
    }
}
