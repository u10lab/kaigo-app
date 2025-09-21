import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
// import tailwindcss from '@tailwindcss/vite';  // 削除: vite 7.xと競合、@vitejs/plugin-vueが優先
import vue from '@vitejs/plugin-vue';            // 追加: Vue.js SFC処理に必須

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        // tailwindcss(),  // 削除: @tailwindcss/viteプラグインを削除
        vue(),            // 追加: Vue.js SFC処理プラグイン
    ],
});
