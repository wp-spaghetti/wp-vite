# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Initial release of WpVite
- Docker compatibility with automatic environment detection
- Hot Module Replacement (HMR) support with configurable hosts and ports
- Obfuscated assets support (`.obf.js`, `.min.obf.css`)
- Smart asset detection with priority-based pattern matching
- Timestamp-based cache busting for production assets
- Development/Production mode switching
- Multiple file extension support (JS, TS, JSX, TSX, CSS, SCSS, SASS)
- Subdirectory asset organization with automatic detection
- Extension auto-detection (no need to specify file extensions)
- Vite manifest.json integration for optimized production builds
- WordPress constants and .env file configuration support
- Comprehensive PSR-3 compatible logging
- Zero external dependencies operation

### Added - Core Features
- `Vite::init()` - Initialize with plugin/theme paths and version
- `Vite::asset()` - Get asset URLs with automatic dev/production switching
- `Vite::enqueueScript()` - Enqueue JavaScript with automatic existence checking
- `Vite::enqueueStyle()` - Enqueue CSS with automatic existence checking
- `Vite::isDevServer()` - Check if Vite dev server is running
- `Vite::jsExists()` / `Vite::cssExists()` - Asset existence checking
- `Vite::devScripts()` - Output Vite client scripts for HMR
- `Vite::getDebugInfo()` - Comprehensive debug information
- `Vite::resetCache()` - Reset internal cache (useful for testing)

### Added - Environment Integration
- Integration with wp-spaghetti/wp-env for environment management
- Support for WordPress constants (define()) and .env files
- Automatic Docker container detection
- Environment-specific configuration handling
- Boolean, integer, and string environment variable parsing

### Added - Logging Integration  
- Integration with wp-spaghetti/wp-logger for PSR-3 compatible logging
- Automatic log level adjustment based on environment
- Secure file logging with multi-server protection
- Optional Wonolog integration for advanced logging
- Debug and informational logging for asset operations

### Added - Asset Management
- Priority-based asset file detection (production vs development)
- Support for obfuscated assets (`.obf.js`, `.min.obf.css`)
- Timestamp-based cache busting with configurable enable/disable
- Legacy manifest.json support (assets/manifest.json)
- Modern Vite manifest support (assets/.vite/manifest.json)
- Subdirectory asset organization support

### Added - Development Features
- Vite HMR client script injection
- React Refresh support (configurable)
- Development server connectivity checking with timeout
- Docker-aware server URL generation
- Configurable HMR protocol and ports

### Added - Configuration Options
- `VITE_SERVER_HOST` - Vite dev server host
- `VITE_SERVER_PORT` - Vite dev server port  
- `VITE_SERVER_HTTPS` - Enable HTTPS for dev server
- `VITE_HMR_HOST` - HMR WebSocket host
- `VITE_HMR_PORT` - HMR WebSocket port
- `VITE_HMR_CLIENT_PORT` - Client-side HMR port
- `VITE_HMR_HTTPS` - Enable HTTPS for HMR
- `VITE_HMR_PROTOCOL` - HMR protocol (ws/wss)
- `VITE_DEV_SERVER_ENABLED` - Enable/disable dev server checking
- `VITE_DEV_CHECK_TIMEOUT` - Dev server check timeout
- `VITE_CACHE_BUSTING_ENABLED` - Enable timestamp cache busting
- `VITE_OUT_DIR` - Build output directory
- `VITE_MANIFEST_FILE` - Manifest file location
- `VITE_REACT_REFRESH` - Enable React Refresh support

### Added - Testing
- Comprehensive test suite with PHPUnit
- Unit tests for core Vite functionality
- Integration tests for wp-env and wp-logger
- Mock WordPress functions for testing
- Test coverage for Docker detection and environment handling
- File system operation testing with temporary directories

## [1.0.0] - TBD

Initial release of WpVite - A powerful Vite integration service for WordPress with enhanced Docker support, obfuscated assets handling, and flexible cache busting.
