<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Modules\Catalogo\Models\Categoria;
use Modules\Catalogo\Models\Institucion;

class HomeController extends Controller
{
    /** Mapa de facultad → carreras específicas */
    private const CARRERAS_POR_FACULTAD = [
        'Ingeniería' => ['Civil', 'Sistemas', 'Industrial', 'Ambiental', 'Mecatrónica', 'Software', 'Electrónica', 'Minas'],
        'Ciencias de la Salud' => ['Medicina Humana', 'Enfermería', 'Odontología', 'Psicología', 'Nutrición', 'Farmacia', 'Terapia Física'],
        'Administración y Negocios' => ['Administración', 'Contabilidad', 'Marketing', 'Economía', 'Negocios Internacionales', 'Gestión'],
        'Derecho' => ['Derecho', 'Ciencias Políticas'],
        'Derecho y Ciencias Políticas' => ['Derecho', 'Ciencias Políticas'],
        'Arquitectura' => ['Arquitectura', 'Diseño Gráfico', 'Diseño de Interiores', 'Diseño Industrial'],
        'Arquitectura y Diseño' => ['Arquitectura', 'Diseño Gráfico', 'Diseño de Interiores', 'Diseño Industrial'],
        'Comunicaciones' => ['Periodismo', 'Com. Audiovisual', 'Marketing Digital', 'Publicidad', 'Diseño Gráfico'],
        'Ciencias de la Comunicación' => ['Periodismo', 'Com. Audiovisual', 'Marketing Digital', 'Publicidad'],
        'Psicología' => ['Psicología Clínica', 'Psicología Organizacional', 'Psicología Educativa'],
        'Educación y Humanidades' => ['Educación Primaria', 'Educación Inicial', 'Historia', 'Filosofía'],
        'Arte y Cultura' => ['Artes Escénicas', 'Música', 'Artes Visuales', 'Gestión Cultural'],
    ];

    /** Mapa de facultad → icono y gradiente */
    private const ICONOS_GRADIENTES = [
        'Ingeniería' => ['icono' => '⚙️', 'gradiente' => 'from-blue-600 to-blue-800'],
        'Ciencias de la Salud' => ['icono' => '🏥', 'gradiente' => 'from-green-600 to-emerald-800'],
        'Administración y Negocios' => ['icono' => '💼', 'gradiente' => 'from-amber-600 to-orange-800'],
        'Derecho' => ['icono' => '⚖️', 'gradiente' => 'from-red-600 to-rose-800'],
        'Derecho y Ciencias Políticas' => ['icono' => '⚖️', 'gradiente' => 'from-red-600 to-rose-800'],
        'Arquitectura' => ['icono' => '🏛️', 'gradiente' => 'from-pink-600 to-fuchsia-800'],
        'Arquitectura y Diseño' => ['icono' => '🏛️', 'gradiente' => 'from-pink-600 to-fuchsia-800'],
        'Comunicaciones' => ['icono' => '📡', 'gradiente' => 'from-violet-600 to-purple-800'],
        'Ciencias de la Comunicación' => ['icono' => '📡', 'gradiente' => 'from-violet-600 to-purple-800'],
        'Psicología' => ['icono' => '🧠', 'gradiente' => 'from-teal-500 to-cyan-700'],
        'Educación y Humanidades' => ['icono' => '📖', 'gradiente' => 'from-sky-500 to-blue-700'],
        'Arte y Cultura' => ['icono' => '🎨', 'gradiente' => 'from-orange-500 to-amber-700'],
    ];

    public function index()
    {
        [$facultades, $instituciones] = Cache::remember('homepage.data', 3600, function () {
            $facultades = Categoria::where('activo', true)
                ->withCount(['examenes' => function ($q) {
                    $q->where('activo', true);
                }])
                ->get()
                ->groupBy('nombre')
                ->map(function ($grupo, $nombre) {
                    $estilo = self::ICONOS_GRADIENTES[$nombre] ?? ['icono' => '📚', 'gradiente' => 'from-neutral-500 to-neutral-700'];
                    $carreras = self::CARRERAS_POR_FACULTAD[$nombre] ?? [$nombre];

                    return [
                        'nombre' => $nombre,
                        'icono' => $estilo['icono'],
                        'gradiente' => $estilo['gradiente'],
                        'carreras' => $carreras,
                        'instituciones_count' => $grupo->pluck('institucion_id')->unique()->count(),
                        'examenes_count' => $grupo->sum('examenes_count'),
                    ];
                })
                ->values()
                ->toArray();

            $instituciones = Institucion::where('activo', true)
                ->withCount('categorias')
                ->orderBy('nombre')
                ->get()
                ->groupBy('subtipo')
                ->map(fn($items) => $items->values())
                ->toArray();

            return [$facultades, $instituciones];
        });

        // Testimonios
        $testimonios = [
            [
                'nombre' => 'Carlos Mendoza',
                'carrera' => 'Ingeniería de Sistemas',
                'universidad' => 'UPC',
                'texto' => 'Los simulacros me ayudaron a identificar mis puntos débiles. Ingresé en mi primer intento.',
                'nota' => '18/20',
            ],
            [
                'nombre' => 'Valeria Torres',
                'carrera' => 'Medicina Humana',
                'universidad' => 'UNMSM',
                'texto' => 'La retroalimentación por concepto es lo mejor. Supe exactamente qué estudiar para optimizar mi tiempo.',
                'nota' => '16/20',
            ],
            [
                'nombre' => 'Diego Paredes',
                'carrera' => 'Arquitectura',
                'universidad' => 'Universidad de Lima',
                'texto' => '100% recomendado. Los simulacros son muy parecidos al examen real y el temporizador me ayudó mucho.',
                'nota' => '17/20',
            ],
        ];

        return Inertia::render('Welcome', [
            'testimonios' => $testimonios,
            'facultades' => $facultades,
            'instituciones' => $instituciones,
        ]);
    }
}
