<?php
require 'bootstrap.php';
use Illuminate\Database\Capsule\Manager as DB;

try {
    // 1. Create Clientes Table
    if (!DB::schema()->hasTable('clientes')) {
        DB::schema()->create('clientes', function ($table) {
            $table->increments('id');
            $table->integer('empresa_id')->unsigned();
            $table->string('nit', 50);
            $table->string('razon_social', 150);
            $table->string('direccion', 200)->nullable();
            $table->string('ciudad', 100)->nullable();
            $table->string('telefono', 50)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('contacto_nombre', 100)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            
            // Assuming empresa_id is a foreign key logic in app
        });
        echo "Table 'clientes' created.\n";
    } else {
        echo "Table 'clientes' already exists.\n";
    }

    // 2. Update Citas Table with Transactional fields
    if (DB::schema()->hasTable('citas')) {
        DB::schema()->table('citas', function ($table) {
            if (!DB::schema()->hasColumn('citas', 'auxiliar_id')) {
                $table->integer('auxiliar_id')->unsigned()->nullable()->after('estado');
                echo "Added auxiliar_id to citas.\n";
            }
            if (!DB::schema()->hasColumn('citas', 'fecha_movimiento')) {
                $table->date('fecha_movimiento')->nullable()->after('auxiliar_id');
                echo "Added fecha_movimiento to citas.\n";
            }
            if (!DB::schema()->hasColumn('citas', 'hora_inicio')) {
                $table->time('hora_inicio')->nullable()->after('fecha_movimiento');
                echo "Added hora_inicio to citas.\n";
            }
            if (!DB::schema()->hasColumn('citas', 'hora_fin')) {
                $table->time('hora_fin')->nullable()->after('hora_inicio');
                echo "Added hora_fin to citas.\n";
            }
        });
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
