<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cuota;
use App\Models\Serie;
use App\Models\Solicitud;
/**Factura Electronica Cliente**/
use Greenter\Model\Client\Client;
use Greenter\Model\Company\Address;
use Greenter\Model\Company\Company;
use Greenter\Model\Sale\FormaPagos\FormaPagoContado;
use Greenter\Model\Sale\Invoice;
use Greenter\Model\Sale\Legend;
use Greenter\Model\Sale\SaleDetail;
/**End Factura Electronica Cliente**/

use Greenter\See;
use Greenter\Ws\Services\SunatEndpoints;
use Illuminate\Http\Request;

class FactElectronicaController extends Controller
{
    public function ConfigGreenter()
    {
        $see = new See;
        $certificate = public_path('certificates\certificate.pem');
        $see->setCertificate(file_get_contents($certificate));
        $see->setService(SunatEndpoints::FE_BETA);
        $see->setClaveSOL('20000000001', 'MODDATOS', 'moddatos');

        return $see;
    }

    public function BoletaElectronica(Request $request)
    {

        $see = $this->ConfigGreenter();

        $cuotas = Cuota::where('id', $request->cuota_id)->get();
        $serie = Serie::latest()->first();
        foreach ($cuotas as $ct) {
            //dd($ct);
            $cliente = Solicitud::with('cliente')->where('id', $ct->solicitud_id)->get();
            foreach ($cliente as $client) {
                //dd($client->cliente->documento);
                //dd($client);

                $client = (new Client)
                    ->setTipoDoc('1')
                    ->setNumDoc($client->cliente->documento)
                    ->setRznSocial($client->nombre_cliente);

                // Emisor
                $address = (new Address)
                    ->setUbigueo('150101')
                    ->setDepartamento('LIMA')
                    ->setProvincia('LIMA')
                    ->setDistrito('LIMA')
                    ->setUrbanizacion('-')
                    ->setDireccion('Av. Villa Nueva 221')
                    ->setCodLocal('0000'); // Codigo de establecimiento asignado por SUNAT, 0000 por defecto.

                $company = (new Company)
                    ->setRuc('20123456789')
                    ->setRazonSocial('GREEN SAC')
                    ->setNombreComercial('GREEN')
                    ->setAddress($address);

                $addigv = ($ct->interes + $ct->comision) * 0.18;
                $invoice = (new Invoice)
                    ->setUblVersion('2.1')
                    ->setTipoOperacion('0101')
                    ->setFecVencimiento(new \DateTime)
                    ->setTipoDoc('03') // Tipo de documento (01 para boleta)
                    ->setSerie('B001')
                    ->setCorrelativo($serie + 1)
                    ->setFechaEmision(new \DateTime)
                    ->setFormaPago(new FormaPagoContado) // FormaPago: Contado
                    ->setTipoMoneda('PEN') // Moneda (PEN para soles, USD para dólares, etc.)
                    ->setCompany($company)
                    ->setClient($client) // Datos del cliente
                    ->setMtoOperGravadas($ct->interes + $ct->comision) // Monto total gravado
                    ->setMtoIGV($ct->igv) // Monto del IGV
                    ->setTotalImpuestos($ct->igv) // Total de impuestos
                    ->setValorVenta($ct->interes + $ct->comision) // Valor de venta
                    ->setSubTotal($ct->interes + $ct->comision + $addigv)
                    ->setMtoImpVenta($ct->interes + $ct->comision + $addigv); // Monto total de la venta

                $item = (new SaleDetail)
                    ->setCodProducto('P00'.$ct->id)
                    ->setUnidad('ZZ') // Unidad - Catalog. 03
                    ->setCantidad(1)
                    ->setMtoValorUnitario($ct->interes + $ct->comision)
                    ->setDescripcion('CUOTA PRESTAMO PERSONAL')
                    ->setMtoBaseIgv($ct->igv)
                    ->setPorcentajeIgv($ct->igv)
                    ->setIgv($ct->igv)
                    ->setTipAfeIgv('10') // Gravado Op. Onerosa - Catalog. 07
                    ->setTotalImpuestos($ct->igv) // Suma de impuestos en el detalle
                    ->setMtoValorVenta($ct->interes + $ct->comision)
                    ->setMtoPrecioUnitario($ct->interes + $ct->comision);

                $legend = (new Legend)
                    ->setCode('1000') // Monto en letras - Catalog. 52
                    ->setValue('SON DOSCIENTOS TREINTA Y SEIS CON 00/100 SOLES');

                $invoice->setDetails([$item])
                    ->setLegends([$legend]);

                $result = $see->send($invoice);

                $pathXml = public_path().'/archive-xml/'.$invoice->getName().'.xml';
                file_put_contents($pathXml, $see->getFactory()->getLastXml());

                if (! $result->isSuccess()) {
                    // Mostrar error al conectarse a SUNAT.
                    echo 'Codigo Error: '.$result->getError()->getCode();
                    echo 'Mensaje Error: '.$result->getError()->getMessage();
                    exit();
                }

                $pathCdr = public_path().'/archive-zip/'.'R-'.$invoice->getName().'.zip';
                file_put_contents($pathCdr, $result->getCdrZip());
            }

        }
    }
}
