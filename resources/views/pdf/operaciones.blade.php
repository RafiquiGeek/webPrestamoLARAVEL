<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Operaciones Generales</title>
    <style>
        /* General Styles */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 20px;
            color: #333;
        }

        h1, h4 {
            color: #004085;
        }

        h1 {
            text-align: center;
            font-size: 24px;
            margin-bottom: 20px;
            font-weight: bold;
        }

        h4 {
            font-size: 18px;
            margin-bottom: 10px;
            text-align: left;
            border-left: 4px solid #004085;
            padding-left: 10px;
        }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background-color: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1);
        }

        th, td {
            padding: 10px;
            text-align: center;
            font-size: 14px;
        }

        th {
            background-color: #004085;
            color: white;
            text-transform: uppercase;
            font-weight: bold;
        }

        td {
            border: 1px solid #ddd;
        }

        tbody tr:nth-child(odd) {
            background-color: #f9f9f9;
        }

        tbody tr:hover {
            background-color: #e2e6ea;
        }

        /* Inner Table Styles */
        table.inner-table {
            margin-top: 10px;
            background-color: #f8f9fa;
        }

        table.inner-table th {
            background-color: #0056b3;
        }

        table.inner-table td {
            font-size: 13px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            h1 {
                font-size: 20px;
            }

            table {
                font-size: 12px;
            }

            th, td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <h1>Operaciones Generales</h1>
    <table>
        <thead>
            <tr>
                <th># Operación General</th>
                <th>Fecha</th>
                <th>Monto Total</th>
                <th>Método de Pago</th>
            </tr>
        </thead>
        <tbody>
            @foreach($operacionesGenerales as $operacion)
            <tr>
                <td>{{ $operacion->id }}</td>
                <td>{{ \Carbon\Carbon::parse($operacion->fecha)->format('d-m-Y') }}</td>
                <td>{{ 'S/. ' . number_format($operacion->abono, 2) }}</td>
                <td>{{ optional($operacion->metodoDePago)->metodo_pago ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td colspan="4">
                    <h4>Operaciones Relacionadas</h4>
                    <table class="inner-table">
                        <thead>
                            <tr>
                                <th># Operación</th>
                                <th>Fecha</th>
                                <th>Monto</th>
                                <th>Método de Pago</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($operacion->operacionesRelacionadas as $relacionada)
                            <tr>
                                <td>{{ $relacionada->id }}</td>
                                <td>{{ \Carbon\Carbon::parse($relacionada->fecha)->format('d-m-Y') }}</td>
                                <td>{{ 'S/. ' . number_format($relacionada->abono, 2) }}</td>
                                <td>{{ optional($relacionada->metodoDePago)->metodo_pago ?? 'N/A' }}</td>
                            </tr>
                            @endforeach
                            @if($operacion->operacionesRelacionadas->isEmpty())
                            <tr>
                                <td colspan="4">No hay operaciones relacionadas</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
