<?php

namespace BitApps\FM\Http\Rules;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


use BitApps\FM\Plugin;
use BitApps\FM\Vendor\BitApps\WPValidator\Rule;

class ValidateLangRule extends Rule
{
    public function validate($value)
    {
        $languages = Plugin::instance()->preferences()->availableLanguages();

        return isset($languages[$value]) ? true : false;
    }

    public function message()
    {
        return __('Language code is not valid', 'file-manager');
    }
}
