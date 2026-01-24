import { defineConfig } from 'vite';
import { resolve } from 'path';
// @ts-ignore - plugin has incorrect type exports
import stimulusHmr from 'vite-plugin-stimulus-hmr';

export default defineConfig({
  plugins: [
    // @ts-ignore
    stimulusHmr.default ? stimulusHmr.default() : stimulusHmr(),
  ],
  build: {
    outDir: 'Resources/public',
    emptyOutDir: false,
    rollupOptions: {
      input: {
        translation: resolve(__dirname, 'assets/translation.ts'),
      },
      output: {
        entryFileNames: 'js/[name].js',
        chunkFileNames: 'js/[name]-[hash].js',
        assetFileNames: (assetInfo) => {
          if (assetInfo.name?.endsWith('.css')) {
            return 'css/[name]';
          }
          return 'assets/[name]';
        },
      },
    },
    manifest: true,
  },
  server: {
    port: 5173,
    strictPort: true,
    hmr: {
      host: 'localhost',
    },
  },
});
