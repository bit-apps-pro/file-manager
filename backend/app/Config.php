<?php

// phpcs:disable Squiz.NamingConventions.ValidVariableName

namespace BitApps\FM;

use DateTimeImmutable;

if (!\defined('ABSPATH')) {
    exit;
}

/**
 * Provides App configurations.
 */
class Config
{
    const SLUG = 'file-manager';

    const TITLE = 'Bit File Manager';

    const VAR_PREFIX = 'bitapps_fm_';

    const VERSION = '6.8.9';

    const VERSION_ID = 689;

    const DB_VERSION = '1.0';

    const REQUIRED_PHP_VERSION = '7.4';

    const REQUIRED_WP_VERSION = '5.0';

    const API_VERSION = '1.0';

    const SUPPORT_URL = 'https://www.bitapps.pro/contact';

    const REVIEW_URL = 'https://wordpress.org/support/plugin/file-manager/reviews/';

    /**
     * Provides configuration for plugin.
     *
     * @param string $type    Type of conf
     * @param string $default Default value
     *
     * @return null|array|string
     */
    public static function get($type, $default = null)
    {
        switch ($type) {
            case 'MAIN_FILE':
                return BITAPPS_FM_MAIN_FILE;

            case 'BASENAME':
                return plugin_basename(trim(self::get('MAIN_FILE')));

            case 'BASEDIR':
                return plugin_dir_path(self::get('MAIN_FILE'));

            case 'BACKEND_DIR':
                return plugin_dir_path(self::get('MAIN_FILE')) . 'backend';

            case 'VIEW_DIR':
                return plugin_dir_path(self::get('MAIN_FILE')) . 'views';

            case 'SITE_URL':
                $parsedUrl = wp_parse_url(get_admin_url());
                $siteUrl   = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
                $siteUrl .= empty($parsedUrl['port']) ? null : ':' . $parsedUrl['port'];

                return $siteUrl;

            case 'ADMIN_URL':
                return str_replace(self::get('SITE_URL'), '', get_admin_url());

            case 'API_URL':
                global $wp_rewrite;

                return [
                    'base'      => get_rest_url() . self::SLUG . '/v1',
                    'separator' => $wp_rewrite->permalink_structure ? '?' : '&',
                ];

            case 'ROOT_URI':
                return plugins_url('', self::get('MAIN_FILE'));

            case 'ASSET_DIR':
                return self::get('BASEDIR') . DIRECTORY_SEPARATOR . 'assets';

            case 'ASSET_URI':
                return self::get('ROOT_URI') . '/assets';

            case 'ASSET_JS_URI':
                return self::get('ASSET_URI') . '/js';

            case 'ASSET_CSS_URI':
                return self::get('ASSET_URI') . '/css';

            case 'PLUGIN_PAGE_LINKS':
                return self::pluginPageLinks();

            default:
                return $default;
        }
    }

    /**
     * Prefixed variable name with prefix.
     *
     * @param string $option Variable name
     *
     * @return string
     */
    public static function withPrefix($option)
    {
        return self::VAR_PREFIX . $option;
    }

    /**
     * Retrieves options from option table.
     *
     * @param string $option  Option name
     * @param bool   $default default value
     * @param bool   $wp      Whether option is default wp option
     *
     * @return mixed
     */
    public static function getOption($option, $default = false, $wp = false)
    {
        if ($wp) {
            return get_option($option, $default);
        }

        return get_option(self::withPrefix($option), $default);
    }

    /**
     * Saves option to option table.
     *
     * @param string $option   Option name
     * @param bool   $autoload Whether option will autoload
     * @param mixed  $value
     *
     * @return bool
     */
    public static function addOption($option, $value, $autoload = false)
    {
        return add_option(self::withPrefix($option), $value, '', $autoload ? 'yes' : 'no');
    }

    /**
     * Save or update option to option table.
     *
     * @param string $option   Option name
     * @param mixed  $value    Option value
     * @param bool   $autoload Whether option will autoload
     *
     * @return bool
     */
    public static function updateOption($option, $value, $autoload = null)
    {
        return update_option(self::withPrefix($option), $value, !\is_null($autoload) ? 'yes' : null);
    }

    public static function isDev()
    {
        return is_readable(Config::get('BASEDIR') . '/port');
    }

    public static function adBanner()
    {
        $hideAT  = new DateTimeImmutable('2024-03-31');
        $current = new DateTimeImmutable();

        $diff = date_diff($current, $hideAT);

        if ($diff->invert) {
            return false;
        }

        return [
            'url' => 'https://bitapps.pro',
            'img' => self::get('ASSET_URI') . '/img/banner.png',
        ];
    }

    public static function tryPlugin()
    {
        if (!current_user_can('install_plugins')) {
            return [];
        }

        $pluginToTry = [
            'bit-form/bitforms.php' => [
                'title'    => 'Try Bit Form: Super Fast, Advanced & Lightweight form builder for WordPress. Create Multi Step Form, Conversational Form, Quiz Form Completely Free.',
                'tutorial' => 'https://www.youtube.com/embed/BUX6-BIPfSA?rel=0&autoplay=1&mute=1&controls=0',
                'slug'     => 'bit-form',
            ]
        ];

        $installed = get_plugins();
        foreach ($pluginToTry as $pluginFile => $value) {
            if (isset($installed[$pluginFile])) {
                unset($pluginToTry[$pluginFile]);
            }
        }

        return $pluginToTry;
    }

    public static function getFinderDirectory()
    {
        return self::get('BASEDIR') . 'vendor/studio-42/elfinder';
    }

    public static function getFinderUrl()
    {
        return self::get('ROOT_URI') . '/vendor/studio-42/elfinder';
    }

    public static function uploadBaseDir()
    {
        $uploadDir = wp_upload_dir();

        return $uploadDir['basedir'] . DIRECTORY_SEPARATOR . 'file-manager';
    }

    public static function uploadBaseURL()
    {
        $uploadDir = wp_upload_dir();

        return $uploadDir['baseurl'] . '/file-manager';
    }

    public static function getTrashDir()
    {
        $uploadDir = wp_upload_dir();

        return $uploadDir['basedir'] . DIRECTORY_SEPARATOR . '.bitapps-fm-trash';
    }

    /**
     * Provides links for plugin pages. Those links will bi displayed in
     * all plugin pages under the plugin name.
     *
     * @return array
     */
    private static function pluginPageLinks()
    {
        return [
            'Support'  => [
                'title' => __('Support', 'file-manager'),
                'url'   => self::get('ADMIN_URL') . 'admin.php?page=file-manager#/support',
            ],
            'Settings' => [
                'title' => __('Settings', 'file-manager'),
                'url'   => self::get('ADMIN_URL') . 'admin.php?page=file-manager#/settings',
            ],
            'Home'     => [
                'title' => __('Home', 'file-manager'),
                'url'   => self::get('ADMIN_URL') . 'admin.php?page=file-manager#/',
            ],
        ];
    }
}
