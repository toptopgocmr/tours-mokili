import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],
    theme: {
        extend: {
            colors: {
                // Brand palette extracted from the MOKILI TOUR logo.
                navy: {
                    DEFAULT: '#0B2A5B',
                    50: '#EAF0FB',
                    600: '#0B2A5B',
                    700: '#081E42',
                    900: '#050F21',
                },
                gold: {
                    DEFAULT: '#D4A017',
                    50: '#FBF3DD',
                    400: '#E4B740',
                    600: '#D4A017',
                },
            },
            fontFamily: {
                sans: ['"Segoe UI"', 'Poppins', 'ui-sans-serif', 'system-ui'],
            },
        },
    },
    plugins: [forms],
};
