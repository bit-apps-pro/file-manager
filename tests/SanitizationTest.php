<?php

declare(strict_types=1);

namespace BitApps\FM\Tests;

use PHPUnit\Framework\TestCase;
use WP_Mock;

class SanitizationTest extends TestCase
{
    public function setUp(): void
    {
        WP_Mock::setUp();
    }

    public function tearDown(): void
    {
        WP_Mock::tearDown();
    }

    public function testHttpRangeIsSanitizedWithSanitizeTextFieldAndWpUnslash(): void
    {
        $httpRangeRaw = 'bytes=500-999';
        $httpRangeUnslashed = 'bytes=500-999';
        $httpRangeSanitized = 'bytes=500-999';

        WP_Mock::userFunction('wp_unslash', [
            'args' => $httpRangeRaw,
            'times' => 1,
            'return' => $httpRangeUnslashed,
        ]);

        WP_Mock::userFunction('sanitize_text_field', [
            'args' => $httpRangeUnslashed,
            'times' => 1,
            'return' => $httpRangeSanitized,
        ]);

        $result = sanitize_text_field(wp_unslash($httpRangeRaw));

        $this->assertSame($httpRangeSanitized, $result);
    }

    public function testHttpRangeWithCommaSuffixIsSanitized(): void
    {
        $httpRangeRaw = 'bytes=0-499, bytes=500-999';
        $httpRangeUnslashed = 'bytes=0-499, bytes=500-999';
        $httpRangeSanitized = 'bytes=0-499, bytes=500-999';

        WP_Mock::userFunction('wp_unslash', [
            'args' => $httpRangeRaw,
            'times' => 1,
            'return' => $httpRangeUnslashed,
        ]);

        WP_Mock::userFunction('sanitize_text_field', [
            'args' => $httpRangeUnslashed,
            'times' => 1,
            'return' => $httpRangeSanitized,
        ]);

        $result = sanitize_text_field(wp_unslash($httpRangeRaw));

        $this->assertSame($httpRangeSanitized, $result);
    }

    public function testRequestActionIsSanitizedWithSanitizeKey(): void
    {
        $actionRaw = 'edit_file';
        $actionSanitized = 'edit_file';

        WP_Mock::userFunction('sanitize_key', [
            'args' => $actionRaw,
            'times' => 1,
            'return' => $actionSanitized,
        ]);

        $result = sanitize_key($actionRaw);

        $this->assertSame($actionSanitized, $result);
    }

    public function testRequestActionWithSpecialCharactersIsSanitized(): void
    {
        $actionRaw = 'delete!@#$%^&*()file';
        $actionSanitized = 'deletefile';

        WP_Mock::userFunction('sanitize_key', [
            'args' => $actionRaw,
            'times' => 1,
            'return' => $actionSanitized,
        ]);

        $result = sanitize_key($actionRaw);

        $this->assertSame($actionSanitized, $result);
    }

    public function testPhpAuthUserIsSanitizedWithSanitizeTextFieldAndWpUnslash(): void
    {
        $authUserRaw = 'admin';
        $authUserUnslashed = 'admin';
        $authUserSanitized = 'admin';

        WP_Mock::userFunction('wp_unslash', [
            'args' => $authUserRaw,
            'times' => 1,
            'return' => $authUserUnslashed,
        ]);

        WP_Mock::userFunction('sanitize_text_field', [
            'args' => $authUserUnslashed,
            'times' => 1,
            'return' => $authUserSanitized,
        ]);

        $result = sanitize_text_field(wp_unslash($authUserRaw));

        $this->assertSame($authUserSanitized, $result);
    }

    public function testPhpAuthUserWithEscapedCharactersIsSanitized(): void
    {
        $authUserRaw = "admin\'test";
        $authUserUnslashed = "admin'test";
        $authUserSanitized = "admin'test";

        WP_Mock::userFunction('wp_unslash', [
            'args' => $authUserRaw,
            'times' => 1,
            'return' => $authUserUnslashed,
        ]);

        WP_Mock::userFunction('sanitize_text_field', [
            'args' => $authUserUnslashed,
            'times' => 1,
            'return' => $authUserSanitized,
        ]);

        $result = sanitize_text_field(wp_unslash($authUserRaw));

        $this->assertSame($authUserSanitized, $result);
    }
}
