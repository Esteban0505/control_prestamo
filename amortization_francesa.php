<?php
// Script para calcular amortización francesa
// Datos proporcionados
$monto = 1000000;
$tasa_anual = 0.10; // 10%
$cuotas = 5;
$fecha_inicio = '2025-09-28';

// Calcular tasa mensual
$tasa_mensual = $tasa_anual / 12;

// Calcular cuota mensual usando la fórmula de amortización francesa
$cuota = $monto * ($tasa_mensual * pow(1 + $tasa_mensual, $cuotas)) / (pow(1 + $tasa_mensual, $cuotas) - 1);
$cuota = round($cuota, 2);

// Inicializar variables
$saldo = $monto;
$fecha = new DateTime($fecha_inicio);
$total_capital = 0;

// Generar tabla HTML
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Período</th><th>Fecha</th><th>Cuota</th><th>Capital</th><th>Interés</th><th>Saldo</th></tr>";

for ($i = 1; $i <= $cuotas; $i++) {
    $interes = $saldo * $tasa_mensual;
    $interes = round($interes, 2);

    if ($i == $cuotas) {
        // Ajustar el último período para que el saldo sea exactamente 0 y la suma de capital sea el monto
        $capital = $saldo;
        $interes = $cuota - $capital;
        $nuevo_saldo = 0;
    } else {
        $capital = $cuota - $interes;
        $nuevo_saldo = $saldo - $capital;
        $nuevo_saldo = round($nuevo_saldo, 2);
    }

    $total_capital += $capital;

    echo "<tr>";
    echo "<td>$i</td>";
    echo "<td>" . $fecha->format('Y-m-d') . "</td>";
    echo "<td>" . number_format($cuota, 2) . "</td>";
    echo "<td>" . number_format($capital, 2) . "</td>";
    echo "<td>" . number_format($interes, 2) . "</td>";
    echo "<td>" . number_format($nuevo_saldo, 2) . "</td>";
    echo "</tr>";

    $saldo = $nuevo_saldo;
    $fecha->modify('+1 month');
}

echo "</table>";
echo "<p>Suma total de capital pagado: " . number_format($total_capital, 2) . "</p>";
echo "<p>Monto original: " . number_format($monto, 2) . "</p>";
echo "<p>Saldo final: " . number_format($saldo, 2) . "</p>";
?>