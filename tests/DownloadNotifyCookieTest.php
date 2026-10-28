<?php

declare(strict_types=1);

namespace BitApps\FM\Tests;

use BitApps\FM\Providers\FileManager\DownloadNotifyCookie;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class DownloadNotifyCookieTest extends TestCase
{
    /**
     * @var array<int, array>
     */
    private array $written = [];

    public function testWritesCookieOnTheUnencodedPagePath(): void
    {
        $args = ['cpath' => '/file-manager-frontend-test/', 'reqid' => '1a18131b20ab3'];

        $this->handler()->onFilePre('file', $args);

        $this->assertCount(1, $this->written);
        [$name, $value, $options] = $this->written[0];
        $this->assertSame('elfdl1a18131b20ab3', $name);
        $this->assertSame('1', $value);
        $this->assertSame('/file-manager-frontend-test/', $options['path']);
        $this->assertFalse($options['httponly']);
    }

    public function testClearsCpathSoElfinderSkipsItsUrlencodedCookie(): void
    {
        $args = ['cpath' => '/wp-admin/admin.php', 'reqid' => 'abc123'];

        $this->handler()->onFilePre('file', $args);

        $this->assertSame('', $args['cpath']);
    }

    public function testKeepsPercentEncodedPathsVerbatim(): void
    {
        $args = ['cpath' => '/%E0%A4%9C%E0%A4%AE/page/', 'reqid' => 'abc123'];

        $this->handler()->onFilePre('file', $args);

        $this->assertSame('/%E0%A4%9C%E0%A4%AE/page/', $this->written[0][2]['path']);
    }

    #[DataProvider('unsafeArgs')]
    public function testIgnoresMissingOrUnsafeInput(array $args): void
    {
        $original = $args;

        $this->handler()->onFilePre('file', $args);

        $this->assertSame([], $this->written);
        $this->assertSame($original, $args);
    }

    /**
     * Requests the handler must leave untouched.
     */
    public static function unsafeArgs(): array
    {
        return [
            'no cpath'            => [['reqid' => 'abc123']],
            'no reqid'            => [['cpath' => '/page/']],
            'relative path'       => [['cpath' => 'page/', 'reqid' => 'abc123']],
            'cookie attr inject'  => [['cpath' => '/page/;domain=evil.test', 'reqid' => 'abc123']],
            'header split'        => [['cpath' => "/page/\r\nX-Evil: 1", 'reqid' => 'abc123']],
            'trailing newline'    => [['cpath' => "/page/\n", 'reqid' => 'abc123']],
            'reqid with symbols'  => [['cpath' => '/page/', 'reqid' => 'a=b;c']],
            'non-string cpath'    => [['cpath' => ['/page/'], 'reqid' => 'abc123']],
        ];
    }

    /**
     * Builds the handler with a cookie writer that records instead of sending headers.
     */
    private function handler(): DownloadNotifyCookie
    {
        return new DownloadNotifyCookie(function (string $name, string $value, array $options): bool {
            $this->written[] = [$name, $value, $options];

            return true;
        });
    }
}
