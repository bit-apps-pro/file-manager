<?php

namespace BitApps\FM\Providers;

use BitApps\FM\Exception\PreCommandException;
use BitApps\FM\Plugin;

use elFinder;
use Exception;

\defined('ABSPATH') || exit();

class AccessControlProvider
{
    /**
     * Navigation/utility commands that read no file content and mutate nothing.
     * The gate binds to `*.pre` (fires for every command), so these are passed
     * explicitly to keep tree/URL browsing working for restricted users.
     */
    public const EXEMPT_COMMANDS = ['open', 'search', 'subdirs', 'url', 'abort', 'callback'];

    /**
     * Commands the connector-level backstop must NOT enforce. `file` (read) is
     * authorized only by the `*.pre` bind: its path-aware `download`-grant check
     * needs the resolved target volume, which does not exist until run() builds
     * elFinder — a connector check that lacks those arguments can only wrongly deny it.
     */
    public const CONNECTOR_UNCHECKED_COMMANDS = ['file'];

    public $settings;

    private $maliciousPatterns = [
        '/<script\b[^>]*>.*?<\/script>/is',
        '/<[^>]+?\s(on\w+)\s*=\s*["\'].*?["\']/is',
        '/<[^>]+?href\s*=\s*["\']\s*javascript:.*?["\']/is',
        '/\/S\s*\/JavaScript\s*\/JS/is',
    ];

    private $scannedResult = [];

    public function __construct()
    {
        $this->settings = Plugin::instance()->preferences();
    }

    /**
     * Per-path elFinder attribute callback: hidden-file policy, then the file_access filter.
     *
     * @param string    $attr    attribute name (read|write|locked|hidden)
     * @param string    $path    absolute file path
     * @param string    $data    value of volume option `accessControlData`
     * @param object    $volume  elFinder volume driver object
     * @param bool|null $isDir   path is directory (true: directory, false: file, null: unknown)
     * @param string    $relPath file path relative to volume root directory started with directory separator
     *
     * @return bool|null
     */
    public function control($attr, $path, $data, $volume, $isDir, $relPath)
    {
        return Plugin::instance()->permissions()->applyContextFilter(
            'file_access',
            $this->hiddenFileAccess($attr, $path, $relPath),
            $attr,
            $path,
            $relPath,
            $isDir
        );
    }

    /**
     * Create or upload .( Dot) started files or folder based on settings.
     * reference : https://github.com/Studio-42/elFinder/wiki/Connector-configuration-options-2.1#acceptedName
     *
     * @param $name file.
     *
     * @return bool
     */
    public function validateName($name)
    {
        return ! (strpos($name, '.') === 0 && !$this->settings->isHiddenFolderAllowed());
    }

    /**
     * Whether the connector may enforce this command before elFinder runs.
     * The authoritative, argument-complete gate is the `*.pre` bind inside run().
     *
     * @param string $command
     *
     * @return bool
     */
    public function isConnectorEnforceable($command)
    {
        return $command !== '' && !\in_array($command, self::CONNECTOR_UNCHECKED_COMMANDS, true);
    }

    /**
     * Command gate for `*.pre` (args: cmd args, elFinder) and the argless connector backstop; returns a preventexec payload on denial.
     *
     * @param string $command
     */
    public function checkPermission($command, ...$args)
    {
        if (\in_array($command, self::EXEMPT_COMMANDS, true)) {
            return;
        }

        $permissionProvider = Plugin::instance()->permissions();
        $cmd                = $this->normalizeCommand($command);
        $paths              = $this->resolvePathsFromArgs($args);

        $authorized = (bool) $permissionProvider->applyContextFilter(
            'can_run_command',
            $this->isAuthorizedBySettings($cmd, $paths, $permissionProvider),
            $cmd,
            $paths,
            $permissionProvider->currentUser()
        );

        if (!$authorized) {
            return (new PreCommandException($this->deniedMessage($cmd, $permissionProvider)))->getError();
        }

        try {
            $this->scanFile($command, $args);
        } catch (PreCommandException $th) {
            return $th->getError();
        }
    }

    /**
     * If a user has permission for any task to perform then they have
     * to be allowed to perform common commands.
     * Lets say, A user has permission to upload file then they will not be
     * able to upload if ls command is not allowed.
     * Checks if the command is required for all commands to perform.
     * Common commands are:
     * - ls
     * - tree
     * - search
     * - info
     * - size
     *
     * @param string              $cmd
     * @param PermissionsProvider $permissionProvider
     *
     * @return bool
     */
    public function isNotRequiredCommandForAllPermission($cmd, $permissionProvider)
    {
        $isNotRequired = true;

        if (
            \in_array($cmd, ['ls', 'tree', 'info', 'size'])
            && (
                $permissionProvider->isCurrentRoleHasPermission()
                || $permissionProvider->isCurrentUserHasPermission()
            )
        ) {
            $isNotRequired = false;
        }

        return $isNotRequired;
    }

    public function scanFile($command, $args)
    {
        if (!\in_array($command, ['put', 'upload']) || \in_array('javascript', Plugin::instance()->permissions()->getEnabledFileType())) {
            return;
        }

        if (isset($args[0]['chunk']) && !empty($args[0]['chunk'])) {
            return;
        }

        if (
            $command === 'upload'
            && !empty($args[0]['FILES']['upload']['tmp_name'])
            && \is_array($args[0]['FILES']['upload']['tmp_name'])
        ) {
            $filePath       = '';
            $fileName       = '';
            $uploadedFiles  = $args[0]['FILES']['upload']['tmp_name'];

            foreach ($uploadedFiles as $index => $tmpName) {
                $content        = '';
                $filePath       = $args[0]['FILES']['upload']['tmp_name'][$index];
                $fileName       = $args[0]['FILES']['upload']['name'][$index];
                if (empty($filePath)) {
                    continue;
                }
                $fileTypeAndExt = wp_check_filetype_and_ext($filePath, $fileName);
                if (!empty($fileTypeAndExt['type'])) {
                    if (stripos($fileTypeAndExt['type'], 'javascript') !== false) {
                        $this->scannedResult[] = wp_sprintf(
                            // translators: 1: file name
                            __(
                                'This file %s type is not allowed',
                                'file-manager'
                            ),
                            $fileName
                        );
                    }
                    if (
                        stripos($fileTypeAndExt['type'], 'text')   !== false
                        || stripos($fileTypeAndExt['type'], 'pdf') !== false
                    ) {
                        $content = file_get_contents($filePath);
                    }
                } else {
                    try {
                        $content = file_get_contents($filePath);
                    } catch (Exception $e) {
                        $this->scannedResult[] = wp_sprintf(
                            // translators: 1: file name
                            __(
                                'Failed to process this file %s',
                                'file-manager'
                            ),
                            $fileName
                        );
                    }
                }

                if (!empty($content)) {
                    $this->scanForPattern($content, $fileName);
                }
            }
        } elseif (isset($_REQUEST['content'])) {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- User needs to edit any type of files. nonce verification done in middleware NonceCheckerMiddleware.
            $this->scanForPattern($_REQUEST['content'], '');
        }

        if (\count($this->scannedResult) > 0) {
            throw new PreCommandException(esc_html(implode('. >> ', $this->scannedResult)));
        }
    }

    public function resolveInvolvedPaths(array $cmdArgs, $elfinder): array
    {
        $hashes = [];

        foreach (['target', 'dst'] as $key) {
            if (isset($cmdArgs[$key]) && \is_string($cmdArgs[$key])) {
                $hashes[] = $cmdArgs[$key];
            }
        }
        foreach (['targets', 'upload_path'] as $key) {
            if (isset($cmdArgs[$key]) && \is_array($cmdArgs[$key])) {
                foreach ($cmdArgs[$key] as $hash) {
                    if (\is_string($hash)) {
                        $hashes[] = $hash;
                    }
                }
            }
        }

        $paths = [];
        foreach (array_unique($hashes) as $hash) {
            $volume = $elfinder->getVolume($hash);
            if (!\is_object($volume) || !method_exists($volume, 'getPath')) {
                continue;
            }
            $path = $volume->getPath($hash);
            if (\is_string($path) && $path !== '') {
                $paths[] = $path;
            }
        }

        return $paths;
    }

    public function isCommandAllowedForPaths($cmd, array $paths, PermissionsProvider $permissions)
    {
        foreach ($paths as $path) {
            $enabled = $permissions->getEnabledCommandsForPath($path);
            if (!\in_array($cmd, $enabled, true) && !\in_array('*', $enabled, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Hidden (dot) file policy from settings; null defers to elFinder's default.
     */
    private function hiddenFileAccess(string $attr, string $path, string $relPath): ?bool
    {
        if (strpos(basename($path), '.') !== 0 || \strlen($relPath) === 1 || $attr === 'locked') {
            return null;
        }

        if ($attr === 'hidden') {
            return !$this->settings->getVisibilityOfHiddenFile();
        }

        return $attr !== 'write' || $this->settings->isHiddenFolderAllowed();
    }

    /**
     * Maps elFinder commands onto the permission names they are governed by.
     */
    private function normalizeCommand(string $command): string
    {
        if ($command === 'file' || $command === 'zipdl') {
            return 'download';
        }

        if ($command === 'put' || $command === 'get') {
            return 'edit';
        }

        return $command;
    }

    /**
     * Absolute target paths from `*.pre` args; empty when called without elFinder context.
     */
    private function resolvePathsFromArgs(array $args): array
    {
        if (!isset($args[0], $args[1]) || !\is_array($args[0]) || !$args[1] instanceof elFinder) {
            return [];
        }

        return $this->resolveInvolvedPaths($args[0], $args[1]);
    }

    /**
     * The plugin's own verdict from permission settings, before the can_run_command filter.
     */
    private function isAuthorizedBySettings(string $cmd, array $paths, PermissionsProvider $permissionProvider): bool
    {
        if (!$this->isNotRequiredCommandForAllPermission($cmd, $permissionProvider)) {
            return true;
        }

        if (!empty($paths) && \in_array($cmd, $permissionProvider->allCommands(), true)) {
            // Path-aware check is authoritative for managed commands when targets resolve.
            return $this->isCommandAllowedForPaths($cmd, $paths, $permissionProvider);
        }

        // Non-managed command or no resolvable target: coarse union (admin + capability filter).
        return $permissionProvider->currentUserCanRun($cmd);
    }

    /**
     * User-facing error for a denied command.
     */
    private function deniedMessage(string $cmd, PermissionsProvider $permissionProvider): string
    {
        $action = $permissionProvider->commandLabel($cmd);

        return $action !== ''
            ? wp_sprintf(
                // translators: 1: human-readable action, e.g. "edit files"
                __("You don't have permission to %s here.", 'file-manager'),
                $action
            )
            : __("You don't have permission to do that here.", 'file-manager');
    }

    private function scanForPattern($content, $fileName)
    {
        $containsJs = false;
        foreach ($this->maliciousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                $containsJs = true;

                break;
            }
        }

        if ($containsJs) {
            $this->scannedResult[] = wp_sprintf(
                // translators: 1: file name
                __(
                    'This file %s contains JS code. Please remove the code and try again. Or allow js mimetype',
                    'file-manager'
                ),
                $fileName
            );
        }
    }
}
