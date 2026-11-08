/**
 * Reintenta una sola vez cuando fetch falla por red. Una respuesta HTTP, incluso
 * de error, ya llegó al navegador y no autoriza a repetir el cierre.
 */
import { expiredSessionRedirect } from './session.js';

export async function requestLogout({ url, csrfToken, loginUrl, confirmUrl, onExpired }, fetcher = globalThis.fetch) {
    let response;
    for (let attempt = 0; attempt < 2; attempt += 1) {
        try {
            response = await fetcher(url, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken },
                credentials: 'same-origin',
            });
            break;
        } catch (error) {
            if (!(error instanceof TypeError)) throw error;
            if (attempt === 1) {
                throw new Error('No pudimos confirmar el cierre por un problema de conexión. Comprueba tu conexión y vuelve a intentarlo.');
            }
        }
    }

    const expired = await expiredSessionRedirect(response, { loginUrl, onMessage: onExpired });
    if (expired) return expired;
    if (response.ok) return loginUrl;
    // La sesión pudo cerrarse antes de perderse la primera respuesta. El GET
    // autenticado lleva al ingreso si terminó o renueva la confirmación si sigue activa.
    if (response.status === 419) return confirmUrl;
    throw new Error('No pudimos cerrar tu sesión. Vuelve a intentarlo; si el problema continúa, comunícate con la administración.');
}

export function bindLogoutForm(form, { fetcher = globalThis.fetch, navigate = (url) => globalThis.location.assign(url) } = {}) {
    const button = form.querySelector('button[type="submit"]');
    const errorBox = form.querySelector('[data-logout-error]');
    const token = form.querySelector('[name="_token"]');
    if (!button || !errorBox || !token) return;
    const originalText = button.textContent;
    let submitting = false;

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (submitting) return;
        submitting = true;
        button.disabled = true;
        button.textContent = 'Cerrando sesión…';
        errorBox.hidden = true;
        form.setAttribute('aria-busy', 'true');
        try {
            const target = await requestLogout({
                url: form.action,
                csrfToken: token.value,
                loginUrl: form.dataset.loginUrl,
                confirmUrl: form.dataset.confirmUrl,
                onExpired: (message) => { errorBox.textContent = message; errorBox.hidden = false; },
            }, fetcher);
            navigate(target);
        } catch (error) {
            errorBox.textContent = error.message || 'No pudimos confirmar el cierre. Vuelve a intentarlo.';
            errorBox.hidden = false;
            errorBox.focus();
        } finally {
            submitting = false;
            button.disabled = false;
            button.textContent = originalText;
            form.removeAttribute('aria-busy');
        }
    });
}
