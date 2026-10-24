import test from 'node:test';
import assert from 'node:assert/strict';
import { validateField, validateRelations } from '../../public/assets/validation.js';

const invalidFields = [
    ['obligatorio con espacios', { required: true, value: '   ' }, /completa/],
    ['correo mal formado', { type: 'email', value: 'ana@' }, /correo válido/],
    ['correo rechazado por el control nativo', { type: 'email', value: 'ana@example.test', typeMismatch: true }, /correo válido/],
    ['usuario con caracteres no admitidos', { name: 'nombre_usuario', value: 'ana@clinica' }, /letras sin acentos/],
    ['contraseña corta', { type: 'password', value: '123', minLength: 12 }, /12 caracteres/],
    ['texto demasiado largo', { value: 'abcd', maxLength: 3 }, /3 caracteres/],
    ['fecha de nacimiento futura', { type: 'date', value: '2030-01-01', max: '2026-10-24' }, /máximo/],
    ['fecha imposible', { type: 'date', value: '2026-02-30' }, /fecha válida/],
    ['fecha inferior al mínimo', { type: 'date', value: '2026-10-23', min: '2026-10-24' }, /mínimo/],
    ['número fuera de rango', { type: 'number', value: '8', min: '1', max: '7' }, /máximo/],
    ['número no interpretable', { type: 'number', value: 'abc' }, /número válido/],
    ['entrada nativa inválida aunque parezca vacía', { type: 'number', value: '', badInput: true }, /valor válido/],
    ['casilla requerida sin verificar', { type: 'checkbox', value: '1', required: true, checked: false }, /completa/],
    ['selección múltiple requerida vacía', { multiple: true, values: [], required: true }, /completa/],
    ['archivo demasiado grande', { type: 'file', file: { type: 'application/pdf', size: 5242881 }, maxFileSize: 5242880 }, /5 MB/],
    ['formato de archivo no admitido', { type: 'file', file: { type: 'text/html', size: 10 }, maxFileSize: 5242880 }, /PDF/],
    ['formato nativo incumplido', { value: 'abc', patternMismatch: true }, /formato/],
];
for (const [name, field, error] of invalidFields) {
    test(`rechaza ${name}`, () => assert.match(validateField(field), error));
}

test('acepta valores válidos y respeta campos opcionales y deshabilitados', () => {
    for (const field of [
        { value: '', required: false },
        { value: '', required: true, disabled: true },
        { type: 'email', value: 'ana@example.test' },
        { name: 'nombre_usuario', value: 'Ana.Prueba-01' },
        { type: 'date', value: '2024-02-29' },
        { type: 'password', value: 'Abcdefghij12', minLength: 12 },
        { multiple: true, values: ['1'], required: true },
        { type: 'file', file: { type: 'application/pdf', size: 5242880 }, maxFileSize: 5242880 },
        { type: 'file', required: false },
    ]) assert.equal(validateField(field), '', JSON.stringify(field));
});

test('cuenta caracteres Unicode y conserva los espacios de una contraseña', () => {
    assert.equal(validateField({ value: '💚é', maxLength: 2 }), '');
    assert.equal(validateField({ type: 'password', value: ' abcd ', minLength: 6 }), '');
});

test('rechaza contraseñas distintas y admite la confirmación exacta', () => {
    assert.match(validateRelations({ password: 'VidaSalud2026!', password_confirmation: 'OtraClave2026!' }).password_confirmation, /no coinciden/);
    assert.deepEqual(validateRelations({ password: 'VidaSalud2026!', password_confirmation: 'VidaSalud2026!' }), {});
});

test('rechaza horas invertidas o franjas incompletas y admite inicio desplazado', () => {
    assert.match(validateRelations({ hora_inicio: '09:00', hora_fin: '08:00' }).hora_fin, /posterior/);
    assert.match(validateRelations({ hora_inicio: '09:00', hora_fin: '09:45' }).hora_fin, /30 minutos/);
    assert.deepEqual(validateRelations({ hora_inicio: '09:15', hora_fin: '10:15' }), {});
});

test('rechaza rangos invertidos y permite fechas iguales y finales opcionales', () => {
    assert.match(validateRelations({ fecha_inicio: '2026-10-24', fecha_fin: '2026-10-23' }).fecha_fin, /anterior/);
    assert.match(validateRelations({ desde: '2026-10-24', hasta: '2026-10-23' }).hasta, /anterior/);
    assert.deepEqual(validateRelations({ desde: '2026-10-24', hasta: '2026-10-24' }), {});
    assert.deepEqual(validateRelations({ fecha_inicio: '2026-10-24' }), {});
});
