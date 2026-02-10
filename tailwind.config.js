const plugin = require('tailwindcss/plugin');
const defaultTheme = require('tailwindcss/defaultTheme');

module.exports = {
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
