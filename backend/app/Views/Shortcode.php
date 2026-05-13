<?php

// phpcs:disable Squiz.NamingConventions.ValidVariableName.NotCamelCaps

namespace BitApps\FM\Views;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


use BitApps\FM\Config;

use function BitApps\FM\Functions\view;

use BitApps\FM\Plugin;

use BitApps\FM\Vendor\BitApps\WPKit\Hooks\Hooks;

use BitApps\FM\Vendor\BitApps\WPKit\Shortcode\Shortcode as SWrapper;

/**
 * The admin Layout and page handler class.
 */
class Shortcode
{
    public function __construct()
    {
        Hooks::addAction('wp_enqueue_scripts', [$this, 'registerAssets']);
        SWrapper::addShortcode('file-manager', [$this, 'shortCodeView']);
        Hooks::addFilter(Config::withPrefix('localized_script'), [$this, 'filterConfigVariable']);
    }

    public function filterConfigVariable($config)
    {
        $options = Plugin::instance()->preferences()->finderOptions();
        if (\count($options['commands'])) {
            $options['commands'][] = 'sort';
            $options['commands'][] = 'fullscreen';
            $options['commands'][] = 'view';
            $options['commands'][] = 'search';
        }

        return (array) $config + [
            'action'  => Config::withPrefix('connector_front'),
            'nonce'   => wp_create_nonce(Config::withPrefix('public_nonce')),
            'options' => $options,
        ];
    }

    public function registerAssets()
    {
        Plugin::instance()->registerAssets();
    }

    public function shortCodeView()
    {
        ob_start();
        view('finder');

        return ob_get_clean();
    }
}
