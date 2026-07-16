import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/pages/admin-archive.css',
                'resources/css/pages/admin-transaction-detail.css',
                'resources/css/pages/daily-stock-report.css',
                'resources/css/pages/daily-stock-transfer.css',
                'resources/css/pages/developer-owners.css',
                'resources/css/pages/owner-dashboard.css',
                'resources/css/pages/owner-transaction-print.css',
                'resources/css/pages/stock-adjust.css',
                'resources/css/pages/stock-restock.css',
                'resources/css/pages/transaction-index.css',
            ],
            refresh: true,
        }),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
