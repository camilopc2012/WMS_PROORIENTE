<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Devolucion;
use App\Models\DevolucionDetalle;
use App\Models\Inventario;
use App\Models\MovimientoInventario;
use App\Models\Ubicacion;

class DevolucionController
{
    /**
     * POST /api/devoluciones
     * Iniciar proceso de devolución (crear encabezado y líneas)
     */
    public function store(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');
        
        if (!$user->hasPermission('recepcion', 'crear')) {
            return $this->json($response, ['error' => true, 'message' => 'No tienes permiso.'], 403);
        }

        $data = $request->getParsedBody();

        $tipo = $data['tipo'] ?? 'ReingresoBuenEstado';
        $motivo_general = $data['motivo_general'] ?? '';
        $detalles = $data['detalles'] ?? [];

        if (empty($detalles) || !is_array($detalles)) {
            return $this->json($response, ['error' => true, 'message' => 'Debe incluir al menos un producto a devolver.'], 400);
        }

        \Illuminate\Database\Capsule\Manager::connection()->beginTransaction();

        try {
            $devolucion = new Devolucion();
            $devolucion->empresa_id = $user->empresa_id;
            $devolucion->sucursal_id = $user->sucursal_id;
            $devolucion->recepcion_id = $data['recepcion_id'] ?? null;
            $devolucion->proveedor = $data['proveedor'] ?? null;
            $devolucion->numero_devolucion = 'DEV-' . time() . '-' . rand(10,99);
            $devolucion->tipo = $tipo;
            $devolucion->estado = 'Procesada'; // Directamente procesada por ahora
            $devolucion->motivo_general = $motivo_general;
            $devolucion->auxiliar_id = $user->id;
            $devolucion->fecha_movimiento = date('Y-m-d');
            $devolucion->hora_inicio = date('H:i:s');
            $devolucion->hora_fin = date('H:i:s');
            $devolucion->save();

            // Buscar la ubicación virtual adecuada (OBSOLETO o PATIO)
            $ubicacion_obsoleto = Ubicacion::where('sucursal_id', $user->sucursal_id)->where('tipo_ubicacion', 'Patio')->where('codigo', 'OBSOLETO')->first();
            $ubicacion_patio = Ubicacion::where('sucursal_id', $user->sucursal_id)->where('tipo_ubicacion', 'Patio')->where('codigo', 'PATIO')->first();

            foreach ($detalles as $linea) {
                $destino = $linea['destino'] ?? 'InventarioObsoleto';
                $ubicacion_destino_id = ($destino === 'InventarioObsoleto') ? ($ubicacion_obsoleto ? $ubicacion_obsoleto->id : null) : ($ubicacion_patio ? $ubicacion_patio->id : null);

                $detalle = new DevolucionDetalle();
                $detalle->devolucion_id = $devolucion->id;
                $detalle->producto_id = $linea['producto_id'];
                $detalle->lote = $linea['lote'] ?? null;
                $detalle->fecha_vencimiento = $linea['fecha_vencimiento'] ?? null;
                $detalle->cantidad = $linea['cantidad'];
                $detalle->motivo = $linea['motivo'] ?? 'Otro';
                $detalle->detalle_motivo = $linea['detalle_motivo'] ?? null;
                $detalle->destino = $destino;
                $detalle->ubicacion_destino_id = $ubicacion_destino_id;
                $detalle->save();

                // Registrar en MovimientoInventario
                $movimiento = new MovimientoInventario();
                $movimiento->empresa_id = $user->empresa_id;
                $movimiento->sucursal_id = $user->sucursal_id;
                $movimiento->producto_id = $linea['producto_id'];
                $movimiento->ubicacion_origen_id = null; // Viene del usuario/proveedor o zona perdida
                $movimiento->ubicacion_destino_id = $ubicacion_destino_id;
                $movimiento->tipo_movimiento = 'Devolucion';
                $movimiento->cantidad = $linea['cantidad'];
                $movimiento->lote = $linea['lote'] ?? null;
                $movimiento->fecha_vencimiento = $linea['fecha_vencimiento'] ?? null;
                $movimiento->referencia_tipo = 'devolucion';
                $movimiento->referencia_id = $devolucion->id;
                $movimiento->auxiliar_id = $user->id;
                $movimiento->fecha_movimiento = date('Y-m-d');
                $movimiento->hora_inicio = $devolucion->hora_inicio;
                $movimiento->hora_fin = $devolucion->hora_fin;
                $movimiento->save();

                // Actualizar inventario en la ubicación destino virtual (Obsoleto o Patio)
                if ($ubicacion_destino_id) {
                    $inventario = Inventario::firstOrNew([
                        'empresa_id' => $user->empresa_id,
                        'sucursal_id' => $user->sucursal_id,
                        'producto_id' => $linea['producto_id'],
                        'ubicacion_id' => $ubicacion_destino_id,
                        'lote' => $linea['lote'] ?? null
                    ]);
                    if (!$inventario->exists) {
                        $inventario->cantidad = 0;
                        $inventario->cantidad_reservada = 0;
                        $inventario->estado = ($destino === 'InventarioObsoleto') ? 'Obsoleto' : 'Disponible';
                    }
                    if (!empty($linea['fecha_vencimiento'])) {
                        $inventario->fecha_vencimiento = $linea['fecha_vencimiento'];
                    }
                    $inventario->cantidad += $linea['cantidad'];
                    $inventario->save();
                }
            }

            \Illuminate\Database\Capsule\Manager::connection()->commit();

            return $this->json($response, ['error' => false, 'message' => 'Devolución procesada. Inventario actualizado.', 'data' => $devolucion], 201);
        } catch (\Exception $e) {
            \Illuminate\Database\Capsule\Manager::connection()->rollBack();
            return $this->json($response, ['error' => true, 'message' => 'Error al procesar devolución: ' . $e->getMessage()], 500);
        }
    }

    private function json(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
    }
}
