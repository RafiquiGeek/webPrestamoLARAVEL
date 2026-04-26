<!DOCTYPE html>
<html lang="en">
<head>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@200;400;600&family=Roboto:wght@400;700&display=swap" rel="stylesheet"  media="print">

    <title>Calculo de Solicitud - APP</title>

    <style>
        * {
            font-family: 'Roboto' !important;
        }
        body {
            background-image: none !important;
            background-color: #fff !important;
            background: #fff !important;
        }
        .simulador form {
            padding: 0;
            border-radius: 0;
            margin-top: 0;
            margin-bottom: 0;
        }
        .simulador .content_simulador {
            padding: 20px 40px;
        }
        .simulador {
            padding: 0;
            width: 100%;
        }
        .calculo p span {
            
        }
        .calculo p {
            
        }
        .calculo p b {
            
        }
        .calculo p span {
            display: inline-block;
            width: 45%;
        }
        .cuadr_a {
            background: #004070;
            color: #fff;
            text-align: center;
            border-bottom-left-radius: 10px;
            font-size: 25px;
            padding: 9px 0;
            font-weight: bold;
        }
        .cuadr_b {
            background: #004070;
            color: #fff;
            text-align: center;
            font-weight: bold;
            margin: 0 5px;
            padding: 5px 0px;
            font-size: 14px;
        }
        .cuadr_c {
            background: #d2d3d5;
            color: #000;
            text-align: center;
            font-weight: bold;
            margin: 0 5px;
            padding: 3px 0px;
            font-size: 14px;
        }
        .col {
            padding: 0;
        }
        .cuadra .form-group {
            margin-bottom: 5px;
        }
        .label_c {
            padding-left: 10px;
        }
        .l2 {
            padding-left: 0; 
        }
        .label_c label {
            font-size: 11px;
            margin-bottom: 0;
        }
        .label_c label span {

        }
        .label_c input {
            background-color: #fff;
            background-image: none;
            border: 1px solid #ccc;
            display: inline-block;
            width: 28%;
            height: 20px;
            margin: 0;
        }
        .cuadro_d {
            background: #d2d3d5;
            color: #000;
            text-align: center;
            font-size: 25px;
            padding: 9px 0;
            font-weight: bold;
        }
        .fechc {
            font-size: 11px;
            border: 1px solid #d2d3d5;
            border-radius: 5px;
            margin: 0 5px 5px 10px;
            padding: 4px 9px;
            color: #000;
        }
        .fechc span {
            color: #004070;
            font-weight: 800;
            font-size: 11px;
        }
        .content_info {
            border: 1px solid #d2d3d5;
            border-radius: 5px;
            margin: 5px 5px 0 0;
            padding: 4px 9px;
        }
        .content_info_t {
            font-size: 11px;
            border: 1px solid #d2d3d5;
            border-radius: 5px;
            margin: 5px 5px 0 0;
            padding: 2px 5px;
        }
        .title_c {
            font-size: 11px;
        }
        .title_c span {
            padding-right: 20px;
        }
        .data_c {
            font-weight: 800;
            color: #004070;
        }
        .content_pago {
            background: #004070;
            color: #fff;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
            padding: 7px 2px 14px;
            margin-top: 5px;
            text-align: center;
            font-weight: 500;
            font-size: 11px;
        }
        .table {
            margin-bottom: 10px;
        }
        thead tr {
            background: #004070;
            color: #fff;
        }
        thead tr th {
            padding: 2px 0 !important;
            text-align: center;
            font-size: 11px;
            font-family: 'Roboto' !important;
        }
        tbody tr {
            background: #fff;
            color: #000;
        }
        tbody tr td {
            font-size: 10px;
            font-weight: 300;
            text-align: center;
            padding: 2px 0 !important;
            font-family: 'Roboto' !important;
        }
        .col {
            float: left;
            position: relative;
            min-height: 1px;
            padding-right: 0;
            padding-left: 0;
        }
        .col1 {
            width: 8.33333333%;
        }
        .col2 {
            width: 16.66666667%;
        }
        .col3 {
            width: 16.66666667%;
        }
        .col4 {
            width: 16.66666667%;
        }
        .col5 {
            width: 16.66666667%;
        }
        .col6 {
            width: 25%;
        }
        .cols {
            float: left;
            position: relative;
            min-height: 1px;
            padding-right: 0;
            padding-left: 0;
        }
        .col7 {
            width: 66.66666667%;
        }
        .col8 {
            width: 33.33333333%;
        }
        .col9 {
            width: 100%;
        }
        .col10 {
            width: 66.66666667%;
        }
        .col11 {
            width: 33.33333333%;
        }
        .col12 {
            width: 100%;
        }
        .col13 {
            width: 8.33333333%;
        }
        .col14 {
            width: 16.66666667%;
        }
        .col15 {
            width: 25%;
        }
        .col16 {
            width: 25%;
        }
        .col17 {
            width: 25%;
        }
        .col18 {
            width: 100%;
        }
        .row {
            margin-right: -15px;
            margin-left: -15px;
        }
        .row:before {
            display: table;
            content: " ";
        }
        .credito_cont {

        }
        .credito_cont h3 {
            color: #004070;
            font-weight: 800;
            font-family: 'Roboto', sans-serif;
            margin-top: 5px !important;
            font-size: 13px;
        }
        .credito_cont p {
            font-size: 8px;
            margin-bottom: 0;
        }
        .credito_cont b {
            color: #004070;
            font-weight: 800;
        }
        .logo_top {
            background: #ecf0f5;
            padding: 10px 0;
            text-align: center;
        }
        .simulador .logo_top .bgform {
            width: 80px;
            margin: auto;
            display: block;
        }
        a .bgform {
            width: 100%;
        }
        @media (max-width: 575px) {
            .simulador {
                padding: 0;
            }
            .simulador .content_simulador {
                padding: 50px 30px;
            }
        }
    </style>
</head>
<body>
    <div class="container calculo simulador">
        <form>
                <div class="logo_top">
                    <img class="bgform" src="{{ public_path('img/pdf/header.png') }}">
                </div>
                
                <div class="content_simulador">
                    <div class="row">
                        <div class="col col1">
                            <div class="cuadr_a cuadra">
                                5
                            </div>
                        </div>
                        
                        <div class="col col2">
                            <div class="cuadr_b cuadra">
                                GB156
                            </div>

                            <div class="cuadr_c cuadra">
                                GB156
                            </div>
                        </div>

                        <div class="col col3">
                            <div class="label_c cuadra">
                                <div class="form-group">
                                    <label>
                                        <span>NUEVO</span>
                                    </label>
                                </div>
                                
                                <div class="form-group">
                                    <label>
                                        <span>RENOV</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="col col4">
                            <div class="label_c l2 cuadra">
                                <div class="form-group">
                                    <label>
                                        <span>EFECTIVO</span>
                                    </label>
                                </div>
                                
                                <div class="form-group">
                                    <label>
                                        <span>TRANF. BANC</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="col col5">
                            <div class="cuadro_d cuadra">
                                Julio Cesar
                            </div>
                        </div>
                        
                        <div class="col col6">
                            <div class="fechc cuadra">
                                F. Entr. <span>10/11/2023<span>
                            </div>

                            <div class="fechc cuadra">
                                F. Vcto. <span>
                                    28/10/2023
                                <span>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-8 col col7">
                            <div class="content_info">
                                <div class="title_c">
                                    <b>Titular:</b> 
                                    <span class="data_c">
                                        Julio cESAR
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4 col col8">
                            <div class="content_info">
                                <div class="title_c">
                                    <b>DNI:</b> 
                                    <span class="data_c">
                                        74863124
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 col col9">
                            <div class="content_info">
                                <div class="title_c">
                                    <b>Direccion:</b> 
                                    <span class="data_c">
                                        Mz h 23 lt 10
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-8 col col7">
                            <div class="content_info">
                                <div class="title_c">
                                    <b>Aval:</b> 
                                    <span class="data_c">
                                        Julio Cesare
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4 col col8">
                            <div class="content_info">
                                <div class="title_c">
                                    <b>DNI:</b> 
                                    <span class="data_c">
                                        74863124
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 col col9">
                            <div class="content_info">
                                <div class="title_c">
                                    <b>Direccion:</b> 
                                    <span class="data_c">
                                        Mz h 23 lt 10
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col col13">
                            <div class="content_info_t">
                                cuenta propia
                            </div>
                        </div>

                        <div class="col col14">
                            <div class="content_info_t">
                                bcp
                            </div>
                        </div>

                        <div class="col col15">
                            <div class="content_info_t">
                                bbva
                            </div>
                        </div>

                        <div class="col col16">
                            <div class="content_info_t">
                                julio cesar
                            </div>
                        </div>

                        <div class="col col17">
                            <div class="content_pago">
                                10/10/2302

                                Dias de pago: <span style="text-transform: capitalize;">4</span>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col col18">
                            <table class="table">
                                <thead class="thead-light">
                                    <tr>
                                        <th scope="col">IT</th>
                                        <th scope="col">FECHA DE PAGO</th>
                                        <th scope="col">CAPITAL</th>
                                        <th scope="col">MONTO CUOTA</th>
                                        <th scope="col">BANCO</th>
                                        <th scope="col">Nº OP.</th>
                                        <th scope="col">OBSERVACIÓN</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr>
                                        <td>1</td>
                                        <td>10/11/2023</td>
                                        <td>dsadsad</td>
                                        <td>dasdsads</td>
                                        <td>dsadsadas</td>
                                        <td></td>
                                        <td></td>
                                    </tr>

                                </tbody>
                            </table>
                            
                            <div class="credito_cont">
                                <h3>
                                    CONDICIONES DEL CREDITO
                                </h3>
                                
                                <p>
                                    <b>1. </b> Realizar sus pagos solo en agentes de los bancos indicados, puede hacer sus pagos en agentes, cajeros deposito, transferencia de cuenta a cuenta y por internet o banca personal.
                                </p>
                                
                                <p>
                                    <b>2. </b> Esta prohibido pagar en la oficina del mismo banco, el cobro adicional por movimiento del banco es de S/9.00 soles, prohibido hacer sus pagos fuera de Lima, el cobro adicional por giro del banco es de S/7.50 soles.
                                </p>
                                
                                <p>
                                    <b>3. </b> El pago de la cuota no incluye la comisión cobrada por el banco, es decir son pagos diferentes, para no generar por parte de la entidad bancaria, respetemos los párrafos 1 y 2. conserve su voucher por seguridad.
                                </p>
                                
                                <p>
                                    <b>4. </b> Confirmar su deposito mediante la fotografía de su voucher vía WHATSAPP, 1ero  deberá escribir su nombre y sus apellidos en la parte superior del voucher sin tapar las letras impresas del mismo voucher.
                                </p>
                                
                                <p class="fondo_credito">
                                    <b>5. </b> Evite interés moratorio, el pago de las cuotas realizadas después del día de vencimiento, se le cobrara EL PAGO DE S/4.00 SOLES ADICIONALES POR CADA DIA DE ATRASO.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            
                <a href="https://wa.me/51999654321" target="_BLANK">
                    <img class="bgform" src="{{ public_path('img/pdf/footer.png') }}">
                </a>

        </form>
    </div>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.6.3/js/bootstrap-select.min.js"></script>
</body>
</html>

