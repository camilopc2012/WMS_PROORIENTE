<?php
require __DIR__ . '/bootstrap.php';
$user = \App\Models\Personal::where('documento', 'admin001')->first();
if ($user) {
    echo "USER FOUND. ID: {$user->id}, ROL: {$user->rol}, PIN_HASH: {$user->pin}\n";
} else {
    echo "USER NOT FOUND\n";
}
