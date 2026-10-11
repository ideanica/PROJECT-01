document.querySelectorAll('[data-profile-photo-editor]').forEach((editor) => {
    const input = editor.querySelector('[data-photo-input]');
    const card = editor.closest('.profile-edit-card');
    const preview = card.querySelector('[data-photo-preview]');
    const placeholder = card.querySelector('[data-photo-placeholder]');
    const remove = editor.querySelector('[data-photo-remove]');
    const error = editor.querySelector('[data-photo-error]');
    const originalSource = preview.getAttribute('src');
    let previewUrl;

    const updatePreview = () => {
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        previewUrl = null;
        const file = input.files[0];
        const removed = remove?.checked;
        const source = removed ? '' : file ? (previewUrl = URL.createObjectURL(file)) : originalSource;
        preview.setAttribute('src', source);
        preview.hidden = !source;
        placeholder.hidden = Boolean(source);
    };

    input.addEventListener('change', () => {
        const file = input.files[0];
        const message = file && !['image/jpeg', 'image/png', 'image/webp'].includes(file.type)
            ? 'Gunakan foto JPG, PNG, atau WebP.'
            : file?.size > 2 * 1024 * 1024 ? 'Ukuran foto maksimal 2 MB.' : '';
        error.textContent = message;
        error.hidden = !message;
        if (message) input.value = '';
        if (file && !message && remove) remove.checked = false;
        updatePreview();
    });
    remove?.addEventListener('change', () => {
        if (remove.checked) input.value = '';
        error.hidden = true;
        updatePreview();
    });
    window.addEventListener('pagehide', () => {
        if (previewUrl) URL.revokeObjectURL(previewUrl);
    });
    window.addEventListener('pageshow', updatePreview);
    updatePreview();
});
