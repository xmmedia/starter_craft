const plugin = require('tailwindcss/plugin');
const defaultTheme = require('tailwindcss/defaultTheme')

module.exports = {
    mode: 'jit',
    content: [
        './templates/**/*.twig',
        './public/js/src/**/*.{vue,js}',
        './config/redactor/**/*.json',
    ],
    safelist: [
        // vue transition classes: https://vuejs.org/v2/guide/transitions.html#Transition-Classes
        '.md-enter-active',
        '.md-leave-active',
        '.md-enter',
        '.md-leave-active',
    ],
    theme: {
        screens: {
            'xs': '400px',
            ...defaultTheme.screens,
            'print': { 'raw': 'print' },
            'retina': { 'raw': '(-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi)' },
        },
        extend: {
            colors: {
            },
            borderWidth: {
                '10': '10px',
            },
            maxWidth: {
                '1/2': '50%',
                '3/5': '60%',
                '11/12': '91%',
            },
            fontFamily: {
                'headings': [
                    '"Helvetica Neue"',
                    'Arial',
                    'sans-serif',
                    '"Apple Color Emoji"',
                    '"Segoe UI Emoji"',
                    '"Segoe UI Symbol"',
                    '"Noto Color Emoji"',
                ],
            },
        },
    },
    plugins: [
        require('@tailwindcss/typography'),
        plugin(({ addBase, theme }) => {
            addBase({
                // same as: transition-all duration-300 ease-in-out
                '.transition-default': {
                    transitionProperty: theme('transitionProperty.all'),
                    transitionDuration: theme('transitionDuration.300'),
                    transitionTimingFunction: theme('transitionTimingFunction.in-out'),
                },
            });
        }),
    ],
};
