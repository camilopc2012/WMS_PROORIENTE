<?php

use Illuminate\Database\Capsule\Manager as Capsule;

return [
    'up' => function () {
        Capsule::schema()->create('productos', function ($table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('marca_id')->nullable();
            $table->string('codigo_interno', 50);
            $table->string('nombre', 300);
            $table->text('descripcion')->nullable();
            $table->string('imagen_url', 500)->nullable();
            $table->string('unidad_medida', 20)->default('UN');
            $table->decimal('peso_unitario', 10, 3)->default(0);
            $table->decimal('volumen_unitario', 10, 4)->default(0);
            $table->boolean('controla_lote')->default(false);
            $table->boolean('controla_vencimiento')->default(false);
            $table->integer('vida_util_dias')->nullable();
            $table->string('temperatura_almacen', 30)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->foreign('marca_id')->references('id')->on('marcas')->onDelete('set null');
            $table->unique(['empresa_id', 'codigo_interno']);
        });

        Capsule::schema()->create('producto_eans', function ($table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('producto_id');
            $table->string('codigo_ean', 50);
            $table->enum('tipo', ['EAN13', 'EAN128', 'DUN14', 'QR', 'INTERNO'])->default('EAN13');
            $table->boolean('es_principal')->default(false);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->foreign('producto_id')->references('id')->on('productos')->onDelete('cascade');
            $table->unique('codigo_ean');
        });
    },
    'down' => function () {
        Capsule::schema()->dropIfExists('producto_eans');
        Capsule::schema()->dropIfExists('productos');
    },
];
