<?php

declare(strict_types=1);

namespace Modules\Bitacora\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Exporta la bitácora de procesos a Excel.
 *
 * Genera una hoja por proceso (resumen, embudo, registro, diagnóstico, áreas,
 * universidades, resultados y actividad reciente) a partir de los datos ya
 * calculados por BitacoraService.
 *
 * Si se indica una $seccion, solo se exporta la hoja correspondiente;
 * con null se exportan todas las hojas.
 */
class BitacoraExport implements WithMultipleSheets
{
    public function __construct(
        private readonly array $bitacora,
        private readonly ?string $seccion = null,
    ) {
    }

    /**
     * Claves de sección exportables (para el filtro de la UI y la validación
     * del controlador). Se derivan del mapa de hojas para tener una única
     * fuente de verdad.
     *
     * @return array<int, string>
     */
    public static function seccionesValidas(): array
    {
        return array_keys(self::hojas([]));
    }

    /**
     * @return array<int, object>
     */
    public function sheets(): array
    {
        $todas = self::hojas($this->bitacora);

        if ($this->seccion !== null && isset($todas[$this->seccion])) {
            return [$todas[$this->seccion]];
        }

        return array_values($todas);
    }

    /**
     * Mapa clave → hoja. Única fuente de verdad para las secciones.
     *
     * @return array<string, object>
     */
    private static function hojas(array $bitacora): array
    {
        return [
            'resumen' => new ResumenSheet($bitacora),
            'embudo' => new EmbudoSheet($bitacora['embudo'] ?? []),
            'registro' => new RegistroSheet($bitacora['registro'] ?? []),
            'diagnostico' => new DiagnosticoSheet($bitacora['diagnostico'] ?? []),
            'areas' => new AreasSheet($bitacora['areas'] ?? []),
            'universidades' => new UniversidadesSheet($bitacora['universidades'] ?? []),
            'resultados' => new ResultadosSheet($bitacora['resultados'] ?? []),
            'actividad' => new ActividadSheet($bitacora['actividadReciente'] ?? []),
        ];
    }
}

/**
 * Hoja 0: Resumen de KPIs.
 */
class ResumenSheet implements FromArray, WithTitle, WithHeadings, ShouldAutoSize
{
    public function __construct(private readonly array $bitacora)
    {
    }

    public function title(): string
    {
        return 'Resumen';
    }

    public function headings(): array
    {
        return ['Indicador', 'Valor'];
    }

    public function array(): array
    {
        $k = $this->bitacora['kpis'] ?? [];

        $filas = [
            ['Fecha de actualización', $this->bitacora['fechaActualizacion'] ?? ''],
            ['Alumnos registrados', $k['alumnos'] ?? 0],
            ['Alumnos aprobados', $k['alumnos_aprobados'] ?? 0],
            ['Alumnos pendientes', $k['alumnos_pendientes'] ?? 0],
            ['Alumnos rechazados', $k['alumnos_rechazados'] ?? 0],
            ['Intentos totales', $k['intentos_total'] ?? 0],
            ['Intentos completados', $k['intentos_completados'] ?? 0],
            ['Intentos en curso', $k['intentos_en_curso'] ?? 0],
            ['Intentos abandonados', $k['intentos_abandonados'] ?? 0],
            ['Diagnósticos completados', $k['diagnosticos_completados'] ?? 0],
            ['Simulacros completados', $k['simulacros_completados'] ?? 0],
            ['Emails enviados', $k['emails_enviados'] ?? 0],
            ['WhatsApp solicitados', $k['whatsapp_solicitados'] ?? 0],
        ];

        return $filas;
    }
}

/**
 * Hoja 1: Embudo de los procesos.
 */
class EmbudoSheet implements FromArray, WithTitle, WithHeadings, ShouldAutoSize
{
    public function __construct(private readonly array $embudo)
    {
    }

    public function title(): string
    {
        return 'Embudo';
    }

    public function headings(): array
    {
        return ['Etapa', 'Alumnos', 'Conversión etapa previa (%)', '% del total'];
    }

    public function array(): array
    {
        return array_map(fn (array $etapa) => [
            $etapa['label'] ?? '',
            $etapa['valor'] ?? 0,
            $etapa['conversion'] ?? 0,
            $etapa['conversion_total'] ?? 0,
        ], $this->embudo);
    }
}

/**
 * Hoja 2: Registro y aprobación de alumnos.
 */
class RegistroSheet implements FromArray, WithTitle, WithHeadings, ShouldAutoSize
{
    public function __construct(private readonly array $registro)
    {
    }

    public function title(): string
    {
        return 'Registro';
    }

    public function headings(): array
    {
        return ['Sección', 'Etapa / Estado', 'Valor'];
    }

    public function array(): array
    {
        $filas = [];

        foreach ($this->registro['por_estado'] ?? [] as $estado) {
            $filas[] = [
                'Registro',
                $estado['label'] ?? $estado['estado'] ?? '',
                $estado['total'] ?? 0,
            ];
        }

        $filas[] = ['Registro', 'Tasa de aprobación (%)', $this->registro['tasa_aprobacion'] ?? 0];

        foreach ($this->registro['ultimos'] ?? [] as $u) {
            $filas[] = ['Últimos registros', "{$u['name']} ({$u['email']}) — {$u['estado']}", $u['fecha'] ?? ''];
        }

        return $filas;
    }
}

/**
 * Hoja 3: Configuración y uso del diagnóstico.
 */
class DiagnosticoSheet implements FromArray, WithTitle, WithHeadings, ShouldAutoSize
{
    public function __construct(private readonly array $diagnostico)
    {
    }

    public function title(): string
    {
        return 'Diagnóstico';
    }

    public function headings(): array
    {
        return ['Sección', 'Concepto', 'Valor'];
    }

    public function array(): array
    {
        $config = $this->diagnostico['config'] ?? [];
        $intentos = $this->diagnostico['intentos'] ?? [];

        return [
            ['Configuración', 'Conceptos configurados', $config['conceptos_configurados'] ?? 0],
            ['Configuración', 'Conceptos totales', $config['conceptos_totales'] ?? 0],
            ['Configuración', 'Promedio de preguntas por concepto', $config['promedio_preguntas_por_concepto'] ?? '—'],
            ['Configuración', 'Duración (minutos)', $config['duracion_minutos'] ?? 'Auto'],
            ['Uso', 'Intentos iniciados', $intentos['iniciados'] ?? 0],
            ['Uso', 'Intentos en curso', $intentos['en_curso'] ?? 0],
            ['Uso', 'Intentos completados', $intentos['completados'] ?? 0],
            ['Uso', 'Intentos abandonados', $intentos['abandonados'] ?? 0],
            ['Uso', 'Intentos aprobados', $intentos['aprobados'] ?? 0],
            ['Uso', 'Promedio de puntaje (%)', $intentos['promedio_puntaje'] ?? '—'],
            ['Uso', 'Alumnos que iniciaron diagnóstico', $this->diagnostico['alumnos_con_diagnostico'] ?? 0],
        ];
    }
}

/**
 * Hoja 4: Simulacros por área académica.
 */
class AreasSheet implements FromArray, WithTitle, WithHeadings, ShouldAutoSize
{
    public function __construct(private readonly array $areas)
    {
    }

    public function title(): string
    {
        return 'Áreas académicas';
    }

    public function headings(): array
    {
        return ['Área', 'Activa', 'Intentos', 'Completados', 'Aprobados', 'Promedio (%)'];
    }

    public function array(): array
    {
        return array_map(function (array $area) {
            return [
                $area['nombre'] ?? '',
                ($area['activo'] ?? false) ? 'Sí' : 'No',
                $area['intentos'] ?? 0,
                $area['completados'] ?? 0,
                $area['aprobados'] ?? 0,
                $area['promedio'] ?? '—',
            ];
        }, $this->areas);
    }
}

/**
 * Hoja 5: Simulacros por universidad y carreras más simuladas.
 */
class UniversidadesSheet implements FromArray, WithTitle, WithHeadings, ShouldAutoSize
{
    public function __construct(private readonly array $universidades)
    {
    }

    public function title(): string
    {
        return 'Universidades';
    }

    public function headings(): array
    {
        return ['Sección', 'Universidad / Carrera', 'Intentos', 'Completados', 'Aprobados', 'Carreras / Promedio'];
    }

    public function array(): array
    {
        $filas = [];

        foreach ($this->universidades['por_institucion'] ?? [] as $inst) {
            $filas[] = [
                'Por universidad',
                $inst['nombre'] ?? '',
                $inst['intentos'] ?? 0,
                $inst['completados'] ?? 0,
                $inst['aprobados'] ?? 0,
                $inst['promedio'] ?? '—',
            ];
        }

        foreach ($this->universidades['top_carreras'] ?? [] as $carrera) {
            $filas[] = [
                'Carreras más simuladas',
                $carrera['carrera'] ?? '',
                $carrera['intentos'] ?? 0,
                $carrera['completados'] ?? 0,
                $carrera['aprobados'] ?? 0,
                '',
            ];
        }

        return $filas;
    }
}

/**
 * Hoja 6: Resultados y envíos.
 */
class ResultadosSheet implements FromArray, WithTitle, WithHeadings, ShouldAutoSize
{
    public function __construct(private readonly array $resultados)
    {
    }

    public function title(): string
    {
        return 'Resultados';
    }

    public function headings(): array
    {
        return ['Indicador', 'Valor'];
    }

    public function array(): array
    {
        $rc = $this->resultados['resultados_concepto'] ?? [];
        $envios = $this->resultados['envios'] ?? [];

        return [
            ['Intentos completados', $this->resultados['completados'] ?? 0],
            ['Aprobados', $this->resultados['aprobados'] ?? 0],
            ['Desaprobados', $this->resultados['desaprobados'] ?? 0],
            ['Promedio global (%)', $this->resultados['promedio_global'] ?? '—'],
            ['Registros por concepto', $rc['registros'] ?? 0],
            ['Intentos con resultados', $rc['intentos_con_resultados'] ?? 0],
            ['Promedio de acierto (%)', $rc['promedio_acierto'] ?? 0],
            ['Emails enviados', $envios['emails'] ?? 0],
            ['WhatsApp solicitados', $envios['whatsapp'] ?? 0],
        ];
    }
}

/**
 * Hoja 7: Actividad reciente (intentos y registros).
 */
class ActividadSheet implements FromArray, WithTitle, WithHeadings, ShouldAutoSize
{
    public function __construct(private readonly array $actividad)
    {
    }

    public function title(): string
    {
        return 'Actividad reciente';
    }

    public function headings(): array
    {
        return ['Tipo', 'Alumno', 'Referencia', 'Estado', 'Aprobado', 'Puntaje (%)', 'Fecha'];
    }

    public function array(): array
    {
        $filas = [];

        foreach ($this->actividad['intentos'] ?? [] as $it) {
            $filas[] = [
                'Intento',
                $it['usuario'] ?? '',
                $it['referencia'] ?? '',
                $it['estado'] ?? '',
                ($it['aprobado'] ?? false) ? 'Sí' : 'No',
                $it['puntaje'] ?? '—',
                $it['fecha'] ?? '',
            ];
        }

        foreach ($this->actividad['registros'] ?? [] as $u) {
            $filas[] = [
                'Registro de alumno',
                $u['name'] ?? '',
                $u['email'] ?? '',
                $u['estado'] ?? '',
                '',
                '',
                $u['fecha'] ?? '',
            ];
        }

        return $filas;
    }
}
