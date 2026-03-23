<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Despacho;
use App\Models\CertificacionDespacho;
use App\Models\OrdenPicking;
use App\Models\MovimientoInventario;
use App\Models\Inventario;

class DespachoController
{
    /**
     * POST /api/despachos
     * Iniciar un despacho
     */
    public function store(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');
        
        if (!$user->hasPermission('despacho', 'crear')) {
            return $this->json($response, ['error' => true, 'message' => 'No tienes permiso.'], 403);
        }

        $data = $request->getParsedBody();

        $despacho = new Despacho();
        $despacho->empresa_id = $user->empresa_id;
        $despacho->sucursal_id = $user->sucursal_id;
        $despacho->numero_despacho = 'DSP-' . time() . '-' . rand(10,99);
        $despacho->cliente = $data['cliente'] ?? null;
        $despacho->ruta = $data['ruta'] ?? null;
        $despacho->muelle_id = $data['muelle_id'] ?? null;
        $despacho->estado = 'Preparando';
        $despacho->auxiliar_id = $user->id;
        $despacho->fecha_movimiento = date('Y-m-d');
        $despacho->hora_inicio = date('H:i:s');
        $despacho->save();

        return $this->json($response, [
            'error' => false,
            'message' => 'Despacho iniciado.',
            'data' => $despacho
        ], 201);
    }

    /**
     * POST /api/despachos/{id}/certificar
     * Agregar ítems escaneados a la certificación del despacho
     */
    public function certify(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');
        $id = $args['id'] ?? null;

        $despacho = Despacho::find($id);
        if (!$despacho || $despacho->sucursal_id !== $user->sucursal_id || $despacho->estado !== 'Preparando') {
            return $this->json($response, ['error' => true, 'message' => 'Despacho inválido.'], 400);
        }

        $data = $request->getParsedBody();
        $producto_id = $data['producto_id'] ?? null;
        $cantidad = $data['cantidad'] ?? 0;

        if (!$producto_id || $cantidad <= 0) {
            return $this->json($response, ['error' => true, 'message' => 'Producto y cantidad son requeridos.'], 400);
        }

        // Add to CertificacionDespacho
        $cert = new CertificacionDespacho();
        $cert->despacho_id = $despacho->id;
        $cert->producto_id = $producto_id;
        $cert->lote = $data['lote'] ?? null;
        $cert->cantidad_certificada = $cantidad;
        $cert->escaneado_por = $user->id;
        $cert->escaneado_at = date('Y-m-d H:i:s');
        $cert->save();

        return $this->json($response, [
            'error' => false,
            'message' => 'Producto certificado para despacho.',
            'data' => $cert
        ]);
    }

    /**
     * POST /api/despachos/{id}/cerrar
     * Finaliza la auditoría de despacho
     */
    public function close(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');
        $id = $args['id'] ?? null;

        $despacho = Despacho::find($id);
        if (!$despacho || $despacho->sucursal_id !== $user->sucursal_id) {
            return $this->json($response, ['error' => true, 'message' => 'Despacho no encontrado.'], 404);
        }

        $despacho->estado = 'Certificado'; // Queda listo para 'Despachado' o se asume cerrado
        $despacho->hora_fin = date('H:i:s');
        
        // Sum properties for metadata
        $totalCajas = CertificacionDespacho::where('despacho_id', $despacho->id)->sum('cantidad_certificada');
        $despacho->total_bultos = $totalCajas;
        // logic to get total weight could be added here by joining with products

        $despacho->save();

        // Podríamos enlazar esto con la Orden de Picking y marcarlas como Despachadas totalmente
        return $this->json($response, ['error' => false, 'message' => 'Despacho certificado correctamente.', 'data' => $despacho]);
    }

    private function json(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
    }
}
