<div class="modal-dialog modal-lg">
  <div class="modal-content">
    <!-- Header -->
    <div class="modal-header">
      <h5 class="modal-title">
        <strong>Préstamo #<?= $loan->id; ?></strong>
        <br>
        Cliente: <span class="text-primary"><?= $loan->customer_name; ?></span>
      </h5>
      <div class="d-flex flex-row-reverse">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
          <i class="fas fa-times fa-sm"></i>
        </button>
        <button type="button" class="close mr-2" onclick="window.print();">
          <i class="fas fa-print fa-sm"></i>
        </button>
      </div>
    </div>

    <!-- Body -->
    <div class="modal-body">
      <div class="row">
        <div class="col-md-12">

          <!-- Información del préstamo -->
          <div class="card p-3 mb-3 shadow-sm">
            <h6 class="mb-2"><strong>Detalles del Préstamo</strong></h6>
            <div class="row">
              <div class="col-md-6">
                <p><strong>Monto Crédito:</strong> <?= format_to_display($loan->credit_amount); ?></p>
                <p><strong>Interés (%):</strong> <?= format_to_display($loan->interest_amount, false) . '%'; ?></p>
                <p><strong>Nro Cuotas:</strong> <?= (int)$loan->num_fee; ?></p>
                <p><strong>Monto Cuota (estimado):</strong> <?= format_to_display($loan->fee_amount); ?></p>
                <p><strong>Tipo Moneda:</strong> <?= strtoupper($loan->short_name); ?></p>
              </div>
              <div class="col-md-6">
                <p><strong>Fecha Crédito:</strong> <?= $loan->date; ?></p>
                <p><strong>Forma Pago:</strong> <?= ucfirst($loan->payment_m); ?></p>
                <p><strong>Amortización:</strong>
                  <?php
                    if (!empty($loan->amortization_type)) {
                      switch ($loan->amortization_type) {
                        case 'francesa': echo 'Francés (cuotas fijas)'; break;
                        case 'estaunidense': echo 'Estadounidense (solo intereses y capital al final)'; break;
                        case 'mixta': echo 'Mixta'; break;
                        default: echo ucfirst($loan->amortization_type);
                      }
                    } else {
                      echo 'N/A';
                    }
                  ?>
                </p>
                <p><strong>Estado Crédito:</strong>
                  <span class="<?= $loan->status ? 'text-danger':'text-success'; ?>">
                    <?= $loan->status ? 'Pendiente':'Pagado'; ?>
                  </span>
                </p>
                <p><strong>Creado por:</strong> <?= isset($loan->created_by_name) ? $loan->created_by_name : 'N/A'; ?></p>
              </div>
            </div>
          </div>

          <!-- Tabla de cuotas con desglose -->
          <div class="table-responsive">
            <h6><strong>Plan de Pagos (Cronograma)</strong></h6>
            <table class="table table-bordered table-striped table-sm" id="amortizationTable" width="100%" cellspacing="0">
              <thead class="thead-dark text-center">
                <tr>
                  <th>Período</th>
                  <th>Fecha de Pago</th>
                  <th class="text-right">Cuota</th>
                  <th class="text-right">Capital</th>
                  <th class="text-right">Interés</th>
                  <th class="text-right">Saldo Restante</th>
                  <th class="text-center">Estado</th>
                  <th class="text-center">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <?php
                  $total_capital = 0.0;
                  $total_interes  = 0.0;
                  $total_cuota    = 0.0;
                  if ($items):
                    $i = 0;
                    foreach ($items as $item):
                      // seguridad: convertir a float si vienen como strings
                      $capital = isset($item->capital_amount) ? (float)$item->capital_amount : 0.0;
                      $interes  = isset($item->interest_amount) ? (float)$item->interest_amount : 0.0;
                      $cuota    = isset($item->fee_amount) ? (float)$item->fee_amount : ($capital + $interes);
                      $saldo    = isset($item->balance) ? (float)$item->balance : 0.0;

                      $total_capital += $capital;
                      $total_interes  += $interes;
                      $total_cuota    += $cuota;
                ?>
                  <tr>
                    <td class="text-center font-weight-bold"><?= ++$i; ?></td>
                    <td class="text-center"><?= date('d/m/Y', strtotime($item->date)); ?></td>
                    <td class="text-right font-weight-bold"><?= format_to_display($cuota); ?></td>
                    <td class="text-right"><?= format_to_display($capital); ?></td>
                    <td class="text-right"><?= format_to_display($interes); ?></td>
                    <td class="text-right font-weight-bold"><?= format_to_display($saldo); ?></td>
                    <td class="text-center">
                      <?= (isset($item->status) && $item->status) ? '<span class="badge badge-danger">Pendiente</span>' : '<span class="badge badge-success">Cancelado</span>'; ?>
                    </td>
                    <td class="text-center">
                      <?php if (isset($item->status) && $item->status): ?>
                        <button class="btn btn-sm btn-primary" onclick="openPaymentModal(<?= $item->id; ?>, <?= $cuota; ?>, <?= $capital; ?>, <?= $interes; ?>)">
                          <i class="fas fa-credit-card"></i> Pagar
                        </button>
                      <?php else: ?>
                        <span class="text-muted">Pagado</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php
                    endforeach;
                  else:
                ?>
                  <tr>
                    <td colspan="7" class="text-center">No hay cuotas registradas para este préstamo.</td>
                  </tr>
                <?php endif; ?>
              </tbody>
              <?php if ($items): ?>
                <tfoot class="table-info">
                  <tr class="font-weight-bold">
                    <td colspan="2" class="text-right">Totales:</td>
                    <td class="text-right"><?= format_to_display($total_cuota); ?></td>
                    <td class="text-right"><?= format_to_display($total_capital); ?></td>
                    <td class="text-right"><?= format_to_display($total_interes); ?></td>
                    <td class="text-right">-</td>
                    <td class="text-center">-</td>
                    <td class="text-center">-</td>
                  </tr>
                </tfoot>
              <?php endif; ?>
            </table>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>

<script>
$(document).ready(function() {
    // Inicializar DataTable para la tabla de amortización
    $('#amortizationTable').DataTable({
        "paging": true,
        "lengthChange": true,
        "searching": true,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "responsive": true,
        "pageLength": 10,
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json"
        },
        "columnDefs": [
            { "orderable": false, "targets": 6 } // Deshabilitar ordenamiento en la columna de estado
        ],
        "order": [[ 0, "asc" ]] // Ordenar por período
    });
});
</script>

<!-- Modal para realizar pagos -->
<div class="modal fade" id="paymentModal" tabindex="-1" role="dialog" aria-labelledby="paymentModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="paymentModalLabel">Realizar Pago</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="paymentForm">
          <input type="hidden" id="loan_id" value="<?= $loan->id; ?>">
          <input type="hidden" id="loan_item_id">
          
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Cuota Total</label>
                <input type="text" class="form-control" id="quota_total" readonly>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Capital</label>
                <input type="text" class="form-control" id="quota_capital" readonly>
              </div>
            </div>
          </div>
          
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Interés</label>
                <input type="text" class="form-control" id="quota_interest" readonly>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Tipo de Pago</label>
                <select class="form-control" id="payment_type" required>
                  <option value="">Seleccione...</option>
                  <option value="full">Pago Total</option>
                  <option value="interest">Solo Intereses</option>
                  <option value="capital">Solo Capital</option>
                  <option value="both">Capital e Intereses</option>
                </select>
              </div>
            </div>
          </div>
          
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Monto a Pagar</label>
                <input type="text" class="form-control currency-input" id="payment_amount" placeholder="0,00" required>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Método de Pago</label>
                <select class="form-control" id="payment_method">
                  <option value="efectivo">Efectivo</option>
                  <option value="transferencia">Transferencia</option>
                  <option value="cheque">Cheque</option>
                  <option value="tarjeta">Tarjeta</option>
                </select>
              </div>
            </div>
          </div>
          
          <div class="form-group">
            <label>Notas (Opcional)</label>
            <textarea class="form-control" id="payment_notes" rows="3"></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="process_payment">Procesar Pago</button>
      </div>
    </div>
  </div>
</div>

<script>
function openPaymentModal(loanItemId, quotaTotal, capital, interest) {
  $('#loan_item_id').val(loanItemId);
  $('#quota_total').val(formatCurrency(quotaTotal));
  $('#quota_capital').val(formatCurrency(capital));
  $('#quota_interest').val(formatCurrency(interest));
  $('#payment_amount').val('');
  $('#payment_type').val('');
  $('#payment_notes').val('');
  $('#paymentModal').modal('show');
}

function formatCurrency(amount) {
  return new Intl.NumberFormat('es-CO', {
    style: 'currency',
    currency: 'COP',
    minimumFractionDigits: 2
  }).format(amount);
}

$(document).ready(function() {
  $('#process_payment').click(function() {
    var loanId = $('#loan_id').val();
    var loanItemId = $('#loan_item_id').val();
    var amount = $('#payment_amount').val();
    var paymentType = $('#payment_type').val();
    var method = $('#payment_method').val();
    var notes = $('#payment_notes').val();
    
    if (!amount || !paymentType) {
      alert('Por favor complete todos los campos requeridos');
      return;
    }
    
    $.ajax({
      url: '<?= site_url("admin/payments/pay"); ?>',
      type: 'POST',
      data: {
        loan_id: loanId,
        loan_item_id: loanItemId,
        amount: amount,
        payment_type: paymentType,
        method: method,
        notes: notes
      },
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          alert('Pago procesado correctamente');
          $('#paymentModal').modal('hide');
          location.reload();
        } else {
          alert('Error: ' + response.error);
        }
      },
      error: function() {
        alert('Error al procesar el pago');
      }
    });
  });
});
</script>
