<?php

use Illuminate\Database\Capsule\Manager as Capsule;

return [
    'up' => function () {
        Capsule::schema()->create('recepciones', function ($table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('sucursal_id');
            $table->unsignedBigInteger('cita_id')->nullable();
            $table->string('numero_recepcion', 30)->unique();
            $table->unsignedBigInteger('auxiliar_id');
            $table->boolean('modo_ciego')->default(false);
            $table->enum('estado', ['Borrador', 'Confirmada', 'Cerrada'])->default('Borrador');
            $table->date('fecha_movimiento');
            $table->time('hora_inicio');
            $table->time('hora_fin')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->foreign('sucursal_id')->references('id')->on('sucursales')->onDelete('cascade');
            $table->foreign('cita_id')->references('id')->on('citas')->onDelete('set null');
            $table->foreign('auxiliar_id')->references('id')->on('personal')->onDelete('restrict');
        });

        Capsule::schema()->create('recepcion_detalles', function ($table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('recepcion_id');
            $table->unsignedBigInteger('producto_id');
            $table->integer('cantidad_esperada')->default(0);
            $table->integer('cantidad_recibida');
            $table->string('lote', 50)->nullable();
            $table->date('fecha_vencimiento')->nullable();
            $table->enum('estado_mercancia', ['BuenEstado', 'Averia', 'Vencido', 'Sobrante', 'Faltante'])->default('BuenEstado');
            $table->text('novedad_motivo')->nullable();
            $table->unsignedBigInteger('ubicacion_destino_id')->nullable();
            $table->timestamps();

            $table->foreign('recepcion_id')->references('id')->on('recepciones')->onDelete('cascade');
            $table->foreign('producto_id')->references('id')->on('productos')->onDelete('restrict');
            $table->foreign('ubicacion_destino_id')->references('id')->on('ubicaciones')->onDelete('set null');
        });
    },
    'down' => function () {
        Capsule::schema()->dropIfExists('recepcion_detalles');
        Capsule::schema()->dropIfExists('recepciones');
    },
];
