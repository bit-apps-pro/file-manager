<?php

namespace BitApps\FM\Providers\FileManager;

use elFinder;
use elFinderConnector;

\defined('ABSPATH') || exit();
class FinderConnector extends elFinderConnector
{
    /**
     * Output json
     * This function is overridden. Some servers disable `fpassthru` function
     *  which is used in this function
     *
     * @param  array  data to output
     * @param mixed $cmd
     *
     * @throws elFinderAbortException
     *
     * @return void
     *
     * @author Dmitry (dio) Levashov
     */
    /**
     * WordPress nonce verification (NonceCheckerMiddleware) already provides CSRF
     * protection for every connector request. elFinder's session-based CSRF check
     * is redundant here and breaks when the server strips the Cookie header on
     * write requests (causing a 403 before our code runs). Disabling it removes
     * the session dependency without weakening security.
     */
    protected function csrfProtectedCommand($cmd)
    {
        return false;
    }

    protected function output(array $data)
    {
        // unlock session data for multiple access
        $this->elFinder->getSession()->close();
        // client disconnect should abort
        ignore_user_abort(false);

        if (!empty($data['header'])) {
            self::sendHeader($data['header']);
        }

        if (isset($data['pointer'])) {
            // set time limit to 0
            elFinder::extendTimeLimit(0);

            // send optional header
            if (!empty($data['header'])) {
                self::sendHeader($data['header']);
            }

            // clear output buffer
            while (ob_get_level() && ob_end_clean());

            $toEnd    = true;
            $fp       = $data['pointer'];
            $sendData = !($this->reqMethod === 'HEAD' || !empty($data['info']['xsendfile']));
            $psize    = null;
            if (($this->reqMethod === 'GET' || !$sendData)
                && (elFinder::isSeekableStream($fp) || elFinder::isSeekableUrl($fp))
                && (array_search('Accept-Ranges: none', headers_list()) === false)) {
                header('Accept-Ranges: bytes');
                if (!empty($_SERVER['HTTP_RANGE'])) {
                    $size = $data['info']['size'];
                    $end  = $size - 1;
                    if (preg_match('/bytes=(\d*)-(\d*)(,?)/i', sanitize_text_field(wp_unslash($_SERVER['HTTP_RANGE'])), $matches)) {
                        if (empty($matches[3])) {
                            if (empty($matches[1]) && $matches[1] !== '0') {
                                $start = $size - $matches[2];
                            } else {
                                $start = \intval($matches[1]);
                                if (!empty($matches[2])) {
                                    $end = \intval($matches[2]);
                                    if ($end >= $size) {
                                        $end = $size - 1;
                                    }
                                    $toEnd = ($end == ($size - 1));
                                }
                            }
                            $psize = $end - $start + 1;

                            header('HTTP/1.1 206 Partial Content');
                            header('Content-Length: ' . $psize);
                            header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);

                            // Apache mod_xsendfile dose not support range request
                            if (isset($data['info']['xsendfile']) && strtolower($data['info']['xsendfile']) === 'x-sendfile') {
                                if (\function_exists('header_remove')) {
                                    header_remove($data['info']['xsendfile']);
                                } else {
                                    header($data['info']['xsendfile'] . ':');
                                }
                                unset($data['info']['xsendfile']);
                                if ($this->reqMethod !== 'HEAD') {
                                    $sendData = true;
                                }
                            }

                            $sendData && !elFinder::isSeekableUrl($fp) && fseek($fp, $start);
                        }
                    }
                }
                if ($sendData && \is_null($psize)) {
                    elFinder::rewind($fp);
                }
            } else {
                header('Accept-Ranges: none');
                if (isset($data['info']) && !$data['info']['size']) {
                    if (\function_exists('header_remove')) {
                        header_remove('Content-Length');
                    } else {
                        header('Content-Length:');
                    }
                }
            }

            if ($sendData) {
                if (\function_exists('fpassthru') && ($toEnd || elFinder::isSeekableUrl($fp))) {
                    // PHP < 5.6 has a bug of fpassthru
                    // see https://bugs.php.net/bug.php?id=66736
                    if (version_compare(PHP_VERSION, '5.6', '<')) {
                        file_put_contents('php://output', $fp);
                    } else {
                        fpassthru($fp);
                    }
                } else {
                    $out = fopen('php://output', 'wb');
                    stream_copy_to_stream($fp, $out, $psize);
                    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Cannot use fclose here because $fp may be used in the next request if the connection is keep-alive.
                    fclose($out);
                }
            }

            if (!empty($data['volume'])) {
                $data['volume']->close($fp, $data['info']['hash']);
            } else {
                // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
                fclose($fp);
            }
            exit();
        }
        self::outputJson($data);
        exit(0);
    }
}
