<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carta de No Adeudo</title>
    <style>
        @page {
            margin: 0;
            size: A4 portrait;
        }

        html {
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 2cm;
            background-image: url('{{ public_path('img/pdf/bg.png') }}');
            background-size: 100% 100%;
            background-position: top left;
            background-repeat: no-repeat;
            min-height: 29.7cm;
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .logo {
            width: 150px;
            height: auto;
            margin-bottom: 20px;
        }
        
        .company-info {
            font-size: 10px;
            color: #666;
            margin-bottom: 30px;
        }
        
        .date {
            text-align: right;
            margin-bottom: 40px;
            font-size: 12px;
            margin-top: 100px;
        }
        
        .title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin: 40px 0;
            text-decoration: underline;
        }
        
        .addressee {
            margin-bottom: 30px;
            font-weight: bold;
        }
        
        .content {
            text-align: justify;
            margin-bottom: 50px;
            line-height: 1.8;
        }
        
        .customer-info {
            font-weight: bold;
        }
        
        .signature-section {
            margin-top: 80px;
            text-align: center;
        }
        
        .signature-line {
            border-top: 1px solid #333;
            width: 250px;
            margin: 0 auto 10px;
        }
        
        .signature-name {
            font-weight: bold;
            font-size: 12px;
        }
        
        .signature-title {
            font-size: 11px;
            margin-bottom: 3px;
        }
        
        .company-name {
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 3px;
        }
        
        .ruc {
            font-size: 11px;
        }
        
        .footer {
            position: fixed;
            bottom: 1cm;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8px;
            color: #888;
        }
    </style>
</head>
<body>
    <!-- Fecha -->
    <div class="date">
        Lima, {{ $fecha }}
    </div>

    <!-- Título -->
    <div class="title">
        CONSTANCIA DE NO ADEUDO
    </div>

    <!-- Destinatario -->
    <div class="addressee">
        Señor (Sra.):<br>
        A QUIEN CORRESPONDA
        Presente.-
    </div>

    <!-- Contenido principal -->
    <div class="content">
        <p>Por medio de la presente informamos que nuestro cliente 
        <span class="customer-info">{{ $cliente_genero }} {{ $cliente_nombre_completo }}</span> 
        con <strong>DNI N° {{ $cliente_dni }}</strong> a la fecha 
        <strong>NO REGISTRA DEUDA</strong> con nuestra institución.</p>
        
        <p>Se expide la presente a solicitud del interesado y para los fines que estime conveniente.</p>
        
        @if(isset($prestamo_info))
        <p style="font-size: 10px; color: #666; margin-top: 30px;">
            <em>Referencia: Préstamo N° {{ $prestamo_info['id'] }} - 
            Cancelado el {{ $prestamo_info['fecha_cancelacion'] }} - 
            Monto: S/. {{ number_format($prestamo_info['monto'], 2) }}</em>
        </p>
        @endif
    </div>

    <!-- Firma -->
    <div class="signature-section">
        <p>Atentamente,</p>
        <br>
        <img src="{{ public_path('img/pdf/firma.png') }}" alt="Firma" style="width: 100px; height: auto; margin: 0 auto 10px;">
        <br>
        <div class="signature-line"></div>
        
        <div class="signature-name">
            {{ $firmante_nombre ?? 'YOSELIN ESTEBAN MANTILLA' }}
        </div>
        
        <div class="signature-title">
            {{ $firmante_cargo ?? 'Representante Legal' }}
        </div>
        
        <div class="company-name">
            {{ $company_name ?? 'Grupo Santiago Peru S.A.C.' }}
        </div>
        
        <div class="ruc">
            R.U.C. Nº {{ $company_ruc ?? '20611373181' }}
        </div>
    </div>
</body>
</html>