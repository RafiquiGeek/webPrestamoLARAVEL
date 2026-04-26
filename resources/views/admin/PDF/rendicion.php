<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Rendición de Cuentas</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 18px;
            margin: 0;
            color: #2c3e50;
        }
        .header p {
            margin: 5px 0;
            font-size: 11px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
        }
        th {
            background-color: #34495e;
            color: white;
            font-weight: bold;
        }
        .total {
            font-weight: bold;
            font-size: 14px;
            margin-top: 10px;
            text-align: right;
        }
        .footer {
            margin-top: 20px;
            font-size: 10px;
            text-align: center;
            color: #7f8c8d;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Rendición de Cuentas - <?php echo ucfirst($tipo); ?></h1>
        <p>Usuario: <?php echo $user_codigo; ?></p>
        <p>Fecha: <?php echo $fecha; ?></p>
        <p>Rendido por: <?php echo $usuario_rendidor; ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Usuario</th>
                <th>Método</th>
                <th>Fecha</th>
                <th>Abono</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($operaciones as $op) { ?>
                <tr>
                    <td><?php echo $op->id; ?></td>
                    <td><?php echo optional($op->user)->codigo; ?></td>
                    <td><?php echo optional($op->metodoDePago)->metodo_pago; ?></td>
                    <td><?php echo \Carbon\Carbon::parse($op->fecha)->format('d/m/Y'); ?></td>
                    <td>S/ <?php echo number_format($op->abono, 2); ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>

    <div class="total">
        Total Rendido: S/ <?php echo number_format($total_rendido, 2); ?>
    </div>

    <div class="footer">
        Generado automáticamente el <?php echo now()->format('d/m/Y H:i:s'); ?>
    </div>
</body>
</html>