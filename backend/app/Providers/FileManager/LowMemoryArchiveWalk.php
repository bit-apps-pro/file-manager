<?php

namespace BitApps\FM\Providers\FileManager;

\defined('ABSPATH') || exit();

/**
 * Keeps elFinder archive builds (zipdl, archive) within a low memory budget; use only in elFinderVolumeDriver subclasses.
 */
trait LowMemoryArchiveWalk
{
    /**
     * Copies items for archiving using extension-based mime and per-directory stat-cache eviction.
     *
     * @param array       $hashes
     * @param null|string $dir
     * @param null|bool   $canLink
     *
     * @return false|string
     */
    protected function getItemsInHand($hashes, $dir = null, $canLink = null)
    {
        if ($dir !== null) {
            // Recursive calls receive the children of a single directory; drop them once their subtrees are copied.
            try {
                return parent::getItemsInHand($hashes, $dir, $canLink);
            } finally {
                $this->evictChildren($this->dirnameCE($this->decode(reset($hashes))));
            }
        }

        // Archiving only needs to tell directories from files, so content sniffing per file is wasted I/O.
        $mimeDetect       = $this->mimeDetect;
        $this->mimeDetect = 'internal';

        try {
            return parent::getItemsInHand($hashes, $dir, $canLink);
        } finally {
            $this->mimeDetect = $mimeDetect;
            // Drop every stat taken with extension-based mime so none outlives the walk.
            $this->clearcache();
        }
    }

    /**
     * Drops cached stats and listings of a directory's direct children, hidden items included.
     *
     * @param string $dirPath
     */
    private function evictChildren($dirPath)
    {
        if ($dirPath === '') {
            return;
        }

        foreach ($this->scandirCE($dirPath) as $path) {
            unset($this->cache[$path], $this->dirsCache[$path]);
        }
    }
}
