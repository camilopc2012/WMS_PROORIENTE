<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as DB;

class ImportExportController
{
    private $models = [
        'empresas' => '\App\Models\Empresa',
        'sucursales' => '\App\Models\Sucursal',
        'marcas' => '\App\Models\Marca',
        'productos' => '\App\Models\Producto',
        'personal' => '\App\Models\Personal',
        'ubicaciones' => '\App\Models\Ubicacion',
        'proveedores' => '\App\Models\Proveedor',
        'clientes' => '\App\Models\Cliente'
    ];

    /**
     * GET /api/import-export/template/{tipo}
     */
    public function getTemplate(Request $request, Response $response, array $args): Response
    {
        $tipo = $args['tipo'];
        if (!isset($this->models[$tipo])) {
            return $this->json($response, ['error' => true, 'message' => 'Tipo no válido'], 400);
        }

        $modelClass = $this->models[$tipo];
        $model = new $modelClass();
        $fillable = $model->getFillable();
        
        // Remove 'empresa_id' from the template, as it's determined by the logged-in user context
        if (($key = array_search('empresa_id', $fillable)) !== false) {
            unset($fillable[$key]);
        }
        
        // Return CSV content
        $csvHeader = implode(',', $fillable) . "\r\n";
        
        // Clean any previous output buffer to avoid corruption
        if (ob_get_length()) ob_clean();
        
        $response->getBody()->write($csvHeader);
        return $response->withHeader('Content-Type', 'text/csv; charset=UTF-8')
                        ->withHeader('Content-Disposition', 'attachment; filename="plantilla_' . $tipo . '.csv"')
                        ->withHeader('Pragma', 'no-cache')
                        ->withHeader('Expires', '0');
    }

    /**
     * POST /api/import-export/upload/{tipo}
     */
    public function uploadCSV(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');
        $tipo = $args['tipo'];
        
        if (!isset($this->models[$tipo])) {
            return $this->json($response, ['error' => true, 'message' => 'Tipo no válido'], 400);
        }

        $uploadedFiles = $request->getUploadedFiles();
        if (empty($uploadedFiles['file'])) {
            return $this->json($response, ['error' => true, 'message' => 'No se recibió ningún archivo'], 400);
        }

        $file = $uploadedFiles['file'];
        if ($file->getError() !== UPLOAD_ERR_OK) {
             return $this->json($response, ['error' => true, 'message' => 'Error al subir el archivo'], 400);
        }

        $stream = $file->getStream();
        $stream->rewind();
        $contents = $stream->getContents();
        
        $lines = explode("\n", $contents);
        if (count($lines) < 2) {
            return $this->json($response, ['error' => true, 'message' => 'El archivo está vacío o no tiene datos válidos'], 400);
        }

        $headers = str_getcsv(array_shift($lines), ';');
        $headers = array_map('trim', $headers);
        
        $modelClass = $this->models[$tipo];
        $successCount = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($lines as $i => $line) {
                if (empty(trim($line))) continue;
                $data = str_getcsv($line, ';');
                
                if (count($data) !== count($headers)) {
                    $errors[] = "Línea " . ($i + 2) . ": El número de columnas no coincide.";
                    continue;
                }

                $mappedData = array_combine($headers, $data);
                $mappedData['empresa_id'] = $user->empresa_id;

                try {
                    $modelClass::create($mappedData);
                    $successCount++;
                } catch (\Exception $e) {
                    $errors[] = "Línea " . ($i + 2) . ": " . $e->getMessage();
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->json($response, ['error' => true, 'message' => 'Error crítico al procesar datos: ' . $e->getMessage()], 500);
        }

        return $this->json($response, [
            'error' => false, 
            'message' => "Importación completada. $successCount registros añadidos.",
            'errores' => $errors
        ]);
    }

    private function json(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
    }
}
