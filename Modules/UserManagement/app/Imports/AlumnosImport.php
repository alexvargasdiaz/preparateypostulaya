<?php

declare(strict_types=1);

namespace Modules\UserManagement\Imports;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class AlumnosImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row)
    {
        $password = !empty($row['password']) ? $row['password'] : Str::password(12);

        return User::create([
            'name' => $row['nombre'],
            'email' => $row['email'],
            'password' => Hash::make($password),
            'rol' => 'estudiante',
            'whatsapp_numero' => $row['whatsapp'] ?? null,
            'estado' => 'activo',
            'fecha_aprobacion' => now(),
            'aprobado_por' => auth()->id(),
        ]);
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['nullable', 'string', 'min:6'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
        ];
    }

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
