<?php

namespace BitApps\FM\Http\Rules;

if (! \defined('ABSPATH')) {
    exit;
}

use BitApps\FM\Plugin;
use BitApps\FM\Providers\PermissionsProvider;
use BitApps\FM\Vendor\BitApps\WPValidator\Rule;

class ValidPathRule extends Rule
{
    private $_message;

    public function validate($value)
    {
        $path   = Plugin::instance()->preferences()->realPath($value);
        $target = \is_string($path) ? PermissionsProvider::realpathWithin($path, ABSPATH) : null;

        if ($target === null) {
            $this->_message = __('Folder Path Must be within WordPress root directory', 'file-manager');

            return false;
        }

        if (!is_readable($target)) {
            $this->_message = __('Please provide a readable folder path', 'file-manager');

            return false;
        }

        return true;
    }

    public function message()
    {
        return $this->_message;
    }
}
