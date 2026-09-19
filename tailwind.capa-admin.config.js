import baseConfig from './tailwind.config.js';

/**
 * Build CSS khusus halaman review CAPA di panel admin Filament.
 * Memakai tema yang sama dengan app employee, tetapi hanya memindai view CAPA
 * dan seluruh selector di-scope ke `.capa-scope` (lihat postcss.config.js)
 * agar tidak bentrok dengan styling Filament.
 */
/** @type {import('tailwindcss').Config} */
export default {
    ...baseConfig,
    content: [
        './resources/views/components/capa/**/*.blade.php',
        './resources/views/filament/resources/ba-incidents/**/*.blade.php',
    ],
};
