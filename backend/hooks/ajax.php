<?php

if (!\defined('ABSPATH')) {
    exit;
}

use BitApps\FM\Http\Controllers\FileManagerController;
use BitApps\FM\Vendor\BitApps\WPKit\Http\Router\Route;

Route::match(['get', 'post'], 'connector', [FileManagerController::class, 'connector'])
    ->middleware('nonce:admin', 'frontend_access');

Route::noAuth()
    ->match(['get', 'post'], 'connector_front', [FileManagerController::class, 'connector'])
    ->middleware('nonce:public', 'frontend_access');
