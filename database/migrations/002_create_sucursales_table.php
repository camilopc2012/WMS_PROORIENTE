<?php

use Illuminate\Database\Capsule\Manager as Capsule;

return [
    'up' => function () {
        Capsule::schema()->create('sucursales', function ($table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('empresa_id');
            $table->string('codigo', 20);
            $table->string('nombre', 200);
            $table->string('direccion', 300)->nullable();
            $table->string('ciudad', 100)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->enum('tipo', ['Bodega', 'CEDI', 'Sucursal', 'Planta'])->default('Bodega');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->unique(['empresa_id', 'codigo']);
        });
    },
    'down' => function () {
        Capsule::schema()->dropIfExists('sucursales');
    },
];
