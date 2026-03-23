<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Cita;
use App\Models\Recepcion;
use App\Models\OrdenPicking;
use App\Models\Despacho;
use App\Models\Inventario;

class DashboardController
{
    /**
     * GET /api/dashboard
     * Retorna estadísticas en tiempo real para el Supervisor
     */
    public function index(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');
        
        if (!in_array($user->rol, ['Admin', 'Supervisor'])) {
            return $this->json($response, ['error' => true, 'message' => 'Acceso denegado.'], 403);
        }

        $empresa = $user->empresa_id;
        $sucursal = $user->sucursal_id;

        // 1. Inbound Stats (Hoy)
        $hoy = date('Y-m-d');
        $citasProgramadas = Cita::where('sucursal_id', $sucursal)->where('fecha', $hoy)->where('estado', 'Programada')->count();
        $recepcionesEnCurso = Recepcion::where('sucursal_id', $sucursal)->where('estado', 'Borrador')->count();
        $recepcionesCerradas = Recepcion::where('sucursal_id', $sucursal)->where('estado', 'Cerrada')->where('fecha_movimiento', $hoy)->count();

        // 2. Outbound Stats
        $pickingPendiente = OrdenPicking::where('sucursal_id', $sucursal)->where('estado', 'Pendiente')->count();
        $pickingEnProceso = OrdenPicking::where('sucursal_id', $sucursal)->where('estado', 'EnProceso')->count();
        $pickingCompletado = OrdenPicking::where('sucursal_id', $sucursal)->where('estado', 'Completada')->where('fecha_movimiento', $hoy)->count();

        $despachosPreparando = Despacho::where('sucursal_id', $sucursal)->where('estado', 'Preparando')->count();

        // 3. Inventory Alerts
        // Productos bajo el mínimo (simplificado: asumiremos que queremos saber cuántas ubicaciones de picking están vacías por ahora, 
        // o cuántos lotes se vencen en los próximos 30 días)
        $vencimientoProximo = Inventario::where('sucursal_id', $sucursal)
            ->whereNotNull('fecha_vencimiento')
            ->where('cantidad', '>', 0)
            ->where('fecha_vencimiento', '<=', date('Y-m-d', strtotime('+30 days')))
            ->count();

        $data = [
            'inbound' => [
                'citas_programadas_hoy' => $citasProgramadas,
                'recepciones_en_curso' => $recepcionesEnCurso,
                'recepciones_completadas_hoy' => $recepcionesCerradas
            ],
            'outbound' => [
                'picking_pendiente' => $pickingPendiente,
                'picking_en_proceso' => $pickingEnProceso,
                'picking_completado_hoy' => $pickingCompletado,
                'despachos_preparando' => $despachosPreparando
            ],
            'alertas' => [
                'lotes_por_vencer_30d' => $vencimientoProximo
            ]
        ];

        return $this->json($response, [
            'error' => false,
            'data' => $data
        ]);
    }

    private function json(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
    }
}
