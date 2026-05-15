<?php

namespace BitApps\FM\Http\Rules;

if (! \defined('ABSPATH')) {
    exit;
}

use BitApps\FM\Vendor\BitApps\WPValidator\Rule;

class BooleanRule extends Rule
{
    private $_message = 'The :attribute field must be true or false';

    public function validate($value): bool
    {
        return \in_array($value, [true, false, 'true', 'false', '1', '0', 1, 0, 'on', 'off', 'yes', 'no'], true);
    }

    public function message()
    {
        return $this->_message;
    }
}
