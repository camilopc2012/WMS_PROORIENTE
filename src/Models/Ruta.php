<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ruta extends Model
{
    protected $table = 'rutas';
    protected $fillable = [
        'empresa_id',
        'nombre',
        'comercial',
        'frecuencia', // Store as JSON or comma-separated string of days
        'activo'
    ];

    protected $casts = [
        'activo' => 'boolean'
    ];

    public function clientes()
    {
        return $this->hasMany(Cliente::class);
    }
}
