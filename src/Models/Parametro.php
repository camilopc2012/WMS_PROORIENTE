<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Parametro extends Model
{
    protected $table = 'parametros';

    protected $fillable = [
        'empresa_id', 'sucursal_id', 'clave', 'valor', 'descripcion',
    ];

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }
}
