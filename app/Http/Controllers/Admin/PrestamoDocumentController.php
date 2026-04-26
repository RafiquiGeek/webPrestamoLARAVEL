<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Prestamo;
use App\Models\PrestamoDocument;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PrestamoDocumentController extends Controller
{
    /**
     * Upload a document for a loan
     */
    public function upload(Request $request)
    {
        \Log::info('Document upload attempt', [
            'prestamo_id' => $request->prestamo_id,
            'document_type' => $request->document_type,
            'file_present' => $request->hasFile('document_file'),
            'file_size' => $request->hasFile('document_file') ? $request->file('document_file')->getSize() : null,
            'file_mime' => $request->hasFile('document_file') ? $request->file('document_file')->getMimeType() : null,
            'file_original_name' => $request->hasFile('document_file') ? $request->file('document_file')->getClientOriginalName() : null,
            'php_upload_max_filesize' => ini_get('upload_max_filesize'),
            'php_post_max_size' => ini_get('post_max_size'),
            'php_max_execution_time' => ini_get('max_execution_time'),
            'storage_disk_path' => Storage::disk('public')->path(''),
            'storage_permissions' => is_writable(storage_path('app/public')),
            'user_agent' => $request->header('User-Agent'),
            'content_length' => $request->header('Content-Length'),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
            'request_method' => $request->method(),
            'all_files' => array_keys($request->allFiles()),
            'all_inputs' => array_keys($request->except('document_file')),
        ]);

        $validator = Validator::make($request->all(), [
            'prestamo_id' => 'required|exists:prestamos,id',
            'document_type' => 'required|string|max:100',
            'custom_type' => 'nullable|string|max:100',
            'document_file' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:51200|min:1', // 50MB max, min 1KB
            'description' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            $errorMessages = [];

            // Log validation failures with detailed information
            \Log::error('Document upload validation failed', [
                'errors' => $errors->toArray(),
                'request_data' => $request->except(['document_file']),
                'file_info' => $request->hasFile('document_file') ? [
                    'name' => $request->file('document_file')->getClientOriginalName(),
                    'size' => $request->file('document_file')->getSize(),
                    'mime' => $request->file('document_file')->getMimeType(),
                    'error' => $request->file('document_file')->getError(),
                    'error_message' => $request->file('document_file')->getErrorMessage(),
                    'is_valid' => $request->file('document_file')->isValid(),
                ] : 'No file uploaded',
                'php_limits' => [
                    'upload_max_filesize' => ini_get('upload_max_filesize'),
                    'post_max_size' => ini_get('post_max_size'),
                    'memory_limit' => ini_get('memory_limit'),
                ],
                'request_size_info' => [
                    'content_length' => $request->header('Content-Length'),
                    'estimated_mb' => $request->header('Content-Length') 
                        ? round($request->header('Content-Length') / 1024 / 1024, 2)
                        : 'unknown',
                ],
            ]);

            // Generate more specific error messages
            if ($errors->has('document_file')) {
                $fileErrors = $errors->get('document_file');
                foreach ($fileErrors as $error) {
                    if (strpos($error, 'max') !== false) {
                        $errorMessages[] = 'El archivo excede el tamaño máximo permitido de 50MB';
                    } elseif (strpos($error, 'mimes') !== false) {
                        $errorMessages[] = 'Formato de archivo no permitido. Use: PDF, DOC, DOCX, JPG, PNG';
                    } elseif (strpos($error, 'uploaded') !== false || strpos($error, 'failed') !== false) {
                        // Specific handling for upload failures
                        $errorMessages[] = 'Error durante la subida del archivo. Verifique los límites del servidor (upload_max_filesize, post_max_size) y que el archivo no esté corrupto';
                    } else {
                        $errorMessages[] = $error;
                    }
                }
            }

            if ($errors->has('prestamo_id')) {
                $errorMessages[] = 'Préstamo no válido';
            }

            if ($errors->has('document_type')) {
                $errorMessages[] = 'Tipo de documento requerido';
            }

            if ($errors->has('custom_type')) {
                $errorMessages[] = 'Especifique el tipo de documento personalizado';
            }

            return response()->json([
                'success' => false,
                'message' => count($errorMessages) > 0 ? implode('. ', $errorMessages) : 'Datos inválidos',
                'errors' => $errors,
                'php_limits' => [
                    'upload_max_filesize' => ini_get('upload_max_filesize'),
                    'post_max_size' => ini_get('post_max_size'),
                    'memory_limit' => ini_get('memory_limit'),
                    'max_file_uploads' => ini_get('max_file_uploads'),
                ],
            ], 422);
        }

        try {
            $prestamo = Prestamo::findOrFail($request->prestamo_id);
            $file = $request->file('document_file');

            // Additional server-side validation
            if (! $file->isValid()) {
                \Log::error('File upload is not valid', [
                    'error_code' => $file->getError(),
                    'error_message' => $file->getErrorMessage(),
                    'file_name' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                ]);

                throw new \Exception('El archivo subido no es válido: '.$file->getErrorMessage());
            }

            // Check file size again (server validation)
            if ($file->getSize() > 50 * 1024 * 1024) { // 50MB
                throw new \Exception('El archivo excede el tamaño máximo de 50MB');
            }

            // Check if file has content
            if ($file->getSize() == 0) {
                throw new \Exception('El archivo está vacío');
            }

            // Verify file can be read
            if (! is_readable($file->getPathname())) {
                throw new \Exception('El archivo no se puede leer. Verifique los permisos');
            }

            // Get file extension and MIME type first
            $extension = strtolower($file->getClientOriginalExtension());
            $mimeType = $file->getMimeType();
            $originalName = $file->getClientOriginalName();

            // Check for suspicious content
            $content = file_get_contents($file->getPathname(), false, null, 0, 1024); // Read first 1KB
            if ($content === false) {
                throw new \Exception('No se pudo leer el contenido del archivo');
            }

            // Check for common corruption indicators
            $suspiciousPatterns = [
                '/\[Pasted text #\d+\]/',
                '/function\(.*\){"use strict".*module\.exports/',
                '/!function\(e,t\)\{"use strict"/',
            ];

            foreach ($suspiciousPatterns as $pattern) {
                if (preg_match($pattern, $content)) {
                    throw new \Exception('El archivo parece contener código JavaScript o estar corrupto. Solo se permiten documentos PDF, Word o imágenes');
                }
            }

            // Additional validation for PDFs
            if ($extension === 'pdf' && substr($content, 0, 5) !== '%PDF-') {
                \Log::warning('File with PDF extension does not have PDF header', [
                    'file_name' => $originalName,
                    'first_bytes' => substr($content, 0, 50),
                ]);
            }

            // Additional MIME type validation for security
            $allowedMimes = [
                'pdf' => ['application/pdf'],
                'doc' => ['application/msword'],
                'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
                'jpg' => ['image/jpeg'],
                'jpeg' => ['image/jpeg'],
                'png' => ['image/png'],
            ];

            if (! isset($allowedMimes[$extension]) || ! in_array($mimeType, $allowedMimes[$extension])) {
                \Log::warning('MIME type mismatch', [
                    'extension' => $extension,
                    'detected_mime' => $mimeType,
                    'allowed_mimes' => $allowedMimes[$extension] ?? 'unknown extension',
                ]);

                // For some files (especially PDFs from scanners), MIME detection might be inconsistent
                // Allow if extension is valid but log the discrepancy
                if (! array_key_exists($extension, $allowedMimes)) {
                    throw new \Exception('Extensión de archivo no permitida: '.$extension);
                }
            }

            \Log::info('File validation passed', [
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime_type' => $mimeType,
                'extension' => $extension,
                'temp_path' => $file->getPathname(),
            ]);

            // Generate unique filename
            $filename = 'prestamo_'.$prestamo->id.'_'.time().'_'.Str::random(8).'.'.$extension;

            // Store file
            $path = $file->storeAs('prestamos/documentos', $filename, 'public');
            \Log::info("File stored at path: {$path}");

            // Verify file was stored correctly
            if (! Storage::disk('public')->exists($path)) {
                \Log::error('File was not stored successfully', [
                    'expected_path' => $path,
                    'storage_disk' => 'public',
                    'full_path' => Storage::disk('public')->path($path),
                    'directory_writable' => is_writable(dirname(Storage::disk('public')->path($path))),
                ]);
                throw new \Exception('Error al guardar el archivo en el servidor. Verifique los permisos de escritura');
            }

            // Verify stored file size matches uploaded file size
            $storedSize = Storage::disk('public')->size($path);
            if ($storedSize !== $file->getSize()) {
                \Log::error('Stored file size mismatch', [
                    'original_size' => $file->getSize(),
                    'stored_size' => $storedSize,
                    'path' => $path,
                ]);

                // Clean up corrupted file
                Storage::disk('public')->delete($path);
                throw new \Exception('El archivo se guardó incorrectamente. Tamaño no coincide');
            }

            // Determine document type
            $documentType = $request->document_type === 'otro'
                ? $request->custom_type
                : $request->document_type;

            // Create document record
            $document = PrestamoDocument::create([
                'prestamo_id' => $prestamo->id,
                'document_type' => $documentType,
                'original_name' => $originalName,
                'filename' => $filename,
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'description' => $request->description,
                'uploaded_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Documento subido correctamente',
                'document' => $document,
                'public_url' => Storage::disk('public')->url($path),
            ]);

        } catch (\Exception $e) {
            \Log::error('Document upload failed with exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request_info' => [
                    'prestamo_id' => $request->prestamo_id,
                    'document_type' => $request->document_type,
                    'file_present' => $request->hasFile('document_file'),
                    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
                    'php_version' => PHP_VERSION,
                    'laravel_version' => app()->version(),
                    'disk_space' => disk_free_space(storage_path()),
                    'memory_usage' => memory_get_usage(true),
                    'memory_peak' => memory_get_peak_usage(true),
                ],
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al subir el documento: '.$e->getMessage(),
                'server_info' => [
                    'error_type' => get_class($e),
                    'server' => 'Ubuntu Server',
                    'timestamp' => now()->toISOString(),
                ],
            ], 500);
        }
    }

    /**
     * List documents for a loan
     */
    public function list($prestamoId)
    {
        try {
            $prestamo = Prestamo::findOrFail($prestamoId);

            $documents = PrestamoDocument::where('prestamo_id', $prestamoId)
                ->with('uploadedBy:id,name')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($doc) {
                    return [
                        'id' => $doc->id,
                        'document_type' => $doc->document_type,
                        'original_name' => $doc->original_name,
                        'file_size' => $this->formatFileSize($doc->file_size),
                        'description' => $doc->description,
                        'uploaded_by' => $doc->uploadedBy->name ?? 'Usuario eliminado',
                        'created_at' => $doc->created_at->format('d/m/Y H:i'),
                        'public_url' => Storage::disk('public')->url($doc->file_path),
                        'file_exists' => Storage::disk('public')->exists($doc->file_path),
                    ];
                });

            return response()->json([
                'success' => true,
                'documents' => $documents,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar los documentos: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download a document
     */
    public function download($documentId)
    {
        try {
            $document = PrestamoDocument::findOrFail($documentId);

            if (! Storage::disk('public')->exists($document->file_path)) {
                abort(404, 'Archivo no encontrado');
            }

            return Storage::disk('public')->download($document->file_path, $document->original_name);

        } catch (\Exception $e) {
            abort(500, 'Error al descargar el documento');
        }
    }

    /**
     * Preview a document inline
     */
    public function preview($documentId)
    {
        try {
            \Log::info("Preview request for document ID: {$documentId}");

            $document = PrestamoDocument::findOrFail($documentId);
            \Log::info("Document found: {$document->original_name}, Path: {$document->file_path}");

            if (! Storage::disk('public')->exists($document->file_path)) {
                \Log::error("File not found at path: {$document->file_path}");
                abort(404, 'Archivo no encontrado en: '.$document->file_path);
            }

            $path = Storage::disk('public')->path($document->file_path);
            \Log::info("Full file path: {$path}");

            // Verificar que el archivo existe físicamente
            if (! file_exists($path)) {
                \Log::error("Physical file does not exist: {$path}");
                abort(404, 'Archivo físico no encontrado');
            }

            // Determinar el tipo MIME correcto si no está guardado correctamente
            $mimeType = $document->mime_type;
            $extension = strtolower(pathinfo($document->original_name, PATHINFO_EXTENSION));

            // Corregir tipos MIME comunes
            switch ($extension) {
                case 'pdf':
                    $mimeType = 'application/pdf';
                    break;
                case 'jpg':
                case 'jpeg':
                    $mimeType = 'image/jpeg';
                    break;
                case 'png':
                    $mimeType = 'image/png';
                    break;
                case 'doc':
                    $mimeType = 'application/msword';
                    break;
                case 'docx':
                    $mimeType = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
                    break;
            }

            \Log::info("Serving file with MIME type: {$mimeType}");

            return response()->file($path, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="'.$document->original_name.'"',
                'X-Frame-Options' => 'SAMEORIGIN',
                'Content-Security-Policy' => "frame-ancestors 'self'",
            ]);

        } catch (\Exception $e) {
            \Log::error('Error previewing document: '.$e->getMessage());
            \Log::error('Stack trace: '.$e->getTraceAsString());

            return response()->json([
                'error' => 'Error al previsualizar el documento',
                'message' => $e->getMessage(),
                'document_id' => $documentId,
            ], 500);
        }
    }

    /**
     * Debug document information
     */
    public function debug($documentId)
    {
        try {
            $document = PrestamoDocument::findOrFail($documentId);

            $info = [
                'document_id' => $document->id,
                'original_name' => $document->original_name,
                'filename' => $document->filename,
                'file_path' => $document->file_path,
                'mime_type' => $document->mime_type,
                'file_size' => $document->file_size,
                'storage_disk' => 'public',
                'storage_path' => Storage::disk('public')->path($document->file_path),
                'public_url' => Storage::disk('public')->url($document->file_path),
                'file_exists_storage' => Storage::disk('public')->exists($document->file_path),
                'file_exists_filesystem' => file_exists(Storage::disk('public')->path($document->file_path)),
                'storage_app_path' => storage_path('app/public/'.$document->file_path),
                'storage_url_base' => config('app.url').'/storage/',
            ];

            return response()->json($info, 200, [], JSON_PRETTY_PRINT);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    /**
     * Delete a document
     */
    public function delete($documentId)
    {
        try {
            $document = PrestamoDocument::findOrFail($documentId);

            // Delete file from storage
            if (Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }

            // Delete record
            $document->delete();

            return response()->json([
                'success' => true,
                'message' => 'Documento eliminado correctamente',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el documento: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Format file size for display
     */
    /**
     * Generate Carta de No Adeudo PDF
     */
    public function generateCartaNoAdeudo($prestamoId)
    {
        try {
            $prestamo = Prestamo::with(['cliente.persona'])->findOrFail($prestamoId);

            // Validate that the loan is completed/finished
            if ($prestamo->estado !== 'Liquidado' && $prestamo->estado !== 'Finalizado') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se puede generar la carta de no adeudo para préstamos liquidados o finalizados.',
                    'estado_actual' => $prestamo->estado,
                ], 422);
            }

            // Check if there are any pending payments (cuotas)
            // Estado 2 = Pagado, cualquier estado diferente a 2 se considera pendiente
            $cuotasPendientes = $prestamo->cuotas()->where('estado', '!=', 2)->count();

            if ($cuotasPendientes > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'El préstamo aún tiene cuotas pendientes de pago.',
                    'cuotas_pendientes' => $cuotasPendientes,
                ], 422);
            }

            // Prepare data for the PDF
            $cliente = $prestamo->cliente;
            $persona = $cliente->persona;

            // Determine gender for proper title
            $clienteGenero = 'Sr(a).';
            if ($persona && $persona->genero) {
                $clienteGenero = $persona->genero === 'M' ? 'Sr.' : 'Sra.';
            }

            $fechaCancelacion = $prestamo->fecha_cancelacion
                ? $prestamo->fecha_cancelacion->format('d/m/Y')
                : ($prestamo->updated_at ? $prestamo->updated_at->format('d/m/Y') : date('d/m/Y'));

            $data = [
                'fecha' => now()->locale('es')->isoFormat('DD [de] MMMM [de] YYYY'),
                'cliente_genero' => $clienteGenero,
                'cliente_nombre_completo' => $persona
                    ? trim($persona->nombres.' '.$persona->ape_pat.' '.$persona->ape_mat)
                    : 'Cliente',
                'cliente_dni' => $persona ? $persona->documento : 'N/A',
                'prestamo_info' => [
                    'id' => $prestamo->id,
                    'monto' => $prestamo->monto_prestamo,
                    'fecha_cancelacion' => $fechaCancelacion,
                ],
                // Company information (can be configured)
                'company_name' => config('app.company_name', 'Grupo Santiago Peru S.A.C.'),
                'company_address' => config('app.company_address', ''),
                'company_phone' => config('app.company_phone', ''),
                'company_email' => config('app.company_email', ''),
                'company_ruc' => config('app.company_ruc', '20611373181'),
                'firmante_nombre' => config('app.signatory_name', 'YOSELIN ESTEBAN MANTILLA'),
                'firmante_cargo' => config('app.signatory_position', 'GERENTE GENERAL'),
            ];

            // Check if carta already exists
            $existingCarta = PrestamoDocument::where('prestamo_id', $prestamo->id)
                ->where('document_type', 'carta_no_adeudo')
                ->first();

            if (! $existingCarta) {
                // Generate and store PDF
                $pdf = Pdf::loadView('pdf.carta-no-adeudo', $data);
                $pdf->setPaper('A4', 'portrait');

                // Generate filename
                $filename = 'carta_no_adeudo_prestamo_'.$prestamo->id.'_'.date('Y-m-d').'.pdf';
                $filePath = 'prestamos/documentos/'.$filename;

                // Save PDF to storage
                Storage::disk('public')->put($filePath, $pdf->output());

                // Create document record
                PrestamoDocument::create([
                    'prestamo_id' => $prestamo->id,
                    'document_type' => 'carta_no_adeudo',
                    'original_name' => $filename,
                    'filename' => $filename,
                    'file_path' => $filePath,
                    'file_size' => strlen($pdf->output()),
                    'mime_type' => 'application/pdf',
                    'description' => 'Carta de No Adeudo generada automáticamente',
                    'uploaded_by' => auth()->id(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Carta de no adeudo generada correctamente',
                'generated' => true,
            ]);

        } catch (\Exception $e) {
            \Log::error('Error generating Carta de No Adeudo: '.$e->getMessage());
            \Log::error('Stack trace: '.$e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Error al generar la carta de no adeudo: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Preview Carta de No Adeudo PDF
     */
    public function previewCartaNoAdeudo($prestamoId)
    {
        try {
            $prestamo = Prestamo::with(['cliente.persona'])->findOrFail($prestamoId);

            // Validate that the loan is completed/finished
            if ($prestamo->estado !== 'Liquidado' && $prestamo->estado !== 'Finalizado') {
                abort(422, 'Solo se puede previsualizar la carta de no adeudo para préstamos liquidados o finalizados.');
            }

            // Check if there are any pending payments (cuotas)
            $cuotasPendientes = $prestamo->cuotas()->where('estado', '!=', 2)->count();
            if ($cuotasPendientes > 0) {
                abort(422, 'El préstamo aún tiene cuotas pendientes de pago.');
            }

            // Prepare data for the PDF
            $cliente = $prestamo->cliente;
            $persona = $cliente->persona;

            $clienteGenero = 'Sr(a).';
            if ($persona && $persona->genero) {
                $clienteGenero = $persona->genero === 'M' ? 'Sr.' : 'Sra.';
            }

            $fechaCancelacion = $prestamo->fecha_cancelacion
                ? $prestamo->fecha_cancelacion->format('d/m/Y')
                : ($prestamo->updated_at ? $prestamo->updated_at->format('d/m/Y') : date('d/m/Y'));

            $data = [
                'fecha' => now()->locale('es')->isoFormat('DD [de] MMMM [de] YYYY'),
                'cliente_genero' => $clienteGenero,
                'cliente_nombre_completo' => $persona
                    ? trim($persona->nombres.' '.$persona->ape_pat.' '.$persona->ape_mat)
                    : 'Cliente',
                'cliente_dni' => $persona ? $persona->documento : 'N/A',
                'prestamo_info' => [
                    'id' => $prestamo->id,
                    'monto' => $prestamo->monto_prestamo,
                    'fecha_cancelacion' => $fechaCancelacion,
                ],
                'company_name' => config('app.company_name', 'Grupo Santiago Peru S.A.C.'),
                'company_address' => config('app.company_address', ''),
                'company_phone' => config('app.company_phone', ''),
                'company_email' => config('app.company_email', ''),
                'company_ruc' => config('app.company_ruc', '20611373181'),
                'firmante_nombre' => config('app.signatory_name', 'YOSELIN ESTEBAN MANTILLA'),
                'firmante_cargo' => config('app.signatory_position', 'GERENTE GENERAL'),
            ];

            // Generate PDF for preview
            $pdf = Pdf::loadView('pdf.carta-no-adeudo', $data);
            $pdf->setPaper('A4', 'portrait');

            return $pdf->stream('carta_no_adeudo_preview.pdf');

        } catch (\Exception $e) {
            \Log::error('Error previewing Carta de No Adeudo: '.$e->getMessage());
            abort(500, 'Error al previsualizar la carta de no adeudo');
        }
    }

    /**
     * Download Carta de No Adeudo PDF
     */
    public function downloadCartaNoAdeudo($prestamoId)
    {
        try {
            $prestamo = Prestamo::findOrFail($prestamoId);

            // Find existing carta document
            $cartaDocument = PrestamoDocument::where('prestamo_id', $prestamo->id)
                ->where('document_type', 'carta_no_adeudo')
                ->first();

            if (! $cartaDocument) {
                return response()->json([
                    'success' => false,
                    'message' => 'La carta de no adeudo no ha sido generada aún.',
                ], 404);
            }

            if (! Storage::disk('public')->exists($cartaDocument->file_path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El archivo de la carta de no adeudo no se encuentra.',
                ], 404);
            }

            return Storage::disk('public')->download($cartaDocument->file_path, $cartaDocument->original_name);

        } catch (\Exception $e) {
            \Log::error('Error downloading Carta de No Adeudo: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al descargar la carta de no adeudo: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check if Carta de No Adeudo exists for a loan
     */
    public function checkCartaNoAdeudo($prestamoId)
    {
        try {
            $exists = PrestamoDocument::where('prestamo_id', $prestamoId)
                ->where('document_type', 'carta_no_adeudo')
                ->exists();

            return response()->json([
                'exists' => $exists,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'exists' => false,
            ]);
        }
    }

    /**
     * Generate Contrato de Mutuo PDF
     */
    public function generateContratoMutuo($prestamoId)
    {
        try {
            $prestamo = Prestamo::with(['cliente.persona', 'cuotas', 'aval.persona'])->findOrFail($prestamoId);

            // Prepare data for the PDF
            $cliente = $prestamo->cliente;
            $persona = $cliente->persona;

            // Calculate contract data
            $totalCuotas = $prestamo->cuotas->count();
            $montoTotal = round($prestamo->cuotas->sum('monto_cuota'), 2);
            $montoCuota = $totalCuotas > 0 ? round($montoTotal / $totalCuotas, 2) : 0;

            // Prepare contract number (can be customized)
            $contratoNumero = date('Y').'-'.str_pad($prestamo->id, 4, '0', STR_PAD_LEFT);

            $data = [
                'prestamo' => $prestamo,
                'cliente' => $cliente,
                'persona' => $persona,
                'fecha_actual' => now()->locale('es')->isoFormat('DD [de] MMMM [de] YYYY'),
                'contrato_numero' => $contratoNumero,
                'monto_prestamo' => $prestamo->monto_prestamo,
                'total_cuotas' => $totalCuotas,
                'monto_cuota' => $montoCuota,
                'tasa_efectiva_semanal' => $prestamo->tasa_interes ?? 1.34,
                'gastos_administrativos' => 0.00,
                'fecha_desembolso' => $prestamo->fecha_desembolso ? $prestamo->fecha_desembolso->format('d/m/Y') : date('d/m/Y'),
                // Company information
                'company_name' => 'GRUPO SANTIAGO PERU S.A.C.',
                'company_ruc' => '20611373181',
                'company_partida' => '15366352',
                'company_address' => 'CAL.SETIMA NRO. 215 PROV. CONST. DEL CALLAO',
                'gerente_nombre' => 'YOSELIN ESTRELLA ESTEBAN MANTILLA',
                'gerente_dni' => '76553582',
                'sbs_resolution' => '2023-77882',
            ];

            // Check if contrato already exists
            $existingContrato = PrestamoDocument::where('prestamo_id', $prestamo->id)
                ->where('document_type', 'contrato_mutuo')
                ->first();

            if (! $existingContrato) {
                // Generate and store PDF
                $pdf = Pdf::loadView('pdf.contrato-mutuo', $data);
                $pdf->setOptions([
                    'dpi' => 150,
                    'defaultFont' => 'sans-serif',
                    'isRemoteEnabled' => true,
                    'isHtml5ParserEnabled' => true,
                ]);
                $pdf->setPaper('A4', 'portrait');

                // Generate filename
                $filename = 'contrato_mutuo_prestamo_'.$prestamo->id.'_'.date('Y-m-d').'.pdf';
                $filePath = 'prestamos/documentos/'.$filename;

                // Save PDF to storage
                $pdf->render();
                Storage::disk('public')->put($filePath, $pdf->output());

                // Create document record
                PrestamoDocument::create([
                    'prestamo_id' => $prestamo->id,
                    'document_type' => 'contrato_mutuo',
                    'original_name' => $filename,
                    'filename' => $filename,
                    'file_path' => $filePath,
                    'file_size' => strlen($pdf->output()),
                    'mime_type' => 'application/pdf',
                    'description' => 'Contrato de Mutuo generado automáticamente',
                    'uploaded_by' => auth()->id(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Contrato de mutuo generado correctamente',
                'generated' => true,
            ]);

        } catch (\Exception $e) {
            \Log::error('Error generating Contrato de Mutuo: '.$e->getMessage());
            \Log::error('Stack trace: '.$e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Error al generar el contrato de mutuo: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Preview Contrato de Mutuo PDF
     */
    public function previewContratoMutuo($prestamoId)
    {
        try {
            $prestamo = Prestamo::with(['cliente.persona', 'cuotas', 'aval.persona'])->findOrFail($prestamoId);

            // Prepare data for the PDF
            $cliente = $prestamo->cliente;
            $persona = $cliente->persona;

            // Calculate contract data
            $totalCuotas = $prestamo->cuotas->count();
            $montoTotal = round($prestamo->cuotas->sum('monto_cuota'), 2);
            $montoCuota = $totalCuotas > 0 ? round($montoTotal / $totalCuotas, 2) : 0;

            // Prepare contract number
            $contratoNumero = date('Y').'-'.str_pad($prestamo->id, 4, '0', STR_PAD_LEFT);

            $data = [
                'prestamo' => $prestamo,
                'cliente' => $cliente,
                'persona' => $persona,
                'fecha_actual' => now()->locale('es')->isoFormat('DD [de] MMMM [de] YYYY'),
                'contrato_numero' => $contratoNumero,
                'monto_prestamo' => $prestamo->monto_prestamo,
                'total_cuotas' => $totalCuotas,
                'monto_cuota' => $montoCuota,
                'tasa_efectiva_semanal' => $prestamo->tasa_interes ?? 1.34,
                'gastos_administrativos' => 0.00,
                'fecha_desembolso' => $prestamo->fecha_desembolso ? $prestamo->fecha_desembolso->format('d/m/Y') : date('d/m/Y'),
                // Company information
                'company_name' => 'GRUPO SANTIAGO PERU S.A.C.',
                'company_ruc' => '20611373181',
                'company_partida' => '15366352',
                'company_address' => 'CAL.SETIMA NRO. 215 PROV. CONST. DEL CALLAO',
                'gerente_nombre' => 'YOSELIN ESTRELLA ESTEBAN MANTILLA',
                'gerente_dni' => '76553582',
                'sbs_resolution' => '2023-77882',
            ];

            // Generate PDF for preview
            $pdf = Pdf::loadView('pdf.contrato-mutuo', $data);
            $pdf->setOptions([
                'dpi' => 150,
                'defaultFont' => 'sans-serif',
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
            ]);
            $pdf->setPaper('A4', 'portrait');

            $pdf->render();
            return $pdf->stream('contrato_mutuo_preview.pdf');

        } catch (\Exception $e) {
            \Log::error('Error previewing Contrato de Mutuo: '.$e->getMessage());
            abort(500, 'Error al previsualizar el contrato de mutuo');
        }
    }

    /**
     * Download Contrato de Mutuo PDF
     */
    public function downloadContratoMutuo($prestamoId)
    {
        try {
            $prestamo = Prestamo::findOrFail($prestamoId);

            // Find existing contrato document
            $contratoDocument = PrestamoDocument::where('prestamo_id', $prestamo->id)
                ->where('document_type', 'contrato_mutuo')
                ->first();

            if (! $contratoDocument) {
                return response()->json([
                    'success' => false,
                    'message' => 'El contrato de mutuo no ha sido generado aún.',
                ], 404);
            }

            if (! Storage::disk('public')->exists($contratoDocument->file_path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El archivo del contrato de mutuo no se encuentra.',
                ], 404);
            }

            return Storage::disk('public')->download($contratoDocument->file_path, $contratoDocument->original_name);

        } catch (\Exception $e) {
            \Log::error('Error downloading Contrato de Mutuo: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al descargar el contrato de mutuo: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check if Contrato de Mutuo exists for a loan
     */
    public function checkContratoMutuo($prestamoId)
    {
        try {
            $exists = PrestamoDocument::where('prestamo_id', $prestamoId)
                ->where('document_type', 'contrato_mutuo')
                ->exists();

            return response()->json([
                'exists' => $exists,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'exists' => false,
            ]);
        }
    }

    /**
     * Format file size for display
     */
    private function formatFileSize($bytes)
    {
        if ($bytes >= 52428800) { // 50 MB
            return number_format($bytes / 1048576, 2).' MB (supera el límite de 50 MB)';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2).' KB';
        } else {
            return $bytes.' bytes';
        }
    }
}
