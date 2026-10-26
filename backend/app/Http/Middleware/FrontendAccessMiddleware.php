<?php

namespace BitApps\FM\Http\Middleware;

if (! \defined('ABSPATH')) {
    exit;
}

use BitApps\FM\Plugin;
use BitApps\FM\Vendor\BitApps\WPKit\Http\Request\Request;

final class FrontendAccessMiddleware
{
    /**
     * Ends any non-admin-area connector request when the can_access_frontend filter denies the user.
     */
    public function handle(Request $request)
    {
        $permissions = Plugin::instance()->permissions();
        if ($permissions->isRequestForAdminArea() || $permissions->canAccessFrontend()) {
            return true;
        }

        // phpcs:ignore -- elFinder escapes error strings client-side.
        echo wp_json_encode(['error' => $permissions->frontendAccessDeniedMessage()]);
        wp_die();
    }
}
