<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contrato de Mutuo</title>
    <style>
        @page {
            /*margin: 1.5cm;*/
            margin-bottom: 1cm;
            size: A4;
        }

        .page-number:after {
            content: "Página " counter(page);
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.5;
            color: #333;
            margin: 0;
            padding: 0;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .title {
            text-align: center;
            font-size: 18pt;
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
            text-align: justify;
        }

        .clause-title {
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 8px;
        }

        .clause p {
            text-align: justify;
        }

        .clause ul {
            text-align: justify;
        }
        
        .signatures {
            margin-top: 60px;
            width: 100%;
        }

        .signatures-table {
            width: 100%;
            border-collapse: collapse;
        }

        .signatures-table td {
            text-align: center;
            vertical-align: top;
            padding: 0 20px;
        }

        .signature-block {
            text-align: center;
            display: inline-block;
            width: 100%;
        }

        .signature-line {
            border-top: 1px solid #333;
            margin: 50px auto 10px;
            width: 180px;
        }

        .signature-info {
            font-size: 10pt;
            line-height: 1.3;
        }
        
        .blank-line {
            border-bottom: 1px solid #333;
            display: inline-block;
            min-width: 200px;
            margin: 0 5px;
        }
        
        .bold {
            font-weight: bold;
        }
        
        .underline {
            text-decoration: underline;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        .anexo-title {
            text-align: center;
            font-weight: bold;
            font-size: 14px;
            margin: 30px 0;
        }
        
        .cronograma-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 9pt;
        }

        .cronograma-table th,
        .cronograma-table td {
            border: 1px solid #333;
            padding: 4px 3px;
            text-align: center;
            font-size: 9pt;
        }

        .cronograma-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-transform: uppercase;
        }

        .cronograma-table td {
            font-size: 9pt;
        }
        
        .footer {
            position: fixed;
            bottom: 9mm;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 10pt;
            color: #888;
        }
        
        .resumen-box {
            border: 2px solid #333;
            padding: 10px;
            margin-bottom: 15px;
            font-size: 8pt;
            background-color: #f9f9f9;
        }

        .resumen-table {
            width: 100%;
            border-collapse: collapse;
        }

        .resumen-table td {
            padding: 2px 6px;
            vertical-align: top;
            font-size: 8pt;
            line-height: 1.3;
        }

        .resumen-table td:first-child {
            width: 48%;
            border-right: 1px solid #ddd;
            padding-right: 10px;
        }

        .resumen-table td:last-child {
            width: 52%;
            padding-left: 10px;
        }

        .resumen-item {
            margin-bottom: 4px;
            display: block;
            clear: both;
        }

        .resumen-label {
            font-weight: bold;
            display: inline-block;
            width: 48%;
            font-size: 8pt!important;
            vertical-align: top;
        }

        .resumen-value {
            display: inline-block;
            width: 50%;
            font-size: 8pt!important;
            vertical-align: top;
        }

        /* Estilos para el Pagaré */
        .anexo-pagare {
            padding: 20px;
        }

        .pagare-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .pagare-title {
            font-size: 24px;
            font-weight: bold;
            text-align: center;
        }

        .pagare-subtitle {
            font-size: 12px;
            font-style: italic;
        }

        .pagare-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 10pt;
        }

        .pagare-table th,
        .pagare-table td {
            border: 1px solid #333;
            padding: 8px;
            text-align: center;
        }

        .pagare-table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }

        .pagare-section {
            margin: 15px 0;
            text-align: justify;
            line-height: 1.5;
        }

        .pagare-section-title {
            font-weight: bold;
            font-size: 12px;
            margin: 20px 0 10px 0;
            text-align: center;
            text-decoration: underline;
        }

        .pagare-firma-section {
            margin-top: 40px;
            width: 100%;
        }

        .pagare-firma-table {
            width: 100%;
            border-collapse: collapse;
        }

        .pagare-firma-table td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 0 20px;
        }

        .pagare-firma-block {
            text-align: center;
            display: inline-block;
            width: 100%;
        }

        .pagare-firma-line {
            border-top: 1px solid #333;
            margin: 40px auto 10px;
            width: 200px;
        }

        .pagare-firma-label {
            font-size: 10pt;
            margin: 5px 0;
            line-height: 1.3;
        }
    </style>
</head>
<body>
    @php
        // Obtener fecha de atención desde la tabla operaciones
        $operacionAtencion = $prestamo->operaciones()
            ->where('tipo_operacion', 'desembolso')
            ->orderBy('fecha', 'asc')
            ->first();
        $fechaAtencion = $operacionAtencion && $operacionAtencion->fecha 
            ? \Carbon\Carbon::parse($operacionAtencion->fecha)->format('d/m/Y') 
            : ($prestamo->fecha_atencion ? \Carbon\Carbon::parse($prestamo->fecha_atencion)->format('d/m/Y') : '__/__/____');

        // Variables para el cliente
        $personaCliente = optional($prestamo->cliente)->persona;
        $nombreCompleto = optional($personaCliente)->nombres ? strtoupper(trim((optional($personaCliente)->nombres ?? '') . ' ' . (optional($personaCliente)->ape_pat ?? '') . ' ' . (optional($personaCliente)->ape_mat ?? ''))) : '_________________';
        $documento = optional($personaCliente)->documento ?? '_________________';
        $direccion = optional($personaCliente)->direccion ? optional($personaCliente)->direccion->direccion : '_________________';
        $distrito = optional($personaCliente)->direccion && optional($personaCliente)->direccion->distrito ? optional($personaCliente)->direccion->distrito->distrito : '_________________';
        $provincia = optional($personaCliente)->direccion && optional($personaCliente)->direccion->distrito && optional($personaCliente)->direccion->distrito->provincia ? optional($personaCliente)->direccion->distrito->provincia->provincia : '_________________';
        $departamento = optional($personaCliente)->direccion && optional($personaCliente)->direccion->distrito && optional($personaCliente)->direccion->distrito->provincia && optional($personaCliente)->direccion->distrito->provincia->departamento ? optional($personaCliente)->direccion->distrito->provincia->departamento->departamento : '_________________';

        // Variables para el aval
        $tieneAval = isset($prestamo->aval) && $prestamo->aval;
        $avalPersona = $tieneAval ? optional($prestamo->aval)->persona : null;
    @endphp

    <script type="text/php">
        if (isset($pdf)) {
            $text = "Página {PAGE_NUM} de {PAGE_COUNT}";
            $size = 10;
            $font = $fontMetrics->getFont("Arial");
            $width = $fontMetrics->get_text_width($text, $font, $size);
            $x = $pdf->get_width() - $width - 20;
            $y = $pdf->get_height() - 20;
            $pdf->page_text($x, $y, $text, $font, $size);
        }
    </script>
    <p>{{ $fechaAtencion }}</p>    
    
    <div class="title">CONTRATO DE MUTUO</div>
    
    <div class="content">
        <p>Conste por el presente documento, el <strong>CONTRATO DE MUTUO</strong> que celebran de una parte <strong>GRUPO SANTIAGO PERU S.A.C.</strong> con R.U.C.: <strong>20611373181</strong> y Partida Registral Nº <strong>15366352</strong> de la Oficina Registral de Lima – SUNARP, debidamente representado por el Gerente General <strong>YOSELIN ESTRELLA ESTEBAN MANTILLA</strong> con D.N.I.: <strong>76553582</strong>, con domicilio fiscal en <strong>CAL.SETIMA NRO. 215 PROV. CONST. DEL CALLAO</strong>, como EL MUTUANTE y que en adelante será denominado LA EMPRESA.</p>

        <p>Y por otro lado el SR./SRA. <strong>{{ $nombreCompleto }}</strong>, con D.N.I./C.E.: <strong>{{ $documento }}</strong>, con domicilio en <strong>{{ $direccion }}</strong>, DISTRITO DE <strong>{{ strtoupper($distrito) }}</strong>, PROVINCIA <strong>{{ strtoupper($provincia) }}</strong> Y DEPARTAMENTO DE <strong>{{ strtoupper($departamento) }}</strong>, como EL MUTUATARIO que en adelante será denominado EL CLIENTE.</p>
    </div>

    <div class="clause">
        <div class="clause-title">CLÁUSULA PRIMERA:</div>
        <p>LA EMPRESA es una EMPRESA DE PRÉSTAMOS que realiza operaciones para préstamos de consumo, capital de trabajo y otros relacionados. Y está inscrita en el REGISTRO DE EMPRESAS Y PERSONAS QUE EFECTUAN OPERACIONES FINANCIERAS O DE CAMBIO DE MONEDA por parte la SUPERINTENDENCIA DE BANCA Y SEGUROS (SBS) con Resolución Oficio SBS Nro. 2023-77882.</p>
    </div>

    <div class="clause">
        <div class="clause-title">CLÁUSULA SEGUNDA:</div>
        <p>Por el presente contrato LA EMPRESA concede a EL CLIENTE un préstamo solicitado por el monto de S/ {{ isset($prestamo) ? number_format($prestamo->cantidad_solicitada, 2) : '0.00' }} ({{ isset($prestamo) ? numeroALetras($prestamo->cantidad_solicitada, 'SOLES') : 'CERO CON 00/100 SOLES' }}) a través de una transferencia bancaria a la cuenta bancaria de EL CLIENTE con fecha {{ $fechaAtencion }}. En caso, que EL CLIENTE no cuente con una cuenta bancaria el préstamo solicitado será entregado bajo las siguientes modalidades: (a) Entrega en efectivo en el local de LA EMPRESA y (b) Entrega en efectivo en el domicilio o negocio de EL CLIENTE. Para ello EL CLIENTE deberá firmar UN RECIBO DE CONSTANCIA DE ATENCIÓN. Los datos del préstamo están descritos en el ANEXO Nro. 1: CRONOGRAMA DE PAGOS.</p>
    </div>

    <div class="clause">
        <div class="clause-title">CLÁUSULA TERCERA:</div>
        @php
            // Parámetros de tasas de interés total según el plazo
            $parametrosTasas = [
                8 => 1.38,
                12 => 1.0374899,
                15 => 1.2505,
                18 => 1.2729,
                20 => 1.2844
            ];

            $plazo = $prestamo->plazo ?? 12;
            $tasaInteresTotal = isset($parametrosTasas[$plazo]) ? $parametrosTasas[$plazo] : 1.0374899;

            // Calcular la tasa semanal efectiva: (1 + tasa_total)^(1/plazo) - 1
            $tasaSemanalDecimal = pow(1 + $tasaInteresTotal, 1 / $plazo) - 1;
            $tasaSemanal = round($tasaSemanalDecimal * 100, 2); // Convertir a porcentaje
            $tasaAnual = round($tasaSemanal * 52, 2);

            // Calcular comisión según el plazo usando las reglas establecidas
            $reglasInteres = [
                8 => ['tasa_maxima' => 1.38, 'comision' => 3.29],
                12 => ['tasa_maxima' => 1.38, 'comision' => 4.73],
                15 => ['tasa_maxima' => 1.38, 'comision' => 4.18],
                18 => ['tasa_maxima' => 1.38, 'comision' => 3.36],
                20 => ['tasa_maxima' => 1.38, 'comision' => 2.84]
            ];
            $comisionSemanal = isset($reglasInteres[$plazo]) ? $reglasInteres[$plazo]['comision'] : 3.29;

            // Calcular el porcentaje de interés (tasa semanal - comisión)
            $interesSemanal = round($tasaSemanal - $comisionSemanal, 2);

            $numCuotas = isset($prestamo) && $prestamo->cuotas ? $prestamo->cuotas->count() : 0;
            $montoCuota = isset($prestamo) && $prestamo->cuotas && $prestamo->cuotas->first() ? $prestamo->cuotas->first()->monto : 0;
            $frecuencia = isset($prestamo) ? ($prestamo->frecuencia_pago ?? 'semanal') : 'semanal';
            $frecuenciaTexto = $frecuencia == 'semanal' ? 'semanales' : ($frecuencia == 'mensual' ? 'mensuales' : 'diarios');
        @endphp
        <ul style="list-style: none; padding-left: 0;">
            <li>- La Tasa de Interés Efectiva Semanal (TES) aplicable al presente contrato es de {{ number_format($tasaSemanal, 2) }}%.</li>
            <li>- Los gastos administrativos se calcularán aplicando una tasa del {{ number_format($comisionSemanal, 2) }}% semanal sobre el saldo principal.</li>
            <li>- EL CLIENTE deberá realizar {{ $numCuotas }} pagos {{ $frecuenciaTexto }} por un monto de S/ {{ number_format($montoCuota, 2) }} ({{ numeroALetras($montoCuota, 'SOLES') }}) cada uno.</li>
            <li>- El detalle de las condiciones financieras, así como el cronograma de pagos, se encuentran especificados en ANEXO Nro. 1: CRONOGRAMA DE PAGOS, que forma parte integral del presente contrato.</li>
        </ul>
    </div>

    <div class="clause">
        <div class="clause-title">CLÁUSULA CUARTA:</div>
        <p>EL CLIENTE se compromete a pagar las cuotas establecidas en el ANEXO Nro. 1: CRONOGRAMA DE PAGOS. Las cuáles serán realizados en la Cuenta Bancaria de LA EMPRESA. En caso, que EL CLIENTE no pueda realizar el pago en la cuenta bancaria de LA EMPRESA, entonces el pago podrá ser realizado bajo las siguientes modalidades: (a) Pago en efectivo en el local de LA EMPRESA y (b) Pago en efectivo en el domicilio o negocio de EL CLIENTE.</p>
    </div>

    <div class="clause">
        <div class="clause-title">CLÁUSULA QUINTA:</div>
        <p>Se establece que, en caso de incumplimiento en el pago en las fechas acordadas, durante un periodo de 7 días, esto implicará la resolución completa del contrato. En tal situación, EL CLIENTE deberá abonar a LA EMPRESA lo siguiente:</p>
        <ul style="list-style: none; padding-left: 0;">
            <li>(a) El capital principal (Saldo Principal).</li>
            <li>(b) Tasa de interés compensatorio equivalente a {{ number_format($tasaSemanal, 2) }}% semanal computado del SALDO PRINCIPAL por los días atrasados.</li>
            <li>(c) Gastos administrativos equivalente al {{ number_format($comisionSemanal, 2) }}% semanal computado del SALDO PRINCIPAL por los días atrasados.</li>
            <li>(d) Interés moratorio, basado en los días de retraso, aplicando la tasa del 15% anual permitida por el Banco Central de Reserva del Perú (BCRP).</li>
            <li>(e) Gasto de seguimiento de crédito equivalente a 4 soles por cada día de atraso.</li>
            <li>(f) Otros gastos de cobranzas producto de las operaciones realizadas para la recuperación del crédito. Las cuáles serán debidamente sustentadas a EL CLIENTE.</li>
        </ul>
    </div>

    <div class="clause">
        <div class="clause-title">CLÁUSULA SEXTA:</div>
        <p>EL CLIENTE firmará un PAGARÉ detallado en el ANEXO Nro. 2 de forma incondicional y solidaria a la orden de LA EMPRESA. Este último tendrá la facultad de protestarlo en caso de incumplimiento del contrato. Además de realizar la inscripción respectiva en los Registros de Protestos y Moras de la Cámara de Comercio de LIMA.</p>
    </div>

    <div class="clause">
        <div class="clause-title">CLÁUSULA SÉPTIMA: DECLARACIÓN DE VOLUNTAD Y INTEGRACIÓN DOCUMENTAL</div>
        <p>Las partes contratantes declaran que el presente contrato ha sido celebrado de manera libre, voluntaria y consciente, habiendo leído y comprendido íntegramente cada una de sus cláusulas. Asimismo, reconocen que el contrato refleja fielmente los términos acordados entre LA EMPRESA y EL CLIENTE, sin que medie error, dolo ni coacción.</p>
        <p>Las partes aceptan que los documentos anexos forman parte integral del presente contrato, incluyendo, pero no limitándose a:</p>
        <p>ANEXO N°1: Cronograma de Pagos</p>
        <p>ANEXO N°2: Datos para Pago de Cuotas</p>
    </div>

    <div class="clause">
        <div class="clause-title">CLÁUSULA OCTAVA:</div>
        <p>EL CLIENTE declara para efectos de notificaciones y cobranza las siguientes direcciones, datos de contacto y comunicación.</p>
        @php
            $personaCliente = optional($prestamo->cliente)->persona;

            // Obtener el teléfono celular del cliente desde la tabla telefonos
            $telefonoCelular = $personaCliente
                ? $personaCliente->telefonos()->where('tipo_telefono', 'celular')->first()
                : null;
            $celularCliente = $telefonoCelular ? $telefonoCelular->numero : '_________________';

            $emailCliente = optional($personaCliente)->email ?? '_________________';
            $direccionCliente = optional(optional($personaCliente)->direccion)->direccion ?? '_________________';

            // Obtener datos de persona de contacto
            // Si el cliente tiene contacto_nombre, buscar sus teléfonos en la tabla personas
            $contactoNombre = optional($prestamo->cliente)->contacto_nombre ?? '_________________';

            // Buscar la persona de contacto por nombre si existe
            $personaContacto = null;
            if ($contactoNombre !== '_________________' && $contactoNombre) {
                // Intentar buscar la persona de contacto en la tabla personas
                // Nota: Esto asume que contacto_nombre contiene el nombre completo o documento
                $personaContacto = \App\Models\Persona::whereRaw(
                    "CONCAT(nombres, ' ', ape_pat, ' ', ape_mat) LIKE ?",
                    ['%' . $contactoNombre . '%']
                )->first();
            }

            // Obtener el celular de la persona de contacto desde la tabla telefonos
            $contactoCelular = '_________________';
            if ($personaContacto) {
                $telefonoContacto = $personaContacto->telefonos()->where('tipo_telefono', 'celular')->first();
                $contactoCelular = $telefonoContacto ? $telefonoContacto->numero : '_________________';
            } else {
                // Si no encontramos la persona, usar el campo antiguo si existe
                $contactoCelular = optional($prestamo->cliente)->contacto_celular ?? '_________________';
            }
        @endphp
        <ul style="list-style: none; padding-left: 0;">
            <li>Celular/WhatsApp: <strong>{{ $celularCliente }}</strong></li>
            <li>Correo Electrónico: <strong>{{ $emailCliente }}</strong></li>
            <li>Dirección: <strong>{{ $direccionCliente }}</strong></li>
            <li>Persona de Contacto (Nombre): <strong>{{ $nombreCompleto }}</strong></li>
            <li>Persona de Contacto (Celular/WhatsApp): <strong>{{ $celularCliente }}</strong></li>
        </ul>
        <p>EL CLIENTE da consentimiento de que LA EMPRESA pueda realizar todas las comunicaciones que vea convenientes para el cumplimiento del presente contrato a través de los medios disponibles. incl.ndo comunicación a la PERSONA DE CONTACTO. En caso de que la información sea falsa o imprecisa esto concede a LA EMPRESA la cancelación del contrato y la devolución integral del principal más intereses y/o gastos generados.</p>
    </div>

    <div class="clause">
        <div class="clause-title">CLÁUSULA NOVENA:</div>
        <p>EL CLIENTE da fe con los datos expresados en el presente contrato son ciertos y que cualquier modificación de sus datos de contacto deben ser notificados en un plazo no mayor de 48 horas a LA EMPRESA a través de su correo electrónico y Mensajería WhatsApp correspondiente.</p>
    </div>

    <div class="clause">
        <div class="clause-title">CLÁUSULA DÉCIMA:</div>
        <p>EL CLIENTE acepta las siguientes acciones y procedimientos que puede realizar LA EMPRESA con fines de cobranzas y otros actos relacionados al presente contrato.</p>
        <ul style="list-style: none; padding-left: 0;">
            <li>(a) LA EMPRESA puede notificar días antes de las fechas de pago a través de WhatsApp, llamadas telefónicas a EL CLIENTE para recordatorios y otras comunicaciones relacionadas al contrato.</li>
            <li>(b) LA EMPRESA puede notificar días posteriores a pagos no realizados a través de WhatsApp, llamadas telefónicas, correo electrónico, cartas simples y/o visitas a la dirección del domicilio y/o local de EL CLIENTE para recordatorios y/o notificaciones.</li>
            <li>(c) EL CLIENTE autoriza a LA EMPRESA a utilizar otros canales alternativos de comunicación para el proceso de cobranza.</li>
            <li>(d) LA EMPRESA tiene la potestad de: Envío de Cartas de Cobranza, Cartas Notariales, y otras relacionadas. El Protesto de Pagaré, inscripción de moras en la Cámara de Comercio de Lima y otras acciones legales y judiciales de acuerdo a ley.</li>
        </ul>
        <p>LA EMPRESA cumplirá con todo lo dispuesto en el Art. 62 de la Ley Nro. 29571 Código de Protección y Defensa del Consumidor, ante el uso de cobranzas indebidas. Y otras disposiciones de INDECOPI.</p>
    </div>

    <div class="clause">
        <div class="clause-title">CLÁUSULA DÉCIMA PRIMERA:</div>
        <p>Intervienen en el presente documento y se constituyen como FIADOR SOLIDARIO de EL CLIENTE frente a LA EMPRESA, para garantizar por todas las deudas y obligaciones, directas, existentes o futuras que EL CLIENTE haya asumido o pudiera asumir frente a LA EMPRESA, sin reserva ni limitación alguna. Los datos y domicilios de EL FIADOR SOLIDARIO constan al final del presente documento. EL FIADOR SOLIDARIO declara que la fianza por ellos constituida, mantendrá su vigencia hasta la total cancelación del préstamo concedido a EL CLIENTE, así como de todas las deudas y obligaciones que este tenga o pudiera tener frente a LA EMPRESA, pues constituye su expresa voluntad, que LA EMPRESA, se encuentre facultada para perseguir en todo momento la recuperación de lo que EL CLIENTE le adeuda, no pudiendo EL FIADOR SOLIDARIO solicitar su liberación mientras todas las obligaciones asumidas por EL CLIENTE no estén totalmente cumplidas a satisfacción de LA EMPRESA.</p>
    </div>

    <div class="clause">
        <div class="clause-title">CLÁUSULA DÉCIMA SEGUNDA:</div>
        <p>Cualquier discrepancia que pueda suscitarse entre las partes, se solucionará en lo posible de acuerdo con los principios de la buena fe y al trato directo.</p>
        <p>Las partes declaran encontrarse conformes con las cláusulas precedentes y en señal de conformidad suscriben el presente contrato en dos ejemplares, igualmente válidos, en la ciudad de Lima, a los {{ isset($prestamo) && $prestamo->created_at ? \Carbon\Carbon::parse($prestamo->created_at)->format('d') : '__' }} días del mes de {{ isset($prestamo) && $prestamo->created_at ? \Carbon\Carbon::parse($prestamo->created_at)->locale('es')->monthName : '________' }} del año {{ isset($prestamo) && $prestamo->created_at ? \Carbon\Carbon::parse($prestamo->created_at)->format('Y') : '____' }}.</p>
    </div>

    <div class="clause">
        <div class="clause-title">CLÁUSULA DÉCIMA TERCERA: PROTECCIÓN DE DATOS PERSONALES</div>
        <p>EL CLIENTE autoriza expresamente a LA EMPRESA, conforme a la Ley N° 29733 – Ley de Protección de Datos Personales y su reglamento, a recopilar, almacenar, procesar y utilizar sus datos personales proporcionados en el presente contrato, con la finalidad de gestionar el préstamo, realizar comunicaciones relacionadas al contrato, ejecutar acciones de cobranza, y cumplir con obligaciones legales y regulatorias.</p>
    </div>

    <!-- Firmas -->
    <div class="signatures">
        <table class="signatures-table">
            <tr>
                <td>
                    <div class="signature-block">
                        {{-- <img src="{{ asset('img/pdf/firma.png') }}" alt="Firma" style="width: 100px; height: auto; margin-bottom:-50px;"> --}}
                        <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('img/pdf/firma.png'))) }}" alt="Firma" style="width: 100px; height: auto; margin-bottom:-50px;">
                        <div class="signature-line"></div>
                        <div class="signature-info">
                            <strong>EL MUTUANTE</strong><br>
                            GRUPO SANTIAGO PERU S.A.C.<br>
                            R.U.C.: 20611373181<br>
                            YOSELIN ESTRELLA ESTEBAN MANTILLA<br>
                            D.N.I.: 76553582<br>
                            GERENTE GENERAL
                        </div>
                    </div>
                </td>
                <td>
                    <div class="signature-block">
                        <div class="signature-line" style="margin-top: 90px;"></div>
                        <div class="signature-info">
                            <strong>EL MUTUATARIO</strong><br>
                            {{ $nombreCompleto }}<br>
                            D.N.I./C.E.: {{ $documento }}
                        </div>
                    </div>
                </td>
                @if($tieneAval)
                @php
                    // Obtener el teléfono del aval desde la tabla telefonos
                    $telefonoAval = $avalPersona
                        ? $avalPersona->telefonos()->where('tipo_telefono', 'celular')->first()
                        : null;
                    $celularAval = $telefonoAval ? $telefonoAval->numero : '_______________';
                @endphp
                <td>
                    <div class="signature-block">
                        <div class="signature-line"style="margin-top: 150px;"></div>
                        <div class="signature-info">
                            <strong>EL FIADOR</strong><br>
                            {{ optional($avalPersona)->nombres ? strtoupper(trim((optional($avalPersona)->nombres ?? '') . ' ' . (optional($avalPersona)->ape_pat ?? '') . ' ' . (optional($avalPersona)->ape_mat ?? ''))) : '_______________' }}<br>
                            D.N.I.: {{ optional($avalPersona)->documento ?? '_______________' }}<br>
                            TELEF: {{ $celularAval }}<br>
                            DIRECCION: {{ optional(optional($avalPersona)->direccion)->direccion ?? '_______________' }}
                        </div>
                    </div>
                </td>
                @endif
            </tr>
        </table>
    </div>

    <!-- Salto de página para el Anexo -->
    <div class="page-break"></div>

    <!-- ANEXO 1: CRONOGRAMA DE PAGOS -->
    @if(isset($prestamo) && $prestamo->cuotas && $prestamo->cuotas->count() > 0)
    <div class="title">ANEXO Nro. 1: CRONOGRAMA DE PAGOS</div>

    <!-- Resumen del Contrato -->
    <div class="resumen-box">
        <table class="resumen-table">
            <tr>
                <td>
                    <div class="resumen-item">
                        <span class="resumen-label">CLIENTE:</span>
                        <span class="resumen-value">{{ $nombreCompleto }}</span>
                    </div>
                    <div class="resumen-item">
                        <span class="resumen-label">N° CONTRATO:</span>
                        <span class="resumen-value">{{ isset($prestamo) ? str_pad($prestamo->id, 4, '0', STR_PAD_LEFT) . '-' . date('Y', strtotime($prestamo->created_at)) : '0001-' . date('Y') }}</span>
                    </div>
                    <div class="resumen-item">
                        <span class="resumen-label">PRÉSTAMO (PRINCIPAL):</span>
                        <span class="resumen-value">S/ {{ number_format($prestamo->cantidad_solicitada, 2) }}</span>
                    </div>
                    <div class="resumen-item">
                        <span class="resumen-label">TIPO DE PRÉSTAMO:</span>
                        <span class="resumen-value">{{ ucfirst($prestamo->frecuencia_pago ?? 'Semanal') }}</span>
                    </div>
                    <div class="resumen-item">
                        <span class="resumen-label">PERIODO:</span>
                        <span class="resumen-value">{{ $prestamo->cuotas->count() }} {{ $frecuenciaTexto }}</span>
                    </div>
                </td>
                <td>
                    <div class="resumen-item">
                        <span class="resumen-label">FECHA:</span>
                        <span class="resumen-value">{{ isset($prestamo->fecha_atencion) ? \Carbon\Carbon::parse($prestamo->fecha_atencion)->format('d/m/Y') : \Carbon\Carbon::parse($prestamo->created_at)->format('d/m/Y') }}</span>
                    </div>
                    <div class="resumen-item">
                        <span class="resumen-label">COMISIÓN {{ strtoupper($frecuencia) }}:</span>
                        <span class="resumen-value">{{ number_format($comisionSemanal, 2) }}% incl. IGV</span>
                    </div>
                    <div class="resumen-item">
                        <span class="resumen-label">INTERÉS {{ strtoupper($frecuencia) }}:</span>
                        <span class="resumen-value">{{ number_format($interesSemanal, 2) }}% incl. IGV</span>
                    </div>
                    <div class="resumen-item">
                        <span class="resumen-label">TASA EFECTIVA {{ strtoupper($frecuencia) }}:</span>
                        <span class="resumen-value">{{ number_format($tasaSemanal, 2) }}% incl. IGV</span>
                    </div>
                    <div class="resumen-item">
                        <span class="resumen-label">Nro de Cuotas:</span>
                        <span class="resumen-value">{{ $prestamo->cuotas->count() }}</span>
                    </div>
                    <div class="resumen-item">
                        <span class="resumen-label">VALOR CUOTA:</span>
                        <span class="resumen-value">S/ {{ number_format($montoCuota, 2) }}</span>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Tabla de Amortización -->
    @php
        $saldoCapital = $prestamo->cantidad_solicitada;
    @endphp

    <table class="cronograma-table">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Nro</th>
                <th>CUOTA</th>
                <th>PAGO DE CAPITAL</th>
                <th>INTERES</th>
                <th>COMISIÓN</th>
                <th>IGV</th>
                <th>SALDO CAPITAL</th>
            </tr>
        </thead>
        <tbody>
            @foreach($prestamo->cuotas as $index => $cuota)
            @php
                // Definir las reglas de comisión según el plazo (igual que en deudas-general.blade.php)
                $reglasInteres = [
                    8 => ['tasa_maxima' => 1.38, 'comision' => 3.29],
                    12 => ['tasa_maxima' => 1.38, 'comision' => 4.73],
                    15 => ['tasa_maxima' => 1.38, 'comision' => 4.18],
                    18 => ['tasa_maxima' => 1.38, 'comision' => 3.36],
                    20 => ['tasa_maxima' => 1.38, 'comision' => 2.84]
                ];

                // Obtener la comisión según el plazo del préstamo
                $plazo = $prestamo->plazo ?? 12;
                $comisionPorcentaje = isset($reglasInteres[$plazo]) ? $reglasInteres[$plazo]['comision'] : 3.29;

                // Pago de capital (el campo en la tabla se llama pago_capital)
                $pagoCapital = $cuota->pago_capital ?? 0;

                // Calcular comisión: (saldo_capital * comision%) / 1.18 (sin IGV)
                $comisionCalculada = round(($saldoCapital * ($comisionPorcentaje / 100)) / 1.18, 2);

                // Interés CON IGV incluido
                $interesConIgv = $cuota->interes ?? 0;

                // Interés SIN IGV (base gravada)
                $interesBaseGravado = round($interesConIgv / 1.18, 2);

                // IGV sobre el interés
                $igv = $cuota->igv ?? 0;

                // Otros gastos
                $otrosGastos = $cuota->gas ?? 0;

                // Calcular saldo capital restante DESPUÉS de esta cuota
                $saldoCapital = max(0, $saldoCapital - $pagoCapital);

                // Cuota total (monto almacenado en la cuota)
                $cuotaTotal = $cuota->monto;
            @endphp
            <tr>
                <td>{{ $cuota->fecha_pago ? \Carbon\Carbon::parse($cuota->fecha_pago)->format('d/m/Y') : '' }}</td>
                <td>{{ $cuota->numero ?? ($index + 1) }}</td>
                <td>S/ {{ number_format($cuotaTotal, 2) }}</td>
                <td>S/ {{ number_format($pagoCapital, 2) }}</td>
                <td>S/ {{ number_format($interesBaseGravado, 2) }}</td>
                <td>S/ {{ number_format($comisionCalculada, 2) }}</td>
                <td>S/ {{ number_format($igv, 2) }}</td>
                <td>S/ {{ number_format($saldoCapital, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="page-break"></div>

    <!-- ANEXO N° 2: Información para el Pago de Cuotas -->
    <div class="title">ANEXO N.° 2: Información para el Pago de Cuotas</div>

    <div style="margin: 30px 0; font-size: 11pt; line-height: 1.8;">
        <p style="margin-bottom: 20px;"><strong>Razón Social:</strong> GRUPO SANTIAGO PERÚ S.A.C. <strong>R.U.C.:</strong> 20611373181</p>

        <p style="margin-bottom: 15px; font-weight: bold; font-size: 12pt;">Datos Bancarios para Depósitos en Moneda Nacional (Soles):</p>

        <ul style="list-style: none; padding-left: 20px; margin-bottom: 30px;">
            <li style="margin-bottom: 8px;"><strong>Banco:</strong> Banco de Crédito del Perú (BCP)</li>
            <li style="margin-bottom: 8px;"><strong>Número de Cuenta:</strong> 191-72907160-93</li>
            <li style="margin-bottom: 8px;"><strong>Código de Cuenta Interbancaria (CCI):</strong> 00219100729071609350</li>
            <li style="margin-bottom: 8px;"><strong>Titular de la Cuenta:</strong> GRUPO SANTIAGO PERÚ S.A.C.</li>
        </ul>

        <p style="font-style: italic; font-size: 10pt; color: #333; margin-top: 30px;">
            📌 Se <em>recomienda verificar los datos antes de realizar cualquier transacción. Conservar el comprobante de pago para fines de validación.</em>
        </p>
    </div>

    <div class="page-break"></div>

    <div class="anexo-pagare">
        <div class="pagare-header">
            <div>
                <div class="title">PAGARÉ</div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 18px; font-weight: bold;">Créditos Santiago</div>
                <div class="pagare-subtitle">Emprendamos Juntos</div>
            </div>
        </div>
        
        <!-- Tabla de información del pagaré -->
        <table class="pagare-table">
            <thead>
                <tr>
                    <th>NÚMERO DE PAGARÉ</th>
                    <th>FECHA DE EMISIÓN</th>
                    <th>FECHA DE VENCIMIENTO</th>
                    <th>MONEDA</th>
                    <th>IMPORTE</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    @php
                        $numeroPagare = isset($prestamo) ? 'PG-' . str_pad($prestamo->id, 6, '0', STR_PAD_LEFT) : '';
                        $fechaEmision = isset($prestamo) && $prestamo->created_at ? \Carbon\Carbon::parse($prestamo->created_at)->format('d/m/Y') : '';
                        $ultimaCuota = isset($prestamo) && $prestamo->cuotas && $prestamo->cuotas->last() ? $prestamo->cuotas->last() : null;
                        $fechaVencimiento = $ultimaCuota && $ultimaCuota->fecha_pago ? \Carbon\Carbon::parse($ultimaCuota->fecha_pago)->format('d/m/Y') : '';

                        // Calcular el monto total del pagaré sumando todas las cuotas
                        // Similar a ComprobantesController: suma de montos de cuotas que ya incl.n capital + interés + comisión + IGV
                        $montoTotal = isset($prestamo) && $prestamo->cuotas ? $prestamo->cuotas->sum('monto') : 0;

                        // Convertir el monto total a letras
                        $montoTotalLetras = numeroALetras($montoTotal, 'SOLES');
                    @endphp
                    <td><strong>{{ $numeroPagare }}</strong></td>
                    <td>{{ $fechaEmision }}</td>
                    <td>{{ $fechaVencimiento }}</td>
                    <td>SOLES (S/)</td>
                    <td><strong>S/ {{ number_format($montoTotal, 2) }}</strong></td>
                </tr>
            </tbody>
        </table>
        
        <!-- Contenido del pagaré -->
        <div class="pagare-section">
            <p>Por el presente documento Pagaré/pagaremos y me/nos obligo/obligamos a pago en forma incondicional y solidaria a la orden de <strong>GRUPO SANTIAGO PERU S.A.C.</strong> con RUC <strong>20611373181</strong> en adelante <strong>CRÉDITOS SANTIAGO</strong>, o a quien se hubiera endosado este pagaré, en sus oficinas de esta ciudad o donde se presente su cobro, indistintamente, la cantidad de <strong>S/ {{ number_format($montoTotal, 2) }}</strong> ({{ $montoTotalLetras }}) que corresponde al importe total del préstamo y cuotas descritas en el presente Pagaré y que corresponde la suma adeudada a <strong>CRÉDITOS SANTIAGO</strong>, conforme al Artículo 10 de la Ley N° 27287 ley de títulos valores, cantidad que es de mi/nuestro cargo, la misma que será cancelada y pagada en la fecha de vencimiento y en la moneda expresada en este Pagaré, quedando establecido que si no efectuase/efectuásemos el pago del monto total del presente Pagaré, abonaré/abonaremos los intereses a las tasas más altas señaladas en el tarifario de <strong>CRÉDITOS SANTIAGO</strong> vigente a la fecha de vencimiento.</p>
            
            <p><strong>CRÉDITOS SANTIAGO</strong> queda autorizado para prorrogar este Pagaré cuando así lo estime conveniente, ya sean estas prórrogas por el importe total o por cantidad menor, sin requerir para dichos efectos mi/nuestra suscripción.</p>
            
            <p>Autorizo/Autorizamos a <strong>CRÉDITOS SANTIAGO</strong> expresa e irrevocablemente, para que, a su vencimiento o fecha posterior, cargue o compense la cantidad necesaria que exista en cualquiera de las cuentas, depósitos, bienes o valores que en cualquier moneda, mantengo/mantenemos, en forma individual o mancomunada, con terceros en <strong>CRÉDITOS SANTIAGO</strong>, o en cualquiera de sus filiales sucursales de Perú y del exterior, conforme al artículo 132 numeral 11 de la Ley 26702.</p>
            
            <p>De conformidad con el artículo 52 de la ley N° 27287, queda expresamente establecido que el presente Pagaré no requiere ser protestado; sin embargo, el tenedor queda facultado a protestarlo por la falta de pago si así lo estimare conveniente, asumiendo los costos de tal protesto, pudiendo trasladar dicho costo al/los EMITENTE/S, según sea el caso.</p>
            
            <p>Las obligaciones contenidas en este Pagaré no se extinguirán aun cuando por culpa del tenedor se hubiese perjudicado este Pagaré, constituyendo el presente acuerdo pacto en contrario a lo dispuesto por el Artículo 1233 del código civil peruano.</p>
            
            <p>El/los EMITENTE/S manifiestan que su domicilio son los indicados líneas abajo, donde se enviarán los avisos y las notificaciones del caso, diligencias notariales, judiciales y demás que fuesen necesarias para los efectos del pago. Cualquier cambio de domicilio solo será validado si el mismo es notificado a <strong>CRÉDITOS SANTIAGO</strong> mediante notificación escrita con (15) días de anticipación, siempre que el nuevo domicilio esté ubicado dentro del radio urbano de la misma ciudad.</p>
            
            <p>El/los EMITENTE/S se someten a la competencia y jurisdicción de los jueces y tribunales del distrito judicial del cercado de Lima o la ciudad en la que se suscribe el presente Pagaré, a decisión de <strong>CRÉDITOS SANTIAGO</strong>, renunciando al fuero de su/sus domicilio/s.</p>
        </div>
        
        <!-- Sección del Fiador Solidario -->
        <div class="title">DEL FIADOR SOLIDARIO (AVAL)</div>
        <div class="pagare-section">
            <p>Por el presente documento me(nos) constituyo(imos) en fiador solidario del emitente y dejo(amos) constancia que renuncio(amos) expresamente al beneficio de excusión del artículo 1879 del código civil, por todas las obligaciones contraídas por el emitente frente a <strong>CRÉDITOS SANTIAGO</strong> incl.ndo aquellas expresadas en este pagaré. Dejo(amos) constancia que esta fianza se constituye por plazo indeterminado y estará vigente hasta que sean canceladas totalmente la obligación a las que sirve de garantía. Acepto(amos) y me(nos) sujeto(amos) a toda variación que <strong>CRÉDITOS SANTIAGO</strong> efectúe en el pagaré, ya sea en cuanto a los plazos de vencimiento parcial, el monto o las tasas de interés aplicable, por lo que <strong>CRÉDITOS SANTIAGO</strong> no está obligado a comunicarnos previamente las mismas. Si al vencimiento de este pagaré el monto adeudado no fuese pagado, autorizo(amos) expresa e irrevocablemente a <strong>CRÉDITOS SANTIAGO</strong> para que cargue en cualquiera de nuestras cuentas mis(nuestras) bancarias o depósitos que tuviese(mos) establecidas en cualquier institución bancaria, la suma que resulten de mi(nuestro) cargo incl.ndo capital, interés, comisiones, penalidades, seguro y gastos. Fijo(amos) mi(nuestros) domicilio(s) en el(los) lugar(es) que se indican al pie de mi(nuestras) firma(s), donde se dirigirán todas las comunicaciones y/o notificaciones derivadas del pagaré. Para la ejecución del pagaré o de la fianza que otorgo(amos), renuncio(amos) a la competencia de los jueces de mi(nuestros) domicilio(s) y me(nos) someto(emos) a la competencia de los jueces del lugar que indique <strong>CRÉDITOS SANTIAGO</strong>.</p>
        </div>
        
        <!-- Sección de Firmas -->
        <div class="pagare-firma-section">
            <table class="pagare-firma-table">
                <tr>
                    <td>
                        <div class="pagare-firma-block">
                            <div class="pagare-firma-line"></div>
                            <div class="pagare-firma-label"><strong>EMITENTE</strong></div>
                            <div class="pagare-firma-label"><strong>FIRMA:</strong></div>
                            <div class="pagare-firma-label"><strong>NOMBRES Y APELLIDOS:</strong> {{ $nombreCompleto }}</div>
                            <div class="pagare-firma-label"><strong>DNI:</strong> {{ $documento }}</div>
                            <div class="pagare-firma-label"><strong>DOMICILIO:</strong> {{ $direccion }}</div>
                        </div>
                    </td>
                    <td>
                        <div class="pagare-firma-block">
                            <div class="pagare-firma-line"></div>
                            <div class="pagare-firma-label"><strong>AVAL SOLIDARIO</strong></div>
                            <div class="pagare-firma-label"><strong>FIRMA:</strong></div>
                            <div class="pagare-firma-label"><strong>NOMBRES Y APELLIDOS:</strong> {{ optional($avalPersona)->nombres ? strtoupper(trim((optional($avalPersona)->nombres ?? '') . ' ' . (optional($avalPersona)->ape_pat ?? '') . ' ' . (optional($avalPersona)->ape_mat ?? ''))) : '' }}</div>
                            <div class="pagare-firma-label"><strong>DNI:</strong> {{ optional($avalPersona)->documento ?? '' }}</div>
                            <div class="pagare-firma-label"><strong>DOMICILIO:</strong> {{ optional(optional($avalPersona)->direccion)->direccion ?? '' }}</div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="footer">
        <span class="page-number"></span>
    </div>

</body>
</html>