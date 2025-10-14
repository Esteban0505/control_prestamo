<?php
// Script para aplicar castigos automáticos a clientes con mora >60 días

require_once 'application/models/Payments_m.php';

$payments_m = new Payments_m();
$applied = $payments_m->apply_automatic_penalties();

echo "Castigos aplicados: $applied\n";
?>