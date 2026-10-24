import { validateField, validateRelations } from './validation.js';

(() => {
    'use strict';

    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        const input = document.getElementById(button.dataset.passwordToggle);
        if (!input) return;
        button.hidden = false;
        button.addEventListener('click', () => {
            const visible = input.type === 'password';
            input.type = visible ? 'text' : 'password';
            button.setAttribute('aria-pressed', String(visible));
            button.setAttribute('aria-label', visible ? 'Ocultar contraseña' : 'Mostrar contraseña');
        });
    });

    document.querySelector('[data-validation-summary]')?.focus();

    /**
     * Se activa progresivamente: sin JavaScript permanecen las restricciones HTML
     * y todas las validaciones PHP. Los mensajes se insertan como texto, nunca HTML.
     */
    function prepareValidation(form) {
        const fields = [...form.querySelectorAll('input, select, textarea')].filter((field) => !['hidden', 'submit', 'button', 'reset'].includes(field.type));
        const touched = new Set();
        const edited = new Set();
        let submitted = false;
        const summary = document.createElement('div');
        summary.className = 'alert alert-danger client-validation-summary';
        summary.setAttribute('role', 'alert');
        summary.tabIndex = -1;
        summary.hidden = true;
        form.prepend(summary);
        const evaluate = () => {
            const errors = new Map();
            const values = {};
            fields.forEach((field) => {
                field.setCustomValidity('');
                values[field.name] = field.value;
                const message = validateField({
                    name: field.name, label: (field.labels?.[0]?.textContent || field.name).replace(/\(obligatorio\)|\*/g, '').trim(),
                    value: field.value, values: field.multiple ? [...field.selectedOptions].map((option) => option.value) : [],
                    type: field.type, required: field.required, disabled: field.disabled, checked: field.checked, multiple: field.multiple,
                    minLength: field.minLength, maxLength: field.maxLength, min: field.min, max: field.max,
                    file: field.files?.[0], maxFileSize: Number(field.dataset.maxSize || 0),
                    typeMismatch: field.validity.typeMismatch, patternMismatch: field.validity.patternMismatch,
                    badInput: field.validity.badInput, stepMismatch: field.validity.stepMismatch,
                });
                if (message) errors.set(field, message);
            });
            Object.entries(validateRelations(values)).forEach(([name, message]) => {
                const field = fields.find((control) => control.name === name && !control.disabled);
                if (field && !errors.has(field)) errors.set(field, message);
            });
            return errors;
        };
        const render = (errors) => {
            fields.forEach((field, index) => {
                if (!submitted && !touched.has(field)) return;
                const wrapper = field.closest('.field') || field.parentElement;
                let message = wrapper.querySelector('[data-client-error]');
                if (!message) {
                    message = document.createElement('p');
                    message.className = 'field-error';
                    message.dataset.clientError = '';
                    message.id = `${field.id || `control-${index}`}-client-error`;
                    wrapper.append(message);
                }
                const error = errors.get(field) || '';
                message.textContent = error;
                message.hidden = !error;
                const describedBy = new Set((field.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean));
                // Los errores de la respuesta anterior dejan de describir un valor editado.
                const serverErrors = [...wrapper.querySelectorAll('.field-error:not([data-client-error])')];
                if (edited.has(field)) {
                    serverErrors.forEach((oldError) => { oldError.hidden = true; describedBy.delete(oldError.id); });
                }
                if (error) { field.setAttribute('aria-invalid', 'true'); describedBy.add(message.id); }
                else {
                    describedBy.delete(message.id);
                    if (serverErrors.some((oldError) => !oldError.hidden)) field.setAttribute('aria-invalid', 'true');
                    else field.removeAttribute('aria-invalid');
                }
                if (describedBy.size) field.setAttribute('aria-describedby', [...describedBy].join(' '));
                else field.removeAttribute('aria-describedby');
            });
            if (submitted) {
                summary.replaceChildren();
                summary.hidden = errors.size === 0;
                if (errors.size) {
                    const heading = document.createElement('p');
                    heading.textContent = 'Revisa los campos indicados antes de continuar.';
                    const list = document.createElement('ul');
                    errors.forEach((message) => { const item = document.createElement('li'); item.textContent = message; list.append(item); });
                    summary.append(heading, list);
                }
            }
        };
        fields.forEach((field) => {
            field.addEventListener('blur', () => { touched.add(field); render(evaluate()); });
            field.addEventListener('change', () => { touched.add(field); edited.add(field); render(evaluate()); });
            field.addEventListener('input', () => { edited.add(field); if (submitted || touched.has(field)) render(evaluate()); });
        });
        form.noValidate = true;
        return () => {
            submitted = true;
            const errors = evaluate();
            render(errors);
            if (errors.size) errors.keys().next().value.focus();
            return errors.size === 0;
        };
    }

    document.querySelectorAll('form[data-submit-form], form.filters').forEach((form) => {
        const validate = prepareValidation(form);
        form.addEventListener('submit', (event) => {
            if (form.dataset.submitting === 'true') {
                event.preventDefault();
                return;
            }
            if (!validate()) {
                event.preventDefault();
                return;
            }
            if (form.dataset.confirm && !window.confirm(form.dataset.confirm)) {
                event.preventDefault();
                return;
            }
            form.dataset.submitting = 'true';
            form.setAttribute('aria-busy', 'true');
            // Se difiere el bloqueo para conservar el valor del botón que envió el formulario.
            window.setTimeout(() => {
                form.querySelectorAll('button[type="submit"]').forEach((button) => {
                    button.dataset.originalText = button.textContent;
                    button.disabled = true;
                    button.textContent = button.dataset.loadingText || 'Guardando…';
                });
            }, 0);
        });
    });

    window.addEventListener('pageshow', () => {
        document.querySelectorAll('form[data-submit-form], form.filters').forEach((form) => {
            delete form.dataset.submitting;
            form.removeAttribute('aria-busy');
            form.querySelectorAll('button[data-original-text]').forEach((button) => {
                button.disabled = false;
                button.textContent = button.dataset.originalText;
                delete button.dataset.originalText;
            });
        });
    });

    document.querySelectorAll('[data-availability]').forEach((section) => {
        const doctor = section.querySelector('[name="medico_id"]');
        const specialty = section.querySelector('[name="especialidad_id"]');
        const date = section.querySelector('[name="fecha"]');
        const select = section.querySelector('[name="inicio"]');
        const status = section.querySelector('[data-availability-status]');
        const retry = section.querySelector('[data-availability-retry]');
        const submit = section.querySelector('[data-book-appointment]');
        if (!doctor || !specialty || !date || !select || !status) return;
        let pending;
        let requestId = 0;
        let initialValue = select.dataset.selected || '';
        const showStatus = (message, isError = false) => {
            status.textContent = message;
            status.classList.toggle('is-error', isError);
            if (retry) retry.hidden = !isError;
        };
        const load = async () => {
            pending?.abort();
            const currentId = ++requestId;
            select.replaceChildren(new Option('Selecciona un horario', ''));
            select.disabled = true;
            select.removeAttribute('aria-busy');
            if (submit) submit.disabled = true;
            if (!doctor.value || !specialty.value || !date.value) {
                showStatus('Selecciona médico, especialidad y fecha para consultar los horarios.');
                return;
            }
            pending = new AbortController();
            showStatus('Consultando horarios disponibles…');
            select.setAttribute('aria-busy', 'true');
            const url = new URL(section.dataset.availability, window.location.origin);
            url.search = new URLSearchParams({ medico_id: doctor.value, especialidad_id: specialty.value, fecha: date.value });
            try {
                const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin', signal: pending.signal });
                if (response.redirected || !response.ok) {
                    const error = new Error('availability');
                    error.status = response.redirected ? 401 : response.status;
                    throw error;
                }
                const data = await response.json();
                if (currentId !== requestId) return;
                if (!Array.isArray(data.slots)) throw new Error('format');
                const slots = data.slots.filter((slot) => typeof slot.inicio === 'string' && typeof slot.label === 'string');
                slots.forEach((slot) => select.add(new Option(slot.label, slot.inicio)));
                select.disabled = slots.length === 0;
                if (submit) submit.disabled = slots.length === 0;
                if (initialValue && slots.some((slot) => slot.inicio === initialValue)) select.value = initialValue;
                initialValue = '';
                showStatus(slots.length ? `${slots.length} horario${slots.length === 1 ? '' : 's'} disponible${slots.length === 1 ? '' : 's'}. Selecciona uno para continuar.` : 'No hay horarios disponibles. Prueba otra fecha o médico.');
            } catch (error) {
                if (error.name !== 'AbortError' && currentId === requestId) {
                    const message = error.status === 422
                        ? 'Revisa el médico, la especialidad y la fecha seleccionados. El médico debe atender esa especialidad y la fecha debe ser actual o futura.'
                        : [401, 419].includes(error.status)
                            ? 'Tu sesión ha vencido. Inicia sesión nuevamente para consultar los horarios.'
                            : error.status === 403
                                ? 'No tienes permiso para consultar estos horarios.'
                                : error.status === 429
                                    ? 'Se hicieron demasiadas consultas. Espera un momento y vuelve a intentarlo.'
                                    : 'No pudimos consultar la disponibilidad. Vuelve a intentarlo.';
                    showStatus(message, true);
                }
            } finally {
                if (currentId === requestId) select.removeAttribute('aria-busy');
            }
        };
        [doctor, specialty, date].forEach((input) => input.addEventListener('change', load));
        retry?.addEventListener('click', load);
        load();
    });

    document.querySelectorAll('[data-print]').forEach((button) => {
        button.hidden = false;
        button.addEventListener('click', () => window.print());
    });
})();
