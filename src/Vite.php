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

namespace WpSpaghetti\WpVite;

use WpSpaghetti\WpEnv\Environment;
use WpSpaghetti\WpLogger\Logger;

if (!\defined('WPINC')) {
    exit;
}

/**
 * Vite integration service with enhanced Docker and environment support.
 *
 * Supports flexible server/HMR configuration for maximum Docker compatibility.
 * Compatible with obfuscated assets and timestamp-based cache busting.
 */
class Vite
{
    private const DEFAULT_SERVER_HOST = 'localhost';

    private const DEFAULT_SERVER_PORT = 3000;

    private const DEFAULT_HMR_HOST = 'localhost';

    private const DEFAULT_HMR_PORT = 3000;

    private const MIN_PATH_PARTS_FOR_DETECTION = 2;

    /**
     * Source to output extension mapping.
     */
    private const EXTENSION_MAP = [
        'css' => ['scss', 'sass', 'css'],
        'js' => ['js', 'ts', 'jsx', 'tsx'],
    ];

    /**
     * Extension to folder mapping for resource files.
     */
    private const EXTENSION_TO_FOLDER = [
        'scss' => 'scss',
        'sass' => 'scss',
        'css' => 'css',
        'js' => 'js',
        'ts' => 'js',
        'jsx' => 'js',
        'tsx' => 'js',
    ];

    /**
     * File pattern priority in production (regex patterns).
     */
    private const PRODUCTION_PATTERNS = [
        '/^(.+)\.min\.obf\.(js|css)$/',  // name.min.obf.js/css
        '/^(.+)\.obf\.(js|css)$/',       // name.obf.js/css
        '/^(.+)\.min\.(js|css)$/',       // name.min.js/css
        '/^(.+)\.(js|css)$/',            // name.js/css
    ];

    /**
     * File pattern priority in development (regex patterns).
     */
    private const DEVELOPMENT_PATTERNS = [
        '/^(.+)\.(js|css)$/',            // name.js/css
        '/^(.+)\.min\.(js|css)$/',       // name.min.js/css
        '/^(.+)\.obf\.(js|css)$/',       // name.obf.js/css
        '/^(.+)\.min\.obf\.(js|css)$/',  // name.min.obf.js/css
    ];

    // Cache for performance
    private static ?bool $isDevServer = null;

    private static array $manifest = [];

    private static array $config = [];

    // Configuration properties
    private static string $basePath = '';

    private static string $baseUrl = '';

    private static string $version = '1.0.0';

    private static string $componentName = '';

    // Performance caches
    private static string $componentEnvPrefix = '';

    // Initialization flag
    private static bool $initialized = false;

    // Logger instance
    private static ?Logger $logger = null;

    /**
     * Initialize WpVite with base paths, configuration and component-specific  settings.
     *
     * @param string $basePath      Base filesystem path (plugin/theme directory)
     * @param string $baseUrl       Base URL (plugin/theme directory URL)
     * @param string $version       Plugin/theme version for cache busting
     * @param string $componentName Optional plugin/theme name for environment variables prefix (auto-detected if empty)
     */
    public static function init(string $basePath, string $baseUrl, string $version = '1.0.0', string $componentName = ''): void
    {
        if (empty($basePath) || empty($baseUrl)) {
            throw new \InvalidArgumentException('Base path and base URL cannot be empty.');
        }

        self::$basePath = trailingslashit($basePath);
        self::$baseUrl = trailingslashit($baseUrl);
        self::$version = $version;
        self::$componentName = $componentName ?: self::detectComponentName($basePath);
        self::$initialized = true;

        // Generate and cache component environment prefix
        self::$componentEnvPrefix = self::generateComponentEnvPrefix(self::$componentName);

        // Reset only the caches, not the initialization flag
        self::$isDevServer = null;
        self::$manifest = [];
        self::$config = [];
        self::$logger = null;
    }

    /**
     * Get component name (plugin/theme) used for environment variable prefixes.
     */
    public static function getComponentName(): string
    {
        if (!self::$initialized) {
            throw new \RuntimeException('WpVite not initialized. Call WpVite::init() first.');
        }

        return self::$componentName;
    }

    /**
     * Get base path (plugin/theme directory path).
     */
    public static function getBasePath(): string
    {
        if (!self::$initialized) {
            throw new \RuntimeException('WpVite not initialized. Call WpVite::init() first.');
        }

        return self::$basePath;
    }

    /**
     * Get base URL (plugin/theme directory URL).
     */
    public static function getBaseUrl(): string
    {
        if (!self::$initialized) {
            throw new \RuntimeException('WpVite not initialized. Call WpVite::init() first.');
        }

        return self::$baseUrl;
    }

    /**
     * Check if Vite dev server is running with improved caching and error handling.
     */
    public static function isDevServer(): bool
    {
        // In test environment, never use dev server to avoid flaky tests
        if (Environment::isTesting()) {
            self::$isDevServer = false;

            return false;
        }

        if (null !== self::$isDevServer) {
            return self::$isDevServer;
        }

        $config = self::getConfig();

        // Skip check if disabled or not in debug mode
        if (!$config['env']['debug_mode'] || !$config['env']['dev_server_enabled']) {
            self::$isDevServer = false;

            return false;
        }

        $serverUrl = self::getServerUrl();

        // Create context for HTTP request
        $contextOptions = [
            'http' => [
                'timeout' => $config['env']['dev_check_timeout'],
                'method' => 'HEAD',
                'ignore_errors' => true,
                'user_agent' => 'WordPress/Vite Dev Check',
            ],
        ];

        // SSL context for HTTPS
        if (str_starts_with($serverUrl, 'https://')) {
            $contextOptions['ssl'] = [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
                'capture_peer_cert' => false,
            ];
        }

        $context = stream_context_create($contextOptions);

        // Suppress errors and warnings
        $headers = @get_headers($serverUrl, true, $context);

        // Check if we got a response (even error responses indicate server is running)
        $isRunning = false !== $headers;
        self::$isDevServer = $isRunning;

        // Log dev server status
        $status = $isRunning ? 'running' : 'not running';
        self::logger()->debug('Vite dev server check: {url} - {status}', [
            'url' => $serverUrl,
            'status' => $status,
        ]);

        return self::$isDevServer;
    }

    /**
     * Get asset URL with improved logic for dev/production modes.
     */
    public static function asset(string $entry): string
    {
        // Development: use Vite dev server
        if (self::isDevServer()) {
            return self::getDevAssetUrl($entry);
        }

        // Production: use compiled assets with manifest
        return self::getProductionAssetUrl($entry);
    }

    /**
     * Enqueue script with automatic existence check.
     */
    public static function enqueueScript(
        string $handle,
        string $entry,
        array $deps = [],
        bool $inFooter = true,
        array $attributes = []
    ): void {
        // Check if asset exists before enqueuing
        if (!self::assetExists($entry, 'js')) {
            if (Environment::isDebug()) {
                self::logger()->debug('JavaScript asset not found for entry: {entry}', [
                    'entry' => $entry,
                ]);
            }

            return;
        }

        if (self::isDevServer()) {
            // Development: enqueue from dev server
            $assetUrl = self::getDevAssetUrl($entry, 'js');

            wp_enqueue_script(
                $handle,
                $assetUrl,
                $deps,
                null, // No version in dev
                $inFooter
            );

            // Add module type and custom attributes for ES modules
            self::addScriptAttributes($handle, array_merge(['type' => 'module'], $attributes));

            self::logger()->debug('Enqueued development script: {handle} -> {url}', [
                'handle' => $handle,
                'url' => $assetUrl,
            ]);
        } else {
            // Production: use compiled assets
            $jsFile = self::getProductionAsset($entry, 'js');

            if ($jsFile) {
                wp_enqueue_script(
                    $handle,
                    $jsFile,
                    $deps,
                    self::getFileVersion($jsFile),
                    $inFooter
                );

                // Add custom attributes if provided
                if (!empty($attributes)) {
                    self::addScriptAttributes($handle, $attributes);
                }

                self::logger()->debug('Enqueued production script: {handle} -> {url}', [
                    'handle' => $handle,
                    'url' => $jsFile,
                ]);
            }
        }
    }

    /**
     * Enqueue style with automatic existence check.
     */
    public static function enqueueStyle(
        string $handle,
        string $entry,
        array $deps = [],
        string $media = 'all'
    ): void {
        // Check if asset exists before enqueuing
        if (!self::assetExists($entry, 'css')) {
            if (Environment::isDebug()) {
                self::logger()->debug('CSS asset not found for entry: {entry}', [
                    'entry' => $entry,
                ]);
            }

            return;
        }

        if (self::isDevServer()) {
            // Development: CSS is injected by Vite, but register placeholder for dependency management
            $assetUrl = self::getDevAssetUrl($entry, 'css');

            wp_register_style(
                $handle,
                $assetUrl,
                $deps,
                null, // No version in dev
                $media
            );

            wp_enqueue_style($handle, '', $deps, null, $media);

            self::logger()->debug('Registered development style: {handle} -> {url}', [
                'handle' => $handle,
                'url' => $assetUrl,
            ]);
        } else {
            // Production: use compiled CSS
            $cssFile = self::getProductionAsset($entry, 'css');

            if ($cssFile) {
                wp_enqueue_style(
                    $handle,
                    $cssFile,
                    $deps,
                    self::getFileVersion($cssFile),
                    $media
                );

                self::logger()->debug('Enqueued production style: {handle} -> {url}', [
                    'handle' => $handle,
                    'url' => $cssFile,
                ]);
            }
        }
    }

    /**
     * Check if CSS asset exists.
     */
    public static function cssExists(string $entry): bool
    {
        return self::assetExists($entry, 'css');
    }

    /**
     * Check if JS asset exists.
     */
    public static function jsExists(string $entry): bool
    {
        return self::assetExists($entry, 'js');
    }

    /**
     * Check if asset exists for the given entry path and type.
     * Unified method that works for both dev and production modes.
     */
    public static function assetExists(string $entry, string $type): bool
    {
        $manifest = self::getManifest();

        // If manifest available, check it first
        if (!empty($manifest)) {
            $possibleKeys = self::generateManifestKeys($entry, $type);

            foreach ($possibleKeys as $possibleKey) {
                if (isset($manifest[$possibleKey])) {
                    // Verify the file actually exists if manifest entry found
                    if (self::verifyManifestFile($manifest[$possibleKey])) {
                        return true;
                    }
                }
            }
        }

        // Fallback to filesystem check
        $isDevMode = self::isDevServer();
        $foundFile = self::findFile($entry, $type, $isDevMode);

        if (null !== $foundFile) {
            return true;
        }

        // In test/staging environment or when WP_DEBUG is enabled, also check the opposite mode
        if (Environment::isStaging() || Environment::isDebug()) {
            $foundFile = self::findFile($entry, $type, !$isDevMode);
            if (null !== $foundFile) {
                return true;
            }
        }

        return false;
    }

    /**
     * Output Vite development scripts with enhanced configuration.
     */
    public static function devScripts(): void
    {
        if (!self::isDevServer()) {
            return;
        }

        $hmrUrl = self::getHmrUrl();

        // Vite client for HMR
        echo '<script type="module" src="'.esc_url($hmrUrl.'/@vite/client').'"></script>'."\n";

        // Optional: React Refresh (if using React)
        if (self::getEnvironmentValueBool('REACT_REFRESH')) {
            echo '<script type="module">
                import RefreshRuntime from "'.esc_url($hmrUrl.'/@react-refresh').'"
                RefreshRuntime.injectIntoGlobalHook(window)
                window.$RefreshReg$ = () => {}
                window.$RefreshSig$ = () => (type) => type
                window.__vite_plugin_react_preamble_installed__ = true
            </script>'."\n";
        }

        self::logger()->debug('Output Vite dev scripts for HMR: {url}', [
            'url' => $hmrUrl,
        ]);
    }

    /**
     * Get manifest data with improved error handling and caching.
     */
    public static function getManifest(): array
    {
        if (!empty(self::$manifest)) {
            return self::$manifest;
        }

        // Try new manifest location first, then fallback to legacy
        $manifestPaths = [
            self::getManifestPath(),
            self::getLegacyManifestPath(),
        ];

        foreach ($manifestPaths as $manifestPath) {
            if (file_exists($manifestPath)) {
                $manifestContent = file_get_contents($manifestPath);

                if ($manifestContent) {
                    $decoded = json_decode($manifestContent, true);

                    if (JSON_ERROR_NONE === json_last_error() && \is_array($decoded)) {
                        self::$manifest = $decoded;

                        self::logger()->debug('Loaded manifest from: {path}', [
                            'path' => $manifestPath,
                            'entries' => \count($decoded),
                        ]);

                        break;
                    }

                    self::logger()->warning('Invalid JSON in manifest file: {path}', [
                        'path' => $manifestPath,
                        'error' => json_last_error_msg(),
                    ]);
                }
            }
        }

        // Log warning if manifest not found in production
        if (empty(self::$manifest) && Environment::isProduction()) {
            self::logger()->warning('Manifest file not found. Run "npm run build" to generate assets.', [
                'checked_paths' => $manifestPaths,
            ]);
        }

        return self::$manifest;
    }

    /**
     * Get debug information for troubleshooting.
     */
    public static function getDebugInfo(): array
    {
        $config = self::getConfig();

        return [
            'dev_server_running' => self::isDevServer(),
            'server_url' => self::getServerUrl(),
            'hmr_url' => self::getHmrUrl(),
            'manifest_loaded' => !empty(self::getManifest()),
            'manifest_entries' => \count(self::getManifest()),
            'config' => $config,
            'base_path' => self::$basePath,
            'base_url' => self::$baseUrl,
            'component_name' => self::$componentName, // Component name (plugin/theme)
            'component_env_prefix' => self::$componentEnvPrefix, // Environment prefix cache
            'environment_type' => Environment::get('WP_ENVIRONMENT_TYPE', Environment::get('WP_ENV', 'production')),
            'is_debug' => Environment::isDebug(),
            'is_docker' => Environment::isDocker(),
        ];
    }

    /**
     * Reset cached values (useful for testing).
     */
    public static function resetCache(): void
    {
        self::$isDevServer = null;
        self::$manifest = [];
        self::$config = [];
        self::$logger = null;

        // For testing environment, clear Environment cache
        if (Environment::isStaging()) {
            Environment::clearCache();
        }
    }

    /**
     * Reset initialization flag (for testing only).
     *
     * @internal
     */
    public static function resetForTesting(): void
    {
        self::resetCache();
        self::$initialized = false;
        self::$basePath = '';
        self::$baseUrl = '';
        self::$version = '1.0.0';
        self::$componentName = '';
        self::$componentEnvPrefix = '';
    }

    /**
     * Get logger instance with component-specific configuration.
     */
    private static function logger(): Logger
    {
        if (!self::$logger instanceof Logger) {
            // Use Environment class to detect testing environment
            $minLogLevel = Environment::isTesting() ? 'emergency' : (Environment::isDebug() ? 'debug' : 'info');

            self::$logger = new Logger([
                'component_name' => self::$componentName,
                'min_log_level' => $minLogLevel,
            ]);
        }

        return self::$logger;
    }

    /**
     * Get manifest path.
     */
    private static function getManifestPath(): string
    {
        return self::getBasePath().'assets/.vite/manifest.json';
    }

    /**
     * Get legacy manifest path.
     */
    private static function getLegacyManifestPath(): string
    {
        return self::getBasePath().'assets/manifest.json';
    }

    /**
     * Get configuration with environment variable support and caching.
     */
    private static function getConfig(): array
    {
        // In test environment, don't use cache to ensure fresh config reads
        if (Environment::isStaging() && !empty(self::$config)) {
            // Clear wp-env cache and our config cache for tests
            Environment::clearCache();
            self::$config = [];
        }

        if (!empty(self::$config)) {
            return self::$config;
        }

        // Determine Docker-aware defaults
        $isDockerEnv = Environment::isDocker();
        // For Docker internal communication, use container name if we're inside Docker
        $defaultServerHost = $isDockerEnv ? 'node' : self::DEFAULT_SERVER_HOST;

        self::$config = [
            // Server configuration (main Vite dev server)
            'server' => [
                'host' => self::getEnvironmentValue('SERVER_HOST', $defaultServerHost),
                'port' => self::getEnvironmentValueInt('SERVER_PORT', self::DEFAULT_SERVER_PORT),
                'https' => self::getEnvironmentValueBool('SERVER_HTTPS'),
            ],

            // HMR configuration (Hot Module Replacement WebSocket)
            'hmr' => [
                'protocol' => self::getEnvironmentValue('HMR_PROTOCOL') ?: (self::getEnvironmentValueBool('HMR_HTTPS') ? 'wss' : 'ws'),
                'host' => self::getEnvironmentValue('HMR_HOST', self::DEFAULT_HMR_HOST),
                'port' => self::getEnvironmentValueInt('HMR_PORT', self::getEnvironmentValueInt('SERVER_PORT', self::DEFAULT_HMR_PORT)),
                'client_port' => self::getEnvironmentValueInt('HMR_CLIENT_PORT', self::getEnvironmentValueInt('HMR_PORT', self::getEnvironmentValueInt('SERVER_PORT', self::DEFAULT_HMR_PORT))),
                'https' => self::getEnvironmentValueBool('HMR_HTTPS'),
            ],

            // Build configuration
            'build' => [
                'out_dir' => self::getEnvironmentValue('OUT_DIR', 'assets'),
                'manifest_file' => self::getEnvironmentValue('MANIFEST_FILE', '.vite/manifest.json'),
            ],

            // Environment detection
            'env' => [
                'is_docker' => $isDockerEnv,
                'debug_mode' => Environment::isDebug(),
                'dev_server_enabled' => self::getEnvironmentValueBool('DEV_SERVER_ENABLED', true),
                'dev_check_timeout' => self::getEnvironmentValueInt('DEV_CHECK_TIMEOUT', 1),
                'cache_busting_enabled' => self::getEnvironmentValueBool('CACHE_BUSTING_ENABLED', Environment::getBool('CACHE_BUSTING_ENABLED', false)),
            ],
        ];

        return self::$config;
    }

    /**
     * Get environment value with component-specific prefix priority.
     *
     * Priority: Component-specific > Global VITE_ > Default
     */
    private static function getEnvironmentValue(string $key, ?string $default = null): ?string
    {
        // Component-specific environment variable (highest priority)
        $componentKey = self::$componentEnvPrefix.$key;
        $componentValue = Environment::get($componentKey);
        if (null !== $componentValue) {
            return $componentValue;
        }

        // Global VITE_ environment variable
        $globalKey = 'VITE_'.$key;

        return Environment::get($globalKey, $default);
    }

    /**
     * Get environment value as boolean with component-specific prefix priority.
     */
    private static function getEnvironmentValueBool(string $key, bool $default = false): bool
    {
        // Component-specific environment variable (highest priority)
        $componentKey = self::$componentEnvPrefix.$key;
        if (null !== Environment::get($componentKey)) {
            return Environment::getBool($componentKey, $default);
        }

        // Global VITE_ environment variable
        $globalKey = 'VITE_'.$key;

        return Environment::getBool($globalKey, $default);
    }

    /**
     * Get environment value as integer with component-specific prefix priority.
     */
    private static function getEnvironmentValueInt(string $key, int $default = 0): int
    {
        // Component-specific environment variable (highest priority)
        $componentKey = self::$componentEnvPrefix.$key;
        if (null !== Environment::get($componentKey)) {
            return Environment::getInt($componentKey, $default);
        }

        // Global VITE_ environment variable
        $globalKey = 'VITE_'.$key;

        return Environment::getInt($globalKey, $default);
    }

    /**
     * Generate and cache component-specific environment variable prefix.
     */
    private static function generateComponentEnvPrefix(string $componentName): string
    {
        $componentPrefix = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '_', $componentName) ?? $componentName);

        return $componentPrefix.'_VITE_';
    }

    /**
     * Derive component name (plugin/theme) from base path if not provided.
     */
    private static function detectComponentName(string $basePath): string
    {
        // Normalize path separators and remove trailing slash
        $normalizedPath = rtrim(str_replace('\\', '/', $basePath), '/');
        $pathParts = explode('/', $normalizedPath);

        // Get the last directory name
        $lastPart = end($pathParts);

        // Handle edge cases
        if (!$lastPart || 'wp-content' === $lastPart) {
            return 'wp-vite';
        }

        // Special handling for WordPress themes and plugins
        if (\count($pathParts) >= self::MIN_PATH_PARTS_FOR_DETECTION) {
            $parentDir = $pathParts[\count($pathParts) - 2];

            // For themes, consider parent/child theme scenarios
            if ('themes' === $parentDir) {
                if (str_ends_with($lastPart, '-child')) {
                    $lastPart = preg_replace('/-child$/', '', $lastPart) ?? $lastPart;
                }

                return preg_replace('/[^a-zA-Z0-9]/', '-', $lastPart) ?? $lastPart;
            }

            // For plugins, use the plugin directory name
            if ('plugins' === $parentDir) {
                return preg_replace('/[^a-zA-Z0-9]/', '-', $lastPart) ?? $lastPart;
            }
        }

        // Fallback: clean the last directory name
        return preg_replace('/[^a-zA-Z0-9]/', '-', $lastPart) ?? $lastPart;
    }

    /**
     * Generate file paths for given entry and type using EXTENSION_MAP.
     */
    private static function generateFilePaths(string $entry, string $type, bool $isDevMode = false): array
    {
        $paths = [];
        $cleanEntry = ltrim($entry, '/');
        $baseEntry = preg_replace('/^(css|js)\//', '', $cleanEntry);

        $sourceExtensions = self::EXTENSION_MAP[$type] ?? [$type];
        $config = self::getConfig();
        $basePath = $isDevMode ? self::getBasePath().'resources' : self::getBasePath().$config['build']['out_dir'];

        foreach ($sourceExtensions as $sourceExtension) {
            if ($isDevMode) {
                // Get the appropriate folder for this extension
                $folder = self::EXTENSION_TO_FOLDER[$sourceExtension] ?? $type;

                // Generate paths for the extension-specific folder
                $paths[] = $basePath.'/'.$folder.'/'.$baseEntry.'.'.$sourceExtension;

                // Also try with the original entry structure
                if (str_contains($cleanEntry, '/')) {
                    $paths[] = $basePath.'/'.$cleanEntry.'.'.$sourceExtension;
                }
            } else {
                // Production: patterns are handled by findAssetByPattern
                $paths[] = $basePath.'/'.$type.'/'.$baseEntry.'.'.$sourceExtension;
                $paths[] = $basePath.'/'.$type.'/'.$cleanEntry.'.'.$sourceExtension;
            }
        }

        return array_unique($paths);
    }

    /**
     * Generate possible manifest keys for an entry and type using EXTENSION_MAP.
     */
    private static function generateManifestKeys(string $entry, string $type): array
    {
        $keys = [];
        $cleanEntry = ltrim($entry, '/');
        $baseEntry = preg_replace('/^(css|js)\//', '', $cleanEntry);

        $sourceExtensions = self::EXTENSION_MAP[$type] ?? [$type];

        foreach ($sourceExtensions as $sourceExtension) {
            // Get the appropriate folder for this extension
            $folder = self::EXTENSION_TO_FOLDER[$sourceExtension] ?? $type;

            // Generate keys for the extension-specific folder
            $keys[] = \sprintf('resources/%s/%s.%s', $folder, $baseEntry, $sourceExtension);
            $keys[] = \sprintf('resources/%s/%s.%s', $folder, $cleanEntry, $sourceExtension);
        }

        return array_unique($keys);
    }

    /**
     * Unified file finder that works for both dev and production modes.
     */
    private static function findFile(string $entry, string $type, bool $isDevMode = false): ?string
    {
        if ($isDevMode) {
            // Development: check source files in resources
            $paths = self::generateFilePaths($entry, $type, true);

            foreach ($paths as $path) {
                if (file_exists($path)) {
                    return $path;
                }
            }
        } else {
            // Production: first try direct patterns, then regex patterns
            $paths = self::generateFilePaths($entry, $type, false);

            foreach ($paths as $path) {
                if (file_exists($path)) {
                    return $path;
                }
            }

            // Fallback to regex pattern matching for production assets
            return self::findAssetByPattern($entry, $type);
        }

        return null;
    }

    /**
     * Find asset file using regex patterns (production only).
     */
    private static function findAssetByPattern(string $entry, string $type): ?string
    {
        $config = self::getConfig();
        $cleanEntry = ltrim($entry, '/');
        $baseEntry = preg_replace('/^(css|js)\//', '', $cleanEntry);
        $assetsDir = self::getBasePath().$config['build']['out_dir'].('/'.$type);

        if (!is_dir($assetsDir)) {
            return null;
        }

        // Get all files in the type directory
        $files = glob($assetsDir.'/*.{js,css}', GLOB_BRACE) ?: [];

        // Determine search patterns based on environment
        $isProduction = Environment::isProduction();
        $patterns = $isProduction ? self::PRODUCTION_PATTERNS : self::DEVELOPMENT_PATTERNS;

        foreach ($patterns as $pattern) {
            foreach ($files as $file) {
                $filename = basename($file);

                if (preg_match($pattern, $filename, $matches)) {
                    $name = $matches[1];
                    $ext = $matches[2];

                    // Check if this file matches our entry
                    if ($ext === $type && ($name === $baseEntry || $name === $cleanEntry)) {
                        return $file;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Verify that manifest file actually exists on filesystem.
     */
    private static function verifyManifestFile(array $manifestEntry): bool
    {
        if (!isset($manifestEntry['file'])) {
            return false;
        }

        $config = self::getConfig();
        $fullPath = self::getBasePath().$config['build']['out_dir'].'/'.$manifestEntry['file'];

        return file_exists($fullPath);
    }

    /**
     * Get server URL for internal communication (Docker-aware).
     */
    private static function getServerUrl(): string
    {
        $config = self::getConfig();

        $protocol = $config['server']['https'] ? 'https' : 'http';

        return \sprintf('%s://%s:%d', $protocol, $config['server']['host'], $config['server']['port']);
    }

    /**
     * Get HMR URL for client-side connections (public-facing).
     */
    private static function getHmrUrl(): string
    {
        $config = self::getConfig();

        $protocol = $config['hmr']['https'] ? 'https' : 'http';

        return \sprintf(
            '%s://%s:%d',
            $protocol,
            $config['hmr']['host'],
            $config['hmr']['client_port']
        );
    }

    /**
     * Get development asset URL.
     * Automatically detects the correct source file extension.
     */
    private static function getDevAssetUrl(string $entry, ?string $type = null): string
    {
        $hmrUrl = self::getHmrUrl();
        $cleanEntry = ltrim($entry, '/');

        // Auto-detect type if not provided
        if (!$type) {
            $type = str_starts_with($cleanEntry, 'css/') ? 'css' : 'js';
        }

        // Find the actual source file that exists
        $actualFile = self::findFile($entry, $type, true);

        if ($actualFile) {
            // Convert absolute path to URL path
            $relativePath = str_replace(self::getBasePath(), '', $actualFile);

            return $hmrUrl.'/'.ltrim($relativePath, '/');
        }

        // Fallback to default extensions if file not found
        $baseEntry = preg_replace('/^(css|js)\//', '', $cleanEntry);
        $sourceExtensions = self::EXTENSION_MAP[$type] ?? [$type];
        $defaultExtension = $sourceExtensions[0]; // Use first extension as default
        $defaultFolder = self::EXTENSION_TO_FOLDER[$defaultExtension] ?? $type;

        return $hmrUrl.\sprintf('/resources/%s/%s.%s', $defaultFolder, $baseEntry, $defaultExtension);
    }

    /**
     * Get production asset URL with fallback logic.
     */
    private static function getProductionAssetUrl(string $entry): string
    {
        // Try to determine type from entry
        $type = str_starts_with($entry, 'css/') ? 'css' : 'js';

        return self::getProductionAsset($entry, $type) ?? '';
    }

    /**
     * Get production asset URL for specific type.
     */
    private static function getProductionAsset(string $entry, string $type): ?string
    {
        $manifest = self::getManifest();
        $config = self::getConfig();

        // Try manifest first
        $possibleKeys = self::generateManifestKeys($entry, $type);

        foreach ($possibleKeys as $possibleKey) {
            if (isset($manifest[$possibleKey]['file'])) {
                $manifestEntry = $manifest[$possibleKey];
                $fullPath = self::getBasePath().$config['build']['out_dir'].'/'.$manifestEntry['file'];

                if (file_exists($fullPath)) {
                    $baseUrl = self::getBaseUrl().$config['build']['out_dir'].'/'.$manifestEntry['file'];

                    return self::applyTimestampCacheBusting($baseUrl, $fullPath, $config);
                }
            }
        }

        // Fallback to filesystem search
        $foundFile = self::findFile($entry, $type, false);

        if ($foundFile) {
            $relativePath = str_replace(self::getBasePath(), '', $foundFile);
            $baseUrl = self::getBaseUrl().ltrim($relativePath, '/');

            return self::applyTimestampCacheBusting($baseUrl, $foundFile, $config);
        }

        return null;
    }

    /**
     * Apply timestamp-based cache busting to asset URL if enabled.
     */
    private static function applyTimestampCacheBusting(string $url, string $filePath, array $config): string
    {
        if (!$config['env']['cache_busting_enabled']) {
            return $url;
        }

        if (!file_exists($filePath)) {
            return $url;
        }

        $timestamp = filemtime($filePath);
        if (false === $timestamp || 0 === $timestamp) {
            return $url;
        }

        // Extract the relative path from the full URL to work with
        $urlPath = str_replace(self::getBaseUrl(), '', $url);

        // Insert timestamp before the last extension
        // e.g., .min.js -> .min.1234567890.js, .css -> .1234567890.css
        $lastDotPos = strrpos($urlPath, '.');
        if (false !== $lastDotPos) {
            $beforeExt = substr($urlPath, 0, $lastDotPos);
            $extension = substr($urlPath, $lastDotPos);
            $timestampedPath = $beforeExt.'.'.$timestamp.$extension;
        } else {
            $timestampedPath = $urlPath.'.'.$timestamp;
        }

        return self::getBaseUrl().$timestampedPath;
    }

    /**
     * Get file version for cache busting.
     */
    private static function getFileVersion(string $url): ?string
    {
        $config = self::getConfig();

        // If cache busting is enabled, version is already in URL
        if ($config['env']['cache_busting_enabled']) {
            return null;
        }

        // Extract timestamp from URL if present
        if (preg_match('/\.(\d{10})\.(?:min\.)?(?:obf\.)?(?:js|css)$/', $url, $matches)) {
            return $matches[1];
        }

        // Fallback: try to get filemtime for local files
        $localPath = str_replace(self::getBaseUrl(), self::getBasePath(), $url);

        if (file_exists($localPath)) {
            return (string) filemtime($localPath);
        }

        // Ultimate fallback
        return self::$version;
    }

    /**
     * Add script attributes (like type="module").
     */
    private static function addScriptAttributes(string $handle, array $attributes): void
    {
        if (empty($attributes)) {
            return;
        }

        add_filter('script_loader_tag', static function ($tag, $handleFilter) use ($handle, $attributes) {
            if ($handleFilter !== $handle) {
                return $tag;
            }

            // Add attributes to script tag
            foreach ($attributes as $attr => $value) {
                if (true === $value) {
                    $tag = str_replace('<script ', \sprintf('<script %s ', $attr), $tag);
                } else {
                    $tag = str_replace('<script ', \sprintf('<script %s="%s" ', $attr, $value), $tag);
                }
            }

            return $tag;
        }, 10, 2);
    }
}
