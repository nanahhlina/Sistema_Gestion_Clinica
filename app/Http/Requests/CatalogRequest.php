<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            if (isset($input[$field]) && is_string($input[$field])) {
                $input[$field] = trim($input[$field]);
            }
        }
        if (isset($input['ci']) && is_string($input['ci'])) {
            $input['ci'] = mb_strtoupper($input['ci']);
        }
        foreach (['nombre_usuario', 'correo'] as $field) {
            if (isset($input[$field]) && is_string($input[$field])) {
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
            'pacientes' => ['contacto_emergencia' => ['nullable', 'string', 'max:150'], 'telefono_emergencia' => ['nullable', 'string', 'max:40'], 'parentesco_emergencia' => ['nullable', 'string', 'max:60'], 'tipo_sangre' => ['nullable', Rule::in(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])], 'ci' => ['nullable', 'string', 'max:40'], 'nombres' => ['required', 'string', 'max:100'], 'apellidos' => ['required', 'string', 'max:100'], 'fecha_nacimiento' => ['required', 'date', 'before_or_equal:today'], 'sexo' => ['nullable', Rule::in(['Femenino', 'Masculino', 'Otro', 'No especificado'])], 'telefono' => ['required', 'string', 'max:40'], 'correo' => ['nullable', 'email', 'max:150'], 'direccion' => ['nullable', 'string', 'max:3000'], 'activo' => ['boolean']],
            'especialidades' => ['nombre' => ['required', 'string', 'max:100'], 'descripcion' => ['nullable', 'string', 'max:3000'], 'activo' => ['boolean']],
            'medicos' => ['usuario_id' => ['required', 'integer', 'exists:usuario,id'], 'nombres' => ['required', 'string', 'max:100'], 'apellidos' => ['required', 'string', 'max:100'], 'correo' => ['nullable', 'string', 'email', 'max:150'], 'registro_profesional' => ['required', 'string', 'max:60'], 'telefono' => ['nullable', 'string', 'max:40'], 'especialidad_ids' => ['required', 'array', 'min:1'], 'especialidad_ids.*' => ['integer', 'distinct', 'exists:especialidad,id'], 'activo' => ['boolean'], 'rol_id' => ['missing'], 'permiso_ids' => ['missing'], 'password' => ['missing'], 'password_hash' => ['missing'], 'paciente_id' => ['missing'], 'nombre_usuario' => ['missing'], 'paciente_verificado_por' => ['missing'], 'paciente_verificado_en' => ['missing']],
            'horarios' => ['medico_id' => ['required', 'integer', 'exists:medico,id'], 'dia_semana' => ['required', 'integer', 'between:1,7'], 'hora_inicio' => ['required', 'date_format:H:i'], 'hora_fin' => ['required', 'date_format:H:i', 'after:hora_inicio'], 'duracion_minutos' => ['required', 'integer', Rule::in([15, 30, 45, 60])], 'activo' => ['boolean']],
            'usuarios' => ['rol_id' => ['required', 'integer', 'exists:rol,id'], 'nombre_usuario' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9._-]+$/'], 'password' => [$id ? 'nullable' : 'required', 'string', 'min:12', 'max:255'], 'nombres' => ['required', 'string', 'max:100'], 'apellidos' => ['required', 'string', 'max:100'], 'correo' => ['nullable', 'email', 'max:150'], 'paciente_id' => ['missing'], 'paciente_verificado_por' => ['missing'], 'paciente_verificado_en' => ['missing'], 'activo' => ['boolean']],
            'roles' => ['nombre' => ['required', 'string', 'max:60'], 'descripcion' => ['nullable', 'string', 'max:3000'], 'permiso_ids' => ['nullable', 'array'], 'permiso_ids.*' => ['integer', 'distinct', 'exists:permiso,id'], 'activo' => ['boolean']],
            default => [],
        };
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->route('module') !== 'horarios' || $validator->errors()->isNotEmpty()) {
                return;
            }
            [$startHour, $startMinute] = array_map('intval', explode(':', $this->input('hora_inicio')));
            [$endHour, $endMinute] = array_map('intval', explode(':', $this->input('hora_fin')));
            $span = ($endHour * 60 + $endMinute) - ($startHour * 60 + $startMinute);
            $duration = (int) $this->input('duracion_minutos');
            if ($span % $duration !== 0) {
                $validator->errors()->add('hora_fin', 'La franja debe contener bloques completos de '.$duration.' minutos.');
            }
        }];
    }

    public function messages(): array
    {
        return ['nombre_usuario.regex' => 'Usa letras sin acentos, números, puntos, guion o guion bajo.', 'password.min' => 'La contraseña debe tener al menos 12 caracteres.'];
    }
}
