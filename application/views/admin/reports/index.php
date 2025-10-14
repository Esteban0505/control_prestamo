<!-- Dashboard Ejecutivo de Cobranzas -->
<div class="row mb-4">
  <div class="col-12">
    <div class="card shadow">
      <div class="card-header py-3 bg-dark text-white">
        <h6 class="m-0 font-weight-bold">📊 Dashboard Ejecutivo de Cobranzas</h6>
      </div>
      <div class="card-body">
        <div class="row">
          <!-- Total Cobros -->
          <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
              <div class="card-body">
                <div class="row no-gutters align-items-center">
                  <div class="col mr-2">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Cobros</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $cobranza_totals['total_cobros']->total_cobros ?? 0; ?></div>
                  </div>
                  <div class="col-auto">
                    <i class="fas fa-cash-register fa-2x text-gray-300"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Cobros por Usuario -->
          <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
              <div class="card-body">
                <div class="row no-gutters align-items-center">
                  <div class="col mr-2">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Cobradores Activos</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $cobranza_totals['cobros_por_usuario'] ?? 0; ?></div>
                  </div>
                  <div class="col-auto">
                    <i class="fas fa-users fa-2x text-gray-300"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Pagos Completos -->
          <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
              <div class="card-body">
                <div class="row no-gutters align-items-center">
                  <div class="col mr-2">
                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Pagos Completos</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $cobranza_totals['pagos_completos'] ?? 0; ?></div>
                  </div>
                  <div class="col-auto">
                    <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Pagos Parciales -->
          <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
              <div class="card-body">
                <div class="row no-gutters align-items-center">
                  <div class="col mr-2">
                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pagos Parciales</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $cobranza_totals['pagos_parciales'] ?? 0; ?></div>
                  </div>
                  <div class="col-auto">
                    <i class="fas fa-clock fa-2x text-gray-300"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Resumen Financiero Consolidado -->
<div class="row mb-4">
  <div class="col-12">
    <div class="card border-left-primary shadow">
      <div class="card-header py-3 bg-primary text-white">
        <h6 class="m-0 font-weight-bold">💰 Resumen Financiero Consolidado</h6>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-3">
            <div class="text-center">
              <div class="text-xs text-muted mb-1">Total Recaudado</div>
              <div class="h4 mb-0 font-weight-bold text-success">$<?php echo number_format($cobranza_totals['total_cobros']->total_recaudado ?? 0, 0, ',', '.'); ?></div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="text-center">
              <div class="text-xs text-muted mb-1">Intereses Generados</div>
              <div class="h4 mb-0 font-weight-bold text-info">$<?php echo number_format(($cobranza_totals['total_cobros']->total_recaudado ?? 0) * 0.4, 0, ',', '.'); ?></div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="text-center">
              <div class="text-xs text-muted mb-1">Comisiones Cobradores (40%)</div>
              <div class="h4 mb-0 font-weight-bold text-warning">$<?php echo number_format(($cobranza_totals['total_cobros']->total_recaudado ?? 0) * 0.4, 0, ',', '.'); ?></div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="text-center">
              <div class="text-xs text-muted mb-1">Capital Recuperado</div>
              <div class="h4 mb-0 font-weight-bold text-primary">$<?php echo number_format(($cobranza_totals['total_cobros']->total_recaudado ?? 0) * 0.6, 0, ',', '.'); ?></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Gráfico Principal de Comisiones -->
<div class="row mb-4">
  <div class="col-xl-8 col-lg-7">
    <div class="card shadow">
      <div class="card-header py-3 bg-primary text-white">
        <h6 class="m-0 font-weight-bold">📈 Rendimiento de Cobradores</h6>
      </div>
      <div class="card-body">
        <canvas id="commissionChart" height="300"></canvas>
      </div>
    </div>
  </div>
  <div class="col-xl-4 col-lg-5">
    <div class="card shadow">
      <div class="card-header py-3 bg-success text-white">
        <h6 class="m-0 font-weight-bold">💼 Estadísticas Generales</h6>
      </div>
      <div class="card-body">
        <div class="mb-3">
          <div class="text-xs text-muted">Total Cobrado</div>
          <div class="h5 mb-0 font-weight-bold text-success" id="total_cobrado">$0</div>
        </div>
        <div class="mb-3">
          <div class="text-xs text-muted">Comisiones Generadas (40%)</div>
          <div class="h5 mb-0 font-weight-bold text-warning" id="total_comisiones">$0</div>
        </div>
        <div class="mb-3">
          <div class="text-xs text-muted">Total Cobros Realizados</div>
          <div class="h5 mb-0 font-weight-bold text-info" id="total_cobros">0</div>
        </div>
        <hr>
        <a href="<?php echo base_url('admin/reports/interest_commissions'); ?>" class="btn btn-warning btn-sm btn-block">
          <i class="fas fa-coins"></i> Ver Detalle de Intereses
        </a>
      </div>
    </div>
  </div>
</div>

<!-- Incluir Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@latest/dist/Chart.min.js"></script>

<!-- Script para gráficas -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Cargar datos de comisiones
  fetch(base_url + 'admin/reports/get_commission_stats')
    .then(response => response.json())
    .then(data => {
      if (data.stats && data.stats.length > 0) {
        renderCommissionChart(data.stats);
        updateTotals(data.totals);
      }
    })
    .catch(error => console.error('Error cargando estadísticas:', error));

  function renderCommissionChart(stats) {
    const ctx = document.getElementById('commissionChart').getContext('2d');
    const labels = stats.map(item => item.user_name);
    const commissions = stats.map(item => parseFloat(item.total_commission));

    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [{
          label: 'Comisiones (40%)',
          data: commissions,
          backgroundColor: 'rgba(54, 162, 235, 0.6)',
          borderColor: 'rgba(54, 162, 235, 1)',
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: 'top',
          },
          tooltip: {
            callbacks: {
              label: function(context) {
                const stat = stats[context.dataIndex];
                return [
                  'Comisión: $' + parseFloat(stat.total_commission).toLocaleString('es-CO'),
                  'Total Cobrado: $' + parseFloat(stat.total_amount).toLocaleString('es-CO'),
                  'N° Cobros: ' + stat.num_cobros
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

  function updateTotals(totals) {
    if (totals) {
      document.getElementById('total_cobrado').textContent = '$' + parseFloat(totals.total_amount || 0).toLocaleString('es-CO');
      document.getElementById('total_comisiones').textContent = '$' + parseFloat(totals.total_commission || 0).toLocaleString('es-CO');
      document.getElementById('total_cobros').textContent = totals.total_cobros || 0;
    }
  }

  // Exportación de comisiones
  document.getElementById('btnExportExcel').addEventListener('click', function() {
    const start = document.getElementById('start_date').value;
    const end = document.getElementById('end_date').value;
    const collector = document.getElementById('collector_id').value;
    window.open(base_url + 'admin/reports/export_commissions_excel?start_date=' + start + '&end_date=' + end + '&collector_id=' + collector, '_blank');
  });

  document.getElementById('btnExportPDF').addEventListener('click', function() {
    const start = document.getElementById('start_date').value;
    const end = document.getElementById('end_date').value;
    const collector = document.getElementById('collector_id').value;
    window.open(base_url + 'admin/reports/export_commissions_pdf?start_date=' + start + '&end_date=' + end + '&collector_id=' + collector, '_blank');
  });

  // Cargar datos para gráficos
  loadChartData();
});

function loadChartData() {
  // Gráfico de pagos por cliente
  fetch(base_url + 'admin/reports/get_chart_data?type=payments_by_customer')
    .then(response => response.json())
    .then(data => {
      if (data.length > 0) {
        renderPaymentsByCustomerChart(data);
      }
    })
    .catch(error => console.error('Error cargando pagos por cliente:', error));

  // Gráfico de top cobradores
  fetch(base_url + 'admin/reports/get_chart_data?type=top_collectors')
    .then(response => response.json())
    .then(data => {
      if (data.length > 0) {
        renderTopCollectorsChart(data);
      }
    })
    .catch(error => console.error('Error cargando top cobradores:', error));

  // Gráfico de rachas
  fetch(base_url + 'admin/reports/get_chart_data?type=streaks')
    .then(response => response.json())
    .then(data => {
      if (Object.keys(data).length > 0) {
        renderStreakChart(data);
      }
    })
    .catch(error => console.error('Error cargando rachas:', error));

  // Gráfico de castigos
  fetch(base_url + 'admin/reports/get_chart_data?type=penalties')
    .then(response => response.json())
    .then(data => {
      if (data.length > 0) {
        renderPenaltiesChart(data);
      }
    })
    .catch(error => console.error('Error cargando castigos:', error));
}

function renderPaymentsByCustomerChart(data) {
  const ctx = document.getElementById('paymentsByCustomerChart').getContext('2d');
  const labels = data.map(item => item.customer_name.length > 15 ? item.customer_name.substring(0, 15) + '...' : item.customer_name);
  const values = data.map(item => parseInt(item.payments_count));

  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [{
        label: 'Número de Pagos',
        data: values,
        backgroundColor: 'rgba(54, 162, 235, 0.6)',
        borderColor: 'rgba(54, 162, 235, 1)',
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
              const item = data[context.dataIndex];
              return [
                'Cliente: ' + item.customer_name,
                'Pagos: ' + item.payments_count,
                'Total: $' + parseFloat(item.total_paid).toLocaleString('es-CO')
              ];
            }
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: { precision: 0 }
        }
      }
    }
  });
}

function renderTopCollectorsChart(data) {
  const ctx = document.getElementById('topCollectorsChart').getContext('2d');
  const labels = data.map(item => item.user_name.length > 15 ? item.user_name.substring(0, 15) + '...' : item.user_name);
  const values = data.map(item => parseInt(item.payments_count));

  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [{
        label: 'Número de Cobranzas',
        data: values,
        backgroundColor: 'rgba(255, 99, 132, 0.6)',
        borderColor: 'rgba(255, 99, 132, 1)',
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
              const item = data[context.dataIndex];
              return [
                'Cobrador: ' + item.user_name,
                'Cobranzas: ' + item.payments_count
              ];
            }
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: { precision: 0 }
        }
      }
    }
  });
}

function renderStreakChart(data) {
  const ctx = document.getElementById('streakChart').getContext('2d');
  const labels = Object.keys(data);
  const values = Object.values(data);

  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [{
        label: 'Racha Máxima (cuotas seguidas)',
        data: values,
        backgroundColor: 'rgba(75, 192, 192, 0.6)',
        borderColor: 'rgba(75, 192, 192, 1)',
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
              return 'Racha: ' + values[context.dataIndex] + ' cuotas seguidas';
            }
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: { precision: 0 }
        }
      }
    }
  });
}

function renderPenaltiesChart(data) {
  const ctx = document.getElementById('penaltiesChart').getContext('2d');
  const labels = data.map(item => item.penalty_reason || 'Sin motivo');
  const values = data.map(item => parseInt(item.count));

  new Chart(ctx, {
    type: 'pie',
    data: {
      labels: labels,
      datasets: [{
        data: values,
        backgroundColor: [
          'rgba(255, 99, 132, 0.6)',
          'rgba(54, 162, 235, 0.6)',
          'rgba(255, 205, 86, 0.6)',
          'rgba(75, 192, 192, 0.6)',
          'rgba(153, 102, 255, 0.6)',
          'rgba(255, 159, 64, 0.6)'
        ],
        borderColor: [
          'rgba(255, 99, 132, 1)',
          'rgba(54, 162, 235, 1)',
          'rgba(255, 205, 86, 1)',
          'rgba(75, 192, 192, 1)',
          'rgba(153, 102, 255, 1)',
          'rgba(255, 159, 64, 1)'
        ],
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { position: 'right' },
        tooltip: {
          callbacks: {
            label: function(context) {
              const item = data[context.dataIndex];
              return [
                'Motivo: ' + (item.penalty_reason || 'Sin motivo'),
                'Cantidad: ' + item.count,
                'Monto Total: $' + parseFloat(item.total_amount).toLocaleString('es-CO')
              ];
            }
          }
        }
      }
    }
  });
}
</script>

<!-- Filtros y Exportación de Comisiones -->
<div class="card shadow mb-4">
  <div class="card-header py-3">
    <h6 class="m-0 font-weight-bold text-primary">Filtros y Exportación de Comisiones</h6>
  </div>
  <div class="card-body">
    <div class="form-row">
      <div class="form-group col-md-3">
        <label class="small mb-1" for="start_date">Fecha Inicio</label>
        <input type="date" class="form-control" id="start_date">
      </div>
      <div class="form-group col-md-3">
        <label class="small mb-1" for="end_date">Fecha Fin</label>
        <input type="date" class="form-control" id="end_date">
      </div>
      <div class="form-group col-md-3">
        <label class="small mb-1" for="collector_id">Cobrador</label>
        <select class="form-control" id="collector_id">
          <option value="">Todos los cobradores</option>
          <?php if (!empty($cobradores_list)) { foreach ($cobradores_list as $c) { ?>
            <option value="<?php echo $c->id; ?>"><?php echo htmlspecialchars($c->nombre); ?></option>
          <?php } } ?>
        </select>
      </div>
      <div class="form-group col-md-3 d-flex align-items-end">
        <div class="d-flex gap-2">
          <button id="btnExportExcel" class="btn btn-success">
            <i class="fa fa-file-excel"></i> Exportar Excel
          </button>
          <button id="btnExportPDF" class="btn btn-danger">
            <i class="fa fa-file-pdf"></i> Exportar PDF
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Selector de Usuario -->
<div class="row mb-4">
  <div class="col-12">
    <div class="card shadow-sm">
      <div class="card-header bg-primary text-white">
        <h6 class="m-0 font-weight-bold">Filtrar por Usuario Cobrador</h6>
      </div>
      <div class="card-body">
        <div class="form-row">
          <div class="form-group col-md-6">
            <label for="user_selector" class="font-weight-bold">Seleccionar Usuario:</label>
            <select class="form-control" id="user_selector">
              <option value="">Todos los usuarios</option>
              <?php
              $this->load->model('user_m');
              $active_users = $this->user_m->get_active_users();
              foreach ($active_users as $user) {
                echo '<option value="' . $user->id . '">' . htmlspecialchars($user->first_name . ' ' . $user->last_name) . '</option>';
              }
              ?>
            </select>
          </div>
          <div class="form-group col-md-6 d-flex align-items-end">
            <button type="button" class="btn btn-primary" id="btn_filter_user">
              <i class="fas fa-filter"></i> Aplicar Filtro
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Información Detallada por Usuario -->
<div class="row mb-4" id="user_details_section" style="display: none;">
  <div class="col-12">
    <div class="card shadow-sm border-left-info">
      <div class="card-header bg-info text-white">
        <h6 class="m-0 font-weight-bold">Información de Cobranzas por Usuario</h6>
      </div>
      <div class="card-body">
        <div id="user_collection_details">
          <!-- Los detalles se cargarán dinámicamente aquí -->
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Filtros de Fecha para Reportes Avanzados -->
<div class="row mb-4">
  <div class="col-12">
    <div class="card shadow">
      <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Filtros de Reportes Avanzados</h6>
      </div>
      <div class="card-body">
        <form method="GET" action="" id="advancedFiltersForm">
          <div class="form-row align-items-end">
            <div class="col-md-4 mb-2">
              <label for="start_date">Fecha de Inicio</label>
              <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date ?? ''); ?>">
            </div>
            <div class="col-md-4 mb-2">
              <label for="end_date">Fecha de Fin</label>
              <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date ?? ''); ?>">
            </div>
            <div class="col-md-4 mb-2">
              <button type="submit" class="btn btn-primary btn-block">Aplicar Filtros</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Análisis de Rendimiento por Categorías -->
<div class="row mb-4">
  <div class="col-12">
    <div class="card shadow">
      <div class="card-header py-3 bg-success text-white">
        <h6 class="m-0 font-weight-bold">📊 Análisis de Rendimiento por Categorías</h6>
      </div>
      <div class="card-body">
        <div class="row">
          <!-- Top Registradores de Clientes -->
          <div class="col-md-4">
            <div class="card border-left-success shadow-sm h-100">
              <div class="card-header py-2">
                <h6 class="m-0 font-weight-bold text-success">👥 Top Registradores</h6>
              </div>
              <div class="card-body">
                <div class="table-responsive">
                  <table class="table table-sm">
                    <thead class="table-success">
                      <tr>
                        <th>Usuario</th>
                        <th class="text-right">Clientes</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (!empty($user_performance['registrations'])) { ?>
                        <?php foreach (array_slice($user_performance['registrations'], 0, 5) as $user) { ?>
                        <tr>
                          <td><?php echo htmlspecialchars($user->user_name); ?></td>
                          <td class="text-right badge badge-success"><?php echo number_format($user->clients_registered, 0, ',', '.'); ?></td>
                        </tr>
                        <?php } ?>
                      <?php } else { ?>
                        <tr><td colspan="2" class="text-center text-muted">Sin datos</td></tr>
                      <?php } ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>

          <!-- Top Cobradores -->
          <div class="col-md-4">
            <div class="card border-left-info shadow-sm h-100">
              <div class="card-header py-2">
                <h6 class="m-0 font-weight-bold text-info">💰 Top Cobradores</h6>
              </div>
              <div class="card-body">
                <div class="table-responsive">
                  <table class="table table-sm">
                    <thead class="table-info">
                      <tr>
                        <th>Usuario</th>
                        <th class="text-right">Cobranzas</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (!empty($user_performance['collections'])) { ?>
                        <?php foreach (array_slice($user_performance['collections'], 0, 5) as $user) { ?>
                        <tr>
                          <td><?php echo htmlspecialchars($user->user_name); ?></td>
                          <td class="text-right badge badge-info"><?php echo number_format($user->collections_count, 0, ',', '.'); ?></td>
                        </tr>
                        <?php } ?>
                      <?php } else { ?>
                        <tr><td colspan="2" class="text-center text-muted">Sin datos</td></tr>
                      <?php } ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>

          <!-- Alertas de Cobranzas Pendientes -->
          <div class="col-md-4">
            <div class="card border-left-warning shadow-sm h-100">
              <div class="card-header py-2">
                <h6 class="m-0 font-weight-bold text-warning">⚠️ Pendientes por Cobrar</h6>
              </div>
              <div class="card-body">
                <div class="table-responsive">
                  <table class="table table-sm">
                    <thead class="table-warning">
                      <tr>
                        <th>Usuario</th>
                        <th class="text-right">Pendientes</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (!empty($user_performance['pending'])) { ?>
                        <?php foreach (array_slice($user_performance['pending'], 0, 5) as $user) { ?>
                        <tr>
                          <td><?php echo htmlspecialchars($user->user_name); ?></td>
                          <td class="text-right badge badge-warning"><?php echo number_format($user->pending_collections, 0, ',', '.'); ?></td>
                        </tr>
                        <?php } ?>
                      <?php } else { ?>
                        <tr><td colspan="2" class="text-center text-muted">Sin datos</td></tr>
                      <?php } ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>



<!-- Navegación entre módulos de reportes -->
<div class="row mb-4">
  <div class="col-12">
    <div class="card shadow">
      <div class="card-header py-3 bg-dark text-white">
        <h6 class="m-0 font-weight-bold">📊 Módulos de Reportes Disponibles</h6>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-3 mb-2">
            <a href="<?php echo base_url('admin/reports'); ?>" class="btn btn-outline-primary btn-block">
              <i class="fas fa-chart-bar"></i><br>Reportes Generales
            </a>
          </div>
          <div class="col-md-3 mb-2">
            <a href="<?php echo base_url('admin/reports/interest_commissions'); ?>" class="btn btn-outline-warning btn-block">
              <i class="fas fa-coins"></i><br>Comisiones 40% Intereses
            </a>
          </div>
          <div class="col-md-3 mb-2">
            <a href="<?php echo base_url('admin/customers/overdue'); ?>" class="btn btn-outline-danger btn-block">
              <i class="fas fa-exclamation-triangle"></i><br>Clientes Vencidos
            </a>
          </div>
          <div class="col-md-3 mb-2">
            <a href="<?php echo base_url('admin/dashboard'); ?>" class="btn btn-outline-success btn-block">
              <i class="fas fa-tachometer-alt"></i><br>Dashboard
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Estadísticas de Comisiones -->
<div class="row mb-4">
  <div class="col-xl-8 col-lg-7">
    <div class="card shadow-sm border-0">
      <div class="card-header bg-primary text-white fw-bold">
        Estadísticas de Comisiones (40%)
      </div>
      <div class="card-body">
        <canvas id="commissionChart" width="400" height="200"></canvas>
      </div>
    </div>
  </div>
  <div class="col-xl-4 col-lg-5">
    <div class="card border-0 shadow-sm p-3">
      <h6 class="fw-bold text-secondary mb-2">Totales Generales</h6>
      <p><strong>Total Cobrado:</strong> <span id="total_cobrado">$0</span></p>
      <p><strong>Total en Comisiones (40%):</strong> <span id="total_comisiones">$0</span></p>
      <p><strong>Número de Cobros:</strong> <span id="total_cobros">0</span></p>
      <hr>
      <a href="<?php echo base_url('admin/reports/interest_commissions'); ?>" class="btn btn-warning btn-sm btn-block">
        <i class="fas fa-coins"></i> Ver Comisiones de Intereses (40%)
      </a>
    </div>
  </div>
</div>

<!-- Tarjetas de Resumen de Cobranza -->
<?php if ($cobranza_totals): ?>
<div class="row mb-4">
  <div class="col-xl-3 col-md-6 mb-4">
    <div class="card border-left-primary shadow h-100 py-2">
      <div class="card-body">
        <div class="row no-gutters align-items-center">
          <div class="col mr-2">
            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Cobros</div>
            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $cobranza_totals['total_cobros']->total_cobros ?? 0; ?></div>
          </div>
          <div class="col-auto">
            <i class="fas fa-cash-register fa-2x text-gray-300"></i>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-xl-3 col-md-6 mb-4">
    <div class="card border-left-success shadow h-100 py-2">
      <div class="card-body">
        <div class="row no-gutters align-items-center">
          <div class="col mr-2">
            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Cobros por Usuario</div>
            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $cobranza_totals['cobros_por_usuario'] ?? 0; ?></div>
          </div>
          <div class="col-auto">
            <i class="fas fa-users fa-2x text-gray-300"></i>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-xl-3 col-md-6 mb-4">
    <div class="card border-left-info shadow h-100 py-2">
      <div class="card-body">
        <div class="row no-gutters align-items-center">
          <div class="col mr-2">
            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Pagos Completos</div>
            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $cobranza_totals['pagos_completos'] ?? 0; ?></div>
          </div>
          <div class="col-auto">
            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-xl-3 col-md-6 mb-4">
    <div class="card border-left-warning shadow h-100 py-2">
      <div class="card-body">
        <div class="row no-gutters align-items-center">
          <div class="col mr-2">
            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pagos Parciales</div>
            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $cobranza_totals['pagos_parciales'] ?? 0; ?></div>
          </div>
          <div class="col-auto">
            <i class="fas fa-clock fa-2x text-gray-300"></i>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Tarjeta de Resumen General -->
<?php if ($cobranza_totals): ?>
<div class="row mb-4">
  <div class="col-xl-12">
    <div class="card border-left-primary shadow h-100 py-2">
      <div class="card-body">
        <div class="row no-gutters align-items-center">
          <div class="col mr-2">
            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Resumen General de Cobranzas</div>
            <div class="row">
              <div class="col-md-3">
                <div class="text-xs text-muted">Total Cobros Realizados</div>
                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($cobranza_totals['total_cobros']->total_cobros ?? 0, 0, ',', '.'); ?></div>
              </div>
              <div class="col-md-3">
                <div class="text-xs text-muted">Total Intereses</div>
                <div class="h5 mb-0 font-weight-bold text-gray-800">$<?php echo number_format($cobranza_totals['total_cobros']->total_recaudado * 0.4 ?? 0, 2, ',', '.'); ?></div>
              </div>
              <div class="col-md-3">
                <div class="text-xs text-muted">Total Comisiones (40%)</div>
                <div class="h5 mb-0 font-weight-bold text-gray-800">$<?php echo number_format($cobranza_totals['total_cobros']->total_recaudado * 0.4 ?? 0, 2, ',', '.'); ?></div>
              </div>
              <div class="col-md-3">
                <div class="text-xs text-muted">Total Recaudado General</div>
                <div class="h5 mb-0 font-weight-bold text-gray-800">$<?php echo number_format($cobranza_totals['total_cobros']->total_recaudado ?? 0, 2, ',', '.'); ?></div>
              </div>
            </div>
          </div>
          <div class="col-auto">
            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Incluir Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@latest/dist/Chart.min.js"></script>

<!-- Script para gráficas -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Cargar datos de comisiones
  fetch(base_url + 'admin/reports/get_commission_stats')
    .then(response => response.json())
    .then(data => {
      if (data.stats && data.stats.length > 0) {
        renderCommissionChart(data.stats);
        updateTotals(data.totals);
      }
    })
    .catch(error => console.error('Error cargando estadísticas:', error));

  function renderCommissionChart(stats) {
    const ctx = document.getElementById('commissionChart').getContext('2d');
    const labels = stats.map(item => item.user_name);
    const commissions = stats.map(item => parseFloat(item.total_commission));

    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [{
          label: 'Comisiones (40%)',
          data: commissions,
          backgroundColor: 'rgba(54, 162, 235, 0.6)',
          borderColor: 'rgba(54, 162, 235, 1)',
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: 'top',
          },
          tooltip: {
            callbacks: {
              label: function(context) {
                const stat = stats[context.dataIndex];
                return [
                  'Comisión: $' + parseFloat(stat.total_commission).toLocaleString('es-CO'),
                  'Total Cobrado: $' + parseFloat(stat.total_amount).toLocaleString('es-CO'),
                  'N° Cobros: ' + stat.num_cobros
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

  function updateTotals(totals) {
    if (totals) {
      document.getElementById('total_cobrado').textContent = '$' + parseFloat(totals.total_amount || 0).toLocaleString('es-CO');
      document.getElementById('total_comisiones').textContent = '$' + parseFloat(totals.total_commission || 0).toLocaleString('es-CO');
      document.getElementById('total_cobros').textContent = totals.total_cobros || 0;
    }
  }

  // Exportación de comisiones
  document.getElementById('btnExportExcel').addEventListener('click', function() {
    const start = document.getElementById('start_date').value;
    const end = document.getElementById('end_date').value;
    const collector = document.getElementById('collector_id').value;
    window.open(base_url + 'admin/reports/export_commissions_excel?start_date=' + start + '&end_date=' + end + '&collector_id=' + collector, '_blank');
  });

  document.getElementById('btnExportPDF').addEventListener('click', function() {
    const start = document.getElementById('start_date').value;
    const end = document.getElementById('end_date').value;
    const collector = document.getElementById('collector_id').value;
    window.open(base_url + 'admin/reports/export_commissions_pdf?start_date=' + start + '&end_date=' + end + '&collector_id=' + collector, '_blank');
  });

  // Funcionalidad de recomendaciones
  document.addEventListener('click', function(e) {
    if (e.target.classList.contains('apply-penalties-btn')) {
      const userId = e.target.getAttribute('data-user-id');
      const riskLevel = e.target.getAttribute('data-risk-level');

      if (confirm('¿Está seguro de aplicar penalizaciones automáticas a clientes de ' + riskLevel + ' riesgo?')) {
        fetch(base_url + 'admin/reports/apply_bulk_penalties', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: 'user_id=' + userId + '&risk_level=' + riskLevel
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            alert('Penalizaciones aplicadas exitosamente: ' + data.applied_count + ' clientes');
            location.reload();
          } else {
            alert('Error: ' + data.error);
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Error al procesar la solicitud');
        });
      }
    }

    if (e.target.classList.contains('reassign-clients-btn')) {
      const fromUsers = e.target.getAttribute('data-from-users');

      if (confirm('¿Está seguro de redistribuir clientes de usuarios de bajo rendimiento?')) {
        fetch(base_url + 'admin/reports/reassign_clients', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: 'from_user_id=' + fromUsers.split(',')[0] + '&to_user_id=' + '<?php echo $this->session->userdata('user_id'); ?>' + '&client_count=3'
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            alert('Clientes reasignados exitosamente: ' + data.reassigned_count + ' clientes');
            location.reload();
          } else {
            alert('Error: ' + data.error);
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Error al procesar la solicitud');
        });
      }
    }
  });

  // Evento para el selector de usuario
  document.getElementById('btn_filter_user').addEventListener('click', function() {
    const userId = document.getElementById('user_selector').value;
    loadUserDetails(userId);
    loadChartData(userId);
  });

  // Cargar datos iniciales para gráficos (sin filtro)
  loadChartData();
});

function loadChartData(userId = null) {
  const userParam = userId ? '&user_id=' + userId : '';

  // Gráfico de pagos por cliente
  fetch(base_url + 'admin/reports/get_chart_data?type=payments_by_customer' + userParam)
    .then(response => response.json())
    .then(data => {
      if (data && data.length > 0) {
        renderPaymentsByCustomerChart(data);
      } else {
        // Mostrar mensaje cuando no hay datos
        const ctx = document.getElementById('paymentsByCustomerChart');
        if (ctx) {
          const chartCanvas = ctx.getContext('2d');
          chartCanvas.clearRect(0, 0, ctx.width, ctx.height);
          chartCanvas.fillStyle = '#999';
          chartCanvas.font = '16px Arial';
          chartCanvas.textAlign = 'center';
          chartCanvas.fillText('No hay datos disponibles', ctx.width / 2, ctx.height / 2);
        }
      }
    })
    .catch(error => console.error('Error cargando pagos por cliente:', error));

  // Gráfico de top cobradores
  fetch(base_url + 'admin/reports/get_chart_data?type=top_collectors' + userParam)
    .then(response => response.json())
    .then(data => {
      if (data && data.length > 0) {
        renderTopCollectorsChart(data);
      } else {
        // Mostrar mensaje cuando no hay datos
        const ctx = document.getElementById('topCollectorsChart');
        if (ctx) {
          const chartCanvas = ctx.getContext('2d');
          chartCanvas.clearRect(0, 0, ctx.width, ctx.height);
          chartCanvas.fillStyle = '#999';
          chartCanvas.font = '16px Arial';
          chartCanvas.textAlign = 'center';
          chartCanvas.fillText('No hay datos disponibles', ctx.width / 2, ctx.height / 2);
        }
      }
    })
    .catch(error => console.error('Error cargando top cobradores:', error));

  // Gráfico de rachas
  fetch(base_url + 'admin/reports/get_chart_data?type=streaks')
    .then(response => response.json())
    .then(data => {
      if (data && Object.keys(data).length > 0) {
        renderStreakChart(data);
      }
    })
    .catch(error => console.error('Error cargando rachas:', error));

  // Gráfico de castigos
  fetch(base_url + 'admin/reports/get_chart_data?type=penalties')
    .then(response => response.json())
    .then(data => {
      if (data && data.length > 0) {
        renderPenaltiesChart(data);
      }
    })
    .catch(error => console.error('Error cargando castigos:', error));
}

// Función para cargar detalles de usuario
function loadUserDetails(userId) {
  if (!userId) {
    document.getElementById('user_details_section').style.display = 'none';
    return;
  }

  fetch(base_url + 'admin/reports/get_user_collection_details?user_id=' + userId)
    .then(response => response.json())
    .then(data => {
      if (data.error) {
        console.error('Error:', data.error);
        return;
      }

      renderUserDetails(data.collections, data.progress);
      document.getElementById('user_details_section').style.display = 'block';
    })
    .catch(error => console.error('Error cargando detalles de usuario:', error));
}

// Función para renderizar detalles de usuario
function renderUserDetails(collections, progress) {
  const container = document.getElementById('user_collection_details');

  if (!collections || collections.length === 0) {
    container.innerHTML = '<div class="alert alert-info">No hay datos de cobranzas para este usuario.</div>';
    return;
  }

  const user = collections[0];
  let html = `
    <div class="row mb-4">
      <div class="col-md-6">
        <h5>Resumen de Cobranzas</h5>
        <p><strong>Usuario:</strong> ${user.user_name}</p>
        <p><strong>Total de cuotas manejadas:</strong> ${user.total_quotas_collected || 0}</p>
        <p><strong>Cuotas cobradas:</strong> ${user.quotas_paid || 0}</p>
        <p><strong>Cuotas pendientes:</strong> ${user.quotas_pending || 0}</p>
        <p><strong>Total recaudado:</strong> $${parseFloat(user.total_amount_collected || 0).toLocaleString('es-CO')}</p>
      </div>
      <div class="col-md-6">
        <h5>Progreso por Préstamo</h5>
        <div class="table-responsive">
          <table class="table table-sm table-striped">
            <thead>
              <tr>
                <th>Cliente</th>
                <th>Cédula</th>
                <th>Cuotas</th>
                <th>Progreso</th>
              </tr>
            </thead>
            <tbody>`;

  if (progress && progress.length > 0) {
    progress.forEach(item => {
      const progressPercent = item.total_quotas > 0 ? Math.round((item.paid_quotas / item.total_quotas) * 100) : 0;
      html += `
        <tr>
          <td>${item.customer_name}</td>
          <td>${item.customer_dni}</td>
          <td>${item.paid_quotas}/${item.total_quotas}</td>
          <td>
            <div class="progress" style="width: 100px;">
              <div class="progress-bar bg-success" role="progressbar" style="width: ${progressPercent}%">
                ${progressPercent}%
              </div>
            </div>
          </td>
        </tr>`;
    });
  } else {
    html += '<tr><td colspan="4">No hay préstamos asociados</td></tr>';
  }

  html += `
            </tbody>
          </table>
        </div>
      </div>
    </div>`;

  container.innerHTML = html;
}

function renderPaymentsByCustomerChart(data) {
  const ctx = document.getElementById('paymentsByCustomerChart').getContext('2d');
  const labels = data.map(item => item.customer_name.length > 15 ? item.customer_name.substring(0, 15) + '...' : item.customer_name);
  const values = data.map(item => parseInt(item.payments_count));

  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [{
        label: 'Número de Pagos',
        data: values,
        backgroundColor: 'rgba(54, 162, 235, 0.6)',
        borderColor: 'rgba(54, 162, 235, 1)',
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
              const item = data[context.dataIndex];
              return [
                'Cliente: ' + item.customer_name,
                'Pagos: ' + item.payments_count,
                'Total: $' + parseFloat(item.total_paid).toLocaleString('es-CO')
              ];
            }
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: { precision: 0 }
        }
      }
    }
  });
}

function renderTopCollectorsChart(data) {
  const ctx = document.getElementById('topCollectorsChart').getContext('2d');
  const labels = data.map(item => item.user_name.length > 15 ? item.user_name.substring(0, 15) + '...' : item.user_name);
  const values = data.map(item => parseInt(item.payments_count));

  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [{
        label: 'Número de Cobranzas',
        data: values,
        backgroundColor: 'rgba(255, 99, 132, 0.6)',
        borderColor: 'rgba(255, 99, 132, 1)',
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
              const item = data[context.dataIndex];
              return [
                'Cobrador: ' + item.user_name,
                'Cobranzas: ' + item.payments_count
              ];
            }
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: { precision: 0 }
        }
      }
    }
  });
}

function renderStreakChart(data) {
  const ctx = document.getElementById('streakChart').getContext('2d');
  const labels = Object.keys(data);
  const values = Object.values(data);

  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [{
        label: 'Racha Máxima (cuotas seguidas)',
        data: values,
        backgroundColor: 'rgba(75, 192, 192, 0.6)',
        borderColor: 'rgba(75, 192, 192, 1)',
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
              return 'Racha: ' + values[context.dataIndex] + ' cuotas seguidas';
            }
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: { precision: 0 }
        }
      }
    }
  });
}

function renderPenaltiesChart(data) {
  const ctx = document.getElementById('penaltiesChart').getContext('2d');
  const labels = data.map(item => item.penalty_reason || 'Sin motivo');
  const values = data.map(item => parseInt(item.count));

  new Chart(ctx, {
    type: 'pie',
    data: {
      labels: labels,
      datasets: [{
        data: values,
        backgroundColor: [
          'rgba(255, 99, 132, 0.6)',
          'rgba(54, 162, 235, 0.6)',
          'rgba(255, 205, 86, 0.6)',
          'rgba(75, 192, 192, 0.6)',
          'rgba(153, 102, 255, 0.6)',
          'rgba(255, 159, 64, 0.6)'
        ],
        borderColor: [
          'rgba(255, 99, 132, 1)',
          'rgba(54, 162, 235, 1)',
          'rgba(255, 205, 86, 1)',
          'rgba(75, 192, 192, 1)',
          'rgba(153, 102, 255, 1)',
          'rgba(255, 159, 64, 1)'
        ],
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { position: 'right' },
        tooltip: {
          callbacks: {
            label: function(context) {
              const item = data[context.dataIndex];
              return [
                'Motivo: ' + (item.penalty_reason || 'Sin motivo'),
                'Cantidad: ' + item.count,
                'Monto Total: $' + parseFloat(item.total_amount).toLocaleString('es-CO')
              ];
            }
          }
        }
      }
    }
  });
}
</script>

<!-- Filtros y Exportación de Comisiones -->
<div class="card shadow mb-4">
  <div class="card-header py-3">
    <h6 class="m-0 font-weight-bold text-primary">Filtros y Exportación de Comisiones</h6>
  </div>
  <div class="card-body">
    <div class="form-row">
      <div class="form-group col-md-3">
        <label class="small mb-1" for="start_date">Fecha Inicio</label>
        <input type="date" class="form-control" id="start_date">
      </div>
      <div class="form-group col-md-3">
        <label class="small mb-1" for="end_date">Fecha Fin</label>
        <input type="date" class="form-control" id="end_date">
      </div>
      <div class="form-group col-md-3">
        <label class="small mb-1" for="collector_id">Cobrador</label>
        <select class="form-control" id="collector_id">
          <option value="">Todos los cobradores</option>
          <?php if (!empty($cobradores_list)) { foreach ($cobradores_list as $c) { ?>
            <option value="<?php echo $c->id; ?>"><?php echo htmlspecialchars($c->nombre); ?></option>
          <?php } } ?>
        </select>
      </div>
      <div class="form-group col-md-3 d-flex align-items-end">
        <div class="d-flex gap-2">
          <button id="btnExportExcel" class="btn btn-success">
            <i class="fa fa-file-excel"></i> Exportar Excel
          </button>
          <button id="btnExportPDF" class="btn btn-danger">
            <i class="fa fa-file-pdf"></i> Exportar PDF
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

    <!-- Gráfico: Pagos por Cliente -->
    <div class="mt-4">
      <h6 class="m-0 font-weight-bold text-primary">Pagos por Cliente (Top 10)</h6>
      <div class="row">
        <div class="col-md-8">
          <canvas id="paymentsByCustomerChart" width="400" height="200"></canvas>
        </div>
        <div class="col-md-4">
          <div class="table-responsive mt-2">
            <table class="table table-striped table-bordered table-sm" id="tbl_payments_by_customer">
              <thead class="thead-dark">
                <tr>
                  <th>Cliente</th>
                  <th class="text-right"># Pagos</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($per_customer_payments)) { foreach (array_slice($per_customer_payments, 0, 10) as $row) { ?>
                <tr>
                  <td><?php echo htmlspecialchars($row->customer_name); ?></td>
                  <td class="text-right"><?php echo (int)$row->payments_count; ?></td>
                </tr>
                <?php } } ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Gráfico: Usuarios con más cobranzas -->
    <div class="mt-4">
      <h6 class="m-0 font-weight-bold text-primary">Usuarios con más Cobranzas (Top 10)</h6>
      <div class="row">
        <div class="col-md-8">
          <canvas id="topCollectorsChart" width="400" height="200"></canvas>
        </div>
        <div class="col-md-4">
          <div class="table-responsive mt-2">
            <table class="table table-striped table-bordered table-sm" id="tbl_top_collectors">
              <thead class="thead-dark">
                <tr>
                  <th>Usuario</th>
                  <th class="text-right"># Cobranzas</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($top_collectors)) { foreach (array_slice($top_collectors, 0, 10) as $row) { ?>
                <tr>
                  <td><?php echo htmlspecialchars($row->user_name); ?></td>
                  <td class="text-right"><?php echo (int)$row->payments_count; ?></td>
                </tr>
                <?php } } ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Gráfico: Cliente con mayor racha de pagos -->
    <div class="mt-4">
      <h6 class="m-0 font-weight-bold text-primary">Clientes con Mayor Racha de Pagos (Top 5)</h6>
      <div class="row">
        <div class="col-md-8">
          <canvas id="streakChart" width="400" height="200"></canvas>
        </div>
        <div class="col-md-4">
          <div class="table-responsive mt-2">
            <table class="table table-striped table-bordered table-sm" id="tbl_longest_streak">
              <thead class="thead-dark">
                <tr>
                  <th>Cliente</th>
                  <th class="text-right">Racha Máxima</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($longest_streak)) { ?>
                <tr>
                  <td><?php echo htmlspecialchars($longest_streak['customer_name']); ?></td>
                  <td class="text-right"><?php echo (int)$longest_streak['max_streak']; ?></td>
                </tr>
                <?php } else { ?>
                <tr>
                  <td colspan="2">Sin datos de pagos registrados.</td>
                </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  </div>

  <!-- Gráfico: Castigos Registrados -->
  <div class="mt-4">
    <h6 class="m-0 font-weight-bold text-primary">Distribución de Castigos por Motivo</h6>
    <div class="row">
      <div class="col-md-8">
        <canvas id="penaltiesChart" width="400" height="200"></canvas>
      </div>
      <div class="col-md-4">
        <div class="table-responsive mt-2">
          <table class="table table-striped table-bordered table-sm" id="tbl_penalties">
            <thead class="thead-dark">
              <tr>
                <th>Motivo</th>
                <th class="text-right">Cantidad</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($penalties)) {
                $penalty_counts = [];
                foreach ($penalties as $p) {
                  $reason = $p->penalty_reason ?: 'Sin motivo';
                  if (!isset($penalty_counts[$reason])) {
                    $penalty_counts[$reason] = 0;
                  }
                  $penalty_counts[$reason]++;
                }
                foreach ($penalty_counts as $reason => $count) { ?>
              <tr>
                <td><?php echo htmlspecialchars($reason); ?></td>
                <td class="text-right"><?php echo $count; ?></td>
              </tr>
              <?php } } else { ?>
              <tr>
                <td colspan="2">No hay castigos registrados.</td>
              </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>