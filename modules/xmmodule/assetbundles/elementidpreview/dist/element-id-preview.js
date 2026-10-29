// One delegated listener covers every block, including ones added after the page loads.
document.addEventListener('input', (e) => {
    const preview = e.target.closest('[data-element-id-preview]')?.querySelector('[data-url]');
    if (!preview) {
        return;
    }

    const id = e.target.value.trim();
    const link = `${preview.dataset.url}#${id}`;

    // <craft-copy-attribute> reads its value once, when it's added to the page, so swap in a new one
    const copy = document.createElement('craft-copy-attribute');
    copy.setAttribute('value', link);
    copy.textContent = link;
    preview.querySelector('craft-copy-attribute').replaceWith(copy);

    // only a valid ID, so the button never hands out a link that can't work
    preview.hidden = !new RegExp(preview.dataset.pattern).test(id);
});
