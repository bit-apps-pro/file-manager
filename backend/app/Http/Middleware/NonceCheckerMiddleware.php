<?php

namespace BitApps\FM\Http\Middleware;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


use BitApps\FM\Config;
use BitApps\FM\Vendor\BitApps\WPKit\Http\Request\Request;

final class NonceCheckerMiddleware
{
    public function handle(Request $request, $role, ...$params)
    {
        $nonceKey = $role === 'admin' ? 'wp_rest' : Config::withPrefix('public_nonce');
        if (
            ! $request->has('nonce')
            || !(
                $request->has('nonce')
                && wp_verify_nonce(sanitize_key($request->nonce), $nonceKey)
            )
        ) {
            $response = \in_array($request->getRoute()->getPath(), ['connector', 'connector_front'])
            ? ['error' => __('Token expired. please reload the page', 'file-manager')]
                : [
                    'message' => __('Token expired. please reload the page', 'file-manager'),
                    'code'    => 'INVALID_NONCE',
                    'status'  => 'error',
                ];

            echo wp_json_encode($response);
            wp_die();
        }

        return true;
    }
}
