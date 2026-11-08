export const expiredSessionMessage = 'Tu sesión ha vencido o no está activa. Inicia sesión para continuar.';

/** Devuelve el ingreso local, sin repetir la operación que perdió autenticación. */
export async function expiredSessionRedirect(response, { loginUrl, onMessage = () => {} }) {
    if (response.status !== 401) return null;
    let message = expiredSessionMessage;
    try {
        const data = await response.json();
        if (typeof data.message === 'string' && data.message.trim()) message = data.message;
    } catch { /* El aviso también funciona si la respuesta no contiene JSON válido. */ }
    onMessage(message);
    return loginUrl;
}
