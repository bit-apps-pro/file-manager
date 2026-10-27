<?php

declare(strict_types=1);

namespace BitApps\FM\Tests;

use BitApps\FM\Providers\FileManager\LocalFileSystemDriver;
use elFinder;
use elFinderVolumeDriver;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use ZipArchive;

class LocalFileSystemDriverTest extends TestCase
{
    private const STOCK_DRIVER = 'LocalFileSystem';

    private string $root;

    protected function setUp(): void
    {
        if (!class_exists(ZipArchive::class)) {
            $this->markTestSkipped('ZipArchive extension is required.');
        }

        $tmp = realpath(sys_get_temp_dir());
        $this->root = $tmp . '/bitfm-driver-' . uniqid();
        $this->makeTree($this->root . '/tree', 3, 3, 4);
        file_put_contents($this->root . '/tree/.secret', 'hidden');
        file_put_contents($this->root . '/tree/fake.jpg', 'plain text behind an image extension');
        mkdir($this->root . '/tmp');

        LocalFileSystemDriver::register();
    }

    protected function tearDown(): void
    {
        if (isset($this->root) && is_dir($this->root)) {
            exec('rm -rf ' . escapeshellarg($this->root));
        }
    }

    public function testRegisterExposesDriverToElFinder(): void
    {
        $this->assertTrue(class_exists('elFinderVolume' . LocalFileSystemDriver::DRIVER));
    }

    public function testZipdlArchiveMatchesStockDriver(): void
    {
        $stock = $this->zipEntries(self::STOCK_DRIVER);

        $this->assertSame($stock, $this->zipEntries(LocalFileSystemDriver::DRIVER));
        $this->assertArrayNotHasKey('tree/.secret', $stock);
        $this->assertArrayHasKey('tree/fake.jpg', $stock);
    }

    public function testZipdlDropsStatCacheThatStockDriverKeeps(): void
    {
        $this->assertNotSame([], $this->cachedTreePaths(self::STOCK_DRIVER));
        $this->assertSame([], $this->cachedTreePaths(LocalFileSystemDriver::DRIVER));
    }

    public function testExtensionMimeDoesNotOutliveTheWalk(): void
    {
        $volume = $this->mountVolume(LocalFileSystemDriver::DRIVER);
        $this->skipWithoutContentSniffing($volume);
        $fakeJpg = $volume->getHash($this->root, 'tree/fake.jpg');

        $volume->zipdl([$volume->getHash($this->root, 'tree')]);

        $this->assertSame('text/plain', $volume->file($fakeJpg)['mime']);
    }

    public function testZipdlRestoresMimeDetection(): void
    {
        $volume = $this->mountVolume(LocalFileSystemDriver::DRIVER);
        $this->skipWithoutContentSniffing($volume);
        $before = $this->readProperty($volume, 'mimeDetect');

        $volume->zipdl([$volume->getHash($this->root, 'tree')]);

        $this->assertSame($before, $this->readProperty($volume, 'mimeDetect'));
    }

    public function testFailedWalkRestoresMimeDetection(): void
    {
        $volume = $this->mountVolume(LocalFileSystemDriver::DRIVER, ['maxArcFilesSize' => 1]);
        $this->skipWithoutContentSniffing($volume);
        $before = $this->readProperty($volume, 'mimeDetect');

        $this->assertFalse($volume->zipdl([$volume->getHash($this->root, 'tree')]));
        $this->assertSame($before, $this->readProperty($volume, 'mimeDetect'));
    }

    /** Builds a nested tree of small files. */
    private function makeTree(string $dir, int $depth, int $dirs, int $files): void
    {
        mkdir($dir, 0700, true);
        for ($i = 0; $i < $files; $i++) {
            file_put_contents("{$dir}/f{$i}.txt", str_repeat((string) $i, 64));
        }
        if ($depth > 1) {
            for ($i = 0; $i < $dirs; $i++) {
                $this->makeTree("{$dir}/d{$i}", $depth - 1, $dirs, $files);
            }
        }
    }

    /** Mounts the test root with the given elFinder driver name. */
    private function mountVolume(string $driver, array $options = []): elFinderVolumeDriver
    {
        $finder = new elFinder([
            'roots' => [array_merge([
                'driver'     => $driver,
                'path'       => $this->root,
                'tmbPath'    => '',
                'tmpPath'    => $this->root . '/tmp',
                'attributes' => [['pattern' => '~/\.secret$~', 'read' => false, 'hidden' => true]],
            ], $options)],
        ]);
        $volumes = new ReflectionProperty(elFinder::class, 'volumes');

        return current($volumes->getValue($finder));
    }

    /** Returns archive entries (name => [crc, size]) that zipdl builds for the tree. */
    private function zipEntries(string $driver): array
    {
        $volume = $this->mountVolume($driver);
        $result = $volume->zipdl([$volume->getHash($this->root, 'tree')]);
        $this->assertIsArray($result, json_encode($volume->error()));

        $zip = new ZipArchive();
        $zip->open($result['path']);
        $entries = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $entries[rtrim($stat['name'], '/')] = [$stat['crc'], $stat['size']];
        }
        $zip->close();
        unlink($result['path']);
        ksort($entries);

        return $entries;
    }

    /** Returns stat and listing cache keys under the tree left behind after zipdl. */
    private function cachedTreePaths(string $driver): array
    {
        $volume = $this->mountVolume($driver);
        $volume->zipdl([$volume->getHash($this->root, 'tree')]);

        $paths = array_merge(
            array_keys($this->readProperty($volume, 'cache')),
            array_keys($this->readProperty($volume, 'dirsCache'))
        );

        return array_values(array_filter(
            $paths,
            fn ($path) => strpos($path, $this->root . '/tree') === 0
        ));
    }

    /** Skips when the volume cannot sniff content, since extension-based mime would then be the norm. */
    private function skipWithoutContentSniffing(elFinderVolumeDriver $volume): void
    {
        if ($this->readProperty($volume, 'mimeDetect') === 'internal') {
            $this->markTestSkipped('Content-based mime detection (fileinfo) is unavailable.');
        }
    }

    /** Reads a protected property of the volume driver. */
    private function readProperty(elFinderVolumeDriver $volume, string $name)
    {
        $property = new ReflectionProperty(elFinderVolumeDriver::class, $name);

        return $property->getValue($volume);
    }
}
