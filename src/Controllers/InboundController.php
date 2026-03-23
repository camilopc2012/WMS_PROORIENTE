<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\OrdenCompra;
use App\Models\OrdenCompraDetalle;
use App\Models\Producto;
use App\Models\ProductoEan;
use Illuminate\Database\Capsule\Manager as Capsule;

/** InboundController — WMS v2 */
class InboundController {
    public function getOrdenesCompra($req,$res):Response{ return $this->json($res,['error'=>false]); }
    public function getODC($req,$res,$a):Response{ return $this->json($res,['error'=>false]); }
    public function buscarProducto($req,$res):Response{ return $this->json($res,['error'=>false]); }
    public function createOrdenCompra($req,$res):Response{ return $this->json($res,['error'=>false]); }
    public function updateOrdenCompra($req,$res,$a):Response{ return $this->json($res,['error'=>false]); }
    public function exportarODC($req,$res,$a):Response{ return $this->json($res,['error'=>false]); }
    public function templateImportacion($req,$res):Response{ return $this->json($res,['error'=>false]); }
    public function importarODC($req,$res):Response{ return $this->json($res,['error'=>false]); }
    private function json($res,$d):Response{ $res->getBody()->write(json_encode($d)); return $res->withHeader('Content-Type','application/json'); }
}
