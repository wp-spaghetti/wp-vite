<?php

declare(strict_types=1);

/*
 * This file is part of the Wp Vite package.
 *
 * (ɔ) Frugan <dev@frugan.it>
 *
 * This source file is subject to the GNU GPLv3 or later license that is bundled
 * with this source code in the file LICENSE.
 */

namespace WpSpaghetti\WpVite\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Test cases for WordPress function mocks.
 *
 * @internal
 *
 * @coversNothing
 */
final class MockFunctionsTest extends TestCase
{
    public function testTrailingSlashitFunction(): void
    {
        self::assertSame('path/', trailingslashit('path'));
        self::assertSame('path/', trailingslashit('path/'));
        self::assertSame('path/', trailingslashit('path\\'));
        self::assertSame('/', trailingslashit(''));
    }

    public function testEscUrlFunction(): void
    {
        $url = 'https://example.com/path';
        self::assertSame($url, esc_url($url));
    }

    public function testWordPressFunctionsMocked(): void
    {
        self::assertTrue(\function_exists('wp_enqueue_script'));
        self::assertTrue(\function_exists('wp_enqueue_style'));
        self::assertTrue(\function_exists('wp_register_style'));
        self::assertTrue(\function_exists('add_filter'));
        self::assertTrue(\function_exists('do_action'));
        self::assertTrue(\function_exists('apply_filters'));
    }

    public function testApplyFiltersReturnValue(): void
    {
        $original = 'test-value';
        $result = apply_filters('test_hook', $original);

        self::assertSame($original, $result);
    }

    public function testAddFilterReturnsTrue(): void
    {
        $result = add_filter('test_hook', static function (): void {}, 10, 1);

        self::assertTrue($result);
    }

    public function testMockEnqueueFunctions(): void
    {
        // These should not throw exceptions
        wp_enqueue_script('test-handle', 'test-src');
        wp_enqueue_style('test-handle', 'test-src');
        wp_register_style('test-handle', 'test-src');
        do_action('test_action');

        self::assertTrue(true); // If we get here, mocks work
    }
}
