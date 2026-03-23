<?php
require __DIR__ . '/bootstrap.php';
require __DIR__ . '/src/Controllers/AuthController.php';

use App\Controllers\AuthController;
use Slim\Psr7\Response;
use Slim\Psr7\Request;

// Mock request
$data = ['documento' => 'admin001', 'pin' => '1234', 'nit' => '900000001'];
echo "Simulating login for admin001...\n";

try {
    $auth = new AuthController();
    // We can't easily call login() because it expects a real Request object with body parsing etc.
    // But we can test the parts that might fail.
    
    $user = \App\Models\Personal::where('documento', 'admin001')->first();
    echo "User found: " . ($user ? 'YES' : 'NO') . "\n";
    
    $config = require __DIR__ . '/config/app.php';
    echo "Config loaded. Secret: " . $config['jwt']['secret'] . "\n";
    
    $payload = [
        'iss' => $config['url'],
        'aud' => $config['url'],
        'iat' => time(),
        'exp' => time() + 3600,
        'uid' => 1,
        'rol' => 'Admin',
        'emp' => 1,
        'suc' => 1
    ];
    
    echo "Encoding JWT...\n";
    $token = \Firebase\JWT\JWT::encode($payload, $config['jwt']['secret'], 'HS256');
    echo "Token generated: " . substr($token, 0, 10) . "...\n";
    
    echo "Looking for permissions...\n";
    $permisos = \App\Models\RolPermiso::with('permiso')
            ->where('empresa_id', 1)
            ->where('rol', 'Admin')
            ->where('concedido', true)
            ->get();
    echo "Permissions found: " . $permisos->count() . "\n";
    
    echo "SUCCESS\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "TRACE: " . $e->getTraceAsString() . "\n";
}
