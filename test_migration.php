<?php
require_once __DIR__ . '/bootstrap.php';
use Illuminate\Database\Capsule\Manager as Capsule;

try {
    $migration = require __DIR__ . '/database/migrations/013_create_movimiento_inventarios_table.php';
    $migration['up']();
    echo "OK\n";
} catch (\Exception $e) {
    file_put_contents('error_log.txt', "ERROR: " . $e->getMessage() . "\n" . "SQL: " . ($e->getPrevious() ? $e->getPrevious()->getMessage() : 'N/A') . "\n");
    echo "Error written to error_log.txt\n";
}
