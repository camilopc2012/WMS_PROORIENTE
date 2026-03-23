<?php

use Illuminate\Database\Capsule\Manager as Capsule;

return [
    'up' => function () {
        // Immutable transaction log — NO updated_at
        Capsule::schema()->create('movimiento_inventarios', function ($table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('sucursal_id');
            $table->unsignedBigInteger('producto_id');
            $table->unsignedBigInteger('ubicacion_origen_id')->nullable();
            $table->unsignedBigInteger('ubicacion_destino_id')->nullable();
            $table->enum('tipo_movimiento', [
                'Entrada', 'Salida', 'Traslado',
                'AjustePositivo', 'AjusteNegativo',
                'Picking', 'Reabastecimiento', 'Devolucion'
            ]);
            $table->integer('cantidad');
            $table->string('lote', 50)->nullable();
            $table->date('fecha_vencimiento')->nullable();
            $table->string('referencia_tipo', 50)->nullable(); // polymorphic: recepcion, picking, conteo, devolucion
            $table->unsignedBigInteger('referencia_id')->nullable();
            $table->unsignedBigInteger('auxiliar_id');
            $table->date('fecha_movimiento');
            $table->time('hora_inicio');
            $table->time('hora_fin')->nullable();
            $table->text('observaciones')->nullable();
            // Only created_at — immutable log, no updated_at
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->foreign('sucursal_id')->references('id')->on('sucursales')->onDelete('cascade');
            $table->foreign('producto_id')->references('id')->on('productos')->onDelete('restrict');
            $table->foreign('ubicacion_origen_id')->references('id')->on('ubicaciones')->onDelete('set null');
            $table->foreign('ubicacion_destino_id')->references('id')->on('ubicaciones')->onDelete('set null');
            $table->foreign('auxiliar_id')->references('id')->on('personal')->onDelete('restrict');

            $table->index(['empresa_id', 'producto_id', 'fecha_movimiento'], 'idx_mov_inv_emp_prod_fecha');
            $table->index(['referencia_tipo', 'referencia_id'], 'idx_mov_inv_ref');
        });
    },
    'down' => function () {
        Capsule::schema()->dropIfExists('movimiento_inventarios');
    },
];
