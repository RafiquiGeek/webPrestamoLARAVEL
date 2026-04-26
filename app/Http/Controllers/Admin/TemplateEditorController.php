<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class TemplateEditorController extends Controller
{
    private $templatePath = 'resources/views/pdf/contrato-mutuo.blade.php';

    public function index()
    {
        $templateContent = $this->getTemplateContent();

        // Extraer solo el contenido (sin HTML structure)
        $contentOnly = $this->extractContentOnly($templateContent);

        // Definir las variables que se mostrarán en el editor
        $variables = [
            'customer_name' => '{{customer_name}}',
            'customer_dni' => '{{customer_dni}}',
            'customer_address' => '{{customer_address}}',
            'customer_phone' => '{{customer_phone}}',
            'customer_email' => '{{customer_email}}',

            'company_name' => '{{company_name}}',
            'company_ruc' => '{{company_ruc}}',
            'company_address' => '{{company_address}}',
            'manager_name' => '{{manager_name}}',
            'manager_dni' => '{{manager_dni}}',

            'loan_amount' => '{{loan_amount}}',
            'loan_amount_words' => '{{loan_amount_words}}',
            'interest_rate' => '{{interest_rate}}',
            'installment_amount' => '{{installment_amount}}',
            'total_installments' => '{{total_installments}}',
            'contract_number' => '{{contract_number}}',
            'payment_frequency' => '{{payment_frequency}}',
            'first_payment_date' => '{{first_payment_date}}',

            'disbursement_date' => '{{disbursement_date}}',
            'contract_date' => '{{contract_date}}',
            'current_date' => '{{current_date}}',

            'guarantor_name' => '{{guarantor_name}}',
            'guarantor_dni' => '{{guarantor_dni}}',
            'guarantor_phone' => '{{guarantor_phone}}',
            'guarantor_address' => '{{guarantor_address}}',

            'installment_table' => '{{installment_table}}',
            'loan_summary' => '{{loan_summary}}',
            'customer_district' => '{{customer_district}}',
            'customer_province' => '{{customer_province}}',
            'customer_department' => '{{customer_department}}',
        ];

        return view('admin.template-editor.simple', compact('contentOnly', 'variables'));
    }

    public function variables()
    {
        return view('admin.template-editor.variables');
    }

    public function getPrestamos()
    {
        $prestamos = \App\Models\Prestamo::with(['cliente.persona'])
            ->select('id', 'cliente_id', 'cantidad_solicitada', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($prestamo) {
                return [
                    'id' => $prestamo->id,
                    'codigo' => str_pad($prestamo->id, 3, '0', STR_PAD_LEFT).'-'.date('Y', strtotime($prestamo->created_at)),
                    'cliente_nombre' => $prestamo->cliente && $prestamo->cliente->persona
                        ? $prestamo->cliente->persona->nombres.' '.$prestamo->cliente->persona->ape_pat
                        : 'Cliente no encontrado',
                    'cantidad_solicitada' => number_format($prestamo->cantidad_solicitada, 2),
                ];
            });

        return response()->json([
            'success' => true,
            'prestamos' => $prestamos,
        ]);
    }

    public function loadPrestamoData(Request $request)
    {
        $request->validate([
            'prestamo_id' => 'required|integer|exists:prestamos,id',
        ]);

        try {
            $prestamo = \App\Models\Prestamo::with(['cliente.persona.direccion.distrito.provincia.departamento', 'cuotas'])
                ->findOrFail($request->prestamo_id);

            $persona = $prestamo->cliente && $prestamo->cliente->persona ? $prestamo->cliente->persona : null;

            // Calcular datos del préstamo
            $montoEnLetras = $this->convertirNumeroALetras($prestamo->cantidad_solicitada ?? 0);
            $cuotaPromedio = $prestamo->cuotas ? $prestamo->cuotas->avg('monto') : 0;
            $totalCuotas = $prestamo->cuotas ? $prestamo->cuotas->count() : 0;

            $data = [
                // Cliente
                'customer_name' => $persona ? strtoupper(trim($persona->nombres.' '.$persona->ape_pat.' '.$persona->ape_mat)) : '',
                'customer_dni' => $persona->documento ?? '',
                'customer_address' => $persona->direccion ?? '',
                'customer_phone' => $persona->telefono ?? '',
                'customer_email' => $persona->email ?? '',

                // Empresa (datos estáticos - podrían venir de configuración)
                'company_name' => 'GRUPO SANTIAGO S.A.C.',
                'company_ruc' => '20123456789',
                'company_address' => 'AV. PRINCIPAL 123, LIMA',
                'manager_name' => 'GERENTE GENERAL',
                'manager_dni' => '12345678',

                // Préstamo
                'loan_amount' => number_format($prestamo->cantidad_solicitada, 2),
                'loan_amount_words' => $montoEnLetras,
                'interest_rate' => number_format($prestamo->tasa_interes ?? 0, 2),
                'installment_amount' => number_format($cuotaPromedio, 2),
                'total_installments' => $totalCuotas,
                'contract_number' => str_pad($prestamo->id, 3, '0', STR_PAD_LEFT).'-'.date('Y', strtotime($prestamo->created_at)),
                'payment_frequency' => $prestamo->frecuencia_pago ?? 'SEMANAL',
                'first_payment_date' => $prestamo->cuotas && $prestamo->cuotas->first() && $prestamo->cuotas->first()->fecha_vencimiento ? $prestamo->cuotas->first()->fecha_vencimiento->format('d/m/Y') : '',

                // Fechas
                'disbursement_date' => $prestamo->fecha_atencion ? $prestamo->fecha_atencion->format('d/m/Y') : date('d/m/Y'),
                'contract_date' => date('d/m/Y'),
                'current_date' => date('d/m/Y'),

                // Aval (si existe)
                'guarantor_name' => '',
                'guarantor_dni' => '',
                'guarantor_phone' => '',
                'guarantor_address' => '',

                // Cronograma
                'installment_table' => $this->generateInstallmentTableHtml($prestamo),
                'loan_summary' => $this->generateLoanSummaryHtml($prestamo),

                // Ubicación geográfica
                'customer_district' => $persona && $persona->direccion && $persona->direccion->distrito ? $persona->direccion->distrito->distrito : '',
                'customer_province' => $persona && $persona->direccion && $persona->direccion->distrito && $persona->direccion->distrito->provincia ? $persona->direccion->distrito->provincia->provincia : '',
                'customer_department' => $persona && $persona->direccion && $persona->direccion->distrito && $persona->direccion->distrito->provincia && $persona->direccion->distrito->provincia->departamento ? $persona->direccion->distrito->provincia->departamento->departamento : '',
            ];

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar los datos del préstamo: '.$e->getMessage(),
            ], 500);
        }
    }

    public function saveVariables(Request $request)
    {
        $variables = $request->input('variables', []);

        // Guardar en sesión o base de datos según necesites
        session(['template_variables' => $variables]);

        return response()->json([
            'success' => true,
            'message' => 'Variables guardadas correctamente',
        ]);
    }

    public function previewWithVariables(Request $request)
    {
        $variables = $request->input('variables', []);

        try {
            // Obtener la plantilla actual
            $templateContent = $this->getTemplateContent();
            $contentOnly = $this->extractContentOnly($templateContent);

            // Reemplazar variables con los valores proporcionados
            $previewContent = $contentOnly;
            foreach ($variables as $key => $value) {
                $variablePattern = '{{'.str_replace('_', '_', $key).'}}';
                $previewContent = str_replace($variablePattern, $value ?: '['.strtoupper(str_replace('_', ' ', $key)).']', $previewContent);
            }

            // Convertir a HTML para preview
            $htmlContent = $this->convertTextToHtml($previewContent);

            // Obtener estilos
            preg_match('/<style>(.*?)<\/style>/s', $templateContent, $styleMatches);
            $styles = $styleMatches[1] ?? $this->getDefaultStyles();

            $fullPreview = '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>'.$styles.'</style>
</head>
<body>
'.$htmlContent.'
</body>
</html>';

            return response()->json([
                'success' => true,
                'html' => $fullPreview,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar la vista previa: '.$e->getMessage(),
            ], 500);
        }
    }

    private function convertirNumeroALetras($numero)
    {
        $entero = floor($numero);
        $decimales = round(($numero - $entero) * 100);

        // Conversión básica - podrías implementar una más completa
        $unidades = ['', 'uno', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve'];
        $decenas = ['', '', 'veinte', 'treinta', 'cuarenta', 'cincuenta', 'sesenta', 'setenta', 'ochenta', 'noventa'];
        $centenas = ['', 'ciento', 'doscientos', 'trescientos', 'cuatrocientos', 'quinientos', 'seiscientos', 'setecientos', 'ochocientos', 'novecientos'];

        if ($entero == 0) {
            return 'cero con '.str_pad($decimales, 2, '0', STR_PAD_LEFT).'/100 soles';
        }

        $resultado = '';

        // Miles
        if ($entero >= 1000) {
            $miles = floor($entero / 1000);
            if ($miles == 1) {
                $resultado .= 'mil ';
            } else {
                $resultado .= $this->convertirCentenas($miles).' mil ';
            }
            $entero = $entero % 1000;
        }

        // Centenas, decenas y unidades
        if ($entero > 0) {
            $resultado .= $this->convertirCentenas($entero);
        }

        return trim($resultado).' con '.str_pad($decimales, 2, '0', STR_PAD_LEFT).'/100 soles';
    }

    private function convertirCentenas($numero)
    {
        $centenas = ['', 'ciento', 'doscientos', 'trescientos', 'cuatrocientos', 'quinientos', 'seiscientos', 'setecientos', 'ochocientos', 'novecientos'];
        $decenas = ['', '', 'veinte', 'treinta', 'cuarenta', 'cincuenta', 'sesenta', 'setenta', 'ochenta', 'noventa'];
        $unidades = ['', 'uno', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve'];

        $resultado = '';

        if ($numero >= 100) {
            if ($numero == 100) {
                $resultado .= 'cien ';
            } else {
                $resultado .= $centenas[floor($numero / 100)].' ';
            }
            $numero = $numero % 100;
        }

        if ($numero >= 20) {
            $resultado .= $decenas[floor($numero / 10)];
            if ($numero % 10 > 0) {
                $resultado .= ' y '.$unidades[$numero % 10];
            }
        } elseif ($numero > 0) {
            $resultado .= $unidades[$numero];
        }

        return trim($resultado);
    }

    public function update(Request $request)
    {
        $request->validate([
            'template_content' => 'required|string',
        ]);

        try {
            // Create backup
            $this->createBackup();

            // Convert simple variables to Laravel blade and rebuild full template
            $newContent = $this->buildFullTemplate($request->input('template_content'));

            // Write new content
            File::put(base_path($this->templatePath), $newContent);

            return response()->json([
                'success' => true,
                'message' => 'Plantilla actualizada correctamente',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la plantilla: '.$e->getMessage(),
            ], 500);
        }
    }

    public function preview(Request $request)
    {
        $templateContent = $request->input('template_content', '');

        try {
            $previewContent = $this->buildFullTemplate($templateContent, true);

            return response()->json([
                'success' => true,
                'html' => $previewContent,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar vista previa: '.$e->getMessage(),
            ], 500);
        }
    }

    public function restore($backupId)
    {
        try {
            $backupPath = storage_path("app/template-backups/contrato-mutuo-{$backupId}.blade.php");

            if (! File::exists($backupPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Backup no encontrado',
                ], 404);
            }

            $backupContent = File::get($backupPath);
            File::put(base_path($this->templatePath), $backupContent);

            return response()->json([
                'success' => true,
                'message' => 'Plantilla restaurada correctamente',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al restaurar la plantilla: '.$e->getMessage(),
            ], 500);
        }
    }

    public function getBackups()
    {
        $backupDir = storage_path('app/template-backups');

        if (! File::exists($backupDir)) {
            return response()->json(['backups' => []]);
        }

        $files = File::files($backupDir);
        $backups = [];

        foreach ($files as $file) {
            if (str_contains($file->getFilename(), 'contrato-mutuo-')) {
                $timestamp = str_replace(['contrato-mutuo-', '.blade.php'], '', $file->getFilename());
                $backups[] = [
                    'id' => $timestamp,
                    'date' => date('d/m/Y H:i', $timestamp),
                    'size' => $file->getSize(),
                ];
            }
        }

        // Sort by timestamp descending
        usort($backups, function ($a, $b) {
            return $b['id'] - $a['id'];
        });

        return response()->json(['backups' => $backups]);
    }

    private function getTemplateContent()
    {
        return File::get(base_path($this->templatePath));
    }

    private function parseTemplateContent($content)
    {
        // Extract styles
        preg_match('/<style>(.*?)<\/style>/s', $content, $styleMatches);
        $styles = $styleMatches[1] ?? '';

        // Extract title
        preg_match('/<div class="title">(.*?)<\/div>/', $content, $titleMatches);
        $title = strip_tags($titleMatches[1] ?? 'CONTRATO DE MUTUO');

        // Extract clauses
        preg_match_all('/<div class="clause">.*?<div class="clause-title">(.*?)<\/div>.*?<div class="content">(.*?)<\/div>.*?<\/div>/s', $content, $clauseMatches, PREG_SET_ORDER);

        $clauses = [];
        foreach ($clauseMatches as $match) {
            $clauses[] = [
                'title' => strip_tags($match[1]),
                'content' => $match[2],
            ];
        }

        // Extract signature info
        preg_match('/<div class="signatures">(.*?)<\/div>/s', $content, $signatureMatches);
        $signatures = $signatureMatches[1] ?? '';

        return [
            'title' => $title,
            'styles' => $styles,
            'clauses' => $clauses,
            'signatures' => $signatures,
        ];
    }

    private function buildTemplateContent($sections, $styles, $isPreview = false)
    {
        $variables = $isPreview ? $this->getPreviewVariables() : '';

        $content = '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contrato de Mutuo</title>
    <style>
'.$styles.'
    </style>
</head>
<body>'.$variables.'
    <div class="title">'.($sections['title'] ?? 'CONTRATO DE MUTUO').'</div>
    <div class="contract-number">Nro. {{ $contract_number }}</div>
    
    <div class="content">
        <p>Conste por el presente documento, el <span class="bold">CONTRATO DE MUTUO</span> que celebran de una parte <span class="bold">{{ $company_name }}</span> con R.U.C.: <span class="bold">{{ $company_ruc }}</span> y Partida Registral Nº <span class="bold">{{ $company_partida }}</span> de la Oficina Registral de Lima – SUNARP, debidamente representado por el Gerente General <span class="bold">{{ $gerente_nombre }}</span> con D.N.I.: <span class="bold">{{ $gerente_dni }}</span>, con domicilio fiscal en <span class="bold">{{ $company_address }}</span>, como EL MUTUANTE y que en adelante será dominado LA EMPRESA.</p>
        
        <p>Y por otro lado el SR./SRA. {{ $persona ? strtoupper(trim($persona->nombres . \' \' . $persona->ape_pat . \' \' . $persona->ape_mat)) : \'______________________________________\' }}, con D.N.I./C.E.: {{ $persona ? $persona->documento : \'_______________________________________________\' }}, con domicilio en {{ $persona && $persona->direccion ? $persona->direccion : \'_________________________\' }}, DISTRITO DE {{ $persona && $persona->distrito ? strtoupper($persona->distrito) : \'_______________\' }}, PROVINCIA {{ $persona && $persona->provincia ? strtoupper($persona->provincia) : \'__________________________\' }} Y DEPARTAMENTO DE {{ $persona && $persona->departamento ? strtoupper($persona->departamento) : \'______________________\' }}, como EL MUTUATARIO que en adelante será dominado EL CLIENTE.</p>
    </div>';

        // Add clauses
        if (isset($sections['clauses']) && is_array($sections['clauses'])) {
            foreach ($sections['clauses'] as $clause) {
                $clauseContent = $this->convertVariablesToBlade($clause['content']);
                $content .= '
    
    <div class="clause">
        <div class="clause-title">'.$clause['title'].'</div>
        <div class="content">
            '.$clauseContent.'
        </div>
    </div>';
            }
        }

        // Add signatures
        $content .= '
    
    <div class="signatures">
        <div class="signature-block">
            <div class="signature-line"></div>
            <div class="signature-info">
                <div class="bold">EL MUTUANTE</div>
                <div>{{ $company_name }}</div>
                <div>R.U.C.: {{ $company_ruc }}</div>
                <div>{{ $gerente_nombre }}</div>
                <div>D.N.I.: {{ $gerente_dni }}</div>
                <div class="bold">GERENTE GENERAL</div>
            </div>
        </div>
        
        <div class="signature-block">
            <div class="signature-line"></div>
            <div class="signature-info">
                <div class="bold">EL MUTUATARIO</div>
                <div>{{ $persona ? strtoupper(trim($persona->nombres . \' \' . $persona->ape_pat . \' \' . $persona->ape_mat)) : \'[NOMBRE COMPLETO]\' }}</div>
                <div>D.N.I./C.E.: {{ $persona ? $persona->documento : \'XXXXXXX\' }}</div>
            </div>
        </div>
        
        <div class="signature-block">
            <div class="signature-line"></div>
            <div class="signature-info">
                <div class="bold">EL FIADOR</div>
                <div>NOMBRE: ______</div>
                <div>D.N.I.: ____</div>
                <div>TELEF: _____</div>
                <div>CORREO: ____</div>
                <div>DIRECCION: ____</div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        Documento generado automáticamente el {{ date(\'d/m/Y H:i\') }} | 
        Sistema de Gestión de Préstamos
    </div>
</body>
</html>';

        return $content;
    }

    private function getPreviewVariables()
    {
        return '
    <?php
        // Variables de ejemplo para preview
        $contrato_numero = "001-2024";
        $company_name = "EJEMPLO FINANCIERA S.A.C.";
        $company_ruc = "20123456789";
        $company_partida = "12345678";
        $gerente_nombre = "JUAN PÉREZ GARCÍA";
        $gerente_dni = "12345678";
        $company_address = "AV. EJEMPLO 123, LIMA";
        $monto_prestamo = 3500.00;
        $fecha_desembolso = "15/01/2024";
        $tasa_efectiva_semanal = 5.0;
        $gastos_administrativos = 2.0;
        $total_cuotas = 12;
        $monto_cuota = 350.00;
        $sbs_resolution = "SBS-2024-001";
        $montoTexto = "Tres mil quinientos con 00/100 soles";
        
        // Variables adicionales para el sistema de plantillas
        $aval_nombre = "PEDRO GONZÁLEZ MARTÍN";
        $aval_dni = "11223344";
        $aval_telefono = "912345678";
        $aval_direccion = "AV. AVAL 789, SAN JUAN DE MIRAFLORES";
        
        $persona = (object)[
            "nombres" => "MARÍA ELENA",
            "ape_pat" => "RODRIGUEZ",
            "ape_mat" => "SILVA",
            "documento" => "87654321",
            "direccion" => "JR. EJEMPLO 456",
            "distrito" => "SAN JUAN DE LURIGANCHO",
            "provincia" => "LIMA",
            "departamento" => "LIMA",
            "telefono" => "987654321",
            "email" => "maria@ejemplo.com"
        ];
    ?>';
    }

    private function createBackup()
    {
        $backupDir = storage_path('app/template-backups');

        if (! File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $timestamp = time();
        $backupPath = $backupDir."/contrato-mutuo-{$timestamp}.blade.php";

        $currentContent = $this->getTemplateContent();
        File::put($backupPath, $currentContent);

        // Keep only last 10 backups
        $this->cleanupOldBackups($backupDir);
    }

    private function cleanupOldBackups($backupDir)
    {
        $files = File::files($backupDir);
        $contractBackups = [];

        foreach ($files as $file) {
            if (str_contains($file->getFilename(), 'contrato-mutuo-')) {
                $timestamp = str_replace(['contrato-mutuo-', '.blade.php'], '', $file->getFilename());
                $contractBackups[$timestamp] = $file->getPathname();
            }
        }

        if (count($contractBackups) > 10) {
            krsort($contractBackups);
            $toDelete = array_slice($contractBackups, 10);

            foreach ($toDelete as $filePath) {
                File::delete($filePath);
            }
        }
    }

    private function convertVariablesToBlade($content)
    {
        // Map of template variables to actual Laravel blade variables
        $variableMap = [
            '{{customer_name}}' => '{{ $persona ? strtoupper(trim($persona->nombres . \' \' . $persona->ape_pat . \' \' . $persona->ape_mat)) : \'[NOMBRE DEL CLIENTE]\' }}',
            '{{customer_dni}}' => '{{ $persona ? $persona->documento : \'[DNI]\' }}',
            '{{customer_address}}' => '{{ $persona && $persona->direccion ? $persona->direccion : \'[DIRECCIÓN]\' }}',
            '{{customer_phone}}' => '{{ $persona && $persona->telefono ? $persona->telefono : \'[TELÉFONO]\' }}',
            '{{customer_email}}' => '{{ $persona && $persona->email ? $persona->email : \'[EMAIL]\' }}',

            '{{company_name}}' => '{{ $company_name }}',
            '{{company_ruc}}' => '{{ $company_ruc }}',
            '{{company_address}}' => '{{ $company_address }}',
            '{{manager_name}}' => '{{ $gerente_nombre }}',
            '{{manager_dni}}' => '{{ $gerente_dni }}',

            '{{loan_amount}}' => '{{ number_format($monto_prestamo, 2) }}',
            '{{loan_amount_words}}' => '{{ $montoTexto }}',
            '{{interest_rate}}' => '{{ $tasa_efectiva_semanal }}%',
            '{{installment_amount}}' => '{{ number_format($monto_cuota, 2) }}',
            '{{total_installments}}' => '{{ $total_cuotas }}',
            '{{contract_number}}' => '{{ $contrato_numero }}',

            '{{disbursement_date}}' => '{{ $fecha_desembolso }}',
            '{{contract_date}}' => '{{ date(\'d/m/Y\') }}',
            '{{current_date}}' => '{{ date(\'d/m/Y\') }}',

            '{{guarantor_name}}' => '{{ $aval_nombre ?? \'[NOMBRE DEL AVAL]\' }}',
            '{{guarantor_dni}}' => '{{ $aval_dni ?? \'[DNI AVAL]\' }}',
            '{{guarantor_phone}}' => '{{ $aval_telefono ?? \'[TELÉFONO AVAL]\' }}',
            '{{guarantor_address}}' => '{{ $aval_direccion ?? \'[DIRECCIÓN AVAL]\' }}',

            '{{payment_frequency}}' => '{{ $prestamo && $prestamo->frecuencia_pago ? strtoupper($prestamo->frecuencia_pago) : \'SEMANAL\' }}',
            '{{first_payment_date}}' => '{{ $prestamo && $prestamo->cuotas->first() ? $prestamo->cuotas->first()->fecha_vencimiento->format(\'d/m/Y\') : date(\'d/m/Y\', strtotime(\'+1 week\')) }}',
            '{{installment_table}}' => '@if($prestamo && $prestamo->cuotas->count() > 0)
                <table class="cronograma-table">
                    <thead>
                        <tr>
                            <th>Nº Cuota</th>
                            <th>Fecha Vencimiento</th>
                            <th>Capital</th>
                            <th>Interés</th>
                            <th>Cuota</th>
                            <th>Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $saldo = $prestamo->cantidad_solicitada; @endphp
                        @foreach($prestamo->cuotas as $index => $cuota)
                        @php
                            $interes = $saldo * ($prestamo->tasa_interes / 100);
                            $capital = $cuota->monto - $interes;
                            $saldo -= $capital;
                        @endphp
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $cuota->fecha_vencimiento->format(\'d/m/Y\') }}</td>
                            <td>S/ {{ number_format($capital, 2) }}</td>
                            <td>S/ {{ number_format($interes, 2) }}</td>
                            <td>S/ {{ number_format($cuota->monto, 2) }}</td>
                            <td>S/ {{ number_format(max(0, $saldo), 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif',
            '{{loan_summary}}' => '@if($prestamo)
                <div class="content">
                    <p><span class="bold">RESUMEN:</span></p>
                    <p>Monto del préstamo: S/ {{ number_format($prestamo->cantidad_solicitada, 2) }}</p>
                    <p>Total de intereses: S/ {{ number_format($prestamo->cuotas->sum(\'monto\') - $prestamo->cantidad_solicitada, 2) }}</p>
                    <p>Total a pagar: S/ {{ number_format($prestamo->cuotas->sum(\'monto\'), 2) }}</p>
                    <p>Número de cuotas: {{ $prestamo->cuotas->count() }}</p>
                    <p>Tasa de interés {{ strtolower($prestamo->frecuencia_pago ?? \'semanal\') }}: {{ number_format($prestamo->tasa_interes, 2) }}%</p>
                </div>
                @endif',

            '{{customer_district}}' => '{{ $persona && $persona->distrito ? strtoupper($persona->distrito->nombre) : \'[DISTRITO]\' }}',
            '{{customer_province}}' => '{{ $persona && $persona->provincia ? strtoupper($persona->provincia->nombre) : \'[PROVINCIA]\' }}',
            '{{customer_department}}' => '{{ $persona && $persona->departamento ? strtoupper($persona->departamento->nombre) : \'[DEPARTAMENTO]\' }}',
        ];

        // Replace all template variables with Laravel blade variables
        foreach ($variableMap as $templateVar => $bladeVar) {
            $content = str_replace($templateVar, $bladeVar, $content);
        }

        return $content;
    }

    private function extractContentOnly($templateContent)
    {
        // Si el contenido está prácticamente vacío, devolver template base
        if (empty($templateContent) || strlen(trim($templateContent)) < 200) {
            return $this->getBaseTemplateContent();
        }

        // Si ya es texto plano (no tiene tags HTML), devolverlo directamente
        if (strpos($templateContent, '<body') === false && strpos($templateContent, '<div') === false) {
            return trim($templateContent);
        }

        // Extraer contenido del body
        if (preg_match('/<body[^>]*>(.*?)<\/body>/s', $templateContent, $matches)) {
            $bodyContent = $matches[1];
        } else {
            $bodyContent = $templateContent;
        }

        // Remover comentarios y footer
        $bodyContent = preg_replace('/<!--.*?-->/s', '', $bodyContent);
        $bodyContent = preg_replace('/<div class="footer">.*?<\/div>/s', '', $bodyContent);

        // Convertir HTML a texto editable manteniendo variables
        $bodyContent = preg_replace('/<div class="title"[^>]*>.*?<\/div>/s', "CONTRATO DE MUTUO\n\n", $bodyContent);
        $bodyContent = preg_replace('/<div class="contract-number"[^>]*>.*?<\/div>/s', "Nro. {{contract_number}}\n\n", $bodyContent);

        // Convertir cláusulas y párrafos
        $bodyContent = preg_replace('/<div class="clause-title"[^>]*>(.*?)<\/div>/s', "$1\n", $bodyContent);

        $bodyContent = preg_replace('/<p[^>]*>(.*?)<\/p>/s', "$1\n\n", $bodyContent);
        $bodyContent = preg_replace('/<div[^>]*class="content"[^>]*>(.*?)<\/div>/s', "$1\n", $bodyContent);
        $bodyContent = preg_replace('/<div[^>]*class="clause"[^>]*>(.*?)<\/div>/s', "$1\n", $bodyContent);

        // Simplificar firmas
        $bodyContent = preg_replace('/<div class="signatures">.*?<\/div>/s', "\n\n_____________________        _____________________        _____________________\nEL MUTUANTE                 EL MUTUATARIO                EL FIADOR\n\n", $bodyContent);

        // Reemplazar cronograma complejo con variable simple
        $bodyContent = preg_replace('/@if\(.*?@endif/s', "\n{{installment_table}}\n", $bodyContent);
        $bodyContent = preg_replace('/<div class="page-break">.*?<\/div>/s', "\n{{installment_table}}\n", $bodyContent);

        // Limpiar formateo pero mantener variables
        $bodyContent = preg_replace('/<span[^>]*class="bold"[^>]*>(.*?)<\/span>/s', '**$1**', $bodyContent);
        $bodyContent = preg_replace('/<span[^>]*>(.*?)<\/span>/s', '$1', $bodyContent);
        $bodyContent = strip_tags($bodyContent);

        // Normalizar espacios y líneas
        $bodyContent = preg_replace('/\n{3,}/', "\n\n", $bodyContent);
        $bodyContent = preg_replace('/[ \t]+/', ' ', $bodyContent);
        $bodyContent = trim($bodyContent);

        return $bodyContent ?: $this->getBaseTemplateContent();
    }

    private function getBaseTemplateContent()
    {
        return 'CONTRATO DE MUTUO

Nro. {{contract_number}}

Conste por el presente documento, el **CONTRATO DE MUTUO** que celebran de una parte **{{company_name}}** con R.U.C.: **{{company_ruc}}** y con domicilio fiscal en **{{company_address}}**, debidamente representado por el Gerente General **{{manager_name}}** con D.N.I.: **{{manager_dni}}**, como EL MUTUANTE y que en adelante será denominado LA EMPRESA.

Y por otro lado el SR./SRA. **{{customer_name}}**, con D.N.I./C.E.: **{{customer_dni}}**, con domicilio en **{{customer_address}}**, DISTRITO DE **{{customer_district}}**, PROVINCIA **{{customer_province}}** Y DEPARTAMENTO DE **{{customer_department}}**, como EL MUTUATARIO que en adelante será denominado EL CLIENTE.

PRIMERA: DEL OBJETO DEL CONTRATO
Por el presente contrato LA EMPRESA entrega en calidad de préstamo a EL CLIENTE la suma de S/ {{loan_amount}} ({{loan_amount_words}}), dinero que EL CLIENTE se obliga a devolver en la forma y condiciones que se establecen en las siguientes cláusulas.

SEGUNDA: DEL PLAZO Y FORMA DE PAGO
EL CLIENTE se obliga a devolver el importe del préstamo en {{total_installments}} cuotas {{payment_frequency}} consecutivas de S/ {{installment_amount}} cada una, venciendo la primera cuota el {{first_payment_date}}.

TERCERA: DE LOS INTERESES
Las partes acuerdan que EL CLIENTE pagará por concepto de intereses compensatorios la tasa efectiva {{payment_frequency}} de {{interest_rate}}%. En caso de incumplimiento, se aplicarán los intereses moratorios según las condiciones establecidas.

CUARTA: DE LAS GARANTÍAS
Para garantizar el cumplimiento de las obligaciones derivadas del presente contrato, EL CLIENTE constituye como fiador solidario al Sr./Sra. {{guarantor_name}}, con D.N.I. Nº {{guarantor_dni}}, quien acepta expresamente la responsabilidad solidaria.

QUINTA: DEL VENCIMIENTO ANTICIPADO
LA EMPRESA podrá considerar vencido el plazo del préstamo y exigir el pago total de la deuda en caso de: a) Incumplimiento de dos o más cuotas consecutivas, b) Falsedad en la información proporcionada, c) Cambio de domicilio sin comunicación previa.

SEXTA: DE LA JURISDICCIÓN
Para cualquier controversia que pudiera suscitarse en la interpretación y/o cumplimiento del presente contrato, las partes se someten a la jurisdicción de los Juzgados y Tribunales de Lima, renunciando expresamente al fuero de sus domicilios.

_____________________        _____________________        _____________________
EL MUTUANTE                 EL MUTUATARIO                EL FIADOR

{{installment_table}}';
    }

    private function buildFullTemplate($contentOnly, $isPreview = false)
    {
        // Convertir el texto plano de vuelta a HTML estructurado
        $htmlContent = $this->convertTextToHtml($contentOnly);

        // Convertir variables simples a Laravel blade
        $convertedContent = $this->convertVariablesToBlade($htmlContent);

        $variables = $isPreview ? $this->getPreviewVariables() : '';

        // Obtener estilos de la plantilla original
        $originalContent = $this->getTemplateContent();
        preg_match('/<style>(.*?)<\/style>/s', $originalContent, $styleMatches);
        $styles = $styleMatches[1] ?? $this->getDefaultStyles();

        $fullTemplate = '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contrato de Mutuo</title>
    <style>
'.$styles.'
    </style>
</head>
<body>'.$variables.'
'.$convertedContent.'

    <!-- Footer -->
    <div class="footer">
        Documento generado automáticamente el {{ date(\'d/m/Y H:i\') }} | 
        Sistema de Gestión de Préstamos
    </div>
</body>
</html>';

        return $fullTemplate;
    }

    private function getDefaultStyles()
    {
        return '
        @page {
            margin: 1.5cm;
            size: A4;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 0;
        }
        
        .title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin: 20px 0;
        }
        
        .contract-number {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 30px;
        }
        
        .content {
            text-align: justify;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        .clause {
            margin-bottom: 15px;
        }
        
        .clause-title {
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 8px;
        }
        
        .signatures {
            margin-top: 50px;
            display: table;
            width: 100%;
        }
        
        .signature-block {
            display: table-cell;
            width: 33.33%;
            text-align: center;
            vertical-align: top;
            padding: 0 10px;
        }
        
        .signature-line {
            border-top: 1px solid #333;
            margin: 50px auto 10px;
            width: 150px;
        }
        
        .signature-info {
            font-size: 9px;
            line-height: 1.2;
        }
        
        .bold {
            font-weight: bold;
        }
        
        .footer {
            position: fixed;
            bottom: 1cm;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8px;
            color: #888;
        }';
    }

    private function convertTextToHtml($textContent)
    {
        // Convertir texto plano de vuelta a HTML estructurado
        $htmlContent = $textContent;

        // Escapar caracteres especiales pero mantener las variables {{}}
        $htmlContent = preg_replace_callback('/(\{\{[^}]+\}\})/', function ($matches) {
            return $matches[1]; // Mantener variables intactas
        }, $htmlContent);

        // Convertir **texto** a <span class="bold">texto</span>
        $htmlContent = preg_replace('/\*\*(.*?)\*\*/', '<span class="bold">$1</span>', $htmlContent);

        // Detectar y convertir el título
        if (preg_match('/^CONTRATO DE MUTUO\s*$/m', $htmlContent)) {
            $htmlContent = preg_replace('/^CONTRATO DE MUTUO\s*$/m', '<div class="title">CONTRATO DE MUTUO</div>', $htmlContent);
        }

        // Detectar y convertir número de contrato
        $htmlContent = preg_replace('/^Nro\.\s*(\{\{[^}]+\}\}|\S+)\s*$/m', '<div class="contract-number">Nro. $1</div>', $htmlContent);

        // Detectar cláusulas (texto que termina con :)
        $htmlContent = preg_replace('/^([A-ZÁÉÍÓÚÑ][^:\n]*:)\s*$/m', '<div class="clause-title">$1</div>', $htmlContent);

        // Convertir párrafos (líneas que no están vacías y no son títulos o cláusulas)
        $lines = explode("\n", $htmlContent);
        $processedLines = [];
        $inClause = false;
        $clauseContent = '';

        foreach ($lines as $line) {
            $line = trim($line);

            if (empty($line)) {
                if ($inClause && ! empty($clauseContent)) {
                    $processedLines[] = '<div class="clause">';
                    $processedLines[] = $clauseContent;
                    $processedLines[] = '</div>';
                    $clauseContent = '';
                    $inClause = false;
                }
                $processedLines[] = '';
            } elseif (strpos($line, '<div class="title">') !== false ||
                     strpos($line, '<div class="contract-number">') !== false ||
                     strpos($line, '_____________________') !== false) {
                $processedLines[] = $line;
            } elseif (strpos($line, '<div class="clause-title">') !== false) {
                if ($inClause && ! empty($clauseContent)) {
                    $processedLines[] = '<div class="clause">';
                    $processedLines[] = $clauseContent;
                    $processedLines[] = '</div>';
                    $clauseContent = '';
                }
                $clauseContent = $line;
                $inClause = true;
            } else {
                // Es contenido normal
                if ($inClause) {
                    $clauseContent .= "\n<div class=\"content\"><p>".$line.'</p></div>';
                } else {
                    // Párrafo normal
                    $processedLines[] = '<div class="content"><p>'.$line.'</p></div>';
                }
            }
        }

        // Cerrar cláusula final si existe
        if ($inClause && ! empty($clauseContent)) {
            $processedLines[] = '<div class="clause">';
            $processedLines[] = $clauseContent;
            $processedLines[] = '</div>';
        }

        // Convertir sección de firmas
        $htmlContent = implode("\n", $processedLines);
        $htmlContent = preg_replace('/_{5,}.*?_{5,}.*?_{5,}\s*\nEL MUTUANTE.*?EL MUTUATARIO.*?EL FIADOR/s',
            '<div class="signatures">
                <div class="signature-block">
                    <div class="signature-line"></div>
                    <div class="signature-info">
                        <div class="bold">EL MUTUANTE</div>
                        <div>{{ $company_name }}</div>
                        <div>R.U.C.: {{ $company_ruc }}</div>
                        <div>{{ $gerente_nombre }}</div>
                        <div>D.N.I.: {{ $gerente_dni }}</div>
                        <div class="bold">GERENTE GENERAL</div>
                    </div>
                </div>
                
                <div class="signature-block">
                    <div class="signature-line"></div>
                    <div class="signature-info">
                        <div class="bold">EL MUTUATARIO</div>
                        <div>{{ $persona ? strtoupper(trim($persona->nombres . " " . $persona->ape_pat . " " . $persona->ape_mat)) : "[NOMBRE COMPLETO]" }}</div>
                        <div>D.N.I./C.E.: {{ $persona ? $persona->documento : "XXXXXXX" }}</div>
                    </div>
                </div>
                
                <div class="signature-block">
                    <div class="signature-line"></div>
                    <div class="signature-info">
                        <div class="bold">EL FIADOR</div>
                        <div>NOMBRE: ______</div>
                        <div>D.N.I.: ____</div>
                        <div>TELEF: _____</div>
                        <div>CORREO: ____</div>
                        <div>DIRECCION: ____</div>
                    </div>
                </div>
            </div>', $htmlContent);

        return $htmlContent;
    }

    private function generateInstallmentTableHtml($prestamo)
    {
        if (! $prestamo || $prestamo->cuotas->count() === 0) {
            return '<p>No hay cronograma de pagos disponible.</p>';
        }

        $html = '<table class="cronograma-table">
                    <thead>
                        <tr>
                            <th>Nº Cuota</th>
                            <th>Fecha Vencimiento</th>
                            <th>Capital</th>
                            <th>Interés</th>
                            <th>Cuota</th>
                            <th>Saldo</th>
                        </tr>
                    </thead>
                    <tbody>';

        $saldo = $prestamo->cantidad_solicitada ?? 0;
        foreach ($prestamo->cuotas as $index => $cuota) {
            $interes = $saldo * (($prestamo->tasa_interes ?? 0) / 100);
            $capital = ($cuota->monto ?? 0) - $interes;
            $saldo -= $capital;

            $html .= '<tr>
                        <td>'.($index + 1).'</td>
                        <td>'.($cuota->fecha_vencimiento ? $cuota->fecha_vencimiento->format('d/m/Y') : date('d/m/Y')).'</td>
                        <td>S/ '.number_format($capital, 2).'</td>
                        <td>S/ '.number_format($interes, 2).'</td>
                        <td>S/ '.number_format($cuota->monto ?? 0, 2).'</td>
                        <td>S/ '.number_format(max(0, $saldo), 2).'</td>
                      </tr>';
        }

        $html .= '</tbody></table>';

        return $html;
    }

    private function generateLoanSummaryHtml($prestamo)
    {
        if (! $prestamo) {
            return '<p>No hay información del préstamo disponible.</p>';
        }

        $cuotasSum = $prestamo->cuotas ? $prestamo->cuotas->sum('monto') : 0;
        $cantidadSolicitada = $prestamo->cantidad_solicitada ?? 0;
        $totalIntereses = $cuotasSum - $cantidadSolicitada;
        $totalPagar = $cuotasSum;

        return '<div class="loan-summary">
                    <p><strong>RESUMEN DEL PRÉSTAMO:</strong></p>
                    <p>Monto del préstamo: S/ '.number_format($cantidadSolicitada, 2).'</p>
                    <p>Total de intereses: S/ '.number_format($totalIntereses, 2).'</p>
                    <p>Total a pagar: S/ '.number_format($totalPagar, 2).'</p>
                    <p>Número de cuotas: '.($prestamo->cuotas ? $prestamo->cuotas->count() : 0).'</p>
                    <p>Tasa de interés '.strtolower($prestamo->frecuencia_pago ?? 'semanal').': '.number_format($prestamo->tasa_interes ?? 0, 2).'%</p>
                </div>';
    }
}
