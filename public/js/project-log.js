document.querySelectorAll('[data-log-submit]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
        if (form.dataset.sending === 'true') { event.preventDefault(); return; }
        form.dataset.sending = 'true';
        form.setAttribute('aria-busy', 'true');
        const button = form.querySelector('button[type="submit"]');
        if (button) { button.dataset.label = button.textContent; button.disabled = true; button.textContent = 'Guardando…'; }
    });
});
window.addEventListener('pageshow', function () {
    document.querySelectorAll('[data-log-submit]').forEach(function (form) {
        delete form.dataset.sending;
        form.removeAttribute('aria-busy');
        const button = form.querySelector('button[type="submit"]');
        if (button) { button.disabled = false; if (button.dataset.label) button.textContent = button.dataset.label; }
    });
});
