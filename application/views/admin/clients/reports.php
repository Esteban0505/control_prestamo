<div class="container-fluid mt-4">
  <h3 class="mb-3 text-center">📊 Reportes de Mora y Recuperación</h3>

  <!-- Controles de Reporte -->
  <div class="card shadow mb-4">
    <div class="card-header bg-info text-white">
      <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Generar Reportes</h5>
    </div>
    <div class="card-body">
      <form id="reportForm" method="POST">
        <div class="row">
          <div class="col-md-3">
            <div class="form-group">
              <label for="start_date">Fecha Inicio:</label>
              <input type="date" class="form-control" id="start_date" name="start_date" value="<?= date('Y-m-01') ?>">
            </div>
          </div>
          <div class="col-md-3">
            <div class="form-group">
              <label for="end_date">Fecha Fin:</label>
              <input type="date" class="form-control" id="end_date" name="end_date" value="<?= date('Y-m-d') ?>">
            </div>
          </div>
          <div class="col-md-3">
            <div class="form-group">
              <label>Tipo de Reporte:</label><br>
              <div class="btn-group btn-group-toggle" data-toggle="buttons">
                <label class="btn btn-outline-primary active">
                  <input type="radio" name="report_type" value="summary" checked> Resumen Ejecutivo
                </label>
                <label class="btn btn-outline-primary">
                  <input type="radio" name="report_type" value="detailed"> Detallado
                </label>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="form-group">
              <label>Acciones:</label><br>
              <button type="button" class="btn btn-success btn-sm" onclick="generateReport()">
                <i class="fas fa-file-excel"></i> Generar Excel
              </button>
              <button type="button" class="btn btn-danger btn-sm" onclick="generatePDF()">
                <i class="fas fa-file-pdf"></i> Generar PDF
              </button>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- KPIs Principales -->
  <div class="row mb-4">
    <div class="col-md-3">
      <div class="card bg-danger text-white">
        <div class="card-body text-center">
          <h5 class="card-title">👥 Clientes Morosos</h5>
          <h3 id="total-clients">0</h3>
          <p class="mb-0">Total activos</p>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card bg-warning text-white">
        <div class="card-body text-center">
          <h5 class="card-title">💰 Monto Adeudado</h5>
          <h3 id="total-amount">$0.00</h3>
          <p class="mb-0">En riesgo</p>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card bg-info text-white">
        <div class="card-body text-center">
          <h5 class="card-title">📈 Tasa Recuperación</h5>
          <h3 id="recovery-rate">0.00%</h3>
          <p class="mb-0">Últimos 30 días</p>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card bg-success text-white">
        <div class="card-body text-center">
          <h5 class="card-title">⏱️ Promedio Atraso</h5>
          <h3 id="avg-days">0</h3>
          <p class="mb-0">Días promedio</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Gráficos -->
  <div class="row mb-4">
    <div class="col-md-6">
      <div class="card shadow">
        <div class="card-header">
          <h5 class="mb-0">📈 Tendencias Mensuales de Mora</h5>
        </div>
        <div class="card-body">
          <canvas id="trendsChart" width="400" height="200"></canvas>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card shadow">
        <div class="card-header">
          <h5 class="mb-0">🎯 Distribución por Nivel de Riesgo</h5>
        </div>
        <div class="card-body">
          <canvas id="riskChart" width="400" height="200"></canvas>
        </div>
      </div>
    </div>
  </div>

  <!-- Top Clientes Morosos -->
  <div class="card shadow mb-4">
    <div class="card-header bg-warning text-white">
      <h5 class="mb-0"><i class="fas fa-trophy"></i> Top 10 Clientes con Mayor Mora</h5>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-striped">
          <thead>
            <tr>
              <th>#</th>
              <th>Cliente</th>
              <th>Cédula</th>
              <th>Monto Adeudado</th>
              <th>Días Atraso</th>
              <th>Cuotas Vencidas</th>
            </tr>
          </thead>
          <tbody id="topClientsTable">
            <!-- Datos cargados dinámicamente -->
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Resumen Ejecutivo -->
  <div class="card shadow">
    <div class="card-header bg-primary text-white">
      <h5 class="mb-0"><i class="fas fa-clipboard-list"></i> Resumen Ejecutivo</h5>
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-6">
          <h6>📊 Estadísticas Generales</h6>
          <ul class="list-unstyled">
            <li><strong>Total de clientes morosos:</strong> <span id="summary-clients">0</span></li>
            <li><strong>Monto total adeudado:</strong> $<span id="summary-amount">0.00</span></li>
            <li><strong>Promedio de días de atraso:</strong> <span id="summary-avg-days">0</span> días</li>
            <li><strong>Tasa de recuperación (30 días):</strong> <span id="summary-recovery">0.00%</span></li>
          </ul>
        </div>
        <div class="col-md-6">
          <h6>🎯 Recomendaciones</h6>
          <div id="recommendations">
            <p class="text-muted">Cargando recomendaciones...</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Scripts para Gráficos -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function(){
  loadReportData();
});

function loadReportData() {
  // Cargar datos para KPIs
  $.ajax({
    url: '<?= site_url("admin/customers/get_report_data") ?>',
    type: 'POST',
    success: function(response) {
      if (response.success) {
        updateKPIs(response.data);
        updateCharts(response.data);
        updateTopClients(response.data.top_clients);
        updateSummary(response.data);
        generateRecommendations(response.data);
      }
    },
    error: function() {
      alert('Error al cargar datos del reporte');
    }
  });
}

function updateKPIs(data) {
  $('#total-clients').text(data.total_clients || 0);
  $('#total-amount').text('$' + (data.total_amount || 0).toLocaleString('es-CO', {minimumFractionDigits: 2}));
  $('#recovery-rate').text((data.recovery_rate || 0).toFixed(2) + '%');
  $('#avg-days').text(Math.round(data.avg_days_overdue || 0));
}

function updateCharts(data) {
  // Gráfico de tendencias mensuales
  const trendsCtx = document.getElementById('trendsChart').getContext('2d');
  new Chart(trendsCtx, {
    type: 'line',
    data: {
      labels: data.monthly_trends.map(item => item.month),
      datasets: [{
        label: 'Clientes Morosos',
        data: data.monthly_trends.map(item => item.clients_count),
        borderColor: 'rgb(255, 99, 132)',
        backgroundColor: 'rgba(255, 99, 132, 0.2)',
        tension: 0.1
      }]
    },
    options: {
      responsive: true,
      scales: {
        y: {
          beginAtZero: true
        }
      }
    }
  });

  // Gráfico de distribución por riesgo
  const riskCtx = document.getElementById('riskChart').getContext('2d');
  new Chart(riskCtx, {
    type: 'doughnut',
    data: {
      labels: data.risk_distribution.map(item => item.risk_level),
      datasets: [{
        data: data.risk_distribution.map(item => item.clients_count),
        backgroundColor: [
          'rgb(255, 99, 132)',   // Alto riesgo
          'rgb(255, 205, 86)',   // Medio riesgo
          'rgb(54, 162, 235)'    // Bajo riesgo
        ]
      }]
    },
    options: {
      responsive: true
    }
  });
}

function updateTopClients(clients) {
  let html = '';
  clients.forEach((client, index) => {
    html += `
      <tr>
        <td>${index + 1}</td>
        <td>${client.client_name}</td>
        <td>${client.client_cedula}</td>
        <td>$${parseFloat(client.total_adeudado).toLocaleString('es-CO', {minimumFractionDigits: 2})}</td>
        <td>${client.max_dias_atraso} días</td>
        <td>${client.cuotas_vencidas}</td>
      </tr>
    `;
  });
  $('#topClientsTable').html(html);
}

function updateSummary(data) {
  $('#summary-clients').text(data.total_clients || 0);
  $('#summary-amount').text((data.total_amount || 0).toLocaleString('es-CO', {minimumFractionDigits: 2}));
  $('#summary-avg-days').text(Math.round(data.avg_days_overdue || 0));
  $('#summary-recovery').text((data.recovery_rate || 0).toFixed(2) + '%');
}

function generateRecommendations(data) {
  let recommendations = [];

  if (data.total_clients > 50) {
    recommendations.push('🚨 Alto volumen de clientes morosos. Considerar intensificar esfuerzos de cobranza.');
  }

  if (data.recovery_rate < 20) {
    recommendations.push('📉 Tasa de recuperación baja. Revisar estrategias de cobranza y comunicación.');
  }

  if (data.avg_days_overdue > 45) {
    recommendations.push('⏰ Promedio de atraso elevado. Implementar recordatorios automáticos más frecuentes.');
  }

  const highRiskCount = data.risk_distribution.find(item => item.risk_level.includes('Alto'))?.clients_count || 0;
  if (highRiskCount > 10) {
    recommendations.push('🔴 Muchos clientes de alto riesgo. Priorizar acciones de recuperación urgentes.');
  }

  if (recommendations.length === 0) {
    recommendations.push('✅ Situación de mora bajo control. Continuar con las estrategias actuales.');
  }

  const html = recommendations.map(rec => `<p>${rec}</p>`).join('');
  $('#recommendations').html(html);
}

function generateReport() {
  const formData = new FormData(document.getElementById('reportForm'));
  formData.append('report_type', $('input[name="report_type"]:checked').val());

  // Mostrar indicador de carga
  const btn = event.target.closest('button');
  const originalText = btn.innerHTML;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando...';
  btn.disabled = true;

  $.ajax({
    url: '<?= site_url("admin/customers/export_overdue_report") ?>',
    type: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    success: function(response) {
      if (response.success) {
        window.open(response.file_url, '_blank');
      } else {
        alert('Error al generar el reporte: ' + response.error);
      }
    },
    error: function() {
      alert('Error de conexión. Intente nuevamente.');
    },
    complete: function() {
      btn.innerHTML = originalText;
      btn.disabled = false;
    }
  });
}

function generatePDF() {
  alert('Funcionalidad de PDF próximamente disponible. Use Excel por ahora.');
}
</script>