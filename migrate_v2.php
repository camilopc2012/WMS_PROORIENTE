<?php
require 'bootstrap.php';
use Illuminate\Database\Capsule\Manager as Capsule;

try {
    // 1. Add 'modulo' to ubicaciones
    if (!Capsule::schema()->hasColumn('ubicaciones', 'modulo')) {
        Capsule::schema()->table('ubicaciones', function ($table) {
            $table->string('modulo', 10)->after('pasillo')->nullable();
        });
        echo "Columna 'modulo' añadida a 'ubicaciones'.\n";
    }

    // 2. Create 'rutas' table
    if (!Capsule::schema()->hasTable('rutas')) {
        Capsule::schema()->create('rutas', function ($table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->string('nombre');
            $table->string('comercial')->nullable();
            $table->string('frecuencia')->nullable(); // stored as days
            $table->boolean('activo')->default(true);
            $table->timestamps();
            
            $table->foreign('empresa_id')->references('id')->on('empresas');
        });
        echo "Tabla 'rutas' creada.\n";
    }

    // 3. Add 'ruta_id' to 'clientes'
    if (!Capsule::schema()->hasColumn('clientes', 'ruta_id')) {
        Capsule::schema()->table('clientes', function ($table) {
            $table->unsignedBigInteger('ruta_id')->after('empresa_id')->nullable();
            // Optional: $table->foreign('ruta_id')->references('id')->on('rutas');
        });
        echo "Columna 'ruta_id' añadida a 'clientes'.\n";
    }

    echo "Migración completada con éxito.\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
