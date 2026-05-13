<?php

namespace BitApps\FM\Http\Requests\Permissions;

if (! \defined('ABSPATH')) {
    exit;
}

use BitApps\FM\Http\Rules\ValidateCommandsRule;
use BitApps\FM\Http\Rules\ValidateRolesRule;
use BitApps\FM\Http\Rules\ValidateUsersRule;
use BitApps\FM\Http\Rules\ValidPathRule;
use BitApps\FM\Vendor\BitApps\WPKit\Http\Request\Request;
use BitApps\FM\Vendor\BitApps\WPKit\Utils\Capabilities;

class PermissionsUpdateRequest extends Request
{
    public function authorize()
    {
        return Capabilities::filter('bitapps_fm_can_change_permissions', 'install_plugins');
    }

    public function rules()
    {
        return [
            'do_not_use_for_admin' => ['sanitize:text', 'nullable','boolean'],
            'fileType'             => ['nullable','array'],
            'file_size'            => ['sanitize:text', 'nullable','Integer'],
            'root_folder'          => ['sanitize:text', 'nullable', ValidPathRule::class],
            'root_folder_url'      => ['sanitize:text', 'nullable','string'],
            'folder_options'       => ['sanitize:text', 'nullable','string'],
            'by_role'              => ['nullable', ValidateRolesRule::class],
            'by_user'              => ['nullable', ValidateUsersRule::class],
            'by_user.*.path'       => ['nullable', ValidPathRule::class],
            'by_user.*.commands'   => ['nullable', ValidateCommandsRule::class],
            'by_role.*.path'       => ['nullable', ValidPathRule::class],
            'by_role.*.commands'   => ['nullable', ValidateCommandsRule::class],
            'guest.path'           => ['sanitize:text', 'nullable','string', ValidPathRule::class],
            'guest.can_download'   => ['sanitize:text', 'nullable', 'boolean'],
        ];
    }
}
