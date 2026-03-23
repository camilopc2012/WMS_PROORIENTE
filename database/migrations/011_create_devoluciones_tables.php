<?php

use Illuminate\Database\Capsule\Manager as Capsule;

return [
    'up' => function () {
        Capsule::schema()->create('devoluciones', function ($table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('sucursal_id');
            $table->unsignedBigInteger('recepcion_id')->nullable();
            $table->string('numero_devolucion', 30)->unique();
            $table->string('proveedor', 200)->nullable();
            $table->enum('tipo', ['AProveedorAveria', 'AProveedorVencido', 'ReingresoBuenEstado']);
            $table->unsignedBigInteger('auxiliar_id');
            $table->date('fecha_movimiento');
            $table->time('hora_inicio');
            $table->time('hora_fin')->nullable();
            $table->enum('estado', ['Borrador', 'Aprobada', 'Procesada'])->default('Borrador');
            $table->text('motivo_general');
            $table->timestamps();

            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->foreign('sucursal_id')->references('id')->on('sucursales')->onDelete('cascade');
            $table->foreign('recepcion_id')->references('id')->on('recepciones')->onDelete('set null');
            $table->foreign('auxiliar_id')->references('id')->on('personal')->onDelete('restrict');
        });

        Capsule::schema()->create('devolucion_detalles', function ($table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('devolucion_id');
            $table->unsignedBigInteger('producto_id');
            $table->string('lote', 50)->nullable();
            $table->date('fecha_vencimiento')->nullable();
            $table->integer('cantidad');
            $table->enum('motivo', ['Averia', 'Vencido', 'ErrorProveedor', 'CalidadDeficiente', 'Otro']);
            $table->text('detalle_motivo')->nullable();
            $table->enum('destino', ['InventarioObsoleto', 'Reingreso', 'DevolucionProveedor']);
            $table->unsignedBigInteger('ubicacion_destino_id')->nullable();
            $table->timestamps();

            $table->foreign('devolucion_id')->references('id')->on('devoluciones')->onDelete('cascade');
            $table->foreign('producto_id')->references('id')->on('productos')->onDelete('restrict');
            $table->foreign('ubicacion_destino_id')->references('id')->on('ubicaciones')->onDelete('set null');
        });
    },
    'down' => function () {
        Capsule::schema()->dropIfExists('devolucion_detalles');
        Capsule::schema()->dropIfExists('devoluciones');
    },
];
