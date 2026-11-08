/**
 * Validaciones de presentación sin dependencias del DOM, compartidas con pruebas Node.
 * PHP y PostgreSQL siguen siendo la autoridad para permisos, unicidad y disponibilidad.
 * @param {object} field Datos y restricciones del control, nunca HTML del usuario.
 * @returns {string} Primer mensaje de error o cadena vacía.
 */
export function validateField(field) {
    if (field.disabled) return '';
    const label = field.label || 'Este campo';
    const value = typeof field.value === 'string' ? field.value : '';
    const text = value.trim();
    const empty = field.type === 'checkbox' ? !field.checked
        : field.type === 'file' ? !field.file
        : field.multiple ? !field.values?.length : text.length === 0;
    if (field.required && empty) return `${label}: completa este campo.`;
    if (field.badInput) return `${label}: ingresa un valor válido.`;
    if (empty) return '';
    if (field.type === 'file') {
        if (field.maxFileSize && field.file.size > field.maxFileSize) return 'El archivo no debe superar los 5 MB.';
        if (!['application/pdf', 'image/png', 'image/jpeg'].includes(field.file.type)) return 'Selecciona un archivo PDF, PNG o JPEG.';
        return '';
    }
    if (field.type === 'email' && (field.typeMismatch || !/^[^\s@]+@[^\s@]+$/.test(text))) return `${label}: ingresa un correo válido.`;
    if (field.name === 'nombre_usuario' && !/^[a-z0-9._-]+$/i.test(text)) return 'Usa letras sin acentos, números, puntos, guion o guion bajo.';
    // La contraseña se cuenta tal como se envía: no se recorta ni se modifica.
    const length = [...(field.type === 'password' ? value : text)].length;
    if (field.minLength > 0 && length < field.minLength) return `${label}: usa al menos ${field.minLength} caracteres.`;
    if (field.maxLength > 0 && length > field.maxLength) return `${label}: no superes ${field.maxLength} caracteres.`;
    if (field.type === 'date' && !isValidDate(text)) return `${label}: ingresa una fecha válida.`;
    if (field.type === 'number' && !Number.isFinite(Number(text))) return `${label}: ingresa un número válido.`;
    if (['date', 'time', 'number'].includes(field.type)) {
        const current = field.type === 'number' ? Number(text) : text;
        if (field.min !== '' && field.min != null && current < (field.type === 'number' ? Number(field.min) : field.min)) return `${label}: el valor mínimo es ${field.min}.`;
        if (field.max !== '' && field.max != null && current > (field.type === 'number' ? Number(field.max) : field.max)) return `${label}: el valor máximo es ${field.max}.`;
    }
    if (field.patternMismatch || field.stepMismatch) return `${label}: revisa el formato indicado.`;
    return '';
}

function isValidDate(value) {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) return false;
    const date = new Date(`${value}T00:00:00Z`);
    return Number.isFinite(date.getTime()) && date.toISOString().slice(0, 10) === value;
}

/** Comprueba relaciones dentro del mismo formulario; no mezcla formularios clínicos. */
export function validateRelations(values) {
    const errors = {};
    if (values.password_confirmation && values.password !== values.password_confirmation) errors.password_confirmation = 'Las contraseñas no coinciden.';
    const minutes = (value) => /^\d{2}:\d{2}$/.test(value || '') ? Number(value.slice(0, 2)) * 60 + Number(value.slice(3)) : null;
    const start = minutes(values.hora_inicio);
    const end = minutes(values.hora_fin);
    const duration = Number(values.duracion_minutos ?? 30);
    if (start !== null && end !== null) {
        if (end <= start) errors.hora_fin = 'La hora de fin debe ser posterior a la hora de inicio.';
        if (![15, 30, 45, 60].includes(duration)) errors.duracion_minutos = 'Selecciona una duración de 15, 30, 45 o 60 minutos.';
        else if (end > start && (end - start) % duration !== 0) errors.hora_fin = `La franja debe contener bloques completos de ${duration} minutos.`;
    }
    for (const [from, to] of [['fecha_inicio', 'fecha_fin'], ['desde', 'hasta']]) {
        if (isValidDate(values[from] || '') && isValidDate(values[to] || '') && values[to] < values[from]) errors[to] = 'La fecha final no puede ser anterior a la inicial.';
    }
    return errors;
}
