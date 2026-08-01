<?php

declare(strict_types=1);

namespace Modules\UserManagement\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class UsuariosExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected array $filtros;

    public function __construct(array $filtros = [])
    {
        $this->filtros = $filtros;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = User::with('aprobadoPor:id,name');

        // Aplicar los mismos filtros que en el index
        if (!empty($this->filtros['estado'])) {
            $query->where('estado', $this->filtros['estado']);
        }

        if (!empty($this->filtros['rol'])) {
            $query->where('rol', $this->filtros['rol']);
        }

        if (!empty($this->filtros['busqueda'])) {
            $busqueda = $this->filtros['busqueda'];
            $query->where(function ($q) use ($busqueda) {
                $q->where('name', 'like', "%{$busqueda}%")
                  ->orWhere('email', 'like', "%{$busqueda}%");
            });
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Encabezados del Excel.
     */
    public function headings(): array
    {
        return [
            'ID',
            'Nombre',
            'Email',
            'Rol',
            'WhatsApp',
            'Estado',
            'Aprobado por',
            'Fecha de aprobación',
            'Registrado',
        ];
    }

    /**
     * Mapeo de cada fila.
     */
    public function map($user): array
    {
        return [
            $user->id,
            $user->name,
            $user->email,
            match ($user->rol?->value) {
                'super_admin' => 'Super Admin',
                'admin' => 'Admin',
                'estudiante' => 'Estudiante',
                'invitado' => 'Invitado',
                default => $user->rol?->value ?? 'N/A',
            },
            $user->whatsapp_numero ?? '—',
            match ($user->estado) {
                'activo' => 'Activo',
                'pendiente' => 'Pendiente',
                'rechazado' => 'Rechazado',
                default => $user->estado ?? 'N/A',
            },
            $user->aprobadoPor?->name ?? '—',
            $user->fecha_aprobacion ? $user->fecha_aprobacion->format('d/m/Y H:i') : '—',
            $user->created_at->format('d/m/Y H:i'),
        ];
    }
}
