export default {
    plugins: {
        'postcss-env-function': {},
        'postcss-nesting': {},
        // PostCSS is handled by tailwind, so we don't need to include it here, but the nested and env-function plugins are not
    },
}
