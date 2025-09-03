# Wp Vite Configuration Examples

This directory contains example configuration files to help you get started with Wp Vite in different environments.

## Files Overview

### Core Configuration Files

- **`.env.example`** - Comprehensive environment configuration example
- **`vite.config.js`** - Vite build tool configuration
- **`package.json`** - Node.js dependencies and scripts

### Docker Development

- **`docker-compose.yml`** - Complete Docker development environment
- **`.dockerignore`** - Files to exclude from Docker context

## Quick Start

### 1. Traditional WordPress Setup

1. Copy `.env.example` to `.env` in your project root
2. Copy `vite.config.js` to your project root
3. Copy `package.json` to your project root
4. Customize the configuration for your needs
5. Run `npm install` to install dependencies
6. Start development with `npm run dev`

### 2. Docker Development Setup

1. Copy `docker-compose.yml` to your project root
2. Copy `.env.example` to `.env` and configure for Docker:
   ```env
   VITE_SERVER_HOST=node
   VITE_HMR_HOST=localhost
   WP_ENVIRONMENT_TYPE=development
   ```
3. Copy `vite.config.js` and `package.json` to your project root
4. Start the entire environment:
   ```bash
   docker-compose up -d
   ```

### 3. Bedrock Setup

For Bedrock-based WordPress installations:

1. Copy `.env.example` variables to your existing `.env` file
2. Copy `vite.config.js` and `package.json` to your theme/plugin directory
3. Update paths in `vite.config.js` if needed
4. Install dependencies and start development

## Configuration Sections

### Environment Variables

#### WordPress Environment
- `WP_ENVIRONMENT_TYPE` - Environment type detection
- `WP_DEBUG` - Enable WordPress debug mode
- `WP_DEBUG_LOG` - Enable debug logging

#### Vite Server
- `VITE_SERVER_HOST` - Development server host
- `VITE_SERVER_PORT` - Development server port
- `VITE_DEV_SERVER_ENABLED` - Enable/disable dev server checking

#### Hot Module Replacement (HMR)
- `VITE_HMR_HOST` - HMR WebSocket host
- `VITE_HMR_PORT` - HMR WebSocket port
- `VITE_HMR_CLIENT_PORT` - Client-side HMR port

#### Build Configuration
- `VITE_OUT_DIR` - Build output directory
- `VITE_MANIFEST_FILE` - Vite manifest file location
- `VITE_CACHE_BUSTING_ENABLED` - Enable cache busting

### Vite Configuration

#### Entry Points
Configure your JavaScript and CSS entry points in `vite.config.js`:

```javascript
input: {
  app: resolve(__dirname, 'resources/js/app.js'),
  admin: resolve(__dirname, 'resources/js/admin.js'),
  main: resolve(__dirname, 'resources/css/main.css'),
}
```

#### Path Aliases
Set up convenient import aliases:

```javascript
alias: {
  '@': resolve(__dirname, 'resources'),
  '@js': resolve(__dirname, 'resources/js'),
  '@css': resolve(__dirname, 'resources/css'),
}
```

## Development Workflows

### Standard Development
```bash
# Install dependencies
npm install

# Start development server
npm run dev

# Build for production
npm run build

# Watch for changes and rebuild
npm run build:watch
```

### Docker Development
```bash
# Start all services
docker-compose up -d

# View logs
docker-compose logs -f node

# Stop services
docker-compose down

# Rebuild and start
docker-compose up --build -d
```

## Directory Structure

Your project should follow this structure:

```
your-plugin/
├── assets/                 # Built files (auto-generated)
│   ├── .vite/
│   │   └── manifest.json
│   ├── js/
│   └── css/
├── resources/              # Source files
│   ├── js/
│   │   ├── app.js
│   │   ├── admin.js
│   │   └── components/
│   ├── css/
│   │   └── main.css
│   └── scss/
│       └── styles.scss
├── .env                   # Your environment config
├── vite.config.js         # Vite configuration
├── package.json           # Node dependencies
└── your-plugin.php        # Main plugin file
```

## Customization Tips

### 1. Plugin/Theme Integration

In your main PHP file:

```php
<?php
use WpSpaghetti\WpVite\Vite;

// Initialize Wp Vite
add_action('init', function() {
    Vite::init(
        plugin_dir_path(__FILE__),
        plugin_dir_url(__FILE__),
        '1.0.0'
    );
});

// Enqueue assets
add_action('wp_enqueue_scripts', function() {
    Vite::enqueueScript('my-app', 'js/app');
    Vite::enqueueStyle('my-styles', 'css/main');
});
```

### 2. Environment-Specific Configuration

Use different configurations for different environments:

```javascript
// vite.config.js
export default defineConfig({
  server: {
    host: process.env.VITE_SERVER_HOST || 'localhost',
    port: parseInt(process.env.VITE_SERVER_PORT) || 3000,
  },
  build: {
    minify: process.env.NODE_ENV === 'production' ? 'esbuild' : false,
    sourcemap: process.env.NODE_ENV === 'development',
  }
})
```

### 3. Framework Integration

#### React
Add React support:

```bash
npm install @vitejs/plugin-react react react-dom
```

```javascript
// vite.config.js
import react from '@vitejs/plugin-react'

export default defineConfig({
  plugins: [react()]
})
```

#### Vue
Add Vue support:

```bash
npm install @vitejs/plugin-vue vue
```

```javascript
// vite.config.js
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()]
})
```

## Troubleshooting

### Common Issues

1. **Dev server not connecting**: Check `VITE_SERVER_HOST` and Docker network settings
2. **HMR not working**: Ensure `VITE_HMR_HOST` is accessible from your browser
3. **Assets not found**: Verify entry points in `vite.config.js` match your file structure
4. **Build failing**: Check file paths and ensure all dependencies are installed

### Debug Information

Get detailed debug information in PHP:

```php
$debugInfo = Vite::getDebugInfo();
var_dump($debugInfo);
```

### Log Files

Wp Vite logs are stored in your WordPress content directory. Check:
- Development: Debug level logging enabled
- Production: Info level logging only

## Support

For more information:
- [Main Documentation](../README.md)
- [GitHub Issues](https://github.com/wp-spaghetti/wp-vite/issues)
- [Contributing Guidelines](../CONTRIBUTING.md)