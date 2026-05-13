<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    public function testTrueIsTrue(): void
    {
        $this->assertTrue(true);
    }

    public function testWpMockIsLoaded(): void
    {
        $this->assertTrue(class_exists(\WP_Mock::class));
    }
}