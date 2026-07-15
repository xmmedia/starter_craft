class SoftHyphenPlugin {
    static get pluginName() { return 'SoftHyphenPlugin'; }
    static get requires() { return []; }

    constructor(editor) {
        this.editor = editor;
    }

    init() {
        const proc = this.editor.data.processor;
        const origToData = proc.toData.bind(proc);

        // toData outputs U+00AD as the raw character which is invisible in source
        // editing mode. Replace with the &shy; entity so it is visible and round-trips
        // cleanly (DOMParser converts &shy; back to U+00AD on the way in).
        proc.toData = (view) => origToData(view).replace(/­/g, '&shy;');
    }
}

return {
    extraPlugins: [SoftHyphenPlugin],

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
