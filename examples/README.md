# Wp Vite Configuration Examples

This directory contains example configuration files to help you get started with Wp Vite in different environments, including multi-plugin/theme setups.

## Files Overview

### Core Configuration Files

- **`.env.example`** - Comprehensive environment configuration with component-specific examples
- **`vite.config.js`** - Vite build tool configuration
- **`package.json`** - Node.js dependencies and scripts

### Docker Development

- **`docker-compose.yml`** - Complete Docker development environment with multi-container support
- **`.dockerignore`** - Files to exclude from Docker context

## Quick Start

### 1. Single Plugin/Theme Setup

1. Copy `.env.example` to `.env` in your project root
2. Copy `vite.config.js` to your project root
3. Copy `package.json` to your project root
4. Customize the configuration for your needs
5. Run `npm install` to install dependencies
6. Start development with `npm run dev`

### 2. Multi-Plugin Development Setup

For WordPress installations with multiple plugins/themes:

1. Copy `.env.example` to `.env` and configure component-specific variables:
   ```env
   # Main plugin
   ADMIN_DASHBOARD_VITE_SERVER_PORT=3001
   ADMIN_DASHBOARD_VITE_OUT_DIR=admin-assets
   
   # E-commerce plugin
   ECOMMERCE_VITE_SERVER_PORT=3002
   ECOMMERCE_VITE_OUT_DIR=shop-assets
   ECOMMERCE_VITE_REACT_REFRESH=true
   
   # Theme
   MY_THEME_VITE_SERVER_PORT=3003
   MY_THEME_VITE_OUT_DIR=theme-assets
   
   # Global fallback
   VITE_SERVER_HOST=localhost
   VITE_DEV_SERVER_ENABLED=true
   ```

2. Set up multiple dev servers (see Docker Multi-Container section)
3. Initialize each component with appropriate settings

### 3. Docker Multi-Container Setup

1. Copy `docker-compose.yml` to your project root
2. Configure component-specific services and environment variables
3. Start the entire environment:
   ```bash
   docker compose up -d
   ```

### 4. Bedrock Setup

For Bedrock-based WordPress installations:

1. Copy `.env.example` variables to your existing `.env` file
2. Add component-specific variables as needed
3. Copy `vite.config.js` and `package.json` to your theme/plugin directories
4. Update paths in `vite.config.js` if needed

## Component-Specific Configuration

### Component Name Detection

Wp Vite automatically detects component names from file paths:

```php
// Detects "my-awesome-plugin"
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
    'custom-component-name'
);
```

### Environment Variable Priority

1. **Component-specific**: `{COMPONENT_NAME}_VITE_{SETTING}` (highest priority)
2. **Global**: `VITE_{SETTING}`
3. **Default values**

### Example Configurations

#### Multi-Plugin Development
```env
# Admin Dashboard Plugin
ADMIN_DASHBOARD_VITE_SERVER_HOST=localhost
ADMIN_DASHBOARD_VITE_SERVER_PORT=3001
ADMIN_DASHBOARD_VITE_HMR_PORT=3001
ADMIN_DASHBOARD_VITE_OUT_DIR=admin-assets

# E-commerce Plugin
ECOMMERCE_VITE_SERVER_HOST=localhost
ECOMMERCE_VITE_SERVER_PORT=3002
ECOMMERCE_VITE_HMR_PORT=3002
ECOMMERCE_VITE_OUT_DIR=shop-assets
ECOMMERCE_VITE_REACT_REFRESH=true

# Analytics Plugin
ANALYTICS_VITE_SERVER_HOST=localhost
ANALYTICS_VITE_SERVER_PORT=3003
ANALYTICS_VITE_HMR_PORT=3003
ANALYTICS_VITE_OUT_DIR=analytics-assets

# Theme
MY_THEME_VITE_SERVER_HOST=localhost
MY_THEME_VITE_SERVER_PORT=3004
MY_THEME_VITE_HMR_PORT=3004
MY_THEME_VITE_OUT_DIR=theme-assets

# Global fallback settings
VITE_SERVER_HOST=localhost
VITE_SERVER_PORT=3000
VITE_DEV_SERVER_ENABLED=true
VITE_CACHE_BUSTING_ENABLED=true
```

#### Docker Multi-Container
```env
# Internal Docker communication
ADMIN_DASHBOARD_VITE_SERVER_HOST=admin-node
ECOMMERCE_VITE_SERVER_HOST=ecommerce-node
ANALYTICS_VITE_SERVER_HOST=analytics-node

# External HMR hosts (browser-accessible)
ADMIN_DASHBOARD_VITE_HMR_HOST=localhost
ECOMMERCE_VITE_HMR_HOST=localhost
ANALYTICS_VITE_HMR_HOST=localhost

# Development environment
WP_ENVIRONMENT_TYPE=development
WP_DEBUG=true
```

## Configuration Sections

### Environment Variables

#### WordPress Environment
- `WP_ENVIRONMENT_TYPE` - Environment type detection
- `WP_DEBUG` - Enable WordPress debug mode
- `WP_DEBUG_LOG` - Enable debug logging

#### Component-Specific Vite Server
- `{COMPONENT}_VITE_SERVER_HOST` - Development server host
- `{COMPONENT}_VITE_SERVER_PORT` - Development server port
- `{COMPONENT}_VITE_DEV_SERVER_ENABLED` - Enable/disable dev server checking

#### Component-Specific HMR
- `{COMPONENT}_VITE_HMR_HOST` - HMR WebSocket host
- `{COMPONENT}_VITE_HMR_PORT` - HMR WebSocket port
- `{COMPONENT}_VITE_HMR_CLIENT_PORT` - Client-side HMR port

#### Component-Specific Build
- `{COMPONENT}_VITE_OUT_DIR` - Build output directory
- `{COMPONENT}_VITE_MANIFEST_FILE` - Vite manifest file location
- `{COMPONENT}_VITE_CACHE_BUSTING_ENABLED` - Enable cache busting

### Vite Configuration

#### Multiple Entry Points
Configure entry points for different components in `vite.config.js`:

```javascript
// For multi-plugin setup, create separate configs
input: {
  // Admin plugin entries
  'admin-app': resolve(__dirname, 'admin-plugin/resources/js/app.js'),
  'admin-dashboard': resolve(__dirname, 'admin-plugin/resources/js/dashboard.js'),
  
  // E-commerce plugin entries
  'shop-app': resolve(__dirname, 'ecommerce-plugin/resources/js/app.js'),
  'checkout': resolve(__dirname, 'ecommerce-plugin/resources/js/checkout.js'),
  
  // Theme entries
  'theme-main': resolve(__dirname, 'theme/resources/js/main.js'),
  'theme-style': resolve(__dirname, 'theme/resources/css/style.css'),
}
```

#### Component-Specific Aliases
```javascript
alias: {
  '@admin': resolve(__dirname, 'admin-plugin/resources'),
  '@shop': resolve(__dirname, 'ecommerce-plugin/resources'),
  '@theme': resolve(__dirname, 'theme/resources'),
}
```

## Development Workflows

### Single Component Development
```bash
# Install dependencies
npm install

# Start development server
npm run dev

# Build for production
npm run build
```

### Multi-Component Development
```bash
# Start all dev servers with Docker
docker compose up -d

# Or start individual services
docker compose up admin-node ecommerce-node

# View logs for specific component
docker compose logs -f admin-node

# Stop all services
docker compose down
```

### Component-Specific Docker Services
```yaml
# docker-compose.yml example structure
services:
  # Admin Dashboard Dev Server
  admin-node:
    image: node:18-alpine
    working_dir: /app/admin-plugin
    ports:
      - "3001:3001"
    environment:
      - ADMIN_DASHBOARD_VITE_SERVER_HOST=0.0.0.0
      - ADMIN_DASHBOARD_VITE_SERVER_PORT=3001

  # E-commerce Dev Server
  ecommerce-node:
    image: node:18-alpine
    working_dir: /app/ecommerce-plugin
    ports:
      - "3002:3002"
    environment:
      - ECOMMERCE_VITE_SERVER_HOST=0.0.0.0
      - ECOMMERCE_VITE_SERVER_PORT=3002
```

## Directory Structure

Multi-component project structure:

```
wordpress/
├── wp-content/
│   ├── plugins/
│   │   ├── admin-dashboard/
│   │   │   ├── assets/                # Built files
│   │   │   ├── resources/             # Source files
│   │   │   ├── vite.config.js
│   │   │   ├── package.json
│   │   │   └── admin-dashboard.php
│   │   │
│   │   ├── ecommerce/
│   │   │   ├── assets/
│   │   │   ├── resources/
│   │   │   ├── vite.config.js
│   │   │   ├── package.json
│   │   │   └── ecommerce.php
│   │   │
│   │   └── analytics/
│   │       ├── assets/
│   │       ├── resources/
│   │       └── analytics.php
│   │
│   └── themes/
│       └── my-theme/
│           ├── assets/
│           ├── resources/
│           ├── vite.config.js
│           ├── package.json
│           └── style.css
│
├── .env                              # Global environment config
└── docker-compose.yml               # Multi-container setup
```

## Practical Examples

### 1. WordPress Agency Setup

```php
// Admin Dashboard Plugin
    Vite::init(
        plugin_dir_path(__FILE__),
        plugin_dir_url(__FILE__),
    '1.0.0',
    'admin-dashboard'
    );

// E-commerce Plugin
Vite::init(
    plugin_dir_path(__FILE__),
    plugin_dir_url(__FILE__),
    '2.1.0',
    'ecommerce'
);

// Custom Theme
Vite::init(
    get_template_directory() . '/',
    get_template_directory_uri() . '/',
    wp_get_theme()->get('Version'),
    'agency-theme'
);
```

### 2. Child Theme with Parent Theme Assets

```php
// Parent theme
Vite::init(
    get_template_directory() . '/',
    get_template_directory_uri() . '/',
    wp_get_theme(get_template())->get('Version'),
    'parent-theme'
);

// Child theme
Vite::init(
    get_stylesheet_directory() . '/',
    get_stylesheet_directory_uri() . '/',
    wp_get_theme()->get('Version'),
    'child-theme'
);
```

```env
# Parent theme settings
PARENT_THEME_VITE_SERVER_PORT=3000
PARENT_THEME_VITE_OUT_DIR=parent-assets

# Child theme settings
CHILD_THEME_VITE_SERVER_PORT=3001
CHILD_THEME_VITE_OUT_DIR=child-assets
```

### 3. Framework Integration Examples

#### React Multi-Plugin Setup
```bash
# Admin plugin with React
cd admin-plugin
npm install @vitejs/plugin-react react react-dom

# E-commerce plugin with React
cd ../ecommerce-plugin  
npm install @vitejs/plugin-react react react-dom
```

```env
# Enable React Refresh for specific plugins
ADMIN_DASHBOARD_VITE_REACT_REFRESH=true
ECOMMERCE_VITE_REACT_REFRESH=true
```

## Troubleshooting

### Common Multi-Component Issues

1. **Port conflicts**: Ensure each component uses different ports
2. **Asset conflicts**: Use component-specific output directories
3. **HMR not working**: Check component-specific HMR host settings
4. **Build conflicts**: Verify separate manifest files for each component

### Debug Multiple Components

```php
// Get debug info for each component
$adminDebug = Vite::getDebugInfo(); // After admin plugin init
$shopDebug = Vite::getDebugInfo();  // After shop plugin init

// Check component names and configurations
echo "Admin component: " . $adminDebug['component_name'];
echo "Shop component: " . $shopDebug['component_name'];
```

### Component-Specific Log Files

Each component maintains separate log entries with component identification in the WordPress logs.

## Support

For more information:
- [Main Documentation](../README.md)
- [Component Configuration Guide](../docs/component-configuration.md)
- [GitHub Issues](https://github.com/wp-spaghetti/wp-vite/issues)
- [Contributing Guidelines](../CONTRIBUTING.md)