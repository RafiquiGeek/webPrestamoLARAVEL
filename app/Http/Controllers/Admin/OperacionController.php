<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Operacion;
use Greenter\Model\Client\Client as GreenterClient;
use Greenter\Model\Company\Address;
use Greenter\Model\Company\Company;
use Greenter\Model\Sale\Invoice;
use Greenter\Model\Sale\Legend;
use Greenter\Model\Sale\SaleDetail;
use Greenter\See;
use Greenter\Ws\Services\SunatEndpoints;
use Illuminate\Support\Facades\File;
use PDF;

class OperacionController extends Controller
{
    public function generarComprobante($operacion_id)
    {
        $operacion = Operacion::findOrFail($operacion_id);

        // Configurar Greenter en modo de prueba
        $see = new See;
        $see->setService(SunatEndpoints::FE_BETA);

        // Cargar certificado
        $certificadoPath = storage_path('app/keys/certificado_prueba.pem');
        if (! file_exists($certificadoPath)) {
            return response()->json(['error' => 'El certificado no se encuentra.'], 500);
        }
        $see->setCertificate(file_get_contents($certificadoPath));

        // Cargar clave privada
        $clavePrivadaPath = storage_path('app/keys/clave_privada.pem');
        if (! file_exists($clavePrivadaPath)) {
            return response()->json(['error' => 'La clave privada no se encuentra.'], 500);
        }

        $privateKeyContent = file_get_contents($clavePrivadaPath);
        // Asegúrate de que la clave sea válida
        if (! $privateKeyContent) {
            return response()->json(['error' => 'No se pudo cargar la clave privada.'], 500);
        }

        // Establecer la clave privada en Greenter
        $see->setPrivateKey($privateKeyContent);

        // Configura las credenciales (RUC y usuario)
        $see->setCredentials('20000000001MODDATOS', 'moddatos');

        // Configuración de la empresa (datos de prueba)
        $company = new Company;
        $company->setRuc('20123456789')
            ->setRazonSocial('EMPRESA DE PRUEBA S.A.')
            ->setNombreComercial('EMPRESA DE PRUEBA')
            ->setAddress((new Address)
                ->setUbigueo('150101')
                ->setDistrito('LIMA')
                ->setProvincia('LIMA')
                ->setDepartamento('LIMA')
                ->setUrbanizacion('-')
                ->setCodLocal('0000')
                ->setDireccion('AV. LOS TEST 123'));

        // Configuración del cliente
        $client = new GreenterClient;
        $client->setTipoDoc('6')
            ->setNumDoc('20000000001')
            ->setRznSocial('CLIENTE DE PRUEBA S.A.');

        // Configuración del comprobante (Factura)
        $invoice = new Invoice;
        $invoice->setUblVersion('2.1')
            ->setTipoOperacion('0101')
            ->setTipoDoc('01')
            ->setSerie('F001')
            ->setCorrelativo($operacion->id)
            ->setFechaEmision(new \DateTime)
            ->setCompany($company)
            ->setClient($client)
            ->setMtoOperGravadas($operacion->abono)
            ->setMtoIGV($operacion->calcularIGV())
            ->setTotalImpuestos($operacion->calcularIGV())
            ->setValorVenta($operacion->abono)
            ->setSubTotal($operacion->abono + $operacion->calcularIGV())
            ->setMtoImpVenta($operacion->abono + $operacion->calcularIGV());

        // Detalles del comprobante
        $item = new SaleDetail;
        $item->setCodProducto('OP'.$operacion->id)
            ->setUnidad('NIU')
            ->setDescripcion('Abono de cuota')
            ->setCantidad(1)
            ->setMtoValorUnitario($operacion->abono)
            ->setMtoBaseIgv($operacion->abono)
            ->setPorcentajeIgv(18)
            ->setIgv($operacion->calcularIGV())
            ->setTipAfeIgv('10')
            ->setTotalImpuestos($operacion->calcularIGV())
            ->setMtoValorVenta($operacion->abono)
            ->setMtoPrecioUnitario($operacion->abono * 1.18);

        $invoice->setDetails([$item]);

        // Agregar leyenda (total en palabras)
        $legend = new Legend;
        $legend->setCode('1000')
            ->setValue('SON '.strtoupper($this->convertirNumeroALetras($operacion->abono + $operacion->calcularIGV())).' SOLES');
        $invoice->setLegends([$legend]);

        // Enviar a SUNAT en modo prueba
        $result = $see->send($invoice);

        if (! $result->isSuccess()) {
            return response()->json(['error' => $result->getError()->getMessage()], 500);
        } else {
            // Guardar XML
            $xmlContent = $see->getFactory()->getLastXml();
            File::put(storage_path('app/public/comprobante_'.$operacion->id.'.xml'), $xmlContent);

            return response()->download(storage_path('app/public/comprobante_'.$operacion->id.'.xml'), 'comprobante_'.$operacion->id.'.xml');
        }
    }

    private function convertirNumeroALetras($monto)
    {
        return 'CIENTO DIECIOCHO CON 00/100'; // Implementa la conversión correcta
    }

    public function generarPDF($prestamo_id)
    {
        // Cargar las operaciones generales asociadas al préstamo
        $operacionesGenerales = Operacion::where('prestamo_id', $prestamo_id)
            ->whereNull('operacion_general_id') // Asegurarse de que sean operaciones generales
            ->with('operacionesRelacionadas', 'user') // Cargar las operaciones relacionadas y el usuario
            ->get();

        // Generar el PDF utilizando la vista 'pdf.operaciones' y pasar las operaciones generales
        $pdf = PDF::loadView('pdf.operaciones', compact('operacionesGenerales'));

        // Descargar el PDF generado
        return $pdf->download('operaciones_generales_'.$prestamo_id.'.pdf');
    }

    public function generarPDFIndividual($prestamo_id, $operacion_id)
    {
        // Obtener la operación general junto con las operaciones relacionadas y el usuario
        $operacionGeneral = Operacion::where('prestamo_id', $prestamo_id)
            ->where('id', $operacion_id)
            ->with('operacionesRelacionadas', 'user')  // Asegúrate de que la relación 'user' esté cargada
            ->first();

        // Verificar si la operación general existe
        if (! $operacionGeneral) {
            return redirect()->back()->with('error', 'Operación no encontrada.');
        }

        // Generar el PDF utilizando la vista 'pdf.operacion_individual' y pasar la operación general
        $pdf = PDF::loadView('pdf.operacion_individual', compact('operacionGeneral'));

        // Descargar el PDF generado
        return $pdf->download('operacion_'.$operacion_id.'.pdf');
    }
}
