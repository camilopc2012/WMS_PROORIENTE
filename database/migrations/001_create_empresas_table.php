<?php

use Illuminate\Database\Capsule\Manager as Capsule;

return [
    'up' => function () {
        Capsule::schema()->create('empresas', function ($table) {
            $table->bigIncrements('id');
            $table->string('nit', 20)->unique();
            $table->string('razon_social', 200);
            $table->string('direccion', 300)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('logo_url', 500)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    },
    'down' => function () {
        Capsule::schema()->dropIfExists('empresas');
    },
];
