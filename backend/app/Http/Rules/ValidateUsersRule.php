<?php

namespace BitApps\FM\Http\Rules;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


use BitApps\FM\Plugin;
use BitApps\FM\Vendor\BitApps\WPValidator\Rule;

class ValidateUsersRule extends Rule
{
    public function validate($value)
    {
        $users = Plugin::instance()->permissions()->mappedUsers(array_keys($value));

        if (!\is_array($value)) {
            return false;
        }

        foreach ($value as $usrId => $permissions) {
            if (!isset($users[$usrId])) {
                return false;
            }
        }

        return true;
    }

    public function message()
    {
        return __('User is not valid', 'file-manager');
    }
}
