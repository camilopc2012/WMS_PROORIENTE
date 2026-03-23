<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cita extends Model
{
    protected $table = 'citas';

    protected $fillable = [
        'empresa_id', 'sucursal_id', 'proveedor', 'fecha', 'hora_programada',
        'cantidad_cajas', 'tipo_vehiculo', 'kilos', 'odc', 'estado', 'notas',
    ];

    protected $casts = [
        'fecha' => 'date',
        'kilos' => 'decimal:2',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function recepciones()
    {
        return $this->hasMany(Recepcion::class);
    }
}
