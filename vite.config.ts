import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defineConfig } from 'vite';

// La imagen de build de Vercel corre Node, no PHP: el runtime de PHP se arma en una
// etapa aparte. Cualquier plugin de Vite que invoque `php artisan` rompe el build con
// `php: command not found`, y Wayfinder es justamente uno de esos.
//
// Por eso el código que genera está commiteado en el repo y acá sólo se regenera
// cuando hay un PHP disponible. Simulá el entorno con `VERCEL=1 npm run build`.
const canRunArtisan = !process.env.VERCEL;

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.ts'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        inertia(),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        ...(canRunArtisan
            ? [
                  wayfinder({
                      formVariants: true,
                  }),
              ]
            : []),
    ],
});
