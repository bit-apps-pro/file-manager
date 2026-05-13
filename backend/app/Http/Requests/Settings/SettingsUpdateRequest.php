<?php

namespace BitApps\FM\Http\Requests\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


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
            'show_url_path'               => ['sanitize:text', 'nullable','boolean'],
            'show_hidden_files'           => ['sanitize:text', 'nullable','boolean'],
            'wp_media_sync'               => ['sanitize:text', 'nullable','boolean'],
            'create_trash_files_folders'  => ['sanitize:text', 'nullable','boolean'],
            'create_hidden_files_folders' => ['sanitize:text', 'nullable','boolean'],
            'remember_last_dir'           => ['sanitize:text', 'nullable','boolean'],
            'clear_history_on_reload'     => ['sanitize:text', 'nullable','boolean'],
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
