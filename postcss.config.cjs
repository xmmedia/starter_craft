/** @type {import('postcss-load-config').Config} */
const config = {
    plugins: [
        require('@tailwindcss/postcss'),
    ],
    sourceMap : 'prev',
};

module.exports = config;
