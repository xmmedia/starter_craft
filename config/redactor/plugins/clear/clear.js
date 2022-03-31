Redactor.add('plugin', 'clear', {
    init: function (t) {
        this.app = t, this.toolbar = t.toolbar;
    },
    start: function () {
        this.toolbar.addButton('clearformat', {
            title: 'Clear formatting',
            api: 'module.inline.clearformat',
            icon: '<i class="re-icon-variable"></i>',
        });
    },
});
