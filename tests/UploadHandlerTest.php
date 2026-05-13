<?php

declare(strict_types=1);

namespace BitApps\FM\Tests;

use PHPUnit\Framework\TestCase;

class UploadHandlerTest extends TestCase
{
    private string $pluginRoot;

    public function setUp(): void
    {
        parent::setUp();
        $this->pluginRoot = __DIR__ . '/..';
    }

    public function testNoMoveUploadedFileInPluginCode(): void
    {
        $backendDir = $this->pluginRoot . '/backend';

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($backendDir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        $filesWithMoveUploadedFile = [];

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $content = file_get_contents($file->getPathname());

                if (strpos($content, 'move_uploaded_file') !== false) {
                    $filesWithMoveUploadedFile[] = $file->getPathname();
                }
            }
        }

        $this->assertEmpty(
            $filesWithMoveUploadedFile,
            'The following files contain move_uploaded_file() calls: ' . implode(', ', $filesWithMoveUploadedFile)
        );
    }

    public function testNoDirectWpHandleUploadInPluginCode(): void
    {
        $backendDir = $this->pluginRoot . '/backend';

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($backendDir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        $filesWithWpHandleUpload = [];

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $content = file_get_contents($file->getPathname());

                if (strpos($content, 'wp_handle_upload') !== false) {
                    $filesWithWpHandleUpload[] = $file->getPathname();
                }
            }
        }

        $this->assertEmpty(
            $filesWithWpHandleUpload,
            'The following files contain wp_handle_upload() calls: ' . implode(', ', $filesWithWpHandleUpload)
        );
    }

    public function testMediaSynchronizerUsesWordPressAttachmentAPIs(): void
    {
        $mediaSynchronizerFile = $this->pluginRoot . '/backend/app/Providers/MediaSynchronizer.php';
        $content = file_get_contents($mediaSynchronizerFile);

        $this->assertStringContainsString('wp_insert_attachment', $content);
        $this->assertStringContainsString('wp_generate_attachment_metadata', $content);
        $this->assertStringContainsString('wp_update_attachment_metadata', $content);
    }

    public function testMediaSynchronizerChecksMediaSyncPreference(): void
    {
        $mediaSynchronizerFile = $this->pluginRoot . '/backend/app/Providers/MediaSynchronizer.php';
        $content = file_get_contents($mediaSynchronizerFile);

        $this->assertStringContainsString('isWpMediaSyncEnabled', $content);
    }

    public function testMediaSynchronizerUsesUploadDirForBasePath(): void
    {
        $mediaSynchronizerFile = $this->pluginRoot . '/backend/app/Providers/MediaSynchronizer.php';
        $content = file_get_contents($mediaSynchronizerFile);

        $this->assertStringContainsString('wp_upload_dir', $content);
        $this->assertStringContainsString('basedir', $content);
    }

    public function testOnFileUploadSkipsWhenNotInUploadDirectory(): void
    {
        $this->markTestSkipped('Requires full WordPress environment and elFinder integration');
    }

    public function testOnFileUploadSkipsWhenMediaSyncDisabled(): void
    {
        $this->markTestSkipped('Requires full WordPress environment and elFinder integration');
    }

    public function testOnFileUploadProcessesFilesWhenConditionsMet(): void
    {
        $this->markTestSkipped('Requires full WordPress environment and elFinder integration');
    }
}