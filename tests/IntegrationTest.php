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
 * Integration tests for wp-env and wp-logger functionality.
 *
 * @internal
 *
 * @coversNothing
 */
final class IntegrationTest extends TestCase
{
    private string $testBasePath;

    private string $testBaseUrl;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testBasePath = sys_get_temp_dir().'/wp-vite-integration-test/';
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

        // Clear global mock variables
        $this->clearMockVariables();
    }

    public function testEnvironmentIntegration(): void
    {
        // Test that Vite correctly uses Environment class
        Vite::init($this->testBasePath, $this->testBaseUrl);

        $debugInfo = Vite::getDebugInfo();

        // Check that environment detection is working
        self::assertArrayHasKey('environment_type', $debugInfo);
        self::assertArrayHasKey('is_debug', $debugInfo);
        self::assertArrayHasKey('is_docker', $debugInfo);

        // In test environment, expect 'testing' as environment type
        self::assertSame('testing', $debugInfo['environment_type']);

        // These should match what Environment class returns
        self::assertSame(Environment::isDebug(), $debugInfo['is_debug']);
        self::assertSame(Environment::isDocker(), $debugInfo['is_docker']);
    }

    public function testEnvironmentVariableHandling(): void
    {
        // Use global mock variables instead of putenv() for wp-env compatibility
        global $mock_environment_vars;
        $mock_environment_vars = [
            'VITE_SERVER_HOST' => 'test-host',
            'VITE_SERVER_PORT' => '5000',
            'VITE_SERVER_HTTPS' => 'true',
            'VITE_HMR_HOST' => 'hmr-host',
            'VITE_HMR_PORT' => '5001',
            'VITE_OUT_DIR' => 'custom-assets',
            'VITE_DEV_SERVER_ENABLED' => '1',
            'VITE_CACHE_BUSTING_ENABLED' => '1',
        ];

        try {
            // Reset cache to pick up new environment variables
            Vite::resetCache();
            Environment::clearCache();
            Vite::init($this->testBasePath, $this->testBaseUrl);

            $debugInfo = Vite::getDebugInfo();
            $config = $debugInfo['config'];

            // Verify server configuration
            self::assertSame('test-host', $config['server']['host']);
            self::assertSame(5000, $config['server']['port']);
            self::assertTrue($config['server']['https']);

            // Verify HMR configuration
            self::assertSame('hmr-host', $config['hmr']['host']);
            self::assertSame(5001, $config['hmr']['port']);

            // Verify build configuration
            self::assertSame('custom-assets', $config['build']['out_dir']);

            // Verify environment configuration
            self::assertTrue($config['env']['dev_server_enabled']);
            self::assertTrue($config['env']['cache_busting_enabled']);
        } finally {
            // Clean up environment variables
            $mock_environment_vars = [];

            // Reset cache after cleanup
            Vite::resetCache();
            Environment::clearCache();
        }
    }

    public function testDockerDetection(): void
    {
        Vite::init($this->testBasePath, $this->testBaseUrl);

        $debugInfo = Vite::getDebugInfo();

        // Docker detection should match Environment class
        self::assertSame(Environment::isDocker(), $debugInfo['is_docker']);
        self::assertSame(Environment::isDocker(), $debugInfo['config']['env']['is_docker']);
    }

    public function testBooleanEnvironmentVariables(): void
    {
        // Test various boolean formats using mock variables
        $testCases = [
            ['VITE_TEST_BOOL_1', '1', true],
            ['VITE_TEST_BOOL_2', 'true', true],
            ['VITE_TEST_BOOL_3', 'TRUE', true],
            ['VITE_TEST_BOOL_4', 'yes', true],
            ['VITE_TEST_BOOL_5', '0', false],
            ['VITE_TEST_BOOL_6', 'false', false],
            ['VITE_TEST_BOOL_7', 'no', false],
            ['VITE_TEST_BOOL_8', '', false],
        ];

        foreach ($testCases as [$envVar, $value, $expected]) {
            global $mock_environment_vars;
            $mock_environment_vars = [$envVar => $value];
            Environment::clearCache();

            // Environment should handle boolean parsing correctly
            $result = Environment::getBool($envVar);
            self::assertSame($expected, $result, \sprintf('Failed for %s=%s', $envVar, $value));

            $mock_environment_vars = [];
        }
    }

    public function testIntegerEnvironmentVariables(): void
    {
        global $mock_environment_vars;

        try {
            $mock_environment_vars = [
                'VITE_TEST_INT_1' => '123',
                'VITE_TEST_INT_2' => '0',
                'VITE_TEST_INT_3' => '-456',
            ];
            Environment::clearCache();

            self::assertSame(123, Environment::getInt('VITE_TEST_INT_1'));
            self::assertSame(0, Environment::getInt('VITE_TEST_INT_2'));
            self::assertSame(-456, Environment::getInt('VITE_TEST_INT_3'));
            self::assertSame(999, Environment::getInt('VITE_NONEXISTENT_INT', 999));
        } finally {
            $mock_environment_vars = [];
            Environment::clearCache();
        }
    }

    public function testEnvironmentTypeDetection(): void
    {
        Vite::init($this->testBasePath, $this->testBaseUrl);

        // Test that Vite uses Environment class for type detection
        $debugInfo = Vite::getDebugInfo();

        // Include 'testing' as a valid environment type for test suite
        self::assertContains($debugInfo['environment_type'], [
            'development',
            'staging',
            'production',
            'local',
            'testing',
        ]);

        // In test environment, WP_ENV is set to 'testing' in bootstrap
        // So we expect 'testing' to be returned
        self::assertSame('testing', $debugInfo['environment_type']);

        // Should match Environment class result
        self::assertSame(Environment::get('WP_ENVIRONMENT_TYPE', Environment::get('WP_ENV', 'production')), $debugInfo['environment_type']);
    }

    public function testCacheBustingWithTimestamps(): void
    {
        global $mock_environment_vars;
        $mock_environment_vars = [
            'VITE_CACHE_BUSTING_ENABLED' => '1',
        ];

        try {
            Vite::resetCache();
            Environment::clearCache();
            Vite::init($this->testBasePath, $this->testBaseUrl);

            // Create test directories and files
            mkdir($this->testBasePath.'assets/js/', 0777, true);
            $testFile = $this->testBasePath.'assets/js/test.min.js';
            file_put_contents($testFile, '// Test file');

            // Get asset URL
            $assetUrl = Vite::asset('js/test');

            // Should contain timestamp for cache busting
            self::assertStringContainsString($this->testBaseUrl, $assetUrl);
            // Check for timestamp in filename pattern: .min.TIMESTAMP.js
            self::assertMatchesRegularExpression('/\.min\.\d+\.js$/', $assetUrl);
        } finally {
            $mock_environment_vars = [];
            Vite::resetCache();
            Environment::clearCache();
        }
    }

    public function testHMRConfiguration(): void
    {
        global $mock_environment_vars;
        $mock_environment_vars = [
            'VITE_HMR_PROTOCOL' => 'wss',
            'VITE_HMR_HOST' => 'localhost',
            'VITE_HMR_PORT' => '3001',
            'VITE_HMR_CLIENT_PORT' => '3002',
        ];

        try {
            Vite::resetCache();
            Environment::clearCache();
            Vite::init($this->testBasePath, $this->testBaseUrl);

            $debugInfo = Vite::getDebugInfo();
            $config = $debugInfo['config'];

            self::assertSame('wss', $config['hmr']['protocol']);
            self::assertSame('localhost', $config['hmr']['host']);
            self::assertSame(3001, $config['hmr']['port']);
            self::assertSame(3002, $config['hmr']['client_port']);

            // HMR URL should be properly formatted
            self::assertStringStartsWith('http://localhost:3002', $debugInfo['hmr_url']);
        } finally {
            $mock_environment_vars = [];
            Vite::resetCache();
            Environment::clearCache();
        }
    }

    public function testProductionEnvironmentBehavior(): void
    {
        // Mock production environment
        global $mock_environment_vars;
        $mock_environment_vars = [
            'WP_ENV' => 'production',
            'WP_ENVIRONMENT_TYPE' => 'production',
        ];

        try {
            Vite::resetCache();
            Environment::clearCache();
            Vite::init($this->testBasePath, $this->testBaseUrl);

            $debugInfo = Vite::getDebugInfo();

            // In production, dev server should be disabled
            self::assertFalse($debugInfo['dev_server_running']);

            // Should detect production environment
            self::assertSame('production', $debugInfo['environment_type']);
        } finally {
            $mock_environment_vars = [];
            Vite::resetCache();
            Environment::clearCache();
        }
    }

    public function testDevelopmentEnvironmentBehavior(): void
    {
        // Mock development environment
        global $mock_environment_vars;
        $mock_environment_vars = [
            'WP_ENV' => 'development',
            'WP_ENVIRONMENT_TYPE' => 'development',
            'WP_DEBUG' => '1',
        ];

        try {
            Vite::resetCache();
            Environment::clearCache();
            Vite::init($this->testBasePath, $this->testBaseUrl);

            $debugInfo = Vite::getDebugInfo();

            // Should detect development environment
            self::assertSame('development', $debugInfo['environment_type']);
            self::assertTrue($debugInfo['is_debug']);
        } finally {
            $mock_environment_vars = [];
            Vite::resetCache();
            Environment::clearCache();
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

    private function clearMockVariables(): void
    {
        global $mock_environment_vars, $mock_env_vars, $mock_constants;
        $mock_environment_vars = [];
        $mock_env_vars = [];
        $mock_constants = [];
    }
}
