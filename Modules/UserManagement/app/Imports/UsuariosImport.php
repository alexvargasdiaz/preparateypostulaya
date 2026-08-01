<?php

declare(strict_types=1);

namespace Modules\UserManagement\Imports;

use App\Enums\RolUsuario;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class UsuariosImport implements ToModel, WithHeadingRow, WithValidation
{
    /**
     * @param array $row
     *
     * @return User|null
     */
    public function model(array $row)
    {
        $rol = $this->normalizarRol($row['rol'] ?? 'Estudiante');

        // Generar contraseña aleatoria si no se proporciona
        $password = !empty($row['password']) ? $row['password'] : Str::password(12);

        return User::create([
            'name' => $row['nombre'],
            'email' => $row['email'],
            'password' => Hash::make($password),
            'rol' => $rol,
            'whatsapp_numero' => $row['whatsapp'] ?? null,
            'estado' => 'activo',
            'fecha_aprobacion' => now(),
            'aprobado_por' => auth()->id(),
        ]);
    }

    /**
     * Normaliza el nombre del rol a su valor del enum.
     */
    private function normalizarRol(string $rol): string
    {
        $mapa = [
            'super_admin' => 'super_admin',
            'super admin' => 'super_admin',
            'superadministrador' => 'super_admin',
            'admin' => 'admin',
            'administrador' => 'admin',
            'estudiante' => 'estudiante',
            'student' => 'estudiante',
            'alumno' => 'estudiante',
            'invitado' => 'estudiante',
            'guest' => 'estudiante',
        ];

        $rolNormalizado = strtolower(trim($rol));

        return $mapa[$rolNormalizado] ?? 'estudiante';
    }

    /**
     * Reglas de validación.
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['nullable', 'string', 'min:6'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
        ];
    }

    /**
     * Nombres personalizados para los atributos.
     */
    public function customValidationAttributes(): array
    {
        return [
            'nombre' => 'Nombre',
            'email' => 'Email',
            'password' => 'Contraseña',
            'whatsapp' => 'WhatsApp',
        ];
    }
}
