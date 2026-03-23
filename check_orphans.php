<?php
require __DIR__ . '/bootstrap.php';
$orphans = \App\Models\RolPermiso::doesntHave('permiso')->get();
echo "ORPHANS COUNT: " . $orphans->count() . "\n";
foreach ($orphans as $o) {
    echo "Orphan RolPermiso ID: {$o->id}, Permiso ID: {$o->permiso_id}, Rol: {$o->rol}\n";
}
