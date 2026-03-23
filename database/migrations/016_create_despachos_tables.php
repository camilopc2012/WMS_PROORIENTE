<?php

use Illuminate\Database\Capsule\Manager as Capsule;

return [
    'up' => function () {
        Capsule::schema()->create('despachos', function ($table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('sucursal_id');
            $table->string('numero_despacho', 30)->unique();
            $table->string('cliente', 200)->nullable();
            $table->string('ruta', 100)->nullable();
            $table->unsignedBigInteger('muelle_id')->nullable();
            $table->integer('total_bultos')->default(0);
            $table->decimal('peso_total', 10, 2)->default(0);
            $table->enum('estado', ['Preparando', 'Certificado', 'Despachado'])->default('Preparando');
            $table->unsignedBigInteger('auxiliar_id')->nullable();
            $table->date('fecha_movimiento');
            $table->time('hora_inicio');
            $table->time('hora_fin')->nullable();
            $table->timestamps();

            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->foreign('sucursal_id')->references('id')->on('sucursales')->onDelete('cascade');
            $table->foreign('muelle_id')->references('id')->on('ubicaciones')->onDelete('set null');
            $table->foreign('auxiliar_id')->references('id')->on('personal')->onDelete('set null');
        });

        Capsule::schema()->create('certificacion_despachos', function ($table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('despacho_id');
            $table->unsignedBigInteger('producto_id');
            $table->string('lote', 50)->nullable();
            $table->integer('cantidad_certificada');
            $table->unsignedBigInteger('escaneado_por');
            $table->timestamp('escaneado_at')->useCurrent();
            $table->timestamps();

            $table->foreign('despacho_id')->references('id')->on('despachos')->onDelete('cascade');
            $table->foreign('producto_id')->references('id')->on('productos')->onDelete('restrict');
            $table->foreign('escaneado_por')->references('id')->on('personal')->onDelete('restrict');
        });
    },
    'down' => function () {
        Capsule::schema()->dropIfExists('certificacion_despachos');
        Capsule::schema()->dropIfExists('despachos');
    },
];
