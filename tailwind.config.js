const defaultTheme = require('tailwindcss/defaultTheme');

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['"Jost"', 'Nunito', ...defaultTheme.fontFamily.sans],
                serif: ['"Cormorant Garamond"', ...defaultTheme.fontFamily.serif],
                display: ['"Cormorant Garamond"', 'Georgia', 'serif'],
            },
            colors: {
                ivory: {
                    50: '#FBF9F5',
                    100: '#F6F3EE',
                    200: '#EFE9E0',
                    300: '#E3DACB',
                },
                clay: {
                    100: '#EFE7DC',
                    200: '#DECFBB',
                    300: '#CBB89B',
                    400: '#B7A280',
                    500: '#A68F6B',
                },
                ink: {
                    DEFAULT: '#1E1E1B',
                    soft: '#4A4842',
                    muted: '#8B877D',
                },
            },
        },
    },

    plugins: [require('@tailwindcss/forms'), require('@tailwindcss/aspect-ratio')],
};