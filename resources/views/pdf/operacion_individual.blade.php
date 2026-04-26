<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Constancia de Pago</title>
    
    <style>
        *{
            font-weight: bold;
        }
        body {
            font-family: Arial, sans-serif;
            color: #333;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .container {
            width: 100mm; /* Ancho del ticket */
            background: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
        }

        .header {
            color: white;
            padding: 20px 20px 40px;
            text-align: center;
            position: relative;
        }
        .head {
            width: 100mm;
            height: auto;
            object-fit: cover;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
        }

        .header p {
            margin: 5px 0 0;
            color: #87CEEB;
            font-size: 14px;
        }

        .wave {
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 40px;
            background-size: cover;
        }

        .content {
            padding: 20px;
            margin-top: -80px;
        }

        .subheader {
            color: #004085;
            margin-bottom: 0px;
        }

        .subheader h2 {
            font-size: 18px;
            margin: 0 0 10px;
            text-align: center;
        }

        .subheader p {
            margin: 5px 0;
            font-size: 14px;
            color: #666;
            text-align: center;
        }

        .details {
            padding: 10px;
            margin: 20px 0;
            background: #f2f2f2;
            text-align: center;
        }

        .row {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            font-size: 14px;
        }

        .bold {
            font-weight: bold;
            color: #004085;
        }

        .amount {
            text-align: center;
            margin-top: -10px;
        }

        .amount p {
            margin: 5px 0;
        }

        .amount p:first-child {
            font-size: 16px;
            color: #1fbbed;
        }

        .amount p:last-child {
            font-size: 32px;
            font-weight: bold;
            color: #004085;
        }

        .footer {
            background-color: #004085;
            color: white;
            text-align: center;
            padding: 15px;
            font-size: 12px;
            margin-top: -20px;
        }

        .footer p {
            margin-top: 5px 0;
        }

        .message {
            text-align: center;
            font-size: 9px;
            color: #666;
            margin: 5px 0;
        }
        .red-text {
            color: #1a4d7a;
        }
        .gray-text {
            color:rgb(102, 102, 102);
        }
        .black-text {
            color: black;
        }
        .cod-text{
            color: black;
            font-size: 12pt
        }
        .us-text{
            color: #1a4d7a;
            font-size: 12pt
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img class="head" src="{{ asset('img/pdf/head.png') }}" alt="Logo" />
            <div class="wave"></div>
        </div>

        <div class="content">
        <div class="subheader">
            <p><span class="gray-text">Nro de Operación:</span> <span class="black-text">{{ $operacionGeneral->id }}</span></p>
            <h2><b>CONSTANCIA DE PAGO</b></h2>
            <p><span class="red-text">Fecha:</span> <span class="black-text">{{ \Carbon\Carbon::parse($operacionGeneral->fecha)->format('d/m/Y') }}</span></p>
            <p><span class="red-text">Hora:</span> <span class="black-text">{{ \Carbon\Carbon::parse($operacionGeneral->fecha)->format('H:i:s A') }}</span></p>
        </div>


        <div class="details">
            <div class="row">
                <div><span class="bold">Cliente:</span> {{ $operacionGeneral->cliente->persona->nombres }} {{ $operacionGeneral->cliente->persona->ape_pat }} {{ $operacionGeneral->cliente->persona->ape_mat ?? 'No disponible' }}</div>
                <div><span class="bold">DNI:</span> {{ $operacionGeneral->cliente->persona->documento ?? 'N/A' }}</div>
            </div>
            <hr style="background-color: rgb(196, 196, 196);border: 0;width: 100%;height: 1px;">
            <div class="row">
                <div><span class="bold">Cuota:</span> S/ {{ number_format($operacionGeneral->abono, 2) }}</div>
                <div><span class="bold">Penalidad:</span> S/ 0.00</div>
            </div>
            <div class="row">
                <div><span class="bold">G.A.:</span> S/ 0.00</div>
            </div>
        </div>

        <div class="amount">
            <p>¡Pago exitoso!</p>
            <p>S/ {{ number_format($operacionGeneral->abono, 2) }}</p>
        </div>
        <div class="" style="text-align: center;">
            <hr style="background-color: rgb(196, 196, 196);border: 0;width: 100%;height: 1px;">
            <p><span class="us-text">Usuario:</span> <span class="cod-text">{{ optional($operacionGeneral->user)->codigo ?? 'N/A' }}</span></p>
        </div>
        <div class="message">
            Mantenga al día en tus pagos para obtener mejores ofertas en créditos personales.
        </div>
    </div>

        <div class="footer">
            <p>www.gruposantiago.pe</p>
            <p>987 730 985</p>
        </div>
    </div>
</body>
</html>
