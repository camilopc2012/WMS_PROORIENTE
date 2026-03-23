<?php

use Illuminate\Database\Capsule\Manager as Capsule;

return [
    'up' => function () {
        Capsule::schema()->create('citas', function ($table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('sucursal_id');
            $table->string('proveedor', 200);
            $table->date('fecha');
            $table->time('hora_programada');
            $table->integer('cantidad_cajas')->default(0);
            $table->string('tipo_vehiculo', 50)->nullable();
            $table->decimal('kilos', 10, 2)->default(0);
            $table->string('odc', 50)->nullable();
            $table->enum('estado', ['Programada', 'EnCurso', 'Completada', 'Cancelada'])->default('Programada');
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->foreign('sucursal_id')->references('id')->on('sucursales')->onDelete('cascade');
        });
    },
    'down' => function () {
        Capsule::schema()->dropIfExists('citas');
    },
];
