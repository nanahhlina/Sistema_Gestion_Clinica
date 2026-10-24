<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    use HasFactory;

    protected $table = 'usuario';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected $hidden = ['password_hash'];

    protected $rememberTokenName = null;

    protected function casts(): array
    {
        return ['activo' => 'boolean', 'creado_en' => 'datetime', 'paciente_verificado_en' => 'datetime'];
    }

    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    public function getDisplayNameAttribute(): string
    {
        return trim($this->nombres.' '.$this->apellidos);
    }

    public function roleName(): string
    {
        return (string) DB::table('rol')->where('id', $this->rol_id)->value('nombre');
    }

    public function isActive(): bool
    {
        return $this->activo && DB::table('rol')->where('id', $this->rol_id)->where('activo', true)->exists();
    }

    public function hasPermission(string $permission): bool
    {
        return $this->isActive() && DB::table('rol_permiso as rp')->join('permiso as p', 'p.id', '=', 'rp.permiso_id')->where('rp.rol_id', $this->rol_id)->where('p.codigo', $permission)->exists();
    }

    public function isPatient(): bool
    {
        return $this->roleName() === 'Paciente';
    }

    public function isDoctor(): bool
    {
        return $this->roleName() === 'Medico';
    }
}
