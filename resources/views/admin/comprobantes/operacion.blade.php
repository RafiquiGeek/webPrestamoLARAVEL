<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Pago</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            border: 1px solid #ddd;
            padding: 20px;
            background-color: #fff;
        }
        .header {
            text-align: center;
            background-color: #004A7C;
            color: white;
            padding: 15px;
            font-size: 1.5em;
            font-weight: bold;
        }
        .subheader {
            text-align: center;
            font-size: 1em;
            color: #666;
            margin-bottom: 20px;
        }
        .content {
            padding: 10px 0;
        }
        .content .row {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
        }
        .row span {
            font-weight: bold;
            color: #004A7C;
        }
        .amount {
            text-align: center;
            font-size: 1.5em;
            color: #004A7C;
            font-weight: bold;
            margin-top: 10px;
        }
        .footer {
            text-align: center;
            font-size: 0.9em;
            color: #666;
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">Créditos Santiago</div>
        <div class="subheader">¡Para emprendedores como tú!</div>
        
        <h3 class="text-center">Constancia de Pago</h3>
        <div class="content">
            <div class="row"><span>Fecha:</span> {{ \Carbon\Carbon::parse($operacion->fecha)->format('d/m/Y') }}</div>
            <div class="row"><span>Hora:</span> {{ \Carbon\Carbon::parse($operacion->fecha)->format('H:i:s') }}</div>
            <div class="row"><span>Nro de Operación:</span> GS-{{ str_pad($operacion->id, 4, '0', STR_PAD_LEFT) }}</div>
            
            <hr>
            
            <div class="row"><span>Cliente:</span> {{ $operacion->cliente->nombre_completo ?? 'No disponible' }}</div>
            <div class="row"><span>DNI:</span> {{ $operacion->cliente->dni ?? 'No disponible' }}</div>

            <hr>
            
            <div class="row"><span>Cuota:</span> S/ {{ number_format($operacion->abono, 2) }}</div>
            <div class="row"><span>Penalidad:</span> S/ {{ number_format($operacion->mora ?? 0, 2) }}</div>
            <div class="row"><span>G.A.:</span> S/ {{ number_format($operacion->gas ?? 0, 2) }}</div>
            
            <div class="amount">¡Pago exitoso!<br>S/ {{ number_format($operacion->abono, 2) }}</div>
            
            <div class="row"><span>Usuario:</span> {{ auth()->user()->name ?? 'ADM' }}</div>
        </div>

        <div class="footer">
            Mantente al día en tus pagos para obtener mejores ofertas en créditos personales.<br>
            <strong>Contacto:</strong> 987 730 985 | www.gruposantiago.pe
        </div>
    </div>
</body>
</html>
