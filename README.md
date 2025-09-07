![PHP Version](https://img.shields.io/packagist/php-v/wp-spaghetti/wp-vite)
![GitHub Downloads (all assets, all releases)](https://img.shields.io/github/downloads/wp-spaghetti/wp-vite/total)
![GitHub Actions Workflow Status](https://github.com/wp-spaghetti/wp-vite/actions/workflows/main.yml/badge.svg)
![Coverage Status](https://img.shields.io/codecov/c/github/wp-spaghetti/wp-vite)
![GitHub Issues](https://img.shields.io/github/issues/wp-spaghetti/wp-vite)
![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen)
![GitHub Release](https://img.shields.io/github/v/release/wp-spaghetti/wp-vite)
![License](https://img.shields.io/github/license/wp-spaghetti/wp-vite)
<!--
![Code Climate](https://img.shields.io/codeclimate/maintainability/wp-spaghetti/wp-vite)
-->

# Wp Vite

A powerful Vite integration service for WordPress with enhanced Docker support, obfuscated assets handling, flexible cache busting, and comprehensive logging.

## Features

- **Docker Compatibility**: Automatic Docker environment detection with flexible server/HMR configuration
- **Hot Module Replacement (HMR)**: Full support for Vite's HMR with configurable hosts and ports
- **Obfuscated Assets Support**: Built-in handling for obfuscated JavaScript and CSS files (`.obf.js`, `.min.obf.css`)
- **Smart Asset Detection**: Automatic file discovery with priority-based pattern matching
- **Cache Busting**: Timestamp-based cache busting for production assets
- **Development/Production Modes**: Seamless switching between Vite dev server and compiled assets
- **Multiple File Extensions**: Support for JS, TS, JSX, TSX, CSS, SCSS, SASS
- **Subdirectory Support**: Organize assets in subdirectories with automatic detection
- **Extension Auto-Detection**: No need to specify file extensions - Wp Vite finds the right file automatically
- **Manifest Integration**: Full Vite manifest.json support for optimized production builds
- **Environment Management**: Built-in support for WordPress constants and .env files via [WP Env](https://github.com/wp-spaghetti/wp-env)
- **Comprehensive Logging**: PSR-3 compatible logging with [WP Logger](https://github.com/wp-spaghetti/wp-logger) integration
- **Zero External Dependencies**: Works with or without optional logging libraries

## Installation

Install via Composer:

```bash
composer require wp-spaghetti/wp-vite
```

## Quick Start

### 1. Initialize Wp Vite

In your plugin or theme, initialize Wp Vite with your base paths:

```php
<?php
use WpSpaghetti\WpVite\Vite;

// For plugins
Vite::init(
    plugin_dir_path(__FILE__), // Base path
    plugin_dir_url(__FILE__),  // Base URL
    '1.0.0'                    // Version
);

// For themes
Vite::init(
    get_template_directory() . '/',
    get_template_directory_uri() . '/',
    wp_get_theme()->get('Version')
);
```

### 2. Enqueue Assets

```php
// Enqueue JavaScript
Vite::enqueueScript('my-app', 'js/app', ['jquery'], true);

// Enqueue CSS
Vite::enqueueStyle('my-styles', 'css/main');

// Check if assets exist
if (Vite::jsExists('js/admin')) {
    Vite::enqueueScript('admin-script', 'js/admin');
}
```

### 3. Development Mode Setup

For optimal Hot Module Replacement (HMR), add the Vite client scripts in development:

```php
// In your theme's header.php or via wp_head hook
add_action('wp_head', function() {
    if (WP_DEBUG) {
        Vite::devScripts();
    }
});
```

**What does `devScripts()` do?**
- Injects the Vite WebSocket client (`/@vite/client`) for real-time HMR communication
- Adds React Refresh support (if enabled) for component-level hot reloading
- Without this, you'll only get basic live reload instead of full HMR capabilities

## Configuration

Wp Vite supports both WordPress-native `define()` constants and modern `.env` files for configuration through the integrated wp-env library. Additionally, it supports component-specific environment variables for multi-plugin/theme environments.

### Component-Specific Configuration

Wp Vite automatically detects your plugin or theme name and supports component-specific environment variables, allowing independent configuration for each component.

#### Component Name Detection

```php
// Auto-detected from path: "my-awesome-plugin"
Vite::init(
    plugin_dir_path(__FILE__), // /wp-content/plugins/my-awesome-plugin/
    plugin_dir_url(__FILE__),
    '1.0.0'
);

// Explicit component name
Vite::init(
    plugin_dir_path(__FILE__),
    plugin_dir_url(__FILE__),
    '1.0.0',
    'custom-component-name'  // Override auto-detection
);
```

#### Environment Variable Priority

1. **Component-specific**: `{COMPONENT_NAME}_VITE_{SETTING}` (highest priority)
2. **Global**: `VITE_{SETTING}`
3. **Default values**

```env
# Component-specific (for plugin "ecommerce-toolkit")
ECOMMERCE_TOOLKIT_VITE_SERVER_PORT=3001
ECOMMERCE_TOOLKIT_VITE_HMR_PORT=3002

# Global fallback
VITE_SERVER_PORT=3000
VITE_HMR_PORT=3000
```

### WordPress Constants (wp-config.php)
```php
// Global server configuration
define('VITE_SERVER_HOST', 'localhost');
define('VITE_SERVER_PORT', 3000);
define('VITE_SERVER_HTTPS', false);

// Component-specific (for plugin "my-plugin")
define('MY_PLUGIN_VITE_SERVER_PORT', 3001);
define('MY_PLUGIN_VITE_OUT_DIR', 'custom-assets');

// HMR configuration
define('VITE_HMR_HOST', 'localhost');
define('VITE_HMR_PORT', 3000);
define('VITE_HMR_CLIENT_PORT', 3000);
define('VITE_HMR_HTTPS', false);

// Feature toggles
define('VITE_DEV_SERVER_ENABLED', true);
define('VITE_CACHE_BUSTING_ENABLED', true);

// React Refresh support
define('VITE_REACT_REFRESH', true);
```

### Environment File (.env)
For modern setups like Bedrock, you can use `.env` files:

```env
# Global Vite dev server settings
VITE_SERVER_HOST=localhost
VITE_SERVER_PORT=3000
VITE_SERVER_HTTPS=false

# Plugin-specific settings
ADMIN_DASHBOARD_VITE_SERVER_PORT=3001
ECOMMERCE_VITE_SERVER_PORT=3002
ANALYTICS_VITE_SERVER_PORT=3003

# HMR (Hot Module Replacement) settings
VITE_HMR_HOST=localhost
VITE_HMR_PORT=3000
VITE_HMR_CLIENT_PORT=3000
VITE_HMR_HTTPS=false
VITE_HMR_PROTOCOL=ws

# Enable/disable features
VITE_DEV_SERVER_ENABLED=true
VITE_DEV_CHECK_TIMEOUT=1
VITE_CACHE_BUSTING_ENABLED=true

# React development
VITE_REACT_REFRESH=true
```

**Priority**: WordPress constants take precedence over `.env` files, and component-specific variables take precedence over global ones.

### Multi-Plugin/Theme Development

For complex WordPress installations with multiple plugins/themes:

```env
# Main plugin
MAIN_PLUGIN_VITE_SERVER_PORT=3001
MAIN_PLUGIN_VITE_OUT_DIR=main-assets

# E-commerce plugin  
ECOMMERCE_VITE_SERVER_PORT=3002
ECOMMERCE_VITE_OUT_DIR=shop-assets
ECOMMERCE_VITE_REACT_REFRESH=true

# Theme
MY_THEME_VITE_SERVER_PORT=3003
MY_THEME_VITE_OUT_DIR=theme-assets

# Global fallback for other components
VITE_SERVER_HOST=localhost
VITE_DEV_SERVER_ENABLED=true
```

### Docker Configuration
For Docker environments, Wp Vite automatically detects the container and adjusts configuration:

```env
# Docker-specific settings
VITE_SERVER_HOST=node  # Container name
VITE_HMR_HOST=localhost  # Public-facing host

# Component-specific Docker settings
ADMIN_PLUGIN_VITE_SERVER_HOST=admin-node
SHOP_PLUGIN_VITE_SERVER_HOST=shop-node
```

### Build Configuration
```env
VITE_OUT_DIR=assets
VITE_MANIFEST_FILE=.vite/manifest.json

# Component-specific build settings
MY_PLUGIN_VITE_OUT_DIR=custom-assets
MY_PLUGIN_VITE_MANIFEST_FILE=.vite/my-plugin-manifest.json
```

For advanced component-specific configuration, see [Component Configuration Guide](docs/component-configuration.md).

## File Structure

Wp Vite expects the following directory structure:

```
your-plugin/
├── assets/              # Built files (production)
│   ├── .vite/
│   │   └── manifest.json
│   ├── js/
│   │   ├── app.js
│   │   ├── app.min.js
│   │   └── app.min.obf.js
│   └── css/
│       ├── main.css
│       └── main.min.obf.css
└── resources/           # Source files (development)
    ├── js/
    │   ├── app.js
    │   ├── admin.ts
    │   └── components/
    │       └── modal.jsx
    ├── css/
    │   └── main.css
    └── scss/
        └── styles.scss
```

### Subdirectory Support

Wp Vite fully supports organizing your assets in subdirectories:

```php
// These all work automatically:
Vite::enqueueScript('modal', 'js/components/modal');  // No .jsx extension needed
Vite::enqueueStyle('admin', 'css/admin/dashboard');   // Finds .scss, .sass, or .css
Vite::enqueueScript('utils', 'js/utils/helpers');     // Auto-detects .ts or .js
```

**Why no file extensions?** Wp Vite automatically detects the correct source file extension (`.js`, `.ts`, `.jsx`, `.scss`, etc.) and maps it to the appropriate compiled output. This makes your code cleaner and more flexible.

## Asset Priority System

Wp Vite uses different file priority patterns for production and development:

### Production Priority
1. `.min.obf.js/css` (minified + obfuscated)
2. `.obf.js/css` (obfuscated only)
3. `.min.js/css` (minified only)
4. `.js/css` (standard)

### Development Priority
1. `.js/css` (standard)
2. `.min.js/css` (minified)
3. `.obf.js/css` (obfuscated)
4. `.min.obf.js/css` (minified + obfuscated)

## API Reference

### Core Methods

#### `Vite::init(string $basePath, string $baseUrl, string $version = '1.0.0', string $componentName = '')`
Initialize Wp Vite with your plugin/theme paths. This also initializes the integrated logger.

**Parameters:**
- `$basePath` - Base filesystem path (plugin/theme directory)
- `$baseUrl` - Base URL (plugin/theme directory URL)  
- `$version` - Plugin/theme version for cache busting
- `$componentName` - Optional component name for environment variables prefix (auto-detected if empty)

```php
// Auto-detect component name from path
Vite::init(
    plugin_dir_path(__FILE__),
    plugin_dir_url(__FILE__),
    '1.0.0'
);

// Explicit component name
Vite::init(
    plugin_dir_path(__FILE__),
    plugin_dir_url(__FILE__),
    '1.0.0',
    'my-custom-plugin'
);
```

#### `Vite::getPluginName(): string`
Get the detected or explicitly set component name (plugin/theme name).

```php
$componentName = Vite::getPluginName(); // Returns: "my-plugin"
```

#### `Vite::asset(string $entry): string`
Get the URL for any asset. Automatically switches between dev server and production URLs.

#### `Vite::enqueueScript(string $handle, string $entry, array $deps = [], bool $inFooter = true, array $attributes = [])`
Enqueue JavaScript files with automatic existence checking and logging.

#### `Vite::enqueueStyle(string $handle, string $entry, array $deps = [], string $media = 'all')`
Enqueue CSS files with automatic existence checking and logging.

### Utility Methods

#### `Vite::isDevServer(): bool`
Check if Vite dev server is running using environment-aware detection.

#### `Vite::jsExists(string $entry): bool`
Check if JavaScript asset exists with extension auto-detection.

#### `Vite::cssExists(string $entry): bool`
Check if CSS asset exists with extension auto-detection.

#### `Vite::devScripts(): void`
Output Vite client scripts for HMR (development only).

#### `Vite::getDebugInfo(): array`
Get comprehensive debug information including environment details for troubleshooting.

**Returns information about:**
- Component name and environment prefix
- Dev server status and URLs  
- Environment detection (Docker, debug mode, etc.)
- Configuration values (server, HMR, build settings)
- Manifest loading status

```php
$debugInfo = Vite::getDebugInfo();

// Component information
echo $debugInfo['component_name'];        // "my-plugin"
echo $debugInfo['component_env_prefix'];  // "MY_PLUGIN_VITE_"

// Environment detection
var_dump($debugInfo['is_docker']);        // true/false
var_dump($debugInfo['is_debug']);         // true/false
var_dump($debugInfo['environment_type']); // "development", "production", etc.

// Server configuration  
echo $debugInfo['server_url'];            // "http://localhost:3000"
echo $debugInfo['hmr_url'];              // "http://localhost:3000"

// Asset information
var_dump($debugInfo['dev_server_running']); // true/false
var_dump($debugInfo['manifest_loaded']);    // true/false
```
## Example Usage

### Plugin Integration

```php
<?php
/*
Plugin Name: My Awesome Plugin
*/

use WpSpaghetti\WpVite\Vite;

// Initialize on plugin load
add_action('init', function() {
    Vite::init(
        plugin_dir_path(__FILE__),
        plugin_dir_url(__FILE__),
        '1.2.3'
    );
});

// Enqueue frontend assets
add_action('wp_enqueue_scripts', function() {
    Vite::enqueueScript('my-plugin-app', 'js/app', ['jquery']);
    Vite::enqueueStyle('my-plugin-styles', 'css/main');
});

// Enqueue admin assets
add_action('admin_enqueue_scripts', function() {
    if (Vite::jsExists('js/admin')) {
        Vite::enqueueScript('my-plugin-admin', 'js/admin');
    }
});

// Add dev scripts in development
add_action('wp_head', function() {
    if (WP_DEBUG) {
        Vite::devScripts();
    }
});
```

### Traditional WordPress Setup (without Bedrock)

If you're not using Bedrock or modern WordPress setups, you can configure Wp Vite using WordPress constants in `wp-config.php`:

```php
<?php
// In wp-config.php

// Basic Vite configuration
define('VITE_DEV_SERVER_ENABLED', true);
define('VITE_SERVER_HOST', 'localhost');
define('VITE_SERVER_PORT', 3000);

// For Docker users
define('VITE_SERVER_HOST', 'node'); // Your Docker container name
define('VITE_HMR_HOST', 'localhost'); // Public-facing host

// Enable cache busting in production
define('VITE_CACHE_BUSTING_ENABLED', true);
```

Then in your plugin or theme:

```php
<?php
use WpSpaghetti\WpVite\Vite;

// Initialize
Vite::init(
    plugin_dir_path(__FILE__),
    plugin_dir_url(__FILE__),
    '1.0.0'
);

// Your assets will work automatically!
add_action('wp_enqueue_scripts', function() {
    Vite::enqueueScript('my-app', 'js/app');
    Vite::enqueueStyle('my-styles', 'css/main');
});
```

### Theme Integration

```php
<?php
// In functions.php

use WpSpaghetti\WpVite\Vite;

// Initialize
Vite::init(
    get_template_directory() . '/',
    get_template_directory_uri() . '/',
    wp_get_theme()->get('Version')
);

// Enqueue theme assets
add_action('wp_enqueue_scripts', function() {
    Vite::enqueueScript('theme-main', 'js/main');
    Vite::enqueueStyle('theme-style', 'css/style');
});

// Add HMR support in development
add_action('wp_head', function() {
    if (WP_DEBUG) {
        Vite::devScripts();
    }
});
```

## Docker Setup

For Docker environments, Wp Vite provides automatic detection and configuration. Your `docker-compose.yml` might look like:

```yaml
services:
  wordpress:
    # ... your WordPress config
    
  node:
    image: node:18
    working_dir: /var/www/html/wp-content/plugins/your-plugin
    command: npm run dev
    ports:
      - "3000:3000"
    environment:
      - VITE_HMR_HOST=localhost
```

Set your environment variables:
```env
VITE_SERVER_HOST=node
VITE_HMR_HOST=localhost
```

## Logging & Debugging

Wp Vite includes comprehensive logging through the integrated [WP Logger](https://github.com/wp-spaghetti/wp-logger) library:

### Debug Information
```php
// Get detailed debug information
$debugInfo = Vite::getDebugInfo();

// Information includes:
// - Dev server status
// - Environment detection
// - Configuration values  
// - Manifest loading status
// - Docker detection
// - Asset paths and URLs
```

### Logging Configuration

For detailed information about log levels, log files, and advanced logging configuration, see the [WP Logger documentation](https://github.com/wp-spaghetti/wp-logger).

## Requirements

- PHP 8.0 or higher
- WordPress 5.0 or higher
- Node.js and Vite for development
- [WP Env](https://github.com/wp-spaghetti/wp-env) 2.0+ for environment management
- [WP Logger](https://github.com/wp-spaghetti/wp-logger) 2.0+ for logging service
- Optional: [Inpsyde Wonolog](https://github.com/inpsyde/wonolog) for advanced logging

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for a detailed list of changes for each release.

We follow [Semantic Versioning](https://semver.org/) and use [Conventional Commits](https://www.conventionalcommits.org/) to automatically generate our changelog.

### Release Process

- **Major versions** (1.0.0 → 2.0.0): Breaking changes
- **Minor versions** (1.0.0 → 1.1.0): New features, backward compatible
- **Patch versions** (1.0.0 → 1.0.1): Bug fixes, backward compatible

All releases are automatically created when changes are pushed to the `main` branch, based on commit message conventions.

## Contributing

For your contributions please use:

- [Conventional Commits](https://www.conventionalcommits.org)
- [git-flow workflow](https://danielkummer.github.io/git-flow-cheatsheet/)
- [Pull request workflow](https://docs.github.com/en/get-started/exploring-projects-on-github/contributing-to-a-project)

See [CONTRIBUTING](.github/CONTRIBUTING.md) for detailed guidelines.

## Sponsor

[<img src="https://cdn.buymeacoffee.com/buttons/v2/default-yellow.png" width="200" alt="Buy Me A Coffee">](https://buymeacoff.ee/frugan)

## License

(ɔ) Copyleft 2025 [Frugan](https://frugan.it).  
[GNU GPLv3](https://choosealicense.com/licenses/gpl-3.0/), see [LICENSE](LICENSE) file.
