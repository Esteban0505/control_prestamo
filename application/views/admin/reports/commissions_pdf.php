<h3 style="text-align:center;">Reporte de Comisiones (40%)</h3>
<p style="text-align:center;">Desde <?= $start_date ?> hasta <?= $end_date ?></p>
<table border="1" cellspacing="0" cellpadding="5" width="100%">
  <thead style="background:#f0f0f0;">
    <tr>
      <th>Cliente</th>
      <th>Cédula</th>
      <th>Cobrador</th>
      <th>Total Pagado</th>
      <th>Interés</th>
      <th>Comisión (40%)</th>
      <th>Fecha</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach($commissions as $row): ?>
    <tr>
      <td><?= $row->client_name ?></td>
      <td><?= $row->client_cedula ?></td>
      <td><?= $row->user_name ?></td>
      <td><?= number_format($row->total_paid, 2, ',', '.') ?></td>
      <td><?= number_format($row->interest_amount, 2, ',', '.') ?></td>
      <td><?= number_format($row->commission, 2, ',', '.') ?></td>
      <td><?= date('d/m/Y H:i', strtotime($row->created_at)) ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>