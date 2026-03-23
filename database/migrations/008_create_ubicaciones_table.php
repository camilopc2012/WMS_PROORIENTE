<?php

use Illuminate\Database\Capsule\Manager as Capsule;

return [
    'up' => function () {
        Capsule::schema()->create('ubicaciones', function ($table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('sucursal_id');
            $table->string('codigo', 30);
            $table->string('zona', 10);
            $table->string('pasillo', 10);
            $table->string('nivel', 10);
            $table->string('posicion', 10)->nullable();
            $table->enum('tipo_ubicacion', ['Picking', 'Almacenamiento', 'Muelle', 'Carro', 'Patio']);
            $table->integer('capacidad_maxima')->default(0); // 0 = unlimited
            $table->enum('estado', ['Libre', 'Ocupada', 'Parcial', 'Locked'])->default('Libre');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->foreign('sucursal_id')->references('id')->on('sucursales')->onDelete('cascade');
            $table->unique(['sucursal_id', 'codigo']);
        });
    },
    'down' => function () {
        Capsule::schema()->dropIfExists('ubicaciones');
    },
];
