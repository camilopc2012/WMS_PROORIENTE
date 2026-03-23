<?php

use Illuminate\Database\Capsule\Manager as Capsule;

return [
    'up' => function () {
        Capsule::schema()->create('inventarios', function ($table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('sucursal_id');
            $table->unsignedBigInteger('producto_id');
            $table->unsignedBigInteger('ubicacion_id');
            $table->string('lote', 50)->nullable();
            $table->date('fecha_vencimiento')->nullable();
            $table->integer('cantidad')->default(0);
            $table->integer('cantidad_reservada')->default(0);
            $table->enum('estado', ['Disponible', 'Reservado', 'Cuarentena', 'Obsoleto'])->default('Disponible');
            $table->timestamps();

            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->foreign('sucursal_id')->references('id')->on('sucursales')->onDelete('cascade');
            $table->foreign('producto_id')->references('id')->on('productos')->onDelete('restrict');
            $table->foreign('ubicacion_id')->references('id')->on('ubicaciones')->onDelete('restrict');
            $table->unique(['empresa_id', 'producto_id', 'ubicacion_id', 'lote'], 'inv_prod_ubic_lote_unique');
        });
    },
    'down' => function () {
        Capsule::schema()->dropIfExists('inventarios');
    },
];
