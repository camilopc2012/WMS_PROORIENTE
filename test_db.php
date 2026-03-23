<?php
require __DIR__ . '/bootstrap.php';
try {
    \Illuminate\Database\Capsule\Manager::connection()->getPdo();
    echo "DB OK\n";
} catch (\Exception $e) {
    echo "DB ERROR: " . $e->getMessage() . "\n";
}
