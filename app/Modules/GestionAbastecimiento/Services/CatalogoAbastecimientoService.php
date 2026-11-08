<?php

namespace App\Modules\GestionAbastecimiento\Services;

use App\Models\User;
use App\Modules\GestionFinanciera\Services\Importe;
use App\Modules\Shared\Clinic;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CatalogoAbastecimientoService
{
    public function definition(string $kind): array
    {
        return match ($kind) {
            'productos' => ['table' => 'producto', 'permission' => 'productos.gestionar', 'title' => 'Productos', 'fields' => [
                'nombre' => ['label' => 'Nombre', 'required' => true, 'max' => 150],
                'descripcion' => ['label' => 'Descripción', 'type' => 'textarea', 'max' => 3000],
                'tipo' => ['label' => 'Tipo', 'options' => ['Medicamento' => 'Medicamento', 'Insumo' => 'Insumo médico'], 'required' => true],
                'unidad_medida' => ['label' => 'Unidad de medida (unidades enteras)', 'required' => true, 'max' => 40],
                'precio_venta' => ['label' => 'Precio de venta BOB (opcional)', 'max' => 13],
                'activo' => ['label' => 'Estado', 'options' => [1 => 'Activo', 0 => 'Inactivo'], 'required' => true],
            ]],
            'proveedores' => ['table' => 'proveedor', 'permission' => 'proveedores.gestionar', 'title' => 'Proveedores', 'fields' => [
                'nombre' => ['label' => 'Nombre', 'required' => true, 'max' => 150],
                'nit' => ['label' => 'NIT (opcional, único)', 'max' => 30],
                'telefono' => ['label' => 'Teléfono', 'max' => 40],
                'correo' => ['label' => 'Correo', 'type' => 'email', 'max' => 150],
                'direccion' => ['label' => 'Dirección', 'type' => 'textarea', 'max' => 3000],
                'activo' => ['label' => 'Estado', 'options' => [1 => 'Activo', 0 => 'Inactivo'], 'required' => true],
            ]],
            default => abort(404),
        };
    }

    public function rules(string $kind): array
    {
        $common = ['nombre' => ['required', 'string', 'max:150', 'regex:/\S/u'], 'activo' => ['required', Rule::in([0, 1])]];

        return $common + match ($kind) {
            'productos' => ['descripcion' => 'nullable|string|max:3000', 'tipo' => ['required', Rule::in(['Medicamento', 'Insumo'])],
                'unidad_medida' => ['required', 'string', 'max:40', 'regex:/\S/u'],
                'precio_venta' => ['nullable', 'string', 'regex:/^(0|[1-9][0-9]{0,9})(\.[0-9]{1,2})?$/D']],
            'proveedores' => ['nit' => 'nullable|string|max:30', 'telefono' => 'nullable|string|max:40', 'correo' => 'nullable|email|max:150', 'direccion' => 'nullable|string|max:3000'],
            default => abort(404),
        };
    }

    public function version(string $kind, object $row): string
    {
        $values = [];
        foreach (array_keys($this->definition($kind)['fields']) as $field) {
            $values[$field] = $row->$field;
        }

        return hash('sha256', json_encode($values));
    }

    /** Se llama dentro de OperacionWeb: bloqueo y auditoría comparten transacción. */
    public function save(User $user, string $kind, ?int $id, array $data): int
    {
        $definition = $this->definition($kind);
        Clinic::authorize($user, $definition['permission']);
        $row = $id ? DB::table($definition['table'])->where('id', $id)->lockForUpdate()->first() ?? abort(404) : null;
        if ($row && ! hash_equals($this->version($kind, $row), $data['version'])) {
            Clinic::fail('El registro cambió durante la operación. Consulta sus datos nuevamente.');
        }
        $values = array_intersect_key($data, $definition['fields']);
        foreach ($values as $field => $value) {
            if (is_string($value)) {
                $values[$field] = trim($value) === '' ? null : trim($value);
            }
        }
        $values['activo'] = (bool) $data['activo'];
        if ($kind === 'proveedores') {
            $values['nit'] = isset($values['nit']) ? mb_strtoupper($values['nit']) : null;
            $values['correo'] = isset($values['correo']) ? mb_strtolower($values['correo']) : null;
            if ($values['nit'] !== null && DB::table('proveedor')->whereRaw('upper(btrim(nit))=?', [$values['nit']])->when($id, fn ($q) => $q->where('id', '<>', $id))->exists()) {
                Clinic::fail('El NIT ya pertenece a un proveedor, incluso si está inactivo.', 'nit');
            }
        } else {
            $values['precio_venta'] = isset($values['precio_venta']) ? Importe::decimal($values['precio_venta']) : null;
            if ($row && ($row->tipo !== $values['tipo'] || $row->unidad_medida !== $values['unidad_medida']) && DB::table('movimiento_inventario')->where('producto_id', $id)->exists()) {
                Clinic::fail('El tipo y la unidad no pueden cambiar después de registrar movimientos.');
            }
            if ($id && $values['tipo'] === 'Medicamento' && DB::table('lote')->where('producto_id', $id)->whereNull('fecha_vencimiento')->exists()) {
                Clinic::fail('Completa el vencimiento de los lotes antes de clasificar este producto como medicamento.');
            }
        }
        if ($id) {
            DB::table($definition['table'])->where('id', $id)->update($values);

            return $id;
        }

        return DB::table($definition['table'])->insertGetId($values);
    }
}
