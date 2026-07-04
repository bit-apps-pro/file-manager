<?php

declare(strict_types=1);

namespace BitApps\FM\Tests;

use BitApps\FM\Providers\FileManager\Options;
use PHPUnit\Framework\TestCase;

class BindKeyNormalizationTest extends TestCase
{
    public function testCollapsesNewlinesAndIndentationToSingleSpaces(): void
    {
        $input = "get.pre put.pre file.pre\n             mkfile.pre zipdl.pre\n             reload.pre";

        $this->assertSame(
            'get.pre put.pre file.pre mkfile.pre zipdl.pre reload.pre',
            Options::normalizeBindKey($input)
        );
    }

    public function testTrimsAndCollapsesRepeatedSpaces(): void
    {
        $this->assertSame('a.pre b.pre', Options::normalizeBindKey("  a.pre    b.pre  "));
    }

    public function testEveryTokenIsWhitespaceFree(): void
    {
        $key = Options::normalizeBindKey("mkfile.pre\n zipdl.pre");
        foreach (explode(' ', $key) as $token) {
            $this->assertSame($token, trim($token));
            $this->assertStringNotContainsString("\n", $token);
        }
    }
}
