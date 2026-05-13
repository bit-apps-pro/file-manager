<?php

namespace BitApps\FM\Http\Requests\Permissions;

if (! \defined('ABSPATH')) {
    exit;
}

use BitApps\FM\Http\Rules\ValidateCommandsRule;
use BitApps\FM\Http\Rules\ValidPathRule;
use BitApps\FM\Vendor\BitApps\WPKit\Http\Request\Request;
use BitApps\FM\Vendor\BitApps\WPKit\Utils\Capabilities;

/**
 * @property int    $id
 * @property string $path
 * @property array  $commands
 */
class AddUserPermissionRequest extends Request
{
    public function authorize()
    {
        return Capabilities::filter('bitapps_fm_can_change_permissions', 'install_plugins');
    }

    public function rules()
    {
        return [
            'id'                   => ['sanitize:text', 'required','Integer'],
            'path'                 => ['nullable', ValidPathRule::class],
            'commands'             => ['nullable', ValidateCommandsRule::class]
        ];
    }
}
