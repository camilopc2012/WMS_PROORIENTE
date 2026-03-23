<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\OrdenPicking;
use App\Models\PickingDetalle;
use App\Models\Inventario;
use App\Models\MovimientoInventario;
use App\Models\Ubicacion;

class PickingController
{
    /**
     * POST /api/picking/{orden_id}/generar-ruta
     * Genera la lista de picking basada en algoritmo FEFO y Ruta Inteligente
     */
    public function generateRoute(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');
        $orden_id = $args['orden_id'] ?? null;

        if (!$user->hasPermission('picking', 'ejecutar')) {
            return $this->json($response, ['error' => true, 'message' => 'No tienes permiso.'], 403);
        }

        $orden = OrdenPicking::with('detalles')->find($orden_id);
        
        if (!$orden || $orden->sucursal_id !== $user->sucursal_id) {
            return $this->json($response, ['error' => true, 'message' => 'Orden no encontrada.'], 404);
        }

        if ($orden->estado !== 'Pendiente') {
            return $this->json($response, ['error' => true, 'message' => "La orden no está en estado Pendiente. Actual: {$orden->estado}"], 400);
        }

        \Illuminate\Database\Capsule\Manager::connection()->beginTransaction();

        try {
            $asignaciones = [];
            $faltantes = [];

            foreach ($orden->detalles as $detalle) {
                // Cantidad requerida que falta por asignar
                $pendiente = $detalle->cantidad_solicitada;

                // 1. Algoritmo FEFO + Ruta Lógica (Pasillo/Nivel ascendente)
                // Busca inventario disponible de este producto en la sucursal actual
                $inventarios = Inventario::where('empresa_id', $user->empresa_id)
                    ->where('sucursal_id', $user->sucursal_id)
                    ->where('producto_id', $detalle->producto_id)
                    ->where('estado', 'Disponible')
                    ->whereRaw('(cantidad - cantidad_reservada) > 0')
                    ->join('ubicaciones', 'inventarios.ubicacion_id', '=', 'ubicaciones.id')
                    // FEFO Priority 1: Vencimiento más próximo
                    ->orderByRaw('ISNULL(inventarios.fecha_vencimiento) ASC') // Pone nulos al final
                    ->orderBy('inventarios.fecha_vencimiento', 'asc')
                    // Ruta Inteligente Priority 2: Ordenar por zona, pasillo, nivel
                    ->orderBy('ubicaciones.zona', 'asc')
                    ->orderBy('ubicaciones.pasillo', 'asc')
                    ->orderBy('ubicaciones.nivel', 'asc')
                    ->select('inventarios.*', 'ubicaciones.pasillo as ubi_pasillo')
                    ->get();

                foreach ($inventarios as $inv) {
                    if ($pendiente <= 0) break;

                    $disponible = $inv->cantidad - $inv->cantidad_reservada;
                    $tomar = min($disponible, $pendiente);

                    // Reservar inventario (Concurrency lock - Soft)
                    $inv->cantidad_reservada += $tomar;
                    if ($inv->cantidad_reservada == $inv->cantidad) {
                        $inv->estado = 'Reservado';
                    }
                    $inv->save();

                    // Asignar ubicación al detalle del picking
                    // NOTA: Si una línea de picking requiere extraer de múltiples ubicaciones,
                    // lo ideal es dividir (split) la línea de detalle.
                    if ($detalle->ubicacion_id === null) {
                        $detalle->ubicacion_id = $inv->ubicacion_id;
                        $detalle->lote = $inv->lote;
                        $detalle->pasillo_lock = $inv->ubi_pasillo;
                        $detalle->save();
                    } else {
                        // Split line
                        $nuevoDetalle = $detalle->replicate();
                        $nuevoDetalle->cantidad_solicitada = $tomar;
                        $nuevoDetalle->cantidad_pickeada = 0;
                        $nuevoDetalle->ubicacion_id = $inv->ubicacion_id;
                        $nuevoDetalle->lote = $inv->lote;
                        $nuevoDetalle->pasillo_lock = $inv->ubi_pasillo;
                        $nuevoDetalle->save();
                        
                        $detalle->cantidad_solicitada -= $tomar;
                        $detalle->save();
                    }

                    $pendiente -= $tomar;
                }

                if ($pendiente > 0) {
                    $faltantes[] = [
                        'producto_id' => $detalle->producto_id,
                        'faltante' => $pendiente
                    ];
                    $detalle->estado = 'Faltante';
                    $detalle->save();
                    
                    // Trigger Auto-replenishment alert (to be handled asynchronously in future)
                } else {
                    $detalle->estado = 'EnProceso';
                    $detalle->save();
                }
            }

            $orden->estado = 'EnProceso';
            $orden->auxiliar_id = $user->id; // El que ejecutó la generación / a quien se le asigna
            $orden->hora_inicio = date('H:i:s');
            $orden->save();

            \Illuminate\Database\Capsule\Manager::connection()->commit();

            return $this->json($response, [
                'error' => false,
                'message' => empty($faltantes) ? 'Ruta y reserva FEFO generada con éxito.' : 'Ruta generada parcialmente. Existen faltantes (Quiebre de stock).',
                'faltantes' => $faltantes
            ]);

        } catch (\Exception $e) {
            \Illuminate\Database\Capsule\Manager::connection()->rollBack();
            return $this->json($response, ['error' => true, 'message' => 'Error al generar ruta FEFO: ' . $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/picking/{orden_id}/confirmar-linea
     * El operario confirma que tomó el ítem de la ubicación mediante swipe/scan
     */
    public function confirmLine(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');
        $orden_id = $args['orden_id'] ?? null;
        $data = $request->getParsedBody();

        $detalle_id = $data['detalle_id'] ?? null;
        $cantidad_pickeada = $data['cantidad_pickeada'] ?? 0;

        if (!$detalle_id || $cantidad_pickeada <= 0) {
            return $this->json($response, ['error' => true, 'message' => 'ID de detalle y cantidad son requeridos.'], 400);
        }

        \Illuminate\Database\Capsule\Manager::connection()->beginTransaction();

        try {
            $detalle = PickingDetalle::find($detalle_id);
            if (!$detalle || $detalle->orden_picking_id != $orden_id) {
                return $this->json($response, ['error' => true, 'message' => 'Línea de picking inválida.'], 404);
            }

            $orden = OrdenPicking::find($orden_id);

            // Descontar inventario (convertir la reserva en salida definitiva)
            $inventario = Inventario::where('empresa_id', $user->empresa_id)
                ->where('sucursal_id', $user->sucursal_id)
                ->where('producto_id', $detalle->producto_id)
                ->where('ubicacion_id', $detalle->ubicacion_id)
                ->where('lote', $detalle->lote)
                ->first();

            if ($inventario) {
                $inventario->cantidad -= $cantidad_pickeada;
                $inventario->cantidad_reservada -= $cantidad_pickeada;
                if ($inventario->cantidad_reservada < 0) $inventario->cantidad_reservada = 0;
                
                if ($inventario->cantidad <= 0) {
                    $inventario->delete(); // Vaciar ubicación
                } else {
                    if ($inventario->cantidad_reservada == 0) $inventario->estado = 'Disponible';
                    $inventario->save();
                }
            }

            $detalle->cantidad_pickeada += $cantidad_pickeada;
            if ($detalle->cantidad_pickeada >= $detalle->cantidad_solicitada) {
                $detalle->estado = 'Completado';
            }
            $detalle->save();

            // Log de Movimiento
            $mov = new MovimientoInventario();
            $mov->empresa_id = $user->empresa_id;
            $mov->sucursal_id = $user->sucursal_id;
            $mov->producto_id = $detalle->producto_id;
            $mov->ubicacion_origen_id = $detalle->ubicacion_id;
            $mov->ubicacion_destino_id = null; // Va para el cliente/camión
            $mov->tipo_movimiento = 'Picking';
            $mov->cantidad = $cantidad_pickeada;
            $mov->lote = $detalle->lote;
            $mov->referencia_tipo = 'picking';
            $mov->referencia_id = $orden->id;
            $mov->auxiliar_id = $user->id;
            $mov->fecha_movimiento = date('Y-m-d');
            $mov->hora_inicio = date('H:i:s');
            $mov->hora_fin = date('H:i:s');
            $mov->save();

            // Check if full order is completed
            $pendingLines = PickingDetalle::where('orden_picking_id', $orden_id)
                ->whereIn('estado', ['Pendiente', 'EnProceso'])
                ->count();
                
            if ($pendingLines === 0) {
                $orden->estado = 'Completada';
                $orden->hora_fin = date('H:i:s');
                $orden->save();
            }

            \Illuminate\Database\Capsule\Manager::connection()->commit();

            return $this->json($response, ['error' => false, 'message' => 'Línea de picking confirmada.']);
        } catch (\Exception $e) {
            \Illuminate\Database\Capsule\Manager::connection()->rollBack();
            return $this->json($response, ['error' => true, 'message' => 'Error al confirmar línea: ' . $e->getMessage()], 500);
        }
    }

    private function json(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
    }
}
