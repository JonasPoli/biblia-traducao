import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        const selector = this.element.tagName === 'TEXTAREA' ? this.element : this.element.querySelector('textarea');

        if (!selector) return;

        // Ensure unique ID for TinyMCE
        if (!selector.id) {
            selector.id = 'tinymce_' + Math.random().toString(36).substr(2, 9);
        }

        const apiKeyMeta = document.querySelector('meta[name="tinymce-api-key"]');
        const apiKey = apiKeyMeta ? apiKeyMeta.content : 'no-api-key';
        const scriptSrc = `https://cdn.tiny.cloud/1/${apiKey}/tinymce/7/tinymce.min.js`;

        if (!document.querySelector(`script[src="${scriptSrc}"]`)) {
            const script = document.createElement('script');
            script.src = scriptSrc;
            script.referrerPolicy = 'origin';
            script.onload = () => this.initEditor(selector);
            document.head.appendChild(script);
        } else if (window.tinymce) {
            this.initEditor(selector);
        } else {
            // Script loaded but tinymce not yet available, wait for it
            const check = setInterval(() => {
                if (window.tinymce) {
                    clearInterval(check);
                    this.initEditor(selector);
                }
            }, 100);
        }
    }

    initEditor(selector) {
        const config = {
            target: selector,
            height: 300,
            menubar: false,
            skin: window.matchMedia('(prefers-color-scheme: dark)').matches ? 'oxide-dark' : 'oxide',
            content_css: window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'default',
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'media', 'table', 'help', 'wordcount'
            ],
            toolbar: 'undo redo | blocks | ' +
                'bold italic backcolor | alignleft aligncenter ' +
                'alignright alignjustify | bullist numlist outdent indent | ' +
                'removeformat | help',
            content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }',
            setup: (editor) => {
                editor.on('change', () => {
                    editor.save(); // Sync content to textarea
                    selector.dispatchEvent(new Event('change', { bubbles: true }));
                    selector.dispatchEvent(new Event('input', { bubbles: true }));
                });
            }
        };

        window.tinymce.init(config);
    }

    disconnect() {
        const selector = this.element.tagName === 'TEXTAREA' ? this.element : this.element.querySelector('textarea');
        if (selector && selector.id && window.tinymce) {
            window.tinymce.remove('#' + selector.id);
        }
    }
}
