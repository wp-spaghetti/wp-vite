# Component-Specific Configuration

Wp Vite supports component-specific environment variables, allowing you to configure different settings for each plugin or theme. This is particularly useful in multi-plugin environments or when working with child themes.

## Component Name Detection

### Automatic Detection

Wp Vite automatically detects the component name (plugin/theme) from the base path:

```php
// For plugins: detects "my-awesome-plugin"
Vite::init(
    '/wp-content/plugins/my-awesome-plugin/',
    'https://example.com/wp-content/plugins/my-awesome-plugin/'
);

// For themes: detects "my-theme"
Vite::init(
    '/wp-content/themes/my-theme/',
    'https://example.com/wp-content/themes/my-theme/'
);

// For child themes: detects "parent-theme" (removes "-child" suffix)
Vite::init(
    '/wp-content/themes/parent-theme-child/',
    'https://example.com/wp-content/themes/parent-theme-child/'
);
```

### Manual Override

You can explicitly specify the component name as the fourth parameter:

```php
Vite::init(
    plugin_dir_path(__FILE__),
    plugin_dir_url(__FILE__),
    '1.0.0',
    'custom-component-name'  // Explicit component name
);
```

## Component-Specific Environment Variables

Environment variables follow a priority system:

1. **Component-specific**: `{COMPONENT_NAME}_VITE_{SETTING}`
2. **Global**: `VITE_{SETTING}`
3. **Default values**

### Example Configuration

For a plugin named "ecommerce-toolkit":

```env
# Component-specific variables (highest priority)
ECOMMERCE_TOOLKIT_VITE_SERVER_HOST=ecommerce-dev
ECOMMERCE_TOOLKIT_VITE_SERVER_PORT=3001
ECOMMERCE_TOOLKIT_VITE_HMR_PORT=3002

# Global variables (fallback)
VITE_SERVER_HOST=localhost
VITE_SERVER_PORT=3000
VITE_HMR_PORT=3000

# Another plugin with different settings
ANALYTICS_PLUGIN_VITE_SERVER_PORT=3003
ANALYTICS_PLUGIN_VITE_OUT_DIR=custom-assets
```

### Supported Component-Specific Variables

All Vite environment variables can be component-specific:

- `{COMPONENT}_VITE_SERVER_HOST`
- `{COMPONENT}_VITE_SERVER_PORT`
- `{COMPONENT}_VITE_SERVER_HTTPS`
- `{COMPONENT}_VITE_HMR_HOST`
- `{COMPONENT}_VITE_HMR_PORT`
- `{COMPONENT}_VITE_HMR_CLIENT_PORT`
- `{COMPONENT}_VITE_HMR_PROTOCOL`
- `{COMPONENT}_VITE_HMR_HTTPS`
- `{COMPONENT}_VITE_OUT_DIR`
- `{COMPONENT}_VITE_MANIFEST_FILE`
- `{COMPONENT}_VITE_DEV_SERVER_ENABLED`
- `{COMPONENT}_VITE_DEV_CHECK_TIMEOUT`
- `{COMPONENT}_VITE_CACHE_BUSTING_ENABLED`
- `{COMPONENT}_VITE_REACT_REFRESH`

## Practical Examples

### Multi-Plugin Development Environment

```env
# Main plugin (admin dashboard)
ADMIN_DASHBOARD_VITE_SERVER_PORT=3001
ADMIN_DASHBOARD_VITE_OUT_DIR=admin-assets

# E-commerce plugin
ECOMMERCE_VITE_SERVER_PORT=3002
ECOMMERCE_VITE_OUT_DIR=shop-assets
ECOMMERCE_VITE_REACT_REFRESH=true

# Analytics plugin
ANALYTICS_VITE_SERVER_PORT=3003
ANALYTICS_VITE_OUT_DIR=analytics-assets

# Global fallback for all other plugins
VITE_SERVER_HOST=localhost
VITE_SERVER_PORT=3000
```

### Docker Multi-Container Setup

```yaml
# docker-compose.yml
services:
  # Admin dashboard dev server
  admin-node:
    image: node:18
    ports:
      - "3001:3001"
    environment:
      - ADMIN_DASHBOARD_VITE_SERVER_HOST=0.0.0.0
      - ADMIN_DASHBOARD_VITE_SERVER_PORT=3001
    working_dir: /app/admin-dashboard
    
  # E-commerce dev server  
  ecommerce-node:
    image: node:18
    ports:
      - "3002:3002"
    environment:
      - ECOMMERCE_VITE_SERVER_HOST=0.0.0.0
      - ECOMMERCE_VITE_SERVER_PORT=3002
    working_dir: /app/ecommerce

  wordpress:
    environment:
      # Component-specific external hosts for HMR
      - ADMIN_DASHBOARD_VITE_HMR_HOST=localhost
      - ECOMMERCE_VITE_HMR_HOST=localhost
```

### Child Theme Configuration

```php
// In child theme's functions.php
Vite::init(
    get_stylesheet_directory() . '/',  // Child theme path
    get_stylesheet_directory_uri() . '/',
    wp_get_theme()->get('Version'),
    'my-child-theme'  // Explicit name to avoid parent theme detection
);
```

```env
# Child theme specific settings
MY_CHILD_THEME_VITE_SERVER_PORT=3100
MY_CHILD_THEME_VITE_OUT_DIR=child-assets

# Parent theme uses defaults or global settings
VITE_SERVER_PORT=3000
VITE_OUT_DIR=assets
```

## Component Information

Get component information programmatically:

```php
// Get detected component name
$componentName = Vite::getPluginName();

// Get debug information including component details
$debugInfo = Vite::getDebugInfo();
echo $debugInfo['component_name'];           // "my-plugin"
echo $debugInfo['component_env_prefix'];     // "MY_PLUGIN_VITE_"
```

## Name Normalization Rules

Component names are automatically normalized for environment variables:

- **Special characters** → **hyphens**: `my_plugin@name` → `my-plugin-name`
- **Uppercase conversion**: `my-plugin` → `MY_PLUGIN_VITE_`
- **Child theme suffix removal**: `parent-theme-child` → `parent-theme`

## Benefits

1. **Isolation**: Each plugin/theme can have independent Vite configurations
2. **Flexibility**: Mix different development setups in the same WordPress installation
3. **Scalability**: Add new plugins without configuration conflicts
4. **Team Development**: Different team members can work on different plugins simultaneously
5. **Environment Consistency**: Same configuration approach across development, staging, and production