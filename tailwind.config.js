const plugin = require('tailwindcss/plugin');
const defaultTheme = require('tailwindcss/defaultTheme');

module.exports = {
    theme: {
        extend: {
            colors: {
                'black-transparent' : 'rgba(0,0,0,0.4)',
                'white-transparent' : 'rgba(255,255,255,0.6)',
                'white-transparent-dark' : 'rgba(255,255,255,0.8)',
            },
            borderWidth: {
                '10': '10px',
            },
            maxWidth: {
                '1/2': '50%',
                '3/5': '60%',
                '11/12': '91%',
                '8xl' : '90rem',
            },
            height: {
                '120': '30rem',
                '152' : '38rem',
            },
            fontFamily: {
                'headings': [
                    '"Roboto"',
                    '"Helvetica Neue"',
                    'Arial',
                    // see https://tailwindcss.com/docs/font-family for list
                    ...defaultTheme.fontFamily.sans,
                ],
            },
        },
    },

    plugins: [
        require('@tailwindcss/typography'),

        plugin(({ addComponents, theme }) => {
            addComponents({
                /* goes into the `components` layer, so @apply can see it */
                '.transition-default': {
                    transitionProperty:   theme('transitionProperty.all'),
                    transitionDuration:   theme('transitionDuration.300'),
                    transitionTimingFunction: theme('transitionTimingFunction.in-out'),
                },
            });
        }),
    ],
};
