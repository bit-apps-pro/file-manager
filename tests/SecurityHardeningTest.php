<?php

declare(strict_types=1);

namespace BitApps\FM\Tests;

use BitApps\FM\Providers\AccessControlProvider;
use BitApps\FM\Providers\FileManager\FileRoot;
use PHPUnit\Framework\TestCase;

/**
 * Regression guards for the 6.9.x security-audit fixes: the `file` read-grant bypass
 * (A-1), the convert-CLI image surface (imgLib DiD), and upload-dir hardening (E-1).
 */
final class SecurityHardeningTest extends TestCase
{
    /** A-1: the MIME re-permit that cleared the `download`-grant denial must stay removed. */
    public function testFileReadMimeRepermitRemoved(): void
    {
        $this->assertFalse(
            method_exists(AccessControlProvider::class, 'isFileAllowedToOpen'),
            'isFileAllowedToOpen re-introduces the download-grant bypass (A-1) — do not restore it'
        );
    }

    /** imgLib DiD: a volume must pin an in-process image library, never `auto`/`convert` (the shell path). */
    public function testVolumeForcesInProcessImageLibrary(): void
    {
        $options = (new FileRoot('/tmp/fm-imglib-test', '', 'Test'))->getOptions();

        $this->assertArrayHasKey('imgLib', $options);
        // The guard that matters: the shell-based libs are never selected (a regression to
        // 'auto' would re-enable the convert-CLI fallback).
        $this->assertNotSame('auto', $options['imgLib']);
        $this->assertNotSame('convert', $options['imgLib']);
        $this->assertContains($options['imgLib'], ['imagick', 'gd', 'none']);
    }

    /** E-1: the dir-hardening helper drops an index stub + a .htaccess denying PHP execution and listing. */
    public function testHardenDirWritesIndexAndHtaccess(): void
    {
        if (!\defined('ABSPATH')) {
            \define('ABSPATH', '/tmp/');
        }
        require_once __DIR__ . '/../backend/functions/global.php';
        $this->assertTrue(\function_exists('bitapps_fm_harden_dir'));

        $dir = sys_get_temp_dir() . '/fm-harden-' . uniqid('', true);
        mkdir($dir);
        try {
            bitapps_fm_harden_dir($dir);

            $this->assertFileExists($dir . '/index.php');
            $this->assertFileExists($dir . '/.htaccess');
            $htaccess = (string) file_get_contents($dir . '/.htaccess');
            $this->assertStringContainsString('Options -Indexes', $htaccess);
            $this->assertMatchesRegularExpression('/php.*(Require all denied|Deny from all)/is', $htaccess);
        } finally {
            @unlink($dir . '/index.php');
            @unlink($dir . '/.htaccess');
            @rmdir($dir);
        }
    }
}
