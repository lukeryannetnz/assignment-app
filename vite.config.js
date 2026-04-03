import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/domains/foundation/css/app.css', 'resources/domains/foundation/js/app.js'],
            refresh: true,
        }),
    ],
});
