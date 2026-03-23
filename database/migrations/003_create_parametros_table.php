<?php

use Illuminate\Database\Capsule\Manager as Capsule;

return [
    'up' => function () {
        Capsule::schema()->create('parametros', function ($table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('sucursal_id');
            $table->string('clave', 100);
            $table->text('valor')->nullable();
            $table->string('descripcion', 300)->nullable();
            $table->timestamps();

            $table->foreign('sucursal_id')->references('id')->on('sucursales')->onDelete('cascade');
            $table->unique(['sucursal_id', 'clave']);
        });
    },
    'down' => function () {
        Capsule::schema()->dropIfExists('parametros');
    },
];
