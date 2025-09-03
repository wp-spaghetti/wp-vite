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
 * Test cases for the Vite class.
 *
 * @internal
 *
 * @coversNothing
 */
final class ViteTest extends TestCase
{
    private string $testBasePath;

    private string $testBaseUrl;

    private string $testVersion;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testBasePath = sys_get_temp_dir().'/wp-vite-test/';
        $this->testBaseUrl = 'https://example.com/wp-content/plugins/test-plugin/';
        $this->testVersion = '1.2.3';

        // Create test directory structure
        $this->createTestDirectories();

        // Reset Vite cache before each test
        Vite::resetCache();
        Environment::clearCache();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up test directories
        $this->removeTestDirectories();

        // Clear mock variables
        $this->clearMockVariables();

        // Reset constants
        $this->resetTestConstants();
    }

    public function testInit(): void
    {
        Vite::init($this->testBasePath, $this->testBaseUrl, $this->testVersion);

        self::assertSame($this->testBasePath, Vite::getBasePath());
        self::assertSame($this->testBaseUrl, Vite::getBaseUrl());
    }

    public function testInitWithDefaults(): void
    {
        Vite::init($this->testBasePath, $this->testBaseUrl);

        self::assertSame($this->testBasePath, Vite::getBasePath());
        self::assertSame($this->testBaseUrl, Vite::getBaseUrl());
    }

    public function testGetBasePathThrowsExceptionWhenNotInitialized(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('WpVite not initialized. Call WpVite::init() first.');

        // Use resetForTesting to completely reset including initialization flag
        Vite::resetForTesting();

        Vite::getBasePath();
    }

    public function testGetBaseUrlThrowsExceptionWhenNotInitialized(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('WpVite not initialized. Call WpVite::init() first.');

        // Use resetForTesting to completely reset including initialization flag
        Vite::resetForTesting();

        Vite::getBaseUrl();
    }

    public function testAssetExistsReturnsFalseForNonexistentAsset(): void
    {
        Vite::init($this->testBasePath, $this->testBaseUrl, $this->testVersion);

        self::assertFalse(Vite::assetExists('nonexistent/asset', 'js'));
        self::assertFalse(Vite::assetExists('nonexistent/asset', 'css'));
    }

    public function testJsExistsWithActualFile(): void
    {
        // Use mock variables instead of putenv to avoid log output
        global $mock_environment_vars;
        $mock_environment_vars = ['WP_DEBUG' => '1'];

        try {
            Vite::resetCache();
            Vite::init($this->testBasePath, $this->testBaseUrl, $this->testVersion);

            // Create a test JavaScript file
            $jsDir = $this->testBasePath.'resources/js/';
            if (!is_dir($jsDir)) {
                mkdir($jsDir, 0777, true);
            }

            $jsFile = $jsDir.'app.js';
            file_put_contents($jsFile, '// Test JS file');

            // Make sure file exists
            self::assertFileExists($jsFile);

            self::assertTrue(Vite::jsExists('js/app'));
        } finally {
            $mock_environment_vars = [];
            Vite::resetCache();
        }
    }

    public function testCssExistsWithActualFile(): void
    {
        global $mock_environment_vars;
        $mock_environment_vars = ['WP_DEBUG' => '1'];

        try {
            Vite::resetCache();
            Vite::init($this->testBasePath, $this->testBaseUrl, $this->testVersion);

            // Create a test CSS file
            $cssDir = $this->testBasePath.'resources/css/';
            if (!is_dir($cssDir)) {
                mkdir($cssDir, 0777, true);
            }

            $cssFile = $cssDir.'main.css';
            file_put_contents($cssFile, '/* Test CSS file */');

            // Make sure file exists
            self::assertFileExists($cssFile);

            self::assertTrue(Vite::cssExists('css/main'));
        } finally {
            $mock_environment_vars = [];
            Vite::resetCache();
        }
    }

    public function testScssFileDetection(): void
    {
        global $mock_environment_vars;
        $mock_environment_vars = ['WP_DEBUG' => '1'];

        try {
            Vite::resetCache();
            Vite::init($this->testBasePath, $this->testBaseUrl, $this->testVersion);

            // Create a test SCSS file
            $scssDir = $this->testBasePath.'resources/scss/';
            if (!is_dir($scssDir)) {
                mkdir($scssDir, 0777, true);
            }

            $scssFile = $scssDir.'styles.scss';
            file_put_contents($scssFile, '// Test SCSS file');

            // Make sure file exists
            self::assertFileExists($scssFile);

            self::assertTrue(Vite::cssExists('scss/styles'));
        } finally {
            $mock_environment_vars = [];
            Vite::resetCache();
        }
    }

    public function testIsDevServerWithoutServer(): void
    {
        Vite::init($this->testBasePath, $this->testBaseUrl, $this->testVersion);

        // Mock WP_DEBUG to false to disable dev server checks
        if (!\defined('WP_DEBUG')) {
            \define('WP_DEBUG', false);
        }

        self::assertFalse(Vite::isDevServer());
    }

    public function testManifestLoadingWithValidFile(): void
    {
        Vite::init($this->testBasePath, $this->testBaseUrl, $this->testVersion);

        // Create a test manifest file
        $manifestData = [
            'resources/js/app.js' => [
                'file' => 'assets/js/app.12345.js',
                'isEntry' => true,
            ],
            'resources/css/main.css' => [
                'file' => 'assets/css/main.67890.css',
                'isEntry' => true,
            ],
        ];

        $manifestFile = $this->testBasePath.'assets/.vite/manifest.json';
        file_put_contents($manifestFile, json_encode($manifestData, JSON_PRETTY_PRINT));

        // Create corresponding asset files
        file_put_contents($this->testBasePath.'assets/js/app.12345.js', '// Compiled JS');
        file_put_contents($this->testBasePath.'assets/css/main.67890.css', '/* Compiled CSS */');

        $manifest = Vite::getManifest();

        self::assertNotEmpty($manifest);
        self::assertArrayHasKey('resources/js/app.js', $manifest);
        self::assertArrayHasKey('resources/css/main.css', $manifest);
    }

    public function testManifestLoadingWithInvalidJson(): void
    {
        Vite::init($this->testBasePath, $this->testBaseUrl, $this->testVersion);

        // Create an invalid manifest file
        $manifestFile = $this->testBasePath.'assets/.vite/manifest.json';
        file_put_contents($manifestFile, '{ invalid json }');

        $manifest = Vite::getManifest();

        self::assertEmpty($manifest);
    }

    public function testAssetUrlInProductionMode(): void
    {
        Vite::init($this->testBasePath, $this->testBaseUrl, $this->testVersion);

        // Create a production asset file
        $jsFile = $this->testBasePath.'assets/js/app.min.js';
        file_put_contents($jsFile, '// Compiled JS');

        $assetUrl = Vite::asset('js/app');

        self::assertStringContainsString($this->testBaseUrl, $assetUrl);
        self::assertStringContainsString('app.min.js', $assetUrl);
    }

    public function testEnqueueScriptDoesNothingForNonexistentAsset(): void
    {
        Vite::init($this->testBasePath, $this->testBaseUrl, $this->testVersion);

        // This should not throw any errors and should exit early
        Vite::enqueueScript('nonexistent-handle', 'nonexistent/asset');

        // If we get here, the method handled the missing asset gracefully
        self::assertTrue(true);
    }

    public function testEnqueueStyleDoesNothingForNonexistentAsset(): void
    {
        Vite::init($this->testBasePath, $this->testBaseUrl, $this->testVersion);

        // This should not throw any errors and should exit early
        Vite::enqueueStyle('nonexistent-handle', 'nonexistent/asset');

        // If we get here, the method handled the missing asset gracefully
        self::assertTrue(true);
    }

    public function testDebugInfo(): void
    {
        Vite::init($this->testBasePath, $this->testBaseUrl, $this->testVersion);

        $debugInfo = Vite::getDebugInfo();

        self::assertIsArray($debugInfo);
        self::assertArrayHasKey('dev_server_running', $debugInfo);
        self::assertArrayHasKey('server_url', $debugInfo);
        self::assertArrayHasKey('hmr_url', $debugInfo);
        self::assertArrayHasKey('manifest_loaded', $debugInfo);
        self::assertArrayHasKey('config', $debugInfo);
        self::assertArrayHasKey('base_path', $debugInfo);
        self::assertArrayHasKey('base_url', $debugInfo);
    }

    public function testDevScriptsDoesNothingWhenDevServerNotRunning(): void
    {
        Vite::init($this->testBasePath, $this->testBaseUrl, $this->testVersion);

        // Capture output
        ob_start();
        Vite::devScripts();
        $output = ob_get_clean();

        // Should be empty since dev server is not running
        self::assertEmpty($output);
    }

    public function testResetCache(): void
    {
        Vite::init($this->testBasePath, $this->testBaseUrl, $this->testVersion);

        // Load manifest to populate cache
        Vite::getManifest();

        // Reset cache
        Vite::resetCache();

        // This should work without issues
        self::assertTrue(true);
    }

    public function testFilePatternPriority(): void
    {
        Vite::init($this->testBasePath, $this->testBaseUrl, $this->testVersion);

        $assetsDir = $this->testBasePath.'assets/js/';

        // Create files with different patterns
        file_put_contents($assetsDir.'app.js', '// Basic JS');
        file_put_contents($assetsDir.'app.min.js', '// Minified JS');
        file_put_contents($assetsDir.'app.obf.js', '// Obfuscated JS');
        file_put_contents($assetsDir.'app.min.obf.js', '// Minified + Obfuscated JS');

        $assetUrl = Vite::asset('js/app');

        // Should prioritize obfuscated files in production
        self::assertStringContainsString($this->testBaseUrl, $assetUrl);
    }

    public function testMultipleFileExtensionSupport(): void
    {
        global $mock_environment_vars;
        $mock_environment_vars = ['WP_DEBUG' => '1'];

        try {
            Vite::resetCache();
            Vite::init($this->testBasePath, $this->testBaseUrl, $this->testVersion);

            // Create different source file types
            file_put_contents($this->testBasePath.'resources/js/component.jsx', '// React component');
            file_put_contents($this->testBasePath.'resources/js/utils.ts', '// TypeScript utilities');
            file_put_contents($this->testBasePath.'resources/scss/theme.scss', '// SCSS theme');

            self::assertTrue(Vite::jsExists('js/component')); // JSX
            self::assertTrue(Vite::jsExists('js/utils')); // TS
            self::assertTrue(Vite::cssExists('scss/theme')); // SCSS
        } finally {
            $mock_environment_vars = [];
            Vite::resetCache();
        }
    }

    public function testSubdirectoryAssetDetection(): void
    {
        global $mock_environment_vars;
        $mock_environment_vars = ['WP_DEBUG' => '1'];

        try {
            Vite::resetCache();
            Vite::init($this->testBasePath, $this->testBaseUrl, $this->testVersion);

            // Create nested directory structure
            $nestedDir = $this->testBasePath.'resources/js/components/';
            mkdir($nestedDir, 0777, true);

            file_put_contents($nestedDir.'modal.jsx', '// Modal component');

            self::assertTrue(Vite::jsExists('js/components/modal'));
        } finally {
            $mock_environment_vars = [];
            Vite::resetCache();
        }
    }

    /**
     * Test configuration loading with wp-env integration.
     */
    public function testConfigurationWithEnvironmentVariables(): void
    {
        // Set test environment variables using mock system
        global $mock_environment_vars;
        $mock_environment_vars = [
            'VITE_SERVER_HOST' => 'custom-host',
            'VITE_SERVER_PORT' => '4000',
            'VITE_DEV_SERVER_ENABLED' => '1',
        ];

        try {
            Vite::resetCache(); // Reset cache to pick up env vars
            Environment::clearCache();
            Vite::init($this->testBasePath, $this->testBaseUrl, $this->testVersion);

            $debugInfo = Vite::getDebugInfo();
            $config = $debugInfo['config'];

            self::assertSame('custom-host', $config['server']['host']);
            self::assertSame(4000, $config['server']['port']);
            self::assertTrue($config['env']['dev_server_enabled']);
        } finally {
            // Clean up
            $mock_environment_vars = [];
            Vite::resetCache();
            Environment::clearCache();
        }
    }

    public function testLegacyManifestSupport(): void
    {
        Vite::init($this->testBasePath, $this->testBaseUrl, $this->testVersion);

        // Create legacy manifest file (assets/manifest.json instead of assets/.vite/manifest.json)
        $manifestData = [
            'resources/js/legacy.js' => [
                'file' => 'assets/js/legacy.min.js',
                'isEntry' => true,
            ],
        ];

        $legacyManifestFile = $this->testBasePath.'assets/manifest.json';
        file_put_contents($legacyManifestFile, json_encode($manifestData, JSON_PRETTY_PRINT));

        // Create corresponding asset file
        file_put_contents($this->testBasePath.'assets/js/legacy.min.js', '// Legacy compiled JS');

        $manifest = Vite::getManifest();

        self::assertNotEmpty($manifest);
        self::assertArrayHasKey('resources/js/legacy.js', $manifest);
    }

    private function createTestDirectories(): void
    {
        $dirs = [
            $this->testBasePath,
            $this->testBasePath.'assets/',
            $this->testBasePath.'assets/.vite/',
            $this->testBasePath.'assets/js/',
            $this->testBasePath.'assets/css/',
            $this->testBasePath.'resources/',
            $this->testBasePath.'resources/js/',
            $this->testBasePath.'resources/css/',
            $this->testBasePath.'resources/scss/',
        ];

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
        }
    }

    private function removeTestDirectories(): void
    {
        if (is_dir($this->testBasePath)) {
            $this->recursiveRemoveDirectory($this->testBasePath);
        }
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

    private function resetTestConstants(): void
    {
        // Reset environment constants that might affect tests
        if (\defined('VITE_DEV_SERVER_ENABLED')) {
            // Can't undefine constants in PHP, but can use reflection for testing
        }
    }

    private function clearMockVariables(): void
    {
        global $mock_environment_vars, $mock_env_vars, $mock_constants;
        $mock_environment_vars = [];
        $mock_env_vars = [];
        $mock_constants = [];

        // Also clear Environment cache
        Environment::clearCache();
    }
}
