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
                    ink: '#0f1c24',
                    teal: '#0f766e',
                    'teal-dark': '#0d5f5a',
                    mist: '#eef6f7',
                    paper: '#f5fafb',
                    line: '#d7e4e8',
                    muted: '#5c6f78',
                    warn: '#b45309',
                    danger: '#b91c1c',
                    info: '#0369a1',
                    ok: '#047857',
                    sky: '#7dd3d8',
                },
            },
            fontFamily: {
                sans: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
                display: ['Literata', ...defaultTheme.fontFamily.serif],
            },
            boxShadow: {
                aksara: '0 8px 20px -12px rgba(15, 28, 36, 0.18)',
            },
        },
    },

    plugins: [forms],
};
