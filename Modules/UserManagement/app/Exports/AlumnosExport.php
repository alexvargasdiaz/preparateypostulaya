<?php

declare(strict_types=1);

namespace Modules\UserManagement\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class AlumnosExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected array $filtros;

    public function __construct(array $filtros = [])
    {
        $this->filtros = $filtros;
    }

    public function collection()
    {
        $query = User::with('aprobadoPor:id,name')
            ->where('rol', 'estudiante');

        if (!empty($this->filtros['estado'])) {
            $query->where('estado', $this->filtros['estado']);
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

    public function headings(): array
    {
        return [
            'ID',
            'Nombre',
            'Email',
            'WhatsApp',
            'Estado',
            'Aprobado por',
            'Fecha de aprobación',
            'Registrado',
        ];
    }

    public function map($user): array
    {
        return [
            $user->id,
            $user->name,
            $user->email,
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
