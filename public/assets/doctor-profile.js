document.querySelectorAll('[data-doctor-profile]').forEach((form) => {
    const accounts = JSON.parse(form.dataset.doctorAccounts);
    const selector = form.querySelector('[name="usuario_id"]');
    selector.addEventListener('change', () => {
        const account = accounts[selector.value];
        if (!account) return;
        ['nombres', 'apellidos', 'correo'].forEach((name) => {
            const field = form.querySelector(`[name="${name}"]`);
            field.value = account[name] || '';
            field.dispatchEvent(new Event('input', { bubbles: true }));
        });
    });
});
