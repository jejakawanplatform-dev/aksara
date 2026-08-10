import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.{js,vue}',
    ],

    theme: {
        extend: {
            colors: {
                aksara: {
                    ink: '#0c2e2a',
                    teal: '#147a68',
                    'teal-dark': '#0f5c4e',
                    mist: '#eef6f4',
                    paper: '#f5f8f7',
                    line: '#d5e3df',
                    muted: '#5b716c',
                    warn: '#b45309',
                    danger: '#b42318',
                    info: '#0369a1',
                    ok: '#047857',
                },
            },
            fontFamily: {
                sans: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
                display: ['Literata', ...defaultTheme.fontFamily.serif],
            },
            boxShadow: {
                aksara: '0 10px 30px -18px rgba(12, 46, 42, 0.35)',
            },
        },
    },

    plugins: [forms],
};
