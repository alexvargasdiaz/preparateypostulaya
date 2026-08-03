<?php

declare(strict_types=1);

namespace Modules\Bitacora\Http\Controllers;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Bitacora\Exports\BitacoraExport;
use Modules\Bitacora\Services\BitacoraService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BitacoraController extends Controller
{
    public function __construct(
        private readonly BitacoraService $bitacoraService,
    ) {}

    /**
     * Muestra la bitácora de procesos del administrador.
     * Acepta ?desde=YYYY-MM-DD y ?hasta=YYYY-MM-DD para acotar la actividad.
     */
    public function index(Request $request): Response
    {
        [$desde, $hasta] = $this->rangoFechas($request);

        return Inertia::render('Admin/Bitacora/Index', [
            'bitacora' => $this->bitacoraService->obtenerDatos($desde, $hasta),
            'filtros' => [
                'desde' => $desde,
                'hasta' => $hasta,
            ],
        ]);
    }

    /**
     * Exporta la bitácora a un archivo Excel (una hoja por proceso).
     * Con ?seccion=... exporta solo la hoja de ese proceso.
     */
    public function exportarExcel(Request $request): BinaryFileResponse|StreamedResponse
    {
        [$desde, $hasta] = $this->rangoFechas($request);
        $datos = $this->bitacoraService->obtenerDatos($desde, $hasta);
        $seccion = $this->seccionValida($request->query('seccion'));

        $sufijo = $this->sufijoArchivo($seccion, $desde, $hasta);

        return Excel::download(
            new BitacoraExport($datos, $seccion),
            'bitacora_procesos' . $sufijo . '_' . now()->format('Y-m-d_H-i-s') . '.xlsx'
        );
    }

    /**
     * Exporta la bitácora a un archivo PDF.
     * Con ?seccion=... exporta solo la sección de ese proceso.
     */
    public function exportarPdf(Request $request): SymfonyResponse
    {
        [$desde, $hasta] = $this->rangoFechas($request);
        $datos = $this->bitacoraService->obtenerDatos($desde, $hasta);
        $seccion = $this->seccionValida($request->query('seccion'));

        $sufijo = $this->sufijoArchivo($seccion, $desde, $hasta);

        return Pdf::loadView('bitacora::exports.bitacora-pdf', [
            'bitacora' => $datos,
            'seccion' => $seccion,
            'desde' => $desde,
            'hasta' => $hasta,
        ])
            ->setPaper('a4', 'landscape')
            ->download('bitacora_procesos' . $sufijo . '_' . now()->format('Y-m-d_H-i-s') . '.pdf');
    }

    /**
     * Valida y normaliza el rango de fechas de la petición.
     *
     * @return array{0: string|null, 1: string|null} [desde, hasta] en formato Y-m-d.
     */
    private function rangoFechas(Request $request): array
    {
        $desde = $this->fechaValida($request->query('desde'));
        $hasta = $this->fechaValida($request->query('hasta'));

        // Si el rango está invertido, se intercambia para que siempre sea válido.
        if ($desde !== null && $hasta !== null && $desde > $hasta) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        return [$desde, $hasta];
    }

    /**
     * Valida que una fecha tenga formato Y-m-d y sea real (ej. 2026-02-30 → null).
     * Devuelve null si no es válida.
     */
    private function fechaValida(mixed $fecha): ?string
    {
        if (! is_string($fecha) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return null;
        }

        $dt = \DateTime::createFromFormat('Y-m-d', $fecha);

        if ($dt === false || $dt->format('Y-m-d') !== $fecha) {
            return null;
        }

        return $fecha;
    }

    /**
     * Construye el sufijo del nombre de archivo a partir de sección y rango.
     */
    private function sufijoArchivo(?string $seccion, ?string $desde, ?string $hasta): string
    {
        $partes = [];

        if ($seccion !== null) {
            $partes[] = $seccion;
        }

        if ($desde !== null && $hasta !== null) {
            $partes[] = "{$desde}_a_{$hasta}";
        } elseif ($desde !== null) {
            $partes[] = "desde_{$desde}";
        } elseif ($hasta !== null) {
            $partes[] = "hasta_{$hasta}";
        }

        return $partes !== [] ? '_' . implode('_', $partes) : '';
    }

    /**
     * Valida la sección solicitada contra la lista blanca del export.
     * Devuelve null cuando no se pidió sección o la sección no existe.
     */
    private function seccionValida(mixed $seccion): ?string
    {
        if (! is_string($seccion) || $seccion === '') {
            return null;
        }

        return in_array($seccion, BitacoraExport::seccionesValidas(), true) ? $seccion : null;
    }
}
