<?php

namespace BitApps\FM\Http\Rules;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


use BitApps\FM\Plugin;
use BitApps\FM\Vendor\BitApps\WPValidator\Rule;

class ValidateCommandsRule extends Rule
{
    public function validate($value)
    {
        $commands = Plugin::instance()->permissions()->allCommands();

        if (!\is_array($value)) {
            return false;
        }

        $visited = [];
        foreach ($commands as $command) {
            $visited[$command] = 1;
        }

        foreach ($value as $key => $command) {
            if (!isset($visited[$command])) {
                return false;
            }
        }

        return true;
    }

    public function message()
    {
        return __('Folder Path Must be within WordPress root directory', 'file-manager');
    }
}
