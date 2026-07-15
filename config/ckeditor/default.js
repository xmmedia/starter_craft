return {
    toolbar: {
        shouldNotGroupWhenFull: true,
    },

    htmlSupport: {
        allow: [
            {
                name: /.*/,
                attributes: true,
                classes: true,
                styles: true,
            },
        ],
    },

    alignment: {
        options: [
            'left',
            'right',
            'center',
        ],
    },
};
