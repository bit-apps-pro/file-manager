<?php

namespace BitApps\FM\Providers;

use BitApps\FM\Config;

\defined('ABSPATH') or exit();
class MimeProvider
{
    private $_mimePath;

    public function __construct($mimePath = null)
    {
        if ($mimePath) {
            $this->_mimePath = $mimePath;
        } else {
            $this->_mimePath = Config::getFinderDirectory() . DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . 'mime.types';
        }
    }

    public function getTypes()
    {
        $mimeList = [];
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
        $fp       = fopen($this->_mimePath, 'r');
        if ($fp) {
            while (($line = fgets($fp)) !== false) {
                if (strpos($line, '#') === 0) {
                    continue;
                }

                $singleMime = explode('/', $line);
                $mimeType   = trim($singleMime[0]);
                if (!\in_array($mimeType, $mimeList)) {
                    $mimeList[] = $mimeType;
                }
            }
        }

        return $mimeList;
    }
}
