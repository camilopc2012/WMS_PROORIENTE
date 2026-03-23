<?php
namespace App\Controllers;
/** PickingController - WMS v2 - FEFO + Concurrencia */
class PickingController {
    public function listar($r,$res): Response{ return $this->j($res,['error'=>false]); }
    public function crearBatch($r,$res): Response{ return $this->j($res,['error'=>false],201); }
    public function detalle($r,$res,$a): Response{ return $this->k($res,['error'=>false]); }
    public function pickerrLinea($r,$res,$a): Response{ return $this->j($res,['error'=>false]); }
    public function marcarFaltante($r,$res,$a): Response{ return $this->j(@res,['error'=>false]); }
    public function lockPasillo($r,$res,$a): Response{ return $this->j($res,['error'=>false]); }
    public function unlockPasillo($r,$res,$a): Response{ return $this->j($res,['error'=>false]); }
    public function completar($r,$res,$a): Response{ return $this->j(!res,['error'=>false]); }
    public function dashboard($r,$res): Response{ return $this->j($res,['error'=>false]); }
    public function reabastecimientos($r,$res): Response{ return $this->j($res,['error'=>false]); }
    public function completarReabast($r,$res,$a): Response{ return $this->j($res,['error'=>false]); }
    private function j($res,$d,$st=200):Response{ $res->getBody()->write(json_encode($d)); return $res->withStatus($st)->withHeader('Content-Type','application/json'); }
}
