<?php

namespace BitApps\FM\Providers;

if (! \defined('ABSPATH')) {
    exit;
}

use BitApps\FM\Config;
use BitApps\FM\Http\Controllers\TelemetryPopupController;
use BitApps\FM\Plugin;
use BitApps\FM\Vendor\BitApps\WPKit\Hooks\Hooks;
use BitApps\FM\Vendor\BitApps\WPKit\Http\RequestType;
use BitApps\FM\Vendor\BitApps\WPKit\Http\Router\Router;

class HookProvider
{
    private $_pluginBackend;

    public function __construct()
    {
        $this->_pluginBackend = Config::get('BACKEND_DIR') . DIRECTORY_SEPARATOR;
        $this->loadAppHooks();
        Hooks::addAction('rest_api_init', [$this, 'loadApi']);
        Hooks::addFilter(Config::VAR_PREFIX . 'telemetry_additional_data',[new TelemetryPopupController(),'filterTrackingData']);
    }

    /**
     * Loads API routes.
     */
    public function loadApi()
    {
        if (
            is_readable($this->_pluginBackend . 'hooks' . DIRECTORY_SEPARATOR . 'api.php')
            && RequestType::is(RequestType::API)
        ) {
            $router = new Router(RequestType::API, Config::SLUG, 'v1');

            include_once $this->_pluginBackend . 'hooks' . DIRECTORY_SEPARATOR . 'api.php';

            $router->register();
        }
    }

    /**
     * Helps to register App hooks.
     */
    protected function loadAppHooks()
    {
        if (
            RequestType::is(RequestType::AJAX)
            && is_readable($this->_pluginBackend . 'hooks' . DIRECTORY_SEPARATOR . 'ajax.php')
        ) {
            $router = new Router(RequestType::AJAX, Config::VAR_PREFIX, '');
            $router->setMiddlewares(Plugin::instance()->middlewares());
            include_once $this->_pluginBackend . 'hooks' . DIRECTORY_SEPARATOR . 'ajax.php';
            $router->register();
        }

        if (is_readable($this->_pluginBackend . 'hooks.php')) {
            include_once $this->_pluginBackend . 'hooks.php';
        }
    }
}
