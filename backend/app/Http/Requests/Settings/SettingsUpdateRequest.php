<?php

namespace BitApps\FM\Http\Requests\Settings;

if (! \defined('ABSPATH')) {
    exit;
}

use BitApps\FM\Http\Rules\BooleanRule;
use BitApps\FM\Http\Rules\ValidPathRule;
use BitApps\FM\Http\Rules\ValidUIOptionRule;
use BitApps\FM\Vendor\BitApps\WPKit\Http\Request\Request;
use BitApps\FM\Vendor\BitApps\WPKit\Utils\Capabilities;

class SettingsUpdateRequest extends Request
{
    public function authorize()
    {
        return Capabilities::filter('bitapps_fm_can_change_settings', 'install_plugins');
    }

    public function rules()
    {
        return [
            'show_url_path'               => ['nullable', BooleanRule::class],
            'show_hidden_files'           => ['nullable', BooleanRule::class],
            'wp_media_sync'               => ['nullable', BooleanRule::class],
            'create_trash_files_folders'  => ['nullable', BooleanRule::class],
            'create_hidden_files_folders' => ['nullable', BooleanRule::class],
            'remember_last_dir'           => ['nullable', BooleanRule::class],
            'clear_history_on_reload'     => ['nullable', BooleanRule::class],
            'root_folder_name'            => ['sanitize:text', 'required','string'],
            'theme'                       => ['sanitize:text', 'required','string'],
            'language'                    => ['sanitize:text', 'required','string'],
            'default_view_type'           => ['sanitize:text', 'required','string'],
            'root_folder_path'            => ['sanitize:text', 'required','string', ValidPathRule::class],
            'root_folder_url'             => ['sanitize:text', 'required','string', 'url'],
            'size.width'                  => ['sanitize:text', 'required','string'],
            'size.height'                 => ['sanitize:text', 'required'],
            'display_ui_options'          => ['required','array', ValidUIOptionRule::class],
        ];
    }
}
