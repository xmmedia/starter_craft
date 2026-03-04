export const logError = function (e) {
    if (console && e !== undefined) {
        // eslint-disable-next-line no-console
        console.error(e);
    }
};
