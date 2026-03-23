<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecepcionDetalle extends Model
{
    protected $table = 'recepcion_detalles';

    protected $fillable = [
        'recepcion_id', 'producto_id', 'cantidad_esperada', 'cantidad_recibida',
        'lote', 'fecha_vencimiento', 'estado_mercancia', 'novedad_motivo',
        'ubicacion_destino_id',
    ];

    protected $casts = [
        'fecha_vencimiento' => 'date',
    ];

    public function recepcion()
    {
        return $this->belongsTo(Recepcion::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function ubicacionDestino()
    {
        return $this->belongsTo(Ubicacion::class, 'ubicacion_destino_id');
    }
}
