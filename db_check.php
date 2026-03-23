<?php
require 'bootstrap.php';
use Illuminate\Database\Capsule\Manager as DB;

$tables = [
    'empresas', 'sucursales', 'usuarios', 'personal', 'roles', 'permisos', 
    'rol_permiso', 'productos', 'producto_eans', 'marcas', 'ubicaciones', 
    'proveedores', 'citas', 'recepciones', 'recepcion_detalles', 
    'devoluciones', 'devolucion_detalles'
];

foreach ($tables as $table) {
    if (DB::schema()->hasTable($table)) {
        echo "TABLE: $table\n";
        print_r(DB::schema()->getColumnListing($table));
        echo "\n";
    } else {
        echo "Table $table does NOT exist.\n\n";
    }
}
