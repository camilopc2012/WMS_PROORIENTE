<?php

use Illuminate\Database\Capsule\Manager as Capsule;

return [
    'up' => function () {
        Capsule::schema()->create('orden_pickings', function ($table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('sucursal_id');
            $table->string('numero_orden', 30)->unique();
            $table->string('cliente', 200)->nullable();
            $table->enum('estado', ['Pendiente', 'EnProceso', 'Completada', 'Cancelada'])->default('Pendiente');
            $table->integer('prioridad')->default(5);
            $table->unsignedBigInteger('auxiliar_id')->nullable();
            $table->date('fecha_movimiento');
            $table->time('hora_inicio');
            $table->time('hora_fin')->nullable();
            $table->date('fecha_requerida')->nullable();
            $table->timestamps();

            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->foreign('sucursal_id')->references('id')->on('sucursales')->onDelete('cascade');
            $table->foreign('auxiliar_id')->references('id')->on('personal')->onDelete('set null');
        });

        Capsule::schema()->create('picking_detalles', function ($table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('orden_picking_id');
            $table->unsignedBigInteger('producto_id');
            $table->unsignedBigInteger('ubicacion_id');
            $table->string('lote', 50)->nullable();
            $table->integer('cantidad_solicitada');
            $table->integer('cantidad_pickeada')->default(0);
            $table->string('pasillo_lock', 10)->nullable();
            $table->enum('estado', ['Pendiente', 'EnProceso', 'Completado', 'Faltante'])->default('Pendiente');
            $table->timestamps();

            $table->foreign('orden_picking_id')->references('id')->on('orden_pickings')->onDelete('cascade');
            $table->foreign('producto_id')->references('id')->on('productos')->onDelete('restrict');
            $table->foreign('ubicacion_id')->references('id')->on('ubicaciones')->onDelete('restrict');
        });

        Capsule::schema()->create('tarea_reabastecimientos', function ($table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('sucursal_id');
            $table->unsignedBigInteger('orden_picking_id');
            $table->unsignedBigInteger('producto_id');
            $table->unsignedBigInteger('ubicacion_origen_id');
            $table->unsignedBigInteger('ubicacion_destino_id');
            $table->integer('cantidad');
            $table->unsignedBigInteger('asignado_a')->nullable();
            $table->enum('estado', ['Pendiente', 'EnProceso', 'Completada'])->default('Pendiente');
            $table->date('fecha_movimiento');
            $table->time('hora_inicio');
            $table->time('hora_fin')->nullable();
            $table->timestamps();

            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->foreign('sucursal_id')->references('id')->on('sucursales')->onDelete('cascade');
            $table->foreign('orden_picking_id')->references('id')->on('orden_pickings')->onDelete('cascade');
            $table->foreign('producto_id')->references('id')->on('productos')->onDelete('restrict');
            $table->foreign('ubicacion_origen_id')->references('id')->on('ubicaciones')->onDelete('restrict');
            $table->foreign('ubicacion_destino_id')->references('id')->on('ubicaciones')->onDelete('restrict');
            $table->foreign('asignado_a')->references('id')->on('personal')->onDelete('set null');
        });
    },
    'down' => function () {
        Capsule::schema()->dropIfExists('tarea_reabastecimientos');
        Capsule::schema()->dropIfExists('picking_detalles');
        Capsule::schema()->dropIfExists('orden_pickings');
    },
];
