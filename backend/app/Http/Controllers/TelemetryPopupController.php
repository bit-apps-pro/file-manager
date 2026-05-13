<?php

namespace BitApps\FM\Http\Controllers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


use Automatic_Upgrader_Skin;
use BitApps\FM\Config;
use BitApps\FM\Http\Requests\TryPluginRequest;
use BitApps\FM\Plugin;
use BitApps\FM\Vendor\BitApps\WPKit\Http\Request\Request;
use BitApps\FM\Vendor\BitApps\WPTelemetry\Telemetry\Telemetry;
use Plugin_Upgrader;
use Throwable;

class TelemetryPopupController
{
    public function filterTrackingData($additional_data)
    {
        return $additional_data;
    }

    public function handleTelemetryPermission(Request $request)
    {
        if ($request->isChecked == true) {
            Telemetry::report()->trackingOptIn();
        } else {
            Telemetry::report()->trackingOptOut();
        }

        update_option(Config::VAR_PREFIX . 'old_version', Config::VERSION);
    }

    public function isPopupDisabled()
    {
        $allowed = Telemetry::report()->isTrackingAllowed();
        if ($allowed == true) {
            return true;
        }

        $popupSkipped             = Config::getOption('tracking_skipped');
        $adminNoticeSkipped       = Config::getOption('tracking_notice_dismissed');

        return $popupSkipped || $adminNoticeSkipped;
    }

    public function tryPlugin(TryPluginRequest $request)
    {
        ignore_user_abort(true);
        $this->maybeInstallPlugins($request->tryPlugin);
    }

    private function maybeInstallPlugins($plugins)
    {
        if (!\is_array($plugins) || empty($plugins)) {
            return;
        }

        $autoInstalled = [];
        foreach ($plugins as $pluginSlug => $isAccepted) {
            $pluginSlug = sanitize_text_field($pluginSlug);
            if ($isAccepted && $this->installPlugin($pluginSlug)) {
                $autoInstalled[] = $pluginSlug;
            }
        }

        $telemetryReport                   = Plugin::instance()->telemetryReport();
        if ($telemetryReport->isTrackingAllowed() && \count($autoInstalled)) {
            $trackingData                   = $telemetryReport->getTrackingData();
            $trackingData['auto-installed'] = $autoInstalled;
            Telemetry::sendReport('plugin-track-create', $trackingData);
        }
    }

    private function installPlugin($slug)
    {
        include_once ABSPATH . 'wp-admin/includes/file.php';
        include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        include_once ABSPATH . 'wp-admin/includes/class-automatic-upgrader-skin.php';
        include_once ABSPATH . 'wp-admin/includes/plugin.php';
        if (!\function_exists('plugins_api')) {
            include_once ABSPATH . 'wp-admin/includes/plugin-install.php';
        }

        ignore_user_abort(true);
        $pluginInfo = plugins_api(
            'plugin_information',
            [
                'slug'   => wp_unslash($slug),
                'fields' => ['short_description' => false,'description' => false,'sections' => false,'contributors' => false, 'ratings' => false,'screenshots' => false,'tags' => false,'versions' => false]
            ]
        );

        if (is_wp_error($pluginInfo)) {
            return $pluginInfo;
        }

        $upgrader = new Plugin_Upgrader(new Automatic_Upgrader_Skin());

        $status = true;

        try {
            $installStatus = $upgrader->install($pluginInfo->download_link);

            if (is_wp_error($installStatus)) {
                $status = false;
            }

            if ($installStatus === true) {
                $activationStatus = activate_plugin($upgrader->plugin_info(), '', false, true);

                if (is_wp_error($activationStatus)) {
                    $status = false;
                }

                $status = $activationStatus === null;
            }

            return $installStatus;
        } catch (Throwable $th) {
            $status = false;
        }

        return $status;
    }
}
