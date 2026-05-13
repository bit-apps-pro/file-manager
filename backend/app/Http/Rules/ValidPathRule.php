<?php

namespace BitApps\FM\Http\Rules;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


use BitApps\FM\Plugin;
use BitApps\FM\Vendor\BitApps\WPValidator\Rule;

class ValidPathRule extends Rule
{
    private $_message;

    public function validate($value)
    {
        $path    = Plugin::instance()->preferences()->realPath($value);
        $isValid = false;
        if (strpos($path, trim(ABSPATH, '/\\')) === false) {
            $this->_message = __('Folder Path Must be within WordPress root directory', 'file-manager');
        } elseif (!is_readable($path)) {
            $this->_message = __('Please provide a readable folder path', 'file-manager');
        } else {
            $isValid = true;
        }

        return $isValid;
    }

    public function message()
    {
        return $this->_message;
    }
}
