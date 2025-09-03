import { defineConfig } from 'vite'
import { resolve } from 'path'

export default defineConfig({
  // Development server configuration
  server: {
    host: process.env.VITE_SERVER_HOST || 'localhost',
    port: parseInt(process.env.VITE_SERVER_PORT) || 3000,
    https: process.env.VITE_SERVER_HTTPS === 'true',
    hmr: {
      host: process.env.VITE_HMR_HOST || 'localhost',
      port: parseInt(process.env.VITE_HMR_PORT) || 3000,
      clientPort: parseInt(process.env.VITE_HMR_CLIENT_PORT) || 3000,
    }
  },

  // Build configuration
  build: {
    outDir: process.env.VITE_OUT_DIR || 'assets',
    manifest: true,
    rollupOptions: {
      input: {
        // Define your entry points here
        app: resolve(__dirname, 'resources/js/app.js'),
        admin: resolve(__dirname, 'resources/js/admin.js'),
        main: resolve(__dirname, 'resources/css/main.css'),
        admin_styles: resolve(__dirname, 'resources/scss/admin.scss'),
      },
      output: {
        // Organize output files
        assetFileNames: (assetInfo) => {
          const info = assetInfo.name.split('.')
          const ext = info[info.length - 1]
          if (/\.(css)$/.test(assetInfo.name)) {
            return `css/[name].[hash].${ext}`
          }
          return `assets/[name].[hash].${ext}`
        },
        chunkFileNames: 'js/[name].[hash].js',
        entryFileNames: 'js/[name].[hash].js',
      }
    },
    // Generate source maps for debugging
    sourcemap: process.env.NODE_ENV === 'development',
    
    // Minification
    minify: process.env.NODE_ENV === 'production' ? 'esbuild' : false,
  },

  // Plugin configuration
  plugins: [
    // Add plugins as needed
    // For React:
    // react(),
    
    // For Vue:
    // vue(),
    
    // For obfuscation (example using custom plugin):
    // process.env.NODE_ENV === 'production' && obfuscatorPlugin({
    //   compact: true,
    //   controlFlowFlattening: true,
    // }),
  ].filter(Boolean),

  // CSS configuration
  css: {
    preprocessorOptions: {
      scss: {
        additionalData: `@import "resources/scss/variables.scss";`
      }
    },
    // CSS modules (if needed)
    modules: {
      localsConvention: 'camelCase'
    }
  },

  // Resolve configuration
  resolve: {
    alias: {
      '@': resolve(__dirname, 'resources'),
      '@js': resolve(__dirname, 'resources/js'),
      '@css': resolve(__dirname, 'resources/css'),
      '@scss': resolve(__dirname, 'resources/scss'),
    }
  },

  // Environment variables
  define: {
    __DEV__: process.env.NODE_ENV === 'development',
  }
})