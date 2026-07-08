document.addEventListener('turbo:load', initAdminArticleEditor);
document.addEventListener('DOMContentLoaded', initAdminArticleEditor);

function initAdminArticleEditor() {
    const editor = document.querySelector('trix-editor');

    if (!editor || editor._bound) {
        return;
    }

    editor._bound = true;

    editor.addEventListener('trix-attachment-add', function (event) {
        const attachment = event.attachment;

        if (!attachment.file) {
            return;
        }

        const formData = new FormData();
        formData.append('file', attachment.file);

        fetch('/admin/article/upload-image', {
            method: 'POST',
            body: formData,
        })
            .then(response => response.json())
            .then(data => {
                if (data.url) {
                    attachment.setAttributes({
                        url: data.url,
                        href: data.url,
                    });
                }
            })
            .catch(() => {
                attachment.remove();
            });
    });
}