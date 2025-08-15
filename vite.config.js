import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                "resources/css/app.css", 
                'resources/js/app.js',
                'resources/js/michael_edit_comment.js',
                'resources/js/append_subcomment.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks: undefined,
            },
        },
    },
    base: process.env.APP_URL ? new URL('/build/', process.env.APP_URL).href : '/',
});
