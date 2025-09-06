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
use WpSpaghetti\WpEnv\Environment;
use WpSpaghetti\WpVite\Vite;

/**
 * Test cases for component name feature.
 *
 * @internal
 *
 * @coversNothing
 */
final class ComponentNameTest extends TestCase
{
    private string $testBasePath;

    private string $testBaseUrl;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testBasePath = sys_get_temp_dir().'/wp-vite-component-test/';
        $this->testBaseUrl = 'https://example.com/wp-content/plugins/test-plugin/';

        // Create test directory
        if (!is_dir($this->testBasePath)) {
            mkdir($this->testBasePath, 0777, true);
        }

        Vite::resetCache();
        Environment::clearCache();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up
        if (is_dir($this->testBasePath)) {
            $this->recursiveRemoveDirectory($this->testBasePath);
        }

        // Clear mock variables
        $this->clearMockVariables();
    }

    public function testComponentNameAutoDetectionFromPluginPath(): void
    {
        $pluginPath = '/var/www/html/wp-content/plugins/my-awesome-plugin/';
        $pluginUrl = 'https://example.com/wp-content/plugins/my-awesome-plugin/';

        Vite::init($pluginPath, $pluginUrl);

        $pluginName = Vite::getPluginName();
        self::assertSame('my-awesome-plugin', $pluginName);
    }

    public function testComponentNameAutoDetectionFromThemePath(): void
    {
        $themePath = '/var/www/html/wp-content/themes/my-theme/';
        $themeUrl = 'https://example.com/wp-content/themes/my-theme/';

        Vite::init($themePath, $themeUrl);

        $pluginName = Vite::getPluginName();
        self::assertSame('my-theme', $pluginName);
    }

    public function testComponentNameAutoDetectionFromChildThemePath(): void
    {
        $childThemePath = '/var/www/html/wp-content/themes/parent-theme-child/';
        $childThemeUrl = 'https://example.com/wp-content/themes/parent-theme-child/';

        Vite::init($childThemePath, $childThemeUrl);

        $pluginName = Vite::getPluginName();
        self::assertSame('parent-theme', $pluginName);
    }

    public function testExplicitComponentNameOverridesAutoDetection(): void
    {
        $pluginPath = '/var/www/html/wp-content/plugins/detected-name/';
        $pluginUrl = 'https://example.com/wp-content/plugins/detected-name/';
        $explicitName = 'custom-component-name';

        Vite::init($pluginPath, $pluginUrl, '1.0.0', $explicitName);

        $pluginName = Vite::getPluginName();
        self::assertSame($explicitName, $pluginName);
    }

    public function testComponentNameWithSpecialCharacters(): void
    {
        $pluginPath = '/var/www/html/wp-content/plugins/my_special@plugin-name/';
        $pluginUrl = 'https://example.com/wp-content/plugins/my_special@plugin-name/';

        Vite::init($pluginPath, $pluginUrl);

        $pluginName = Vite::getPluginName();
        self::assertSame('my-special-plugin-name', $pluginName);
    }

    public function testComponentNameFallbackForEdgeCases(): void
    {
        $edgePath = '/wp-content/';
        $edgeUrl = 'https://example.com/wp-content/';

        Vite::init($edgePath, $edgeUrl);

        $pluginName = Vite::getPluginName();
        self::assertSame('wp-vite', $pluginName);
    }

    public function testComponentNameForWindowsPaths(): void
    {
        $windowsPath = 'C:\inetpub\wwwroot\wp-content\plugins\windows-plugin\\';
        $windowsUrl = 'https://example.com/wp-content/plugins/windows-plugin/';

        Vite::init($windowsPath, $windowsUrl);

        $pluginName = Vite::getPluginName();
        self::assertSame('windows-plugin', $pluginName);
    }

    public function testComponentSpecificEnvironmentVariables(): void
    {
        global $mock_environment_vars;

        // Test with component-specific environment variables
        $componentName = 'my-test-plugin';
        $mock_environment_vars = [
            'MY_TEST_PLUGIN_VITE_SERVER_HOST' => 'component-host',
            'MY_TEST_PLUGIN_VITE_SERVER_PORT' => '4000',
            'VITE_SERVER_HOST' => 'global-host',
            'VITE_SERVER_PORT' => '3000',
        ];

        try {
            Vite::resetCache();
            Environment::clearCache();
            Vite::init($this->testBasePath, $this->testBaseUrl, '1.0.0', $componentName);

            $debugInfo = Vite::getDebugInfo();
            $config = $debugInfo['config'];

            // Component-specific variables should take priority
            self::assertSame('component-host', $config['server']['host']);
            self::assertSame(4000, $config['server']['port']);
            self::assertSame($componentName, $debugInfo['component_name']);
            self::assertSame('MY_TEST_PLUGIN_VITE_', $debugInfo['component_env_prefix']);
        } finally {
            $mock_environment_vars = [];
            Vite::resetCache();
            Environment::clearCache();
        }
    }

    public function testGlobalEnvironmentVariablesFallback(): void
    {
        global $mock_environment_vars;

        $componentName = 'another-plugin';
        $mock_environment_vars = [
            'VITE_SERVER_HOST' => 'global-host',
            'VITE_SERVER_PORT' => '5000',
            // No component-specific variables
        ];

        try {
            Vite::resetCache();
            Environment::clearCache();
            Vite::init($this->testBasePath, $this->testBaseUrl, '1.0.0', $componentName);

            $debugInfo = Vite::getDebugInfo();
            $config = $debugInfo['config'];

            // Should fall back to global variables
            self::assertSame('global-host', $config['server']['host']);
            self::assertSame(5000, $config['server']['port']);
            self::assertSame($componentName, $debugInfo['component_name']);
            self::assertSame('ANOTHER_PLUGIN_VITE_', $debugInfo['component_env_prefix']);
        } finally {
            $mock_environment_vars = [];
            Vite::resetCache();
            Environment::clearCache();
        }
    }

    public function testComponentNameInDebugInfo(): void
    {
        $componentName = 'debug-test-plugin';
        Vite::init($this->testBasePath, $this->testBaseUrl, '1.0.0', $componentName);

        $debugInfo = Vite::getDebugInfo();

        self::assertArrayHasKey('component_name', $debugInfo);
        self::assertArrayHasKey('component_env_prefix', $debugInfo);
        self::assertSame($componentName, $debugInfo['component_name']);
        self::assertSame('DEBUG_TEST_PLUGIN_VITE_', $debugInfo['component_env_prefix']);
    }

    public function testGetPluginNameThrowsExceptionWhenNotInitialized(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('WpVite not initialized. Call WpVite::init() first.');

        Vite::resetForTesting();
        Vite::getPluginName();
    }

    private function recursiveRemoveDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            $fullPath = $dir.\DIRECTORY_SEPARATOR.$file;

            if (is_dir($fullPath)) {
                $this->recursiveRemoveDirectory($fullPath);
            } else {
                unlink($fullPath);
            }
        }

        rmdir($dir);
    }

    private function clearMockVariables(): void
    {
        global $mock_environment_vars, $mock_env_vars, $mock_constants;
        $mock_environment_vars = [];
        $mock_env_vars = [];
        $mock_constants = [];

        Environment::clearCache();
    }
}
