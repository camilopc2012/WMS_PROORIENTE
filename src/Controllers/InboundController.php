<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\OrdenCompra;
use App\Models\OrdenCompraDetalle;
use Illuminate\Database\Capsule\Manager as Capsule;

class InboundController
{
    /**
     * GET /api/odc
     */
    public function getOrdenesCompra(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');
        $odcs = OrdenCompra::where('empresa_id', $user->empresa_id)
            ->with('proveedor')
            ->orderBy('fecha', 'desc')
            ->get();
        return $this->json($response, ['data' => $odcs]);
    }

    /**
     * POST /api/odc
     */
    public function createOrdenCompra(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');
        $data = $request->getParsedBody();

        Capsule::beginTransaction();
        try {
            $odc = OrdenCompra::create([
                'empresa_id' => $user->empresa_id,
                'proveedor_id' => $data['proveedor_id'],
                'numero_odc' => $data['numero_odc'],
                'fecha' => $data['fecha'],
                'estado' => 'Pendiente',
                'observaciones' => $data['observaciones'] ?? null,
            ]);

            foreach ($data['detalles'] as $item) {
                OrdenCompraDetalle::create([
                    'orden_compra_id' => $odc->id,
                    'producto_id' => $item['producto_id'],
                    'cantidad_solicitada' => $item['cantidad_solicitada'],
                ]);
            }

            Capsule::commit();
            return $this->json($response, ['error' => false, 'message' => 'ODC creada con éxito', 'id' => $odc->id]);
        } catch (\Exception $e) {
            Capsule::rollBack();
            return $this->json($response, ['error' => true, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * GET /api/odc/{id}
     */
    public function getODC(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');
        $odc = OrdenCompra::where('empresa_id', $user->empresa_id)
            ->with(['proveedor', 'detalles.producto'])
            ->find($args['id']);
        
        if (!$odc) return $this->json($response, ['error' => true, 'message' => 'ODC no encontrada'], 404);
        return $this->json($response, ['data' => $odc]);
    }

    private function json(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
    }
}
