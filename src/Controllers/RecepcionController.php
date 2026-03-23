<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Recepcion;
use App\Models\RecepcionDetalle;
use App\Models\Cita;
use App\Models\Producto;

class RecepcionController
{
    /**
     * POST /api/recepciones
     * Iniciar una recepción (Cabecera)
     */
    public function store(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');
        
        if (!$user->hasPermission('recepcion', 'crear')) {
            return $this->json($response, ['error' => true, 'message' => 'No tienes permiso.'], 403);
        }

        $data = $request->getParsedBody();

        $cita_id = $data['cita_id'] ?? null;
        $modo_ciego = $data['modo_ciego'] ?? false;
        $observaciones = $data['observaciones'] ?? '';

        if ($cita_id) {
            $cita = Cita::find($cita_id);
            if (!$cita || $cita->sucursal_id !== $user->sucursal_id) {
                return $this->json($response, ['error' => true, 'message' => 'Cita inválida.'], 400);
            }
            $cita->estado = 'EnCurso';
            $cita->save();
        }

        $recepcion = new Recepcion();
        $recepcion->empresa_id = $user->empresa_id;
        $recepcion->sucursal_id = $user->sucursal_id;
        $recepcion->cita_id = $cita_id;
        // Generate unique recepcion number
        $recepcion->numero_recepcion = 'RC-' . time() . '-' . rand(10,99);
        $recepcion->auxiliar_id = $user->id;
        $recepcion->modo_ciego = $modo_ciego;
        $recepcion->estado = 'Borrador';
        $recepcion->fecha_movimiento = date('Y-m-d');
        $recepcion->hora_inicio = date('H:i:s');
        $recepcion->observaciones = $observaciones;
        
        $recepcion->save();

        return $this->json($response, [
            'error' => false,
            'message' => 'Recepción iniciada.',
            'data' => $recepcion
        ], 201);
    }

    /**
     * POST /api/recepciones/{id}/detalle
     * Agregar una línea a la recepción
     */
    public function addDetail(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');
        $id = $args['id'] ?? null;

        $recepcion = Recepcion::find($id);
        if (!$recepcion || $recepcion->sucursal_id !== $user->sucursal_id || $recepcion->estado !== 'Borrador') {
            return $this->json($response, ['error' => true, 'message' => 'Recepción inválida o ya cerrada.'], 400);
        }

        $data = $request->getParsedBody();

        $producto_id = $data['producto_id'] ?? null;
        $cantidad_recibida = $data['cantidad_recibida'] ?? 0;
        
        if (!$producto_id || $cantidad_recibida <= 0) {
            return $this->json($response, ['error' => true, 'message' => 'Producto y cantidad validos son requeridos.'], 400);
        }

        $producto = Producto::find($producto_id);
        if (!$producto) {
            return $this->json($response, ['error' => true, 'message' => 'Producto inexistente.'], 404);
        }

        // FEFO Validation for Inbound
        $fecha_vencimiento = $data['fecha_vencimiento'] ?? null;
        if ($producto->controla_vencimiento && !$fecha_vencimiento) {
            return $this->json($response, ['error' => true, 'message' => "El producto {$producto->codigo_interno} requiere fecha de vencimiento (FEFO)."], 400);
        }

        $detalle = new RecepcionDetalle();
        $detalle->recepcion_id = $recepcion->id;
        $detalle->producto_id = $producto_id;
        
        // Blind mode logic: Only store expected if NOT in blind mode (handled by client, but we enforce)
        $detalle->cantidad_esperada = $recepcion->modo_ciego ? 0 : ($data['cantidad_esperada'] ?? 0);
        $detalle->cantidad_recibida = $cantidad_recibida;
        $detalle->lote = $data['lote'] ?? null;
        $detalle->fecha_vencimiento = $fecha_vencimiento;
        $detalle->estado_mercancia = $data['estado_mercancia'] ?? 'BuenEstado';
        $detalle->novedad_motivo = $data['novedad_motivo'] ?? null;

        // Por defecto va a zona PATIO (Recepcion) o ubicacion default virtual
        $patio_id = \App\Models\Ubicacion::where('sucursal_id', $user->sucursal_id)
                                ->where('tipo_ubicacion', 'Patio')
                                ->value('id');
        $detalle->ubicacion_destino_id = $patio_id;

        $detalle->save();

        return $this->json($response, [
            'error' => false,
            'message' => 'Línea de recepción agregada.',
            'data' => $detalle
        ]);
    }

    /**
     * POST /api/recepciones/{id}/confirm
     * Cierra la recepción y afecta el inventario (ledger)
     */
    public function confirm(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');
        $id = $args['id'] ?? null;

        $recepcion = Recepcion::with('detalles')->find($id);
        if (!$recepcion || $recepcion->sucursal_id !== $user->sucursal_id) {
            return $this->json($response, ['error' => true, 'message' => 'Recepción no encontrada.'], 404);
        }

        if ($recepcion->estado !== 'Borrador') {
            return $this->json($response, ['error' => true, 'message' => "La recepción ya se encuentra {$recepcion->estado}."], 400);
        }

        \Illuminate\Database\Capsule\Manager::connection()->beginTransaction();
        
        try {
            $recepcion->estado = 'Cerrada';
            $recepcion->hora_fin = date('H:i:s');
            $recepcion->save();

            foreach ($recepcion->detalles as $linea) {
                if ($linea->estado_mercancia !== 'BuenEstado') {
                    // Si viene averiada, va a deposito Obsoleto o Cuarentena (logic for devoluciones later)
                    // For now, allow it to go into the default location but flagged (handled by external process or direct to separate virtual bin)
                }

                // Escribir en log Inmutable
                $movimiento = new \App\Models\MovimientoInventario();
                $movimiento->empresa_id = $recepcion->empresa_id;
                $movimiento->sucursal_id = $recepcion->sucursal_id;
                $movimiento->producto_id = $linea->producto_id;
                $movimiento->ubicacion_origen_id = null; // Viene del proveedor
                $movimiento->ubicacion_destino_id = $linea->ubicacion_destino_id;
                $movimiento->tipo_movimiento = 'Entrada';
                $movimiento->cantidad = $linea->cantidad_recibida;
                $movimiento->lote = $linea->lote;
                $movimiento->fecha_vencimiento = $linea->fecha_vencimiento;
                $movimiento->referencia_tipo = 'recepcion';
                $movimiento->referencia_id = $recepcion->id;
                $movimiento->auxiliar_id = $user->id;
                $movimiento->fecha_movimiento = date('Y-m-d');
                $movimiento->hora_inicio = $recepcion->hora_inicio;
                $movimiento->hora_fin = $recepcion->hora_fin;
                $movimiento->save();

                // Actualizar Inventario (stock master)
                $inventario = \App\Models\Inventario::firstOrNew([
                    'empresa_id' => $recepcion->empresa_id,
                    'sucursal_id' => $recepcion->sucursal_id,
                    'producto_id' => $linea->producto_id,
                    'ubicacion_id' => $linea->ubicacion_destino_id,
                    'lote' => $linea->lote
                ]);
                $inventario->fecha_vencimiento = $linea->fecha_vencimiento; // Update expiry if exists
                if (!$inventario->exists) {
                    $inventario->cantidad = 0;
                    $inventario->cantidad_reservada = 0;
                    $inventario->estado = 'Disponible';
                }
                $inventario->cantidad += $linea->cantidad_recibida;
                $inventario->save();
            }

            // Si viene vinculada a una cita, cerrarla
            if ($recepcion->cita_id) {
                $cita = Cita::find($recepcion->cita_id);
                if ($cita) {
                    $cita->estado = 'Completada';
                    $cita->save();
                }
            }

            \Illuminate\Database\Capsule\Manager::connection()->commit();

            return $this->json($response, ['error' => false, 'message' => 'Recepción confirmada. Inventario actualizado.']);
        } catch (\Exception $e) {
            \Illuminate\Database\Capsule\Manager::connection()->rollBack();
            return $this->json($response, ['error' => true, 'message' => 'Error al confirmar recepción: ' . $e->getMessage()], 500);
        }
    }

    private function json(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
    }
}
