<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    
    <link rel="stylesheet" href="{{ public_path('css/skeleton/skeleton.css') }}"> 
    <link rel="stylesheet" href="{{ public_path('css/skeleton/normalize.css') }}">

    {{-- <link rel="stylesheet" href="{{ asset('css/skeleton/skeleton.css') }}"> 
    <link rel="stylesheet" href="{{ asset('css/skeleton/normalize.css') }}"> --}}
    
    <title>Document</title>

    <style>
        @import url('{{ public_path('fonts/Roboto-Regular.ttf') }}');
        *{
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
        }
        .top-logo-container{
            display: flex;
            justify-content: center;
        }
        .top-logo{
            width: 100%;
        }
        .header-container{
            height: 60px;
            margin: 0 30px 0 30px;
        }
        .input-container{
            height: 26px;
            margin: 0 30px 0 30px;
        }
        .table-container{
            margin: 0 30px 0 30px;
        }
        .condiciones-container{
            margin: 0 30px 0 30px;
            position: absolute;
            bottom: 120;
        }
        .foot-logo-container{
            display: flex;
            justify-content: center;
            position: absolute;
            bottom: 0;
        }
        .foot-logo{
            width: 100%;
        }
        .caja{
            display: flex;
            flex-wrap: wrap;
            align-content: center;
            justify-content: center;
            /* border: 1px black solid; */
        }
        .form-input{
            display: flex;
            flex-wrap: wrap;
            align-content: center;
            justify-content: flex-start;
            border: 1px rgba(0, 0, 0, 0.27) solid;
            border-radius: 5px;
            font-size: 12px;
            font-weight: 400;
        }
        .input-value{
            margin-top: 2px;
            margin-left: 10px
        }
        .input-value span{
            color: #004070;
        }
        .all-width{
            width: 100%;
        }
        .all-height{
            height: 98%;
        }

        .mr-0{
            margin-right: 0 !important;
        }
        .mr-1{
            margin-right: 0.25rem !important;
        }
        .mr-2{
            margin-right: 0.5rem !important;
        }
        .mr-3{
            margin-right: 1rem !important;
        }
        .mr-4{
            margin-right: 1.5rem !important;
        }
        .mr-5{
            margin-right: 3rem !important;
        }
        .ml-0{
            margin-left: 0 !important;
        }
        .ml-1{
            margin-left: 0.25rem !important;
        }
        .ml-2{
            margin-left: 0.5rem !important;
        }
        .ml-3{
            margin-left: 1rem !important;
        }
        .ml-4{
            margin-left: 1.5rem !important;
        }
        .ml-5{
            margin-left: 2.2rem !important;
        }
        .mt-0{
            margin-top: 0 !important; 
        }
        .mt-1{
            margin-top: 0.25rem !important;
        }
        .mt-2{
            margin-top: 0.5rem !important;
        }
        .mt-3{
            margin-top: 1rem !important;
        }
        .mt-4{
            margin-top: 1.5rem !important;
        }
        .mt-5{
            margin-top: 2.2rem !important;
        }
        .mb-0{
            margin-bottom: 0 !important;
        }
        .mb-1{
            margin-bottom: 0.25rem !important;
        }
        .mb-2{
            margin-bottom: 0.5rem !important;
        }
        .mb-3{
            margin-bottom: 1rem !important;
        }
        .mb-4{
            margin-bottom: 1.5rem !important;
        }
        .jc-f-start{
            justify-content: flex-start !important;
        }
        .txt-center{
            text-align: center !important;
        }

        /*** Variables unicas ***/

        .primera-caja{
            color: white;
            background-color: #004070;
            font-size: 30px;
            font-weight: bold;
            text-align: center;
            border-bottom-left-radius: 10px;

        }
        .segunda-caja{
            color: white;
            font-size: 15px;
            font-weight: 400;
            text-align: center;
        }
        .segunda-caja-1, .segunda-caja-2{
            height: 49%;
        }
        .segunda-caja-1{
            color: white;
            background-color: #004070;
        }
        .segunda-caja-2{
            color: black;
            background-color: #d2d3d5;
        }
        .tercera-caja{
            color: black;
            font-size: 10px;
            font-weight: bold;
        }
        .tercera-caja-1, .tercera-caja-2{
            height: 49%;
        }
        .cuarta-caja{
            color: black;
            font-size: 10px;
            font-weight: bold;
        }
        .cuarta-caja-1, .cuarta-caja-2{
            height: 49%;
        }
        .quinta-caja{
            color: black;
            background-color: #d2d3d5;
            font-size: 27px;
            font-weight: bold;
            text-align: center;
        }
        .sexta-caja{
            color: black;
            font-weight: 400;
        }
        .sexta-caja span{
            color: #004070;
        }
        .sexta-caja .input-value{
            margin-top: 4px;
        }
        .sexta-caja-1, .sexta-caja-2{
            height: 45%;
            width: 185px !important;
            font-size: 10px;
        }
        .sexta-caja-1{

        }
        .sexta-caja-2{

        }
        .cuadro-dia{
            display: flex;
            flex-wrap: wrap;
            align-content: center;
            justify-content: center;
            text-align: center;
            border: 1px black solid;
            border-radius: 15px 15px 0 0;
            color: #d2d3d5;
            background-color: #004070;
            height: 2em;
            width: 22.5% !important;
            font-size: 23px;
            font-weight: bold;
        }
        .table-header{
            background-color: #004070;
            color: #d2d3d5;
            border: 0.1px white solid;
            font-size: 12px;
        }
        .table-body{
            font-size: 10px;
        }
        .monto-table{
            background-color: #004070;
            color: #d2d3d5;
        }
        .titulo-condiciones{
            color: #004070;
            font-weight: bold;
        }
        .condiciones-container p{
            font-size: 8px;
        }
        .condiciones-container b{
            color: #004070;
        }
    </style>

</head>
<body>

    <div class="top-logo-container">
        <img class="top-logo" src="{{ public_path('img/pdf/header.png') }}">
        {{-- <img class="top-logo" src="{{ asset('img/pdf/header.png') }}"> --}}
    </div>
    <div class="row header-container">
        <div class="one columns caja all-height primera-caja">
            5
        </div>
        <div class="two columns all-height segunda-caja" >
            <div class="caja segunda-caja-1">AS017</div>
            <div class="caja segunda-caja-2">DO15</div>
        </div>
        <div class="two columns all-height tercera-caja">
            <div class="caja tercera-caja-1 jc-f-start">NUEVO</div>
            <div class="caja tercera-caja-2 jc-f-start">RENOV</div>
        </div>
        <div class="two columns all-height cuarta-caja">
            <div class="caja cuarta-caja-1 jc-f-start">EFECTIVO</div>
            <div class="caja cuarta-caja-2 jc-f-start">TRANSF. BANC</div>
        </div>
        <div class="two columns caja all-height quinta-caja">
            LC023
        </div>
        <div class="three columns all-height sexta-caja">
            <div class="form-input sexta-caja-1 jc-f-start mb-1">
                <div class="input-value">
                    F. Entrega: <span>20/12/2023</span>
                </div>
            </div>
            <div class="form-input sexta-caja-2 jc-f-start mt-1">
                <div class="input-value">
                    F. Vencimiento: <span>20/12/2023</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row input-container mt-3">
        <div class="nine columns form-input all-height">
            <div class="input-value">
                Titular: <span>Julio Cesar Espinoza Trujillo</span>
            </div>
        </div>
        <div class="three columns form-input all-height">
            <div class="input-value">
                DNI: <span>74863124</span>
            </div>
        </div>
    </div>
    <div class="row input-container mt-3">
        <div class="u-full-width form-input all-height">
            <div class="input-value">
                Direccion: <span>Mz H 23 Lt 10</span>
            </div>
        </div>
    </div>
    <div class="row input-container mt-3">
        <div class="nine columns form-input all-height">
            <div class="input-value">
                Aval: <span>--</span>
            </div>
        </div>
        <div class="three columns form-input all-height">
            <div class="input-value">
                DNI: <span>--</span>
            </div>
        </div>
    </div>
    <div class="row input-container mt-3">
        <div class="u-full-width form-input all-height">
            <div class="input-value">
                Direccion: <span>--</span>
            </div>
        </div>
    </div>
    <div class="row input-container mt-3">
        <div class="one columns form-input all-height">
            <div class="input-value">
                Pro
            </div>
        </div>
        <div class="two columns form-input all-height">
            <div class="input-value">
                Scotiabank
            </div>
        </div>
        <div class="three columns form-input all-height">
            
        </div>
        <div class="three columns form-input all-height">
            
        </div>
        <div class="three columns cuadro-dia">
            Martes
        </div>
    </div>
    <div class="table-container mt-4">
        <table class="u-full-width table-cuotas mb-2">
            <thead class="table-header">
                <th class="txt-center">IT</th>
                <th class="txt-center">FECHA DE PAGO</th>
                <th class="txt-center">CAPITAL</th>
                <th class="txt-center">MONTO CUOTA</th>
                <th class="txt-center">BANCO</th>
                <th class="txt-center">N° OP.</th>
                <th class="txt-center">OBSERVACION</th>
            </thead>
            <tbody class="table-body">
                @for($i = 1; $i < 19; $i++)
                    <tr>
                        <td class="txt-center">{{$i}}</td>
                        <td class="txt-center">20/12/2023</td>
                        <td class="txt-center">S/ 1860</td>
                        <td class="monto-table txt-center">S/ 145</td>
                        <td class="txt-center">Scotiabank</td>
                        <td class="txt-center">0045125</td>
                        <td class="txt-center">--</td>
                    </tr>
                @endfor
            </tbody>
            <tfoot>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="monto-table txt-center">Total: S/ 1860</td>
                </tr>
            </tfoot>
        </table>
    </div>
    <div class="condiciones-container mt-0">
        <h6 class="titulo-condiciones mb-0">CONDICIONES DEL CREDITO</h6>
        
        <p class="mb-0">
            <b>1. </b> Realizar sus pagos solo en agentes de los bancos indicados, puede hacer sus pagos en agentes, cajeros deposito, transferencia de cuenta a cuenta y por internet o banca personal.
        </p>
        
        <p class="mb-0"> 
            <b>2. </b> Esta prohibido pagar en la oficina del mismo banco, el cobro adicional por movimiento del banco es de S/9.00 soles, prohibido hacer sus pagos fuera de Lima, el cobro adicional por giro del banco es de S/7.50 soles.
        </p>
        
        <p class="mb-0">
            <b>3. </b> El pago de la cuota no incluye la comisión cobrada por el banco, es decir son pagos diferentes, para no generar por parte de la entidad bancaria, respetemos los párrafos 1 y 2. conserve su voucher por seguridad.
        </p>
        
        <p class="mb-0">
            <b>4. </b> Confirmar su deposito mediante la fotografía de su voucher vía WHATSAPP, 1ero  deberá escribir su nombre y sus apellidos en la parte superior del voucher sin tapar las letras impresas del mismo voucher.
        </p>
        <p class="mb-0">
            <b>5. </b> Evite interés moratorio, el pago de las cuotas realizadas después del día de vencimiento, se le cobrara EL PAGO DE S/4.00 SOLES ADICIONALES POR CADA DIA DE ATRASO.
        </p>
    </div>
    <div class="foot-logo-container">
        <img class="foot-logo" src="{{ public_path('img/pdf/footer.png') }}">
        {{-- <img class="top-logo" src="{{ asset('img/pdf/header.png') }}"> --}}
    </div>
</body>
</html>