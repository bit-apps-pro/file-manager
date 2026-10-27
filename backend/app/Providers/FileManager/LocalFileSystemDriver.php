<?php

namespace BitApps\FM\Providers\FileManager;

use BitApps\FM\Config;
use elFinderVolumeLocalFileSystem;

\defined('ABSPATH') || exit();

/**
 * Local volume driver that keeps archive builds (zipdl, archive) within a low memory budget.
 */
class LocalFileSystemDriver extends elFinderVolumeLocalFileSystem
{
    use LowMemoryArchiveWalk;

    /**
     * Driver name elFinder resolves to the "elFinderVolume{DRIVER}" class.
     */
    public const DRIVER = Config::DRIVER_PREFIX . 'LocalFileSystem';

    /**
     * Exposes this class to elFinder's driver lookup.
     */
    public static function register()
    {
        $alias = 'elFinderVolume' . self::DRIVER;
        if (!class_exists($alias, false)) {
            class_alias(self::class, $alias);
        }
    }
}
