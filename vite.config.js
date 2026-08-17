import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/title-tooltips.css',
                'resources/js/admin/app.js',
                'resources/js/site/app.js',
                'resources/js/site/title-tooltips.js',
                'resources/js/site/appointments/index.js',
                'resources/js/site/blog-index.js',
                'resources/js/site/contact-messages/create.js',
                'resources/js/site/cms.js',
                'resources/js/site/gallery-lightbox.js',
                'resources/js/site/member-portal.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
