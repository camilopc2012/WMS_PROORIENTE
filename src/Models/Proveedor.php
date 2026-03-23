<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    protected $table = 'proveedores';
    protected $fillable = [
        'empresa_id', 'nit', 'razon_social', 'telefono', 'email', 'contacto_nombre', 'activo'
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }
}
