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

// Define WordPress constants for testing
if (!defined('ABSPATH')) {
    define('ABSPATH', '/tmp/wordpress/');
}

if (!defined('WPINC')) {
    define('WPINC', 'wp-includes');
}

if (!defined('WP_DEBUG')) {
    define('WP_DEBUG', false);
}

if (!defined('WP_ENV')) {
    define('WP_ENV', 'testing');
}

if (!defined('WP_ENVIRONMENT_TYPE')) {
    define('WP_ENVIRONMENT_TYPE', 'testing');
}

// Autoload Composer dependencies
require_once dirname(__DIR__).'/vendor/autoload.php';

// Mock WordPress functions for testing
if (!function_exists('trailingslashit')) {
    function trailingslashit(string $string): string
    {
        return rtrim($string, '/\\').'/';
    }
}

if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script(string $handle, string $src, array $deps = [], ?string $ver = null, bool $in_footer = false): void
    {
        // Mock implementation for testing
    }
}

if (!function_exists('wp_enqueue_style')) {
    function wp_enqueue_style(string $handle, string $src, array $deps = [], ?string $ver = null, string $media = 'all'): void
    {
        // Mock implementation for testing
    }
}

if (!function_exists('wp_register_style')) {
    function wp_register_style(string $handle, string $src, array $deps = [], ?string $ver = null, string $media = 'all'): void
    {
        // Mock implementation for testing
    }
}

if (!function_exists('esc_url')) {
    function esc_url(string $url): string
    {
        return $url;
    }
}

if (!function_exists('add_filter')) {
    function add_filter(string $hook_name, callable $callback, int $priority = 10, int $accepted_args = 1): bool
    {
        return true;
    }
}

if (!function_exists('do_action')) {
    /**
     * @param mixed ...$args
     */
    function do_action(string $hook_name, ...$args): void
    {
        // Mock implementation for testing
    }
}

if (!function_exists('apply_filters')) {
    /**
     * @param mixed $value
     * @param mixed ...$args
     *
     * @return mixed
     */
    function apply_filters(string $hook_name, $value, ...$args)
    {
        return $value;
    }
}
