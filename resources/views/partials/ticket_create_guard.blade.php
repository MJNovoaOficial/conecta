<script>
(function () {
    if (window.conectaTicketCreateGuard) return;
    window.conectaTicketCreateGuard = true;
    const pending = new Map();
    document.addEventListener('submit', function (event) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.matches('[data-ticket-create]')) return;
        if (pending.has(form)) { event.preventDefault(); return; }
        if (event.defaultPrevented) return;
        const state = { busy: form.getAttribute('aria-busy'), buttons: Array.from(form.elements)
            .filter(el => (el instanceof HTMLButtonElement || el instanceof HTMLInputElement) && el.type === 'submit')
            .map(button => ({ button: button, disabled: button.disabled, value: button.value, children: Array.from(button.childNodes) })) };
        pending.set(form, state);
        form.setAttribute('aria-busy', 'true');
        state.buttons.forEach(function (item) {
            item.button.disabled = true;
            if (item.button.tagName === 'INPUT') item.button.value = 'Enviando...';
            else item.button.replaceChildren(document.createTextNode('Enviando...'));
        });
        // No deshabilitar campos: deben conservarse en el POST, incluidos los archivos.
    });
    window.addEventListener('pageshow', function (event) {
        if (!event.persisted) return;
        pending.forEach(function (state, form) {
            if (state.busy === null) form.removeAttribute('aria-busy');
            else form.setAttribute('aria-busy', state.busy);
            state.buttons.forEach(function (item) {
                item.button.disabled = item.disabled;
                if (item.button.tagName === 'INPUT') item.button.value = item.value;
                else item.button.replaceChildren.apply(item.button, item.children);
            });
        });
        pending.clear();
    });
})();
</script>
