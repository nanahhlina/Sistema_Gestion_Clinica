<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        $definition = config('clinic.catalogs.'.$this->route('module'));

        return $definition && $this->user()->hasPermission($definition['permission']);
    }

    /** Normaliza identificadores antes de validar; la base garantiza la unicidad final. */
    protected function prepareForValidation(): void
    {
        $input = $this->all();
        foreach (['ci', 'nombre_usuario', 'correo', 'nombre', 'registro_profesional'] as $field) {
            if (isset($input[$field])) {
                $input[$field] = trim($input[$field]);
            }
        }
        if (isset($input['ci'])) {
            $input['ci'] = mb_strtoupper($input['ci']);
        }
        foreach (['nombre_usuario', 'correo'] as $field) {
            if (isset($input[$field])) {
                $input[$field] = mb_strtolower($input[$field]);
            }
        }
        $input['activo'] = $this->boolean('activo');
        $this->merge($input);
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return match ($this->route('module')) {
            'pacientes' => ['ci' => ['nullable', 'string', 'max:40'], 'nombres' => ['required', 'string', 'max:100'], 'apellidos' => ['required', 'string', 'max:100'], 'fecha_nacimiento' => ['required', 'date', 'before_or_equal:today'], 'sexo' => ['nullable', Rule::in(['Femenino', 'Masculino', 'Otro', 'No especificado'])], 'telefono' => ['required', 'string', 'max:40'], 'correo' => ['nullable', 'email', 'max:150'], 'direccion' => ['nullable', 'string', 'max:3000'], 'activo' => ['boolean']],
            'especialidades' => ['nombre' => ['required', 'string', 'max:100'], 'descripcion' => ['nullable', 'string', 'max:3000'], 'activo' => ['boolean']],
            'medicos' => ['usuario_id' => ['required', 'integer', 'exists:usuario,id'], 'registro_profesional' => ['required', 'string', 'max:60'], 'telefono' => ['nullable', 'string', 'max:40'], 'especialidad_ids' => ['required', 'array', 'min:1'], 'especialidad_ids.*' => ['integer', 'distinct', 'exists:especialidad,id'], 'activo' => ['boolean']],
            'horarios' => ['medico_id' => ['required', 'integer', 'exists:medico,id'], 'dia_semana' => ['required', 'integer', 'between:1,7'], 'hora_inicio' => ['required', 'date_format:H:i'], 'hora_fin' => ['required', 'date_format:H:i', 'after:hora_inicio'], 'activo' => ['boolean']],
            'usuarios' => ['rol_id' => ['required', 'integer', 'exists:rol,id'], 'nombre_usuario' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9._-]+$/'], 'password' => [$id ? 'nullable' : 'required', 'string', 'min:12', 'max:255'], 'nombres' => ['required', 'string', 'max:100'], 'apellidos' => ['required', 'string', 'max:100'], 'correo' => ['nullable', 'email', 'max:150'], 'paciente_id' => ['nullable', 'integer', 'exists:paciente,id'], 'activo' => ['boolean']],
            'roles' => ['nombre' => ['required', 'string', 'max:60'], 'descripcion' => ['nullable', 'string', 'max:3000'], 'permiso_ids' => ['nullable', 'array'], 'permiso_ids.*' => ['integer', 'distinct', 'exists:permiso,id'], 'activo' => ['boolean']],
            default => [],
        };
    }

    public function messages(): array
    {
        return ['nombre_usuario.regex' => 'Usa letras sin acentos, números, puntos, guion o guion bajo.', 'password.min' => 'La contraseña debe tener al menos 12 caracteres.'];
    }
}
