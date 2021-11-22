const plugin = require('tailwindcss/plugin');
const colors = require('tailwindcss/colors');

module.exports = {
    mode: 'jit',
    purge: {
        content: [
            './templates/**/*.twig',
            './public/js/src/**/*.vue',
            './public/js/src/**/*.js',
        ],
        options: {
            safelist: [
                // vue transition classes: https://vuejs.org/v2/guide/transitions.html#Transition-Classes
                '.md-enter-active',
                '.md-leave-active',
                '.md-enter',
                '.md-leave-active',
            ],
        },
    },
    theme: {
        extend: {
            colors: {
                'inherit': 'inherit',
                black: colors.black,
                white: colors.white,
                rose: colors.rose,
                pink: colors.pink,
                fuchsia: colors.fuchsia,
                purple: colors.purple,
                violet: colors.violet,
                indigo: colors.indigo,
                blue: colors.blue,
                sky: colors.sky,
                cyan: colors.cyan,
                teal: colors.teal,
                emerald: colors.emerald,
                green: colors.green,
                lime: colors.lime,
                yellow: colors.yellow,
                amber: colors.amber,
                orange: colors.orange,
                red: colors.red,
                warmGray: colors.warmGray,
                trueGray: colors.trueGray,
                gray: colors.gray,
                coolGray: colors.coolGray,
                blueGray: colors.blueGray,
            },
            borderWidth: {
                '10': '10px',
            },
            maxWidth: {
                '1/2': '50%',
                '3/5': '60%',
                '11/12': '91%',
            },
            screens: {
                'xs': '400px',
                'print': { 'raw': 'print' },
                'retina': { 'raw': '(-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi)' },
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
    variants: {
        borderColor: ['responsive', 'hover', 'focus', 'group-hover'],
        cursor: ['responsive', 'disabled'],
        margin: ['responsive', 'focus'],
        opacity: ['responsive', 'hover', 'focus', 'group-hover'],
        padding: ['responsive', 'focus'],
        textColor: ['responsive', 'hover', 'focus', 'group-hover'],
    },
    plugins: [
        require('@tailwindcss/typography'),
        plugin(({ addBase, theme }) => {
            addBase({
                // same as: transition-all duration-300 ease-in-out
                '.transition-default': {
                    transitionProperty: theme('transitionProperty.all'),
                    transitionDuration: theme('transitionDuration.300'),
                    transitionTimingFunction: theme('transitionTimingFunction.in-out'),},
            });
        }),
    ],
};
