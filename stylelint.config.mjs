/** @type {import('stylelint').Config} */
export default {
    "extends": [ "stylelint-config-standard", "stylelint-config-tailwindcss" ],
    "rules": {
        "rule-empty-line-before": null,
        "declaration-empty-line-before": null,
        "selector-class-pattern": [
            // trailing `:…` segments allow Tailwind variants, e.g. `blocks-wrap\\:text-left`
            "^[a-z0-9\\-_]+(:[a-z0-9\\-_]+)*$",
            {
                "message": "Expected class selector to be kebab-case or BEM-style (lowercase, digits, hyphens, underscores), optionally with Tailwind variant prefixes.",
            },
        ],
        "at-rule-no-deprecated": null,
        "at-rule-empty-line-before": [
            "always",
            {
                "except": [ "blockless-after-same-name-blockless", "first-nested" ],
                "ignore": [ "after-comment", "first-nested", "inside-block" ],
                "ignoreAtRules": [ "else", "apply", "import", "source" ],
            },
        ],
        "no-invalid-position-at-import-rule": [
            true,
            { "ignoreAtRules": [ "config", "plugin", "source", "theme", "supports", "layer" ] },
        ],
        "custom-property-empty-line-before": null,
        "comment-empty-line-before": null,
        "nesting-selector-no-missing-scoping-root": [true, { "ignoreAtRules": ["utility"] }],
        // `@utility` is a rule, not a plain at-rule, so declarations nested inside it
        // (incl. inside a nested `@supports`/`@media`) are valid
        "no-invalid-position-declaration": [true, { "ignoreAtRules": ["utility"] }],
    },
};
