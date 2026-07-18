<?php

namespace BitApps\FM\Http\Controllers;

if (! \defined('ABSPATH')) {
    exit;
}

use BitApps\FM\Config;
use BitApps\FM\Exception\PreCommandException;

use function BitApps\FM\Functions\fileSystemAdapter;

use BitApps\FM\Plugin;
use BitApps\FM\Providers\FileManager\FileManagerProvider;
use BitApps\FM\Providers\FileManager\FileRoot;
use BitApps\FM\Providers\FileManager\Options;
use BitApps\FM\Vendor\BitApps\WPKit\Utils\Capabilities;

use Exception;

final class FileManagerController
{
    /**
     * File Manager connector function
     *
     * @throws Exception
     */
    public function connector()
    {
        try {
            $command = isset($_REQUEST['cmd']) && \is_scalar($_REQUEST['cmd'])
                ? sanitize_key((string) $_REQUEST['cmd'])
                : '';

            $accessControl = Plugin::instance()->accessControl();
            if ($accessControl->isConnectorEnforceable($command)) {
                $permissionResult = $accessControl->checkPermission($command);
                if (\is_array($permissionResult) && !empty($permissionResult['preventexec'])) {
                    echo wp_json_encode($permissionResult['results']);
                    wp_die();
                }
            }

            $finderProvider = new FileManagerProvider($this->getFinderOptions());
            $finderProvider->getFinder()->run();
        } catch (Exception $th) {
            // phpcs:ignore
            echo wp_json_encode(['error' => $th->getMessage()]);
        }

        wp_die();
    }

    public function getFinderOptions()
    {
        $finderOptions = new Options(Config::isDev() ?? false);

        $finderOptions->setBind(
            'put.pre',
            [
                Plugin::instance()->fileEditValidator(),
                'validate',
            ]
        );

        // Bind the gate to the `*.pre` wildcard, not an explicit command list: elFinder
        // registers a `<cmd>.pre` handler only when the command arrives via $_POST, yet it
        // dispatches from the merged $_GET+$_POST (plus a php://input re-parse past
        // max_input_vars). A command sent only via query string / oversized body would skip
        // registration but still execute, bypassing the gate. `*.pre` registers unconditionally.
        $finderOptions->setBind(
            '*.pre',
            [
                Plugin::instance()->accessControl(),
                'checkPermission',
            ]
        );

        $finderOptions->setBind(
            'upload',
            [Plugin::instance()->mediaSyncs(), 'onFileUpload']
        );

        $finderOptions->setBind(
            'zipdl.pre file.pre rename.pre put.pre rm.pre chmod.pre mkdir.pre mkfile.pre extract.pre',
            [Plugin::instance()->logger(), 'log']
        );

        $finderOptions->setBind(
            'upload',
            [Plugin::instance()->logger(), 'logUpload']
        );

        if (fileSystemAdapter()->is_writable(Config::uploadBaseDir() . '/.tmp/')) {
            $finderOptions->setCommonTempPath(Config::uploadBaseDir() . '/.tmp/');
        }

        $allVolumes         = $this->getFileRoots();
        $volumeCount        = \count($allVolumes);
        $invalidVolumeCount = 0;

        $isTrashAllowed = Plugin::instance()->preferences()->isTrashAllowed();
        $trashVolumes   = [];

        foreach ($allVolumes as $root) {
            if (!$root->isReadable()) {
                $invalidVolumeCount++;

                continue;
            }

            if ($isTrashAllowed) {
                // Each volume gets its own trash folder so deletions are isolated.
                // Trash driver uses id 't'; Nth trash volume root hash = "t{N}_Lw"
                // (base64('/') = 'Lw', the encoded root-relative path '/').
                $trashSeq  = \count($trashVolumes) + 1;
                $trashHash = 't' . $trashSeq . '_Lw';
                $trashDir  = Config::getTrashDir() . DIRECTORY_SEPARATOR . $trashSeq;

                $root->setTrashHash($trashHash);
                $trashVolumes[] = $trashDir;
            }

            $finderOptions->setRoot($root);
        }

        if ($volumeCount === $invalidVolumeCount) {
            throw new PreCommandException(esc_html__('There is no readable volume. Please select an readable folder from settings', 'file-manager'));
        }

        foreach ($trashVolumes as $trashSeq => $trashDir) {
            if (!is_dir($trashDir)) {
                wp_mkdir_p($trashDir);
            }

            if (is_dir($trashDir)) {
                $trashRoot = new FileRoot($trashDir, '', 'Trash', 'Trash');
                // Explicitly set id so the volume hash is predictable: t{trashSeq}_Lw
                $trashRoot->setOption('id', $trashSeq + 1);
                $finderOptions->setRoot($trashRoot);
            }
        }

        return $finderOptions;
    }

    public function getFileRoots()
    {
        if (!is_user_logged_in()) {
            return $this->guestVolume();
        } elseif (is_user_logged_in() && Plugin::instance()->permissions()->isRequestForAdminArea() && Plugin::instance()->permissions()->isDisabledForAdmin()) {
            return $this->getDashboardVolumes();
        }

        return $this->getUserVolumes();
    }

    public function getUrlByPath($path)
    {
        return set_url_scheme(home_url(str_replace(ABSPATH, '', trailingslashit($path))), is_ssl() ? 'https' : 'http');
    }

    /**
     * Sets allowed mimetype for a volume/root
     *
     * @return void
     */
    public function setAllowedFileType(FileRoot $volume)
    {
        $permissions           = Plugin::instance()->permissions();
        $mimes                 = $permissions->getEnabledFileType();
        $maxUploadSize         = $permissions->getMaximumUploadSize();
        $volume->setUploadMaxSize($maxUploadSize == 0 ? 0 : $maxUploadSize . 'M');
        $denyUploadType     = array_diff(Plugin::instance()->mimes()->getTypes(), $mimes);
        $isTextLikeEnabled  = false; // is text like php,javascript, css is enabled or exists in $mimes then true else false

        if (!\in_array('php', $mimes)) {
            $denyUploadType[] = 'text/x-php';
        } else {
            $mimes[]           = 'text/x-php';
            $isTextLikeEnabled = true;
        }

        if (!\in_array('javascript', $mimes)) {
            $denyUploadType[] = 'text/javascript';
        } else {
            $mimes[]           = 'text/javascript';
            $isTextLikeEnabled = true;
        }

        if (!\in_array('css', $mimes)) {
            $denyUploadType[] = 'text/css';
        } else {
            $mimes[]           = 'text/css';
            $isTextLikeEnabled = true;
        }

        $allowedMimes = array_diff($mimes, $denyUploadType);

        if ($isTextLikeEnabled && !\in_array('text', $allowedMimes)) {
            $allowedMimes[] = 'text';
            $denyUploadType = array_diff($denyUploadType, ['text']);
        }

        $volume->setUploadOrder(['allow', 'deny']);
        $volume->setOption('uploadDeny', $denyUploadType);

        $volume->setUploadAllow($allowedMimes);
    }

    private function getDashboardVolumes()
    {
        $mimes                 = Plugin::instance()->mimes()->getTypes();
        $preferences           = Plugin::instance()->preferences();
        $accessControlProvider = Plugin::instance()->accessControl();
        $permissions           = Plugin::instance()->permissions();

        $baseRoot = new FileRoot(
            $preferences->getRootPath(),
            $preferences->getRootUrl(),
            $preferences->getRootVolumeName()
        );

        $baseRoot->setUploadAllow($mimes);

        if ($permissions->currentUserRole() !== 'administrator') {
            $this->setAllowedFileType($baseRoot);
        }

        $baseRoot->setAccessControl([$accessControlProvider, 'control']);
        $baseRoot->setAcceptedName([$accessControlProvider, 'validateName']);
        $baseRoot->setDisabled([]);
        $baseRoot->setWinHashFix(DIRECTORY_SEPARATOR !== '/');

        if (Capabilities::filter(Config::VAR_PREFIX . 'user_can_manage_options')) {
            $baseRoot->setAllowChmodReadOnly(true);
            $baseRoot->setStatOwner(true);
            $baseRoot->setUploadMaxSize(0);
        }

        $roots[] = $baseRoot;

        return $roots;
    }

    private function getUserVolumes()
    {
        $permissions = Plugin::instance()->permissions();

        $role             = $permissions->currentUserRole();
        $permissionByRole = $permissions->getByRole($role);
        $permissionByUser = $permissions->getByUser($permissions->currentUserID());
        $publicPath       = $permissions->getPathByFolderOption();

        $roots[] = $this->processFileRoot(
            $publicPath,
            'Public',
            $this->getUrlByPath($publicPath),
            $permissions->getPublicVolumeDisabledCommands()
        );

        $roots[] = $this->processFileRoot(
            $permissionByRole['path'],
            $role,
            $this->getUrlByPath($permissionByRole['path']),
            $permissions->getRoleVolumeDisabledCommands()
        );

        $roots[] = $this->processFileRoot(
            $permissionByUser['path'],
            $permissions->currentUser()->display_name,
            $this->getUrlByPath($permissionByUser['path']),
            $permissions->getUserVolumeDisabledCommands()
        );

        return $roots;
    }

    private function guestVolume()
    {
        $permissions = Plugin::instance()->permissions();

        $guestPermission = $permissions->getGuestPermissions();

        $root = new FileRoot(
            $guestPermission['path'],
            $this->getUrlByPath($guestPermission['path']),
            \array_key_exists('alias', $guestPermission)
                ? $guestPermission['alias'] : basename($guestPermission['path'])
        );

        $root->setDisabled(array_diff($permissions->allCommands(), $guestPermission['commands']));

        return [$root];
    }

    /**
     * Create Instance of FileRoot
     *
     * @param string     $path
     * @param string     $alias
     * @param string     $url
     * @param array|null $disabledCommands Per-volume disabled-command hint; falls back to global when null.
     *
     * @return FileRoot
     */
    private function processFileRoot($path, $alias, $url, ?array $disabledCommands = null)
    {
        $permissions           = Plugin::instance()->permissions();
        $accessControlProvider = Plugin::instance()->accessControl();

        $volume = new FileRoot(
            $path,
            Plugin::instance()->permissions()->currentUserCanRun('download') ? $url : '', // If a URL is provided, the file will be downloaded regardless of whether the download feature is disabled or not.
            $alias
        );
        $this->setAllowedFileType($volume);
        $volume->setAccessControl([$accessControlProvider, 'control']);
        $volume->setAcceptedName([$accessControlProvider, 'validateName']);
        $volume->setDisabled($disabledCommands ?? $permissions->getDisabledCommand());
        $volume->setWinHashFix(DIRECTORY_SEPARATOR !== '/');

        if (Capabilities::filter(Config::VAR_PREFIX . 'user_can_manage_options')) {
            $volume->setAllowChmodReadOnly(true);
            $volume->setStatOwner(true);
            $volume->setUploadMaxSize(0);
        }

        return $volume;
    }
}
