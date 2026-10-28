<?php

namespace BitApps\FM\Providers\FileManager;

\defined('ABSPATH') || exit();

/**
 * Sets elFinder's download-complete cookie on the page path, which elFinder's own urlencode()d path breaks.
 */
class DownloadNotifyCookie
{
    private const NAME_PREFIX = 'elfdl';

    // Percent-encoded URL path chars only: no ';' or ',' (cookie attribute injection) and no whitespace.
    private const PATH_PATTERN = '#^/[A-Za-z0-9\-._~!$&\'()*+=:@/%]*$#D';

    private const REQID_PATTERN = '/^[A-Za-z0-9]+$/D';

    /**
     * @var callable(string, string, array): bool
     */
    private $writeCookie;

    /**
     * @param null|callable(string, string, array): bool $writeCookie defaults to setcookie()
     */
    public function __construct(?callable $writeCookie = null)
    {
        $this->writeCookie = $writeCookie ?? 'setcookie';
    }

    /**
     * `file.pre` handler: writes the cookie on the raw page path and blanks `cpath` so elFinder skips its broken one.
     *
     * @param string $cmd
     * @param array  $args
     */
    public function onFilePre($cmd, &$args)
    {
        $path  = $args['cpath'] ?? null;
        $reqId = $args['reqid'] ?? null;

        if (!$this->isValid($path, self::PATH_PATTERN) || !$this->isValid($reqId, self::REQID_PATTERN)) {
            return;
        }

        // An urlencode()d path has no leading '/', so browsers fall back to the connector's
        // directory (/wp-admin) and the frontend page never sees the cookie.
        ($this->writeCookie)(
            self::NAME_PREFIX . $reqId,
            '1',
            ['path' => $path, 'httponly' => false, 'samesite' => 'Lax']
        );

        $args['cpath'] = '';
    }

    /**
     * Whether the value is a string matching the pattern.
     *
     * @param mixed  $value
     * @param string $pattern
     */
    private function isValid($value, $pattern): bool
    {
        return \is_string($value) && preg_match($pattern, $value) === 1;
    }
}
