<?php
namespace App\Controllers;
/** DespachoController - WMS v2 - TMS Prep */
class DespachoController {
    public function listar($r,$res):Response{ return $this->j($res,['error'=>false]); }
    public function crear($r,$res):Response{ return $this->j($res,['error'=>false],201); }
    public function certificarItem($r,$res,$a):Response{ return $this->j($res,['error'=>false]); }
    public function estadoCertificacion($r,$res,$a):Response{ return $this->j($res,['error'=>false]); }
    public function certificar($r,$res,$a):Response{ return $this->j($res,['error'=>false]); }
    public function tmsExport($r,$res,$a):Response{ return $this->j($res,['error'=>false]); }
    public function tmsPush($r,$res,$a):Response{ return $this->j($res,['error'=>false]); }
    private function j($res,$d,$st=200):Response{ $res->getBody()->write(json_encode($d)); return $res->withStatus($st)->withHeader('Content-Type','application/json'); }
}
