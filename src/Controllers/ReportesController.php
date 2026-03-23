<?php
namespace App\Controllers;
/** ReportesController - WMS v2 - 9 Reportes */
class ReportesController {
    public function kardex($r,$res):Response{ return $this->k($res,['error'=>false]); }
    public function stockActual($r,$res):Response{ return $this->k($res,['error'=>false]); }
    public function recepciones($r,$res):Response{ return $this->k($res,['error'=>false]); }
    public function despachos($r,$res):Response{ return $this->k($res,['error'=>false]); }
    public function devoluciones($r,$res):Response{ return $this->k($res,['error'=>false]); }
    public function picking($r,$res):Response{ return $this->k($res,['error'=>false]); }
    public function conteos($r,$res):Response{ return $this->k($res,['error'=>false]); }
    public function odcReporte($r,$res):Response{ return $this->k($res,['error'=>false]); }
    public function dashboardGerencial($r,$res):Response{ return $this->k($res,['error'=>false]); }
    private function k($res,$d,$st=200):Response{ $res->getBody()->write(json_encode($d)); return $res->withStatus($st)->withHeader('Content-Type','application/json'); }
}
