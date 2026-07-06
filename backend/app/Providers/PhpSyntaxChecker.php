<?php

namespace BitApps\FM\Providers;

use WP_Error;

\defined('ABSPATH') or exit();

/**
 * Performs PHP syntax validation via loopback HTTP requests.
 *
 * Logic derived from {@see wp_edit_theme_plugin_file()} in wp-admin/includes/file.php.
 */
class PhpSyntaxChecker
{
    /**
     * Write content to the real file, perform a loopback request to detect
     * fatal errors, then roll back the file if a problem is found.
     *
     * @param mixed $content
     * @param mixed $realFile
     *
     * @return WP_Error|null null on success, WP_Error on failure
     */
    public function check($content, $realFile)
    {
        $previousContent = file_get_contents($realFile);

        // phpcs:disable WordPress.WP.AlternativeFunctions.file_system_operations_fopen, WordPress.WP.AlternativeFunctions.file_system_operations_fwrite, WordPress.WP.AlternativeFunctions.file_system_operations_fclose, WordPress.WP.AlternativeFunctions.file_system_operations_is_writable
        if (!is_writable($realFile)) {
            return new WP_Error('file_not_writable');
        }

        $f = fopen($realFile, 'w+');

        if (false === $f) {
            return new WP_Error('file_not_writable');
        }

        $written = fwrite($f, $content);
        fclose($f);

        if (false === $written) {
            return new WP_Error('unable_to_write', __('Unable to write to file.', 'file-manager'));
        }

        wp_opcache_invalidate($realFile, true);

        $result = $this->loopbackRequest();

        if (true !== $result) {
            file_put_contents($realFile, $previousContent);
            wp_opcache_invalidate($realFile, true);

            $message = isset($result['message'])
                ? $result['message']
                : __('An error occurred. Please try again later.', 'file-manager');

            $data = $result;
            unset($data['message']);

            return new WP_Error('php_error', $message, $data);
        }
    }

    /**
     * Perform loopback requests with a scrape key to detect fatal PHP errors.
     *
     * @return true|array true on success, or an array with 'code'/'message' on failure
     */
    private function loopbackRequest()
    {
        $scrapeKey   = md5(wp_rand());
        // The transient key, query parameter names and needle markers below are
        // dictated by WordPress core's scrape handshake — see
        // wp_start_scraping_edited_file_errors() and wp_finalize_scraping_edited_file_errors()
        // in wp-includes/load.php. Renaming any of these breaks the handshake:
        // core would never validate our nonce and the fatal-error check would
        // silently fail. These names are part of a core API contract we invoke,
        // not identifiers owned by this plugin.
        $transient   = 'scrape_key_' . $scrapeKey;
        $scrapeNonce = (string) wp_rand();
        // Transient expires after 60 seconds — enough for both loopback requests.
        set_transient($transient, $scrapeNonce, 60);

        $cookies      = wp_unslash($_COOKIE);
        $scrapeParams = [
            'wp_scrape_key'   => $scrapeKey,
            'wp_scrape_nonce' => $scrapeNonce,
        ];
        $headers = [
            'Cache-Control' => 'no-cache',
        ];

        /** This filter is documented in wp-includes/class-wp-http-streams.php */
        $sslverify = apply_filters('https_local_ssl_verify', false);

        // Include Basic auth in loopback requests.
        if (isset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
            $headers['Authorization'] = 'Basic ' . base64_encode(
                // phpcs:disable WordPress.Security.ValidatedSanitizedInput -- We need to sanitize the username, but the password should be left as-is.
                sanitize_user(wp_unslash($_SERVER['PHP_AUTH_USER'])) . ':' . $_SERVER['PHP_AUTH_PW']
            );
        }

        // Make sure PHP process doesn't die before loopback requests complete.
        if (\function_exists('set_time_limit')) {
            set_time_limit(5 * MINUTE_IN_SECONDS);
        }

        // Close any active session to prevent HTTP requests from timing out.
        if (\function_exists('session_status') && PHP_SESSION_ACTIVE === session_status()) {
            session_write_close();
        }

        $timeout     = 100;
        $needleStart = "###### wp_scraping_result_start:{$scrapeKey} ######";
        $needleEnd   = "###### wp_scraping_result_end:{$scrapeKey} ######";

        $loopbackFailure = [
            'code'    => 'loopback_request_failed',
            'message' => __('Unable to communicate back with site to check for fatal errors, so the PHP change was reverted. You will need to upload your PHP file change by some other means, such as by using SFTP.', 'file-manager'),
        ];
        $jsonParseFailure = [
            'code' => 'json_parse_error',
        ];

        // Use plugin-editor.php as the loopback target — identical to what WordPress core
        // uses when saving plugin files (wp-admin/includes/file.php). The scrape mechanism
        // fires from wp-settings.php before any page-specific code, so the URL doesn't
        // affect which PHP errors are caught. Using plugin-editor.php avoids 403 responses
        // that some server configurations return for requests to the generic admin_url().
        $url    = add_query_arg($scrapeParams, admin_url('plugin-editor.php'));
        $r      = wp_remote_get($url, compact('cookies', 'headers', 'timeout', 'sslverify'));
        $body   = wp_remote_retrieve_body($r);
        $pos    = strpos($body, $needleStart);
        $result = null;

        if (false === $pos) {
            $result = $loopbackFailure;
        } else {
            $errorOutput = substr($body, $pos + \strlen($needleStart));
            $errorOutput = substr($errorOutput, 0, strpos($errorOutput, $needleEnd));
            $result      = json_decode(trim($errorOutput), true);
            if (empty($result)) {
                $result = $jsonParseFailure;
            }
        }

        // Also check homepage to ensure visitors aren't whitescreen'd.
        if (true === $result) {
            $url    = add_query_arg($scrapeParams, home_url('/'));
            $r      = wp_remote_get($url, compact('cookies', 'headers', 'timeout', 'sslverify'));
            $body   = wp_remote_retrieve_body($r);
            $pos    = strpos($body, $needleStart);

            if (false === $pos) {
                $result = $loopbackFailure;
            } else {
                $errorOutput = substr($body, $pos + \strlen($needleStart));
                $errorOutput = substr($errorOutput, 0, strpos($errorOutput, $needleEnd));
                $result      = json_decode(trim($errorOutput), true);
                if (empty($result)) {
                    $result = $jsonParseFailure;
                }
            }
        }

        delete_transient($transient);

        return $result;
    }
}
