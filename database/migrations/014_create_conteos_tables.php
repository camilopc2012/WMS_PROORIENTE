<?php

use Illuminate\Database\Capsule\Manager as Capsule;

return [
    'up' => function () {
        Capsule::schema()->create('conteo_inventarios', function ($table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('sucursal_id');
            $table->enum('tipo_conteo', ['General', 'PorReferencia', 'PorUbicacion']);
            $table->enum('estado', ['Borrador', 'EnConteo', 'PendienteAprobacion', 'Aprobado', 'Rechazado'])->default('Borrador');
            $table->unsignedBigInteger('auxiliar_id');
            $table->unsignedBigInteger('aprobado_por')->nullable();
            $table->date('fecha_movimiento');
            $table->time('hora_inicio');
            $table->time('hora_fin')->nullable();
            $table->timestamps();

            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->foreign('sucursal_id')->references('id')->on('sucursales')->onDelete('cascade');
            $table->foreign('auxiliar_id')->references('id')->on('personal')->onDelete('restrict');
            $table->foreign('aprobado_por')->references('id')->on('personal')->onDelete('set null');
        });

        Capsule::schema()->create('conteo_detalles', function ($table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('conteo_id');
            $table->unsignedBigInteger('ubicacion_id');
            $table->unsignedBigInteger('producto_id');
            $table->string('lote', 50)->nullable();
            $table->integer('cantidad_sistema')->default(0);
            $table->integer('cantidad_fisica')->nullable();
            $table->integer('diferencia')->default(0);
            $table->boolean('es_hallazgo')->default(false);
            $table->enum('estado', ['Pendiente', 'Contado', 'Aprobado', 'Rechazado'])->default('Pendiente');
            $table->timestamps();

            $table->foreign('conteo_id')->references('id')->on('conteo_inventarios')->onDelete('cascade');
            $table->foreign('ubicacion_id')->references('id')->on('ubicaciones')->onDelete('restrict');
            $table->foreign('producto_id')->references('id')->on('productos')->onDelete('restrict');
        });
    },
    'down' => function () {
        Capsule::schema()->dropIfExists('conteo_detalles');
        Capsule::schema()->dropIfExists('conteo_inventarios');
    },
];
