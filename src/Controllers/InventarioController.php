<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Inventario;
use App\Models\MovimientoInventario;

class InventarioController
{
    /**
     * POST /api/inventario/traslado
     * Traslada inventario entre ubicaciones (Putaway o reubicación)
     */
    public function traslado(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');

        if (!$user->hasPermission('inventario', 'transferir')) {
            return $this->json($response, ['error' => true, 'message' => 'No tienes permiso para trasladar inventario.'], 403);
        }

        $data = $request->getParsedBody();

        $producto_id = $data['producto_id'] ?? null;
        $ubicacion_origen_id = $data['ubicacion_origen_id'] ?? null;
        $ubicacion_destino_id = $data['ubicacion_destino_id'] ?? null;
        $cantidad = $data['cantidad'] ?? 0;
        $lote = $data['lote'] ?? null;

        if (!$producto_id || !$ubicacion_origen_id || !$ubicacion_destino_id || $cantidad <= 0) {
            return $this->json($response, ['error' => true, 'message' => 'Parámetros inválidos para traslado.'], 400);
        }

        if ($ubicacion_origen_id == $ubicacion_destino_id) {
            return $this->json($response, ['error' => true, 'message' => 'La ubicación origen y destino deben ser distintas.'], 400);
        }

        \Illuminate\Database\Capsule\Manager::connection()->beginTransaction();

        try {
            // Validar origen
            $invOrigen = Inventario::where('empresa_id', $user->empresa_id)
                ->where('sucursal_id', $user->sucursal_id)
                ->where('producto_id', $producto_id)
                ->where('ubicacion_id', $ubicacion_origen_id)
                ->where('lote', $lote)
                ->first();

            if (!$invOrigen || $invOrigen->cantidad < $cantidad) {
                return $this->json($response, ['error' => true, 'message' => 'Inventario insuficiente en origen.'], 400);
            }

            // Descontar de origen
            $invOrigen->cantidad -= $cantidad;
            $invOrigen->save();

            // Aumentar en destino
            $invDestino = Inventario::firstOrNew([
                'empresa_id' => $user->empresa_id,
                'sucursal_id' => $user->sucursal_id,
                'producto_id' => $producto_id,
                'ubicacion_id' => $ubicacion_destino_id,
                'lote' => $lote
            ]);

            if (!$invDestino->exists) {
                $invDestino->fecha_vencimiento = $invOrigen->fecha_vencimiento;
                $invDestino->cantidad = 0;
                $invDestino->estado = 'Disponible';
            }
            $invDestino->cantidad += $cantidad;
            $invDestino->save();

            // Log de Movimiento (Traslado)
            $mov = new MovimientoInventario();
            $mov->empresa_id = $user->empresa_id;
            $mov->sucursal_id = $user->sucursal_id;
            $mov->producto_id = $producto_id;
            $mov->ubicacion_origen_id = $ubicacion_origen_id;
            $mov->ubicacion_destino_id = $ubicacion_destino_id;
            $mov->tipo_movimiento = 'Traslado';
            $mov->cantidad = $cantidad;
            $mov->lote = $lote;
            $mov->fecha_vencimiento = $invOrigen->fecha_vencimiento;
            $mov->referencia_tipo = 'traslado_manual';
            $mov->auxiliar_id = $user->id;
            $mov->fecha_movimiento = date('Y-m-d');
            $mov->hora_inicio = date('H:i:s');
            $mov->hora_fin = date('H:i:s');
            $mov->save();

            \Illuminate\Database\Capsule\Manager::connection()->commit();

            return $this->json($response, ['error' => false, 'message' => 'Traslado completado con éxito.']);
        } catch (\Exception $e) {
            \Illuminate\Database\Capsule\Manager::connection()->rollBack();
            return $this->json($response, ['error' => true, 'message' => 'Error en traslado: ' . $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/inventario/conteo/nuevo
     */
    public function crearConteo(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');
        $data = $request->getParsedBody();
        
        $conteo = \App\Models\ConteoInventario::create([
            'empresa_id' => $user->empresa_id,
            'sucursal_id' => $user->sucursal_id,
            'tipo_conteo' => $data['tipo'] ?? 'General',
            'estado' => 'Abierto',
            'auxiliar_id' => $user->id,
            'fecha_movimiento' => date('Y-m-d'),
            'hora_inicio' => date('H:i:s')
        ]);

        return $this->json($response, ['error' => false, 'data' => $conteo]);
    }

    /**
     * POST /api/inventario/conteo/{id}/finalizar
     */
    public function finalizarConteo(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');
        $id = $args['id'];
        
        $conteo = \App\Models\ConteoInventario::where('id', $id)
            ->where('empresa_id', $user->empresa_id)
            ->first();
            
        if (!$conteo) return $this->json($response, ['error' => true, 'message' => 'Conteo no encontrado'], 404);

        $conteo->estado = 'Cerrado';
        $conteo->hora_fin = date('H:i:s');
        $conteo->save();

        return $this->json($response, ['error' => false, 'message' => 'Conteo finalizado']);
    }

    /**
     * GET /api/inventario/conteos
     */
    public function getConteos(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');
        $conteos = \App\Models\ConteoInventario::where('empresa_id', $user->empresa_id)
            ->where('sucursal_id', $user->sucursal_id)
            ->orderBy('created_at', 'desc')
            ->get();
            
        return $this->json($response, ['error' => false, 'data' => $conteos]);
    }

    private function json(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
    }
}
