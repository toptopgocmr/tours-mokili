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
                    100: '#D5E0F5',
                    200: '#AFC2E8',
                    300: '#7F9BCF',
                    400: '#4F72B0',
                    500: '#2A4C88',
                    600: '#0B2A5B',
                    700: '#081E42',
                    800: '#061833',
                    900: '#050F21',
                },
                gold: {
                    DEFAULT: '#D4A017',
                    50: '#FBF3DD',
                    100: '#F6E6B4',
                    400: '#E4B740',
                    500: '#DCAA22',
                    600: '#D4A017',
                    700: '#A87F12',
                },
            },
            fontFamily: {
                sans: ['"Segoe UI"', 'Poppins', 'ui-sans-serif', 'system-ui'],
            },
        },
    },
    plugins: [forms],
};
