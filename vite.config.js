import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/site/appointments/index.js',
                'resources/js/site/contact-messages/create.js',
                'resources/js/site/cms.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
