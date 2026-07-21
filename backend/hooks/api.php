<?php

if (!\defined('ABSPATH')) {
    exit;
}

use BitApps\FM\Http\Controllers\LogController;
use BitApps\FM\Http\Controllers\PermissionsController;
use BitApps\FM\Http\Controllers\SettingsController;
use BitApps\FM\Http\Controllers\TelemetryPopupController;
use BitApps\FM\Vendor\BitApps\WPKit\Http\Router\Route;

Route::group(
    function () {
        Route::post('theme/update', [SettingsController::class, 'updateTheme']);

        Route::get('language/get', [SettingsController::class, 'getLanguages'])->middleware('cap:can_access_fm_home');
        Route::post('language/update', [SettingsController::class, 'updateLanguage']);

        Route::post('logs/all', [LogController::class, 'all'])->middleware('cap:bitapps_fm_can_access_logs');
        Route::post('logs/delete', [LogController::class, 'delete']);

        Route::get('settings/get', [SettingsController::class, 'get']);
        Route::post('settings/update', [SettingsController::class, 'update']);
        Route::post('settings/toggle-view', [SettingsController::class, 'toggleView']);

        Route::get('permissions/get', [PermissionsController::class, 'get']);
        Route::post('permissions/update', [PermissionsController::class, 'update']);
        Route::get('permissions/user/get', [PermissionsController::class, 'searchUser']);
        Route::post('permissions/user/add', [PermissionsController::class, 'addPermissionByUser']);
        Route::post('permissions/user/delete', [PermissionsController::class, 'deletePermissionByUser']);

        Route::post('telemetry/tryplugin', [TelemetryPopupController::class, 'tryPlugin']);
        Route::post('telemetry_permission_handle', [TelemetryPopupController::class, 'handleTelemetryPermission'])->middleware('cap:can_access_fm_home');
        Route::get('telemetry_popup_disable_check', [TelemetryPopupController::class, 'isPopupDisabled'])->middleware('cap:can_access_fm_home');
    }
);
