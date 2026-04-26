<div class="table-responsive">
    <table class="table table-hover table-striped border-0">
        <thead class="bg-light">
            <tr>
                <th>Fecha</th>
                <th class="text-center">Nro</th>
                <th class="text-end">PAGO CAPITAL</th>
                <th class="text-end">CUOTA</th>
                <th class="text-end">INTERES</th>
                <th class="text-end">COMISIÓN</th>
                <th class="text-end">IGV</th>
                <th class="text-end">SALDO CAPITAL</th>
            </tr>
        </thead>

        @php
            // Tasas según número de cuotas (estas tasas INCLUYEN IGV)
            $numeroCuotas = $cuotas->count();
            $tasasConIgv = [
                12 => ['interes' => 1.44, 'comision' => 4.67],
                15 => ['interes' => 1.44, 'comision' => 4.11],
                18 => ['interes' => 1.44, 'comision' => 3.22],
                20 => ['interes' => 1.44, 'comision' => 2.77],
            ];
            $tasas = $tasasConIgv[$numeroCuotas] ?? ['interes' => 0, 'comision' => 0];

            // Convertir tasas a base (sin IGV): tasa_base = tasa_con_igv / 1.18
            $tasaInteresBase = $tasas['interes'] / 1.18;
            $tasaComisionBase = $tasas['comision'] / 1.18;

            // Calcular saldo capital inicial = cantidad solicitada del préstamo
            $saldoCapitalInicial = $prestamo->cantidad_solicitada ?? 0;
            $saldoCapital = $saldoCapitalInicial;

            // Variables para totales
            $totalCuota = 0;
            $totalPagoCapital = 0;
            $totalInteres = 0;
            $totalComision = 0;
            $totalIgv = 0;
        @endphp

        <tbody>
            @foreach ($cuotas as $cuota)
                @php
                    // CUOTA = monto de la cuota registrada
                    $cuotaTotal = $cuota->monto;

                    // Calcular INTERÉS y COMISIÓN base (sin IGV) sobre el saldo capital actual
                    $interesBase = $saldoCapital * ($tasaInteresBase / 100);
                    $comisionBase = $saldoCapital * ($tasaComisionBase / 100);

                    // Calcular el IGV (18% sobre interés base + comisión base)
                    $baseTotalSinIgv = $interesBase + $comisionBase;
                    $igv = $baseTotalSinIgv * 0.18;

                    // PAGO CAPITAL = CUOTA - INTERÉS - COMISIÓN - IGV
                    $pagoCapital = $cuotaTotal - $interesBase - $comisionBase - $igv;

                    // Calcular nuevo saldo capital
                    $nuevoSaldoCapital = max(0, $saldoCapital - $pagoCapital);

                    // Para mostrar en las columnas (valores sin IGV)
                    $interes = $interesBase;
                    $comision = $comisionBase;

                    // Actualizar totales
                    $totalCuota += $cuotaTotal;
                    $totalPagoCapital += $pagoCapital;
                    $totalInteres += $interesBase;
                    $totalComision += $comisionBase;
                    $totalIgv += $igv;
                @endphp
                <tr>
                    <td>{{ \Carbon\Carbon::parse($cuota->fecha_pago)->format('d/m/Y') }}</td>
                    <td class="text-center fw-bold">{{ $cuota->numero }}</td>
                    <td class="text-end text-primary fw-bold">S/ {{ number_format($pagoCapital, 2) }}</td>
                    <td class="text-end">S/ {{ number_format($cuotaTotal, 2) }}</td>
                    <td class="text-end">S/ {{ number_format($interes, 2) }}</td>
                    <td class="text-end">S/ {{ number_format($comision, 2) }}</td>
                    <td class="text-end">S/ {{ number_format($igv, 2) }}</td>
                    <td class="text-end text-success fw-bold">S/ {{ number_format($nuevoSaldoCapital, 2) }}</td>
                </tr>
                @php
                    // Actualizar saldo capital para la siguiente iteración
                    $saldoCapital = $nuevoSaldoCapital;
                @endphp
            @endforeach
        </tbody>

        <tfoot class="bg-light">
            <tr class="fw-bold">
                <td colspan="2" class="text-end">TOTALES</td>
                <td class="text-end text-primary">S/ {{ number_format($totalPagoCapital, 2) }}</td>
                <td class="text-end">S/ {{ number_format($totalCuota, 2) }}</td>
                <td class="text-end">S/ {{ number_format($totalInteres, 2) }}</td>
                <td class="text-end">S/ {{ number_format($totalComision, 2) }}</td>
                <td class="text-end">S/ {{ number_format($totalIgv, 2) }}</td>
                <td class="text-end">S/ 0.00</td>
            </tr>
        </tfoot>
    </table>
</div>
