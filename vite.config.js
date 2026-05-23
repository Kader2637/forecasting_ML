import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from 'tailwindcss'; // Tambahkan ini jika Anda menggunakan Tailwind CSS JIT mode

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js', // Asumsikan Anda juga memiliki file JS utama
            ],
            refresh: true,
        }),
        // Jika Anda menggunakan React atau Vue, tambahkan plugin yang sesuai di sini:
        // react(),
        // vue({
        //     template: {
        //         transformAssetUrls: {
        //             base: null,
        //             includeAbsolute: false,
        //         },
        //     },
        // }),
    ],
    // Jika Anda menggunakan Tailwind CSS JIT mode, Anda mungkin perlu menambahkan konfigurasi PostCSS
    // Namun, berdasarkan app.css Anda, sepertinya Anda sudah mengimpor Tailwind di sana,
    // jadi ini mungkin tidak diperlukan kecuali ada konfigurasi khusus.
    // css: {
    //     postcss: {
    //         plugins: [
    //             tailwindcss(),
    //             // autoprefixer(), // Opsional: jika Anda ingin autoprefixer
    //         ],
    //     },
    // },
});
