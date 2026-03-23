<?php
namespace App\Controllers;
/** PutawayController - WMS v2 - Almacenamiento */
class PutawayController {
    public function listarPatio($r,$res):Response{ return $this->j($res,['error'=>false]); }
    public function sugerirUbicacion($r,$res):Response{ return $this->j($res,['error'=>false]); }
    public function ubicar($r,$res):Response{ return $this->j($res,['error'=>false]); }
    public function trasladar($r,$res):Response{ return $this->j($res,['error'=>false]); }
    public function resolverEan($r,$res):Response{ return $this->j($res,['error'=>false]); }
    private function j($res,$d,$st=200):Response{ $res->getBody()->write(json_encode($d)); return $res->withStatus($st)->withHeader('Content-Type','application/json'); }
}
