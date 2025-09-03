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
 * Edge case tests for the Vite class.
 *
 * @internal
 *
 * @coversNothing
 */
final class EdgeCaseTest extends TestCase
{
    private string $testBasePath;

    private string $testBaseUrl;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testBasePath = sys_get_temp_dir().'/wp-vite-edge-test/';
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

    public function testInitWithEmptyPaths(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Base path and base URL cannot be empty.');

        Vite::init('', '');
        Vite::getBasePath();
    }

    public function testInitWithNonExistentPath(): void
    {
        $nonExistentPath = '/non/existent/path/';

        // Should not throw exception during init
        Vite::init($nonExistentPath, $this->testBaseUrl);

        self::assertSame($nonExistentPath, Vite::getBasePath());
    }

    public function testAssetWithEmptyEntry(): void
    {
        Vite::init($this->testBasePath, $this->testBaseUrl);

        $result = Vite::asset('');

        self::assertIsString($result);
    }

    public function testAssetExistsWithSpecialCharacters(): void
    {
        Vite::init($this->testBasePath, $this->testBaseUrl);

        self::assertFalse(Vite::assetExists('js/file-with-dashes', 'js'));
        self::assertFalse(Vite::assetExists('js/file_with_underscores', 'js'));
        self::assertFalse(Vite::assetExists('js/file.with.dots', 'js'));
    }

    public function testEnqueueWithSpecialCharacters(): void
    {
        Vite::init($this->testBasePath, $this->testBaseUrl);

        // These should not throw exceptions
        Vite::enqueueScript('handle-with-dashes', 'js/nonexistent');
        Vite::enqueueScript('handle_with_underscores', 'js/nonexistent');
        Vite::enqueueStyle('style-handle', 'css/nonexistent');

        self::assertTrue(true);
    }

    public function testManifestWithMalformedData(): void
    {
        Vite::init($this->testBasePath, $this->testBaseUrl);

        // Create directories
        mkdir($this->testBasePath.'assets/.vite/', 0777, true);

        // Create manifest with missing file entry
        $malformedManifest = [
            'resources/js/broken.js' => [
                'isEntry' => true,
                // Missing 'file' key
            ],
            'resources/js/incomplete.js' => [
                'file' => 'assets/js/missing.js',
                'isEntry' => true,
            ],
        ];

        file_put_contents(
            $this->testBasePath.'assets/.vite/manifest.json',
            json_encode($malformedManifest)
        );

        $manifest = Vite::getManifest();

        self::assertNotEmpty($manifest);
        self::assertFalse(Vite::assetExists('js/broken', 'js'));
        self::assertFalse(Vite::assetExists('js/incomplete', 'js'));
    }

    public function testMultipleInitCalls(): void
    {
        // Multiple inits should work without issues
        Vite::init($this->testBasePath, $this->testBaseUrl, '1.0.0');
        Vite::init($this->testBasePath, $this->testBaseUrl, '2.0.0');

        self::assertSame($this->testBasePath, Vite::getBasePath());
        self::assertSame($this->testBaseUrl, Vite::getBaseUrl());
    }

    public function testAssetUrlsWithDoubleSlashes(): void
    {
        Vite::init($this->testBasePath.'/', $this->testBaseUrl.'/');

        $debugInfo = Vite::getDebugInfo();

        // Should handle trailing slashes properly
        self::assertSame($this->testBasePath, $debugInfo['base_path']);
        self::assertSame($this->testBaseUrl, $debugInfo['base_url']);
    }

    public function testEnqueueWithEmptyDependencies(): void
    {
        Vite::init($this->testBasePath, $this->testBaseUrl);

        // Create a test file
        mkdir($this->testBasePath.'resources/js/', 0777, true);
        file_put_contents($this->testBasePath.'resources/js/test.js', '// test');

        // Should work with empty dependencies
        Vite::enqueueScript('test-handle', 'js/test', []);
        Vite::enqueueStyle('style-handle', 'js/test', []);

        self::assertTrue(true);
    }

    public function testEnqueueWithNullAttributes(): void
    {
        Vite::init($this->testBasePath, $this->testBaseUrl);

        // Create a test file
        mkdir($this->testBasePath.'resources/js/', 0777, true);
        file_put_contents($this->testBasePath.'resources/js/test.js', '// test');

        // Should work with empty attributes
        Vite::enqueueScript('test-handle', 'js/test', [], true, []);

        self::assertTrue(true);
    }

    public function testAssetDetectionWithCaseVariations(): void
    {
        global $mock_environment_vars;
        $mock_environment_vars = ['WP_DEBUG' => '1'];

        try {
            Vite::resetCache();
            Environment::clearCache();
            Vite::init($this->testBasePath, $this->testBaseUrl);

            // Create files with different cases
            mkdir($this->testBasePath.'resources/js/', 0777, true);
            file_put_contents($this->testBasePath.'resources/js/TestFile.js', '// test');
            file_put_contents($this->testBasePath.'resources/js/testfile.js', '// test');

            // Check exact case matching
            self::assertTrue(Vite::jsExists('js/TestFile'));
            self::assertTrue(Vite::jsExists('js/testfile'));
            self::assertFalse(Vite::jsExists('js/TESTFILE'));
        } finally {
            $mock_environment_vars = [];
            Vite::resetCache();
            Environment::clearCache();
        }
    }

    public function testProductionAssetPriorityWithMixedFiles(): void
    {
        Vite::init($this->testBasePath, $this->testBaseUrl);

        // Create assets directory and mixed files
        mkdir($this->testBasePath.'assets/js/', 0777, true);

        $files = [
            'app.js' => '// basic',
            'app.min.js' => '// minified',
            'app.obf.js' => '// obfuscated',
            'app.min.obf.js' => '// min+obf',
        ];

        foreach ($files as $filename => $content) {
            file_put_contents($this->testBasePath.'assets/js/'.$filename, $content);
        }

        $assetUrl = Vite::asset('js/app');

        // Should find some version of the asset
        self::assertStringContainsString($this->testBaseUrl, $assetUrl);
        self::assertStringContainsString('app', $assetUrl);
    }

    public function testDebugInfoWithMissingDirectories(): void
    {
        // Initialize with non-existent directories
        Vite::init('/tmp/nonexistent/', 'https://example.com/nonexistent/');

        $debugInfo = Vite::getDebugInfo();

        self::assertIsArray($debugInfo);
        self::assertArrayHasKey('dev_server_running', $debugInfo);
        self::assertArrayHasKey('config', $debugInfo);
        self::assertFalse($debugInfo['dev_server_running']);
    }

    public function testResetCacheMultipleTimes(): void
    {
        Vite::init($this->testBasePath, $this->testBaseUrl);

        // Load some data
        Vite::getManifest();
        Vite::getDebugInfo();

        // Reset multiple times
        Vite::resetCache();
        Vite::resetCache();
        Vite::resetCache();

        // Should still work
        $debugInfo = Vite::getDebugInfo();
        self::assertIsArray($debugInfo);
    }

    public function testAssetExistsWithInvalidTypes(): void
    {
        Vite::init($this->testBasePath, $this->testBaseUrl);

        // Test with invalid asset types
        self::assertFalse(Vite::assetExists('js/test', 'invalid'));
        self::assertFalse(Vite::assetExists('js/test', ''));
        self::assertFalse(Vite::assetExists('js/test', 'php'));
    }

    public function testDevScriptsWithoutDevServer(): void
    {
        Vite::init($this->testBasePath, $this->testBaseUrl);

        // Should not output anything when dev server is not running
        ob_start();
        Vite::devScripts();
        $output = ob_get_clean();

        self::assertEmpty($output);
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

        // Also clear Environment cache
        Environment::clearCache();
    }
}
