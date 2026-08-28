class SoftHyphenPlugin {
    static get pluginName () {
        return 'SoftHyphenPlugin';
    }

    static get requires () {
        return [];
    }

    constructor (editor) {
        this.editor = editor;
    }

    init () {
        const proc = this.editor.data.processor;
        const origToData = proc.toData.bind(proc);

        // toData outputs U+00AD as the raw character which is invisible in source
        // editing mode. Replace with the &shy; entity so it is visible and round-trips
        // cleanly (DOMParser converts &shy; back to U+00AD on the way in).
        proc.toData = (view) => origToData(view).replace(/­/g, '&shy;');
    }
}

/* @todo-craft uncomment (with the style key below) if the site needs text styles in the editor;
   add/remove the styles to suit the design, & safelist any class that isn't used in the code
const textStyles = [
    { label: 'Small', class: 'text-sm' },
    { label: 'Large', class: 'text-lg' },
];
// li (rather than ul/ol) so one definition covers bulleted & numbered lists;
// applies to whichever items are selected
const styleTargets = [
    // paragraphs are the unprefixed default; the prefix only exists to keep the
    // names unique, which CKEditor requires to resolve a style
    { prefix: '', element: 'p' },
    { prefix: 'List ', element: 'li' },
];

const styleDefinitions = styleTargets.flatMap(({ prefix, element }) => textStyles.map((style) => ({
    name: `${prefix}${style.label}`,
    element,
    classes: [style.class],
})));
*/

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

    /*
    style: {
        definitions: styleDefinitions,
    },
    */
};
