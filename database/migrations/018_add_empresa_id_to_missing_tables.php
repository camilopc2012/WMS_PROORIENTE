<?php

use Illuminate\Database\Capsule\Manager as Capsule;

return [
    'up' => function () {
        if (!Capsule::schema()->hasColumn('ubicaciones', 'empresa_id')) {
            Capsule::schema()->table('ubicaciones', function ($table) {
                $table->unsignedBigInteger('empresa_id')->after('id')->nullable();
                $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            });
            // Update existing data if possible, but for now we just add it
        }

        if (!Capsule::schema()->hasColumn('parametros', 'empresa_id')) {
            Capsule::schema()->table('parametros', function ($table) {
                $table->unsignedBigInteger('empresa_id')->after('id')->nullable();
                $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            });
        }
    },
    'down' => function () {
        Capsule::schema()->table('ubicaciones', function ($table) {
            $table->dropForeign(['empresa_id']);
            $table->dropColumn('empresa_id');
        });
        Capsule::schema()->table('parametros', function ($table) {
            $table->dropForeign(['empresa_id']);
            $table->dropColumn('empresa_id');
        });
    },
];
