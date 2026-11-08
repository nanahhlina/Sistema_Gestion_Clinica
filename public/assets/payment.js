const copy = document.querySelector('[data-copy-payment]');
copy?.addEventListener('click', async () => {
    const field = document.getElementById('paypal-link');
    const status = document.getElementById('copy-status');
    try {
        if (!navigator.clipboard?.writeText) throw new Error('clipboard unavailable');
        await navigator.clipboard.writeText(field.value);
        status.textContent = 'Enlace copiado. Ahora puedes enviarlo al cliente.';
    } catch {
        field.focus();
        field.select();
        status.textContent = 'Selecciona y copia el enlace para enviarlo al cliente.';
    }
});
const verify = document.querySelector('form[data-customer-verify]');
if (verify) {
    const button = verify.querySelector('button');
    if (button) button.textContent = 'Verificando…';
    verify.requestSubmit();
}
