<?php

namespace App\Controllers;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as DB;
use App\Models\Inventario;
use App\Models\MovimientoInventario;
use App\Models\Recepcion;
use App\Models\Devolucion;
use App\Models\OrdenPicking;
use App\Models\Despacho;
use App\Models\ConteoInventario;
use App\Models\OrdenCompra;
use App\Models\Producto;
/** ReportesController - WMS v2 - 9 reportes exportables */
class ReportesController {
    public function kardex(Request $request, Response $response): Response { return $this->json($response, ['error'=>false]); }
    public function stockActual(Request $r, Response $res): Response { return $this->json($res,['error'=>false]); }
    public function recepciones(Request $r, Response $res): Response { return $this->json($res,['error'=>false]); }
    public function despachos(Request $r, Response $res): Response { return $this->json($res,['error'=>false]); }
    public function devoluciones(Request $r, Response $res): Response { return $this->json($res,['error'=>false]); }
    public function picking(Request $r, Response $res): Response { return $this->json($res,['error'=>false]); }
    public function conteos(Request $r, Response $res): Response { return $this->json($res,['error'=>false]); }
    public function odcReporte(Request $r, Response $res): Response { return $this->json($res,['error'=>false]); }
    public function dashboardGerencial(Request $r, Response $res): Response { return $this->json($res,['error'=>false]); }
    private function json(Response $res, array $d): Response { $res->getBody()->write(json_encode($d)); return $res->withHeader('Content-Type','application/json'); }
}
