<?php

use Illuminate\Database\Capsule\Manager as Capsule;

return [
    'up' => function () {
        Capsule::schema()->create('proveedores', function ($table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('empresa_id');
            $table->string('nit', 20);
            $table->string('razon_social', 200);
            $table->string('telefono', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('contacto_nombre', 150)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->unique(['empresa_id', 'nit']);
        });
    },
    'down' => function () {
        Capsule::schema()->dropIfExists('proveedores');
    },
];
