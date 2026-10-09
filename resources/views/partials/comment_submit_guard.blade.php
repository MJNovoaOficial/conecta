<script>
(function () {
    if (window.conectaCommentSubmitGuard) return;
    window.conectaCommentSubmitGuard = true;
    const pending = new Map();

    document.addEventListener('submit', function (event) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.matches('[data-comment-submit]')) return;
        if (pending.has(form)) {
            event.preventDefault();
            return;
        }
        if (event.defaultPrevented) return;

        const buttons = Array.from(form.querySelectorAll('button[type="submit"], input[type="submit"]'));
        const state = { busy: form.getAttribute('aria-busy'), comments: Array.from(form.querySelectorAll('textarea[name="comment"]')).map(field => ({ field: field, readOnly: field.readOnly })), buttons: buttons.map(button => ({
            button: button,
            disabled: button.disabled,
            value: button.value,
            children: Array.from(button.childNodes)
        })) };
        pending.set(form, state);
        form.setAttribute('aria-busy', 'true');
        state.comments.forEach(function (item) { item.field.readOnly = true; });
        state.buttons.forEach(function (item) {
            item.button.disabled = true;
            if (item.button.tagName === 'INPUT') item.button.value = 'Enviando...';
            else item.button.replaceChildren(document.createTextNode('Enviando...'));
        });
    });

    // Volver con el historial del navegador no debe dejar un botón bloqueado.
    window.addEventListener('pageshow', function (event) {
        if (!event.persisted) return;
        pending.forEach(function (state, form) {
            if (state.busy === null) form.removeAttribute('aria-busy');
            else form.setAttribute('aria-busy', state.busy);
            state.comments.forEach(function (item) { item.field.readOnly = item.readOnly; });
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
