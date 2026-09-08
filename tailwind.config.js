import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/Livewire/**/*.php', // Wajib: agar kelas Tailwind di Livewire terkompilasi
    ],

    theme: {
        extend: {
            colors: {
                brand: {
                    DEFAULT: '#0B7840', // Aksen utama hijau CPS (maks ≤15% per layar)
                    dark: '#085C30',    // State hover / pressed
                    tint: '#E8F5EC',    // Background chip halus
                },
                neutral: {
                    50: '#FAFAFA',      // Background halaman (dominan 85-90%)
                    200: '#E4E4E7',     // Border & hairline divider (0.5-1px)
                    500: '#71717A',     // Teks sekunder / caption / label
                    900: '#18181B',     // Teks utama body & heading
                },
                white: '#FFFFFF',       // Background card, panel, header
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                mono: ['"IBM Plex Mono"', ...defaultTheme.fontFamily.mono],
            },
            borderRadius: {
                DEFAULT: '8px', // Card, button, input form
                md: '8px',
                badge: '2px',   // Khusus badge status kotak bersudut tegas (Design System §4)
            },
            screens: {
                mobile: '375px',  // Mobile (4 kolom, drawer + bottom nav)
                tablet: '820px',  // Tablet (8 kolom, sidebar icon-only)
                desktop: '1440px',// Desktop (12 kolom, sidebar teks+ikon)
            },
        },
    },

    plugins: [forms],
};
