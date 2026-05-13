<?php

namespace BitApps\FM\Compatibility;

\defined('ABSPATH') || exit();

class Deprecated
{
    public static function init()
    {
        require_once __DIR__ . '/deprecated-functions.php';
    }
}
