<!-- Sistema de Comisiones del 40% de Intereses -->
<div class="row mb-4">
  <div class="col-12">
    <div class="card shadow">
      <div class="card-header py-3 bg-warning text-white">
        <h6 class="m-0 font-weight-bold">💰 Sistema de Comisiones del 40% de Intereses</h6>
      </div>
      <div class="card-body">
        <p class="text-muted">Este módulo calcula automáticamente el 40% de los intereses pagados por cada cliente y lo asigna como comisión a los cobradores correspondientes.</p>
      </div>
    </div>
  </div>
</div>

<!-- Filtros -->
<div class="row mb-4">
  <div class="col-12">
    <div class="card shadow">
      <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Filtros de Comisiones de Intereses</h6>
      </div>
      <div class="card-body">
        <form method="GET" action="<?php echo base_url('admin/reports/interest_commissions'); ?>" id="interestFiltersForm">
          <div class="form-row align-items-end">
            <div class="col-md-3 mb-2">
              <label for="start_date">Fecha de Inicio</label>
              <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date ?? ''); ?>">
            </div>
            <div class="col-md-3 mb-2">
              <label for="end_date">Fecha de Fin</label>
              <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date ?? ''); ?>">
            </div>
            <div class="col-md-3 mb-2">
              <label for="collector_id">Cobrador</label>
              <select class="form-control" id="collector_id" name="collector_id">
                <option value="">Todos los cobradores</option>
                <?php if (!empty($cobradores_list)) { foreach ($cobradores_list as $c) { ?>
                  <option value="<?php echo $c->id; ?>" <?php echo ($collector_id == $c->id) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($c->nombre); ?>
                  </option>
                <?php } } ?>
              </select>
              <small class="form-text text-muted">Selecciona un cobrador específico o deja en blanco para ver todos</small>
            </div>
            <div class="col-md-3 mb-2">
              <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Aplicar Filtros</button>
                <button type="button" class="btn btn-success" id="btnExportInterestExcel">
                  <i class="fa fa-file-excel"></i> Exportar Excel
                </button>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Totales Generales -->
<div class="row mb-4">
  <div class="col-12">
    <div class="card border-left-warning shadow h-100 py-2">
      <div class="card-body">
        <div class="row no-gutters align-items-center">
          <div class="col mr-2">
            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Resumen General de Comisiones de Intereses (40%)</div>
            <div class="row">
              <div class="col-md-2">
                <div class="text-xs text-muted">Interés Total Pagado</div>
                <div class="h5 mb-0 font-weight-bold text-gray-800">$<?php echo number_format($total_interest_commissions['total_interest_paid'], 0, ',', '.'); ?></div>
              </div>
              <div class="col-md-2">
                <div class="text-xs text-muted">Comisión Total 40%</div>
                <div class="h5 mb-0 font-weight-bold text-gray-800">$<?php echo number_format($total_interest_commissions['total_commission_40'], 0, ',', '.'); ?></div>
              </div>
              <div class="col-md-2">
                <div class="text-xs text-muted">Monto Total Cobrado</div>
                <div class="h5 mb-0 font-weight-bold text-gray-800">$<?php echo number_format($total_interest_commissions['total_amount_collected'], 0, ',', '.'); ?></div>
              </div>
              <div class="col-md-2">
                <div class="text-xs text-muted">Pagos Realizados</div>
                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($total_interest_commissions['total_payments'], 0, ',', '.'); ?></div>
              </div>
              <div class="col-md-2">
                <div class="text-xs text-muted">Clientes Atendidos</div>
                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($total_interest_commissions['total_customers'], 0, ',', '.'); ?></div>
              </div>
              <div class="col-md-2">
                <div class="text-xs text-muted">Préstamos Gestionados</div>
                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($total_interest_commissions['total_loans'], 0, ',', '.'); ?></div>
              </div>
            </div>
          </div>
          <div class="col-auto">
            <i class="fas fa-coins fa-2x text-gray-300"></i>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Gráfico de Comisiones de Intereses -->
<div class="row mb-4">
  <div class="col-xl-12">
    <div class="card shadow">
      <div class="card-header py-3 bg-info text-white">
        <h6 class="m-0 font-weight-bold">📊 Comisiones del 40% de Intereses por Cobrador</h6>
      </div>
      <div class="card-body">
        <canvas id="interestCommissionsChart" width="400" height="200"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- Tabla de Comisiones por Cobrador -->
<div class="row mb-4">
   <div class="col-12">
     <div class="card shadow">
       <div class="card-header py-3 bg-success text-white">
         <h6 class="m-0 font-weight-bold">💼 Detalle de Comisiones por Cobrador</h6>
         <small class="text-white-50">Selecciona cualquier cobrador del listado para ver sus detalles específicos</small>
       </div>
       <div class="card-body">
         <div class="table-responsive">
           <table class="table table-striped table-bordered table-hover">
             <thead class="thead-dark">
               <tr>
                 <th>Cobrador</th>
                 <th class="text-right">Pagos Realizados</th>
                 <th class="text-right">Interés Total Pagado</th>
                 <th class="text-right">Comisión 40%</th>
                 <th class="text-right">Monto Total Cobrado</th>
                 <th class="text-right">Clientes Atendidos</th>
                 <th class="text-right">Préstamos Gestionados</th>
                 <th class="text-center">Acciones</th>
               </tr>
             </thead>
             <tbody>
               <?php if (!empty($interest_commissions)) { ?>
                 <?php foreach ($interest_commissions as $commission) { ?>
                 <tr class="cobrador-row" data-user-id="<?php echo $commission->user_id; ?>" style="cursor: pointer;">
                   <td>
                     <strong><?php echo htmlspecialchars($commission->user_name); ?></strong>
                     <br><small class="text-muted">ID: <?php echo $commission->user_id; ?></small>
                   </td>
                   <td class="text-right"><?php echo number_format($commission->total_payments, 0, ',', '.'); ?></td>
                   <td class="text-right">$<?php echo number_format($commission->total_interest_paid, 0, ',', '.'); ?></td>
                   <td class="text-right bg-warning text-white font-weight-bold">$<?php echo number_format($commission->interest_commission_40, 0, ',', '.'); ?></td>
                   <td class="text-right">$<?php echo number_format($commission->total_amount_collected, 0, ',', '.'); ?></td>
                   <td class="text-right"><?php echo number_format($commission->customers_handled, 0, ',', '.'); ?></td>
                   <td class="text-right"><?php echo number_format($commission->loans_handled, 0, ',', '.'); ?></td>
                   <td class="text-center">
                     <button class="btn btn-sm btn-info view-client-details"
                             data-user-id="<?php echo $commission->user_id; ?>"
                             data-user-name="<?php echo htmlspecialchars($commission->user_name); ?>">
                       <i class="fas fa-eye"></i> Ver Detalles
                     </button>
                   </td>
                 </tr>
                 <?php } ?>
               <?php } else { ?>
                 <tr>
                   <td colspan="8" class="text-center text-muted">
                     <i class="fas fa-info-circle fa-2x mb-2"></i><br>
                     No hay datos de comisiones de intereses para el período seleccionado.<br>
                     <small>Intenta ajustar los filtros de fecha o seleccionar "Todos los cobradores"</small>
                   </td>
                 </tr>
               <?php } ?>
             </tbody>
           </table>
         </div>

         <!-- Información adicional -->
         <?php if (!empty($interest_commissions)) { ?>
         <div class="mt-3">
           <div class="alert alert-info">
             <i class="fas fa-lightbulb"></i>
             <strong>Consejo:</strong> Haz clic en cualquier fila de cobrador para filtrar automáticamente los datos por ese usuario,
             o usa el botón "Ver Detalles" para obtener información específica de sus clientes.
           </div>
         </div>
         <?php } ?>
       </div>
     </div>
   </div>
 </div>

<!-- Modal para Detalles por Cliente -->
<div class="modal fade" id="clientDetailsModal" tabindex="-1" role="dialog" aria-labelledby="clientDetailsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title" id="clientDetailsModalLabel">Detalles de Comisiones por Cliente</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div id="clientDetailsContent">
          <!-- Los detalles se cargarán aquí dinámicamente -->
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@latest/dist/Chart.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Renderizar gráfico de comisiones de intereses
  renderInterestCommissionsChart();

  // Evento para ver detalles de clientes
  document.addEventListener('click', function(e) {
    if (e.target.classList.contains('view-client-details') || e.target.closest('.view-client-details')) {
      const button = e.target.classList.contains('view-client-details') ? e.target : e.target.closest('.view-client-details');
      const userId = button.getAttribute('data-user-id');
      const userName = button.getAttribute('data-user-name');

      loadClientDetails(userId, userName);
    }
  });

  // Exportación a Excel
  document.getElementById('btnExportInterestExcel').addEventListener('click', function() {
    const start = document.getElementById('start_date').value;
    const end = document.getElementById('end_date').value;
    const collector = document.getElementById('collector_id').value;
    window.open(base_url + 'admin/reports/export_interest_commissions_excel?start_date=' + start + '&end_date=' + end + '&collector_id=' + collector, '_blank');
  });
});

function renderInterestCommissionsChart() {
  const commissions = <?php echo json_encode($interest_commissions); ?>;

  if (!commissions || commissions.length === 0) {
    document.getElementById('interestCommissionsChart').parentNode.innerHTML = '<p class="text-center text-muted">No hay datos disponibles para el gráfico.</p>';
    return;
  }

  const ctx = document.getElementById('interestCommissionsChart').getContext('2d');
  const labels = commissions.map(item => item.user_name.length > 15 ? item.user_name.substring(0, 15) + '...' : item.user_name);
  const commissionsData = commissions.map(item => parseFloat(item.interest_commission_40));

  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [{
        label: 'Comisión 40% de Intereses',
        data: commissionsData,
        backgroundColor: 'rgba(255, 193, 7, 0.6)',
        borderColor: 'rgba(255, 193, 7, 1)',
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { position: 'top' },
        tooltip: {
          callbacks: {
            label: function(context) {
              const item = commissions[context.dataIndex];
              return [
                'Cobrador: ' + item.user_name,
                'Comisión 40%: $' + parseFloat(item.interest_commission_40).toLocaleString('es-CO'),
                'Interés Total: $' + parseFloat(item.total_interest_paid).toLocaleString('es-CO'),
                'Pagos: ' + item.total_payments
              ];
            }
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: function(value) {
              return '$' + value.toLocaleString('es-CO');
            }
          }
        }
      }
    }
  });
}

function loadClientDetails(userId, userName) {
  // Mostrar indicador de carga
  document.getElementById('clientDetailsContent').innerHTML = `
    <div class="text-center">
      <div class="spinner-border text-primary" role="status">
        <span class="sr-only">Cargando...</span>
      </div>
      <p class="mt-2">Cargando detalles de ${userName}...</p>
    </div>`;
  $('#clientDetailsModal').modal('show');

  // Hacer llamada AJAX para obtener detalles actualizados
  fetch(base_url + 'admin/reports/get_user_interest_details?user_id=' + userId + '&start_date=' + document.getElementById('start_date').value + '&end_date=' + document.getElementById('end_date').value)
    .then(response => response.json())
    .then(data => {
      if (data.error) {
        document.getElementById('clientDetailsContent').innerHTML = '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> ' + data.error + '</div>';
        return;
      }

      let html = `
        <div class="alert alert-info">
          <strong>Cobrador:</strong> ${userName}<br>
          <strong>Comisión Total 40%:</strong> $${parseFloat(data.total_commission || 0).toLocaleString('es-CO')}<br>
          <strong>Período:</strong> ${document.getElementById('start_date').value || 'Sin límite'} - ${document.getElementById('end_date').value || 'Sin límite'}
        </div>`;

      if (!data.clients || data.clients.length === 0) {
        html += '<div class="alert alert-info"><i class="fas fa-info-circle"></i> No hay clientes con comisiones de intereses para este cobrador en el período seleccionado.</div>';
      } else {
        html += `
          <div class="table-responsive">
            <table class="table table-sm table-striped table-hover">
              <thead class="thead-light">
                <tr>
                  <th>Cliente</th>
                  <th>Cédula</th>
                  <th>Préstamo</th>
                  <th class="text-right">Monto Original</th>
                  <th class="text-right">Pagos Realizados</th>
                  <th class="text-right">Interés Pagado</th>
                  <th class="text-right">Comisión 40%</th>
                  <th>Último Pago</th>
                </tr>
              </thead>
              <tbody>`;

        data.clients.forEach(client => {
          html += `
            <tr>
              <td><strong>${client.customer_name}</strong></td>
              <td><code>${client.dni}</code></td>
              <td><span class="badge badge-secondary">${client.loan_id}</span></td>
              <td class="text-right">$<strong>${parseFloat(client.credit_amount).toLocaleString('es-CO')}</strong></td>
              <td class="text-right"><span class="badge badge-primary">${client.payments_made}</span></td>
              <td class="text-right">$<strong>${parseFloat(client.total_interest_paid).toLocaleString('es-CO')}</strong></td>
              <td class="text-right bg-warning text-white font-weight-bold">$<strong>${parseFloat(client.interest_commission_40).toLocaleString('es-CO')}</strong></td>
              <td><small>${client.last_payment_date ? new Date(client.last_payment_date).toLocaleDateString('es-CO') : 'N/A'}</small></td>
            </tr>`;
        });

        html += `
              </tbody>
              <tfoot class="bg-light">
                <tr>
                  <th colspan="5">TOTALES</th>
                  <th class="text-right">$<strong>${parseFloat(data.total_interest || 0).toLocaleString('es-CO')}</strong></th>
                  <th class="text-right bg-warning text-white">$<strong>${parseFloat(data.total_commission || 0).toLocaleString('es-CO')}</strong></th>
                  <th></th>
                </tr>
              </tfoot>
            </table>
          </div>`;
      }

      document.getElementById('clientDetailsContent').innerHTML = html;
    })
    .catch(error => {
      console.error('Error cargando detalles:', error);
      document.getElementById('clientDetailsContent').innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Error al cargar los detalles. Por favor, intenta nuevamente.</div>';
    });
}

// Funcionalidad adicional: Filtrar por cobrador al hacer clic en fila
document.addEventListener('DOMContentLoaded', function() {
  // Evento para filas de cobradores
  document.querySelectorAll('.cobrador-row').forEach(row => {
    row.addEventListener('click', function() {
      const userId = this.getAttribute('data-user-id');
      const userName = this.querySelector('strong').textContent;

      // Auto-seleccionar en el filtro
      const selectElement = document.getElementById('collector_id');
      selectElement.value = userId;

      // Mostrar mensaje de confirmación
      const alertDiv = document.createElement('div');
      alertDiv.className = 'alert alert-success alert-dismissible fade show mt-3';
      alertDiv.innerHTML = `
        <i class="fas fa-check-circle"></i>
        <strong>Filtro aplicado:</strong> Mostrando datos solo de ${userName}
        <button type="button" class="close" data-dismiss="alert">
          <span>&times;</span>
        </button>
      `;

      // Insertar después del formulario
      const form = document.getElementById('interestFiltersForm');
      form.parentNode.insertBefore(alertDiv, form.nextSibling);

      // Auto-enviar formulario después de 2 segundos
      setTimeout(() => {
        document.getElementById('interestFiltersForm').submit();
      }, 2000);
    });
  });
});
</script>