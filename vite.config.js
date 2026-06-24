import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { viteStaticCopy } from 'vite-plugin-static-copy';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                // Admin
                'resources/assets/admin/vendors/mdi/css/materialdesignicons.min.css',
                'resources/assets/admin/css/custom.css',
                'resources/assets/admin/css/styles.css',
                'resources/assets/admin/js/app.js',
            ],
            refresh: true,
        }),
        viteStaticCopy({
            targets: [
                {
                    src: 'resources/assets/images',
                    dest: '',
                },
                {
                    src: 'resources/assets/favicon',
                    dest: '',
                },
                {
                    src: 'resources/assets/admin/fonts',
                    dest: '',
                }
            ]
        }),
    ],
});
