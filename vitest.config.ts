import { defineConfig } from 'vitest/config';
import vue from '@vitejs/plugin-vue';
import { fileURLToPath } from 'node:url';
import { resolve } from 'node:path';

export default defineConfig({
    plugins: [
        vue(),
    ],
    test: {
        globals: true,
        environment: 'happy-dom',
        include: ['tests/js/**/*.{test,spec}.{ts,js}'],
        exclude: ['node_modules', 'vendor', 'cypress'],
        setupFiles: ['tests/js/setup.ts'],
        coverage: {
            provider: 'v8',
            reportsDirectory: 'coverage/js',
            include: ['resources/js/Components/**/*.vue', 'resources/js/Composables/**/*.ts'],
        },
        css: {
            modules: { classNameStrategy: 'non-scoped' },
        },
    },
    resolve: {
        alias: {
            '@': resolve(__dirname, 'resources/js'),
            '~': resolve(__dirname, 'resources'),
            // frontend/src — Vue 3 TypeScript SPA фрагменты (AI wizard, геотрекинг и т.д.)
            '@frontend': resolve(__dirname, 'frontend/src'),
            // Leaflet не установлен — в тестах используем stub (DeliveryMap fallback)
            'leaflet': resolve(__dirname, 'tests/js/__mocks__/leaflet.ts'),
        },
    },
});
