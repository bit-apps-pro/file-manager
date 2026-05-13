<?php

namespace BitApps\FM\Http\Middleware;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


use BitApps\FM\Vendor\BitApps\WPKit\Http\Request\Request;
use BitApps\FM\Vendor\BitApps\WPKit\Utils\Capabilities;

final class CapCheckerMiddleware
{
    public function handle(Request $request, $cap)
    {
        if (!$cap || !Capabilities::filter($cap)) {
            echo wp_json_encode(
                [
                    'message' => __('You are not authorized to access this endpoint', 'file-manager'),
                    'code'    => 'NOT_AUTHORIZED',
                    'status'  => 'error',
                ]
            );
            wp_die();
        }

        return true;
    }
}
