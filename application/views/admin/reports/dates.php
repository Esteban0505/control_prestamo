<!-- Vista del Administrador - Estado de Envíos de Comisiones -->
<div class="card shadow mb-4">
  <div class="card-header py-3">
    <h6 class="m-0 font-weight-bold text-primary">
      <i class="fas fa-user-shield"></i> 📊 Reporte de Comisiones por Fechas
    </h6>
    <p class="mb-0 text-muted small">
      Revisa qué cobradores han enviado su 40% y cuáles están pendientes
    </p>
  </div>
  <div class="card-body">
    <!-- Información informativa -->
    <div class="alert alert-info mb-4">
      <h6><i class="fas fa-info-circle"></i> 💼 Panel de Control Administrativo</h6>
      <p class="mb-0">Aquí puedes ver el estado de envío de comisiones del 40% de todos los cobradores. Los estados se actualizan automáticamente cuando los cobradores marcan sus envíos como completados.</p>
    </div>

    <!-- Filtros -->
    <div class="row mb-4">
      <div class="col-md-4">
        <label for="collector_filter" class="form-label">Filtrar por Cobrador:</label>
        <select class="form-control" id="collector_filter">
          <option value="">Todos los cobradores</option>
          <?php foreach ($cobradores_list as $cobrador): ?>
            <option value="<?php echo $cobrador->id; ?>" <?php echo ($collector_id == $cobrador->id) ? 'selected' : ''; ?>>
              <?php echo isset($cobrador->first_name) && isset($cobrador->last_name) ? $cobrador->first_name . ' ' . $cobrador->last_name : (isset($cobrador->nombre) ? $cobrador->nombre : 'Usuario sin nombre'); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label for="start_date" class="form-label">Fecha Inicio:</label>
        <input type="date" class="form-control" id="start_date" value="<?php echo $start_date; ?>">
      </div>
      <div class="col-md-3">
        <label for="end_date" class="form-label">Fecha Fin:</label>
        <input type="date" class="form-control" id="end_date" value="<?php echo $end_date; ?>">
      </div>
      <div class="col-md-2 d-flex align-items-end">
        <button class="btn btn-primary w-100" id="filter_btn">
          <i class="fas fa-search"></i> Filtrar
        </button>
      </div>
    </div>


    <!-- Tabla de Estado de Envíos -->
    <div class="table-responsive">
      <table class="table table-bordered" id="commissionsTable" width="100%" cellspacing="0">
        <thead class="table-dark">
          <tr>
            <th>Cobrador</th>
            <th class="text-right">Interés Total</th>
            <th class="text-right">Comisión 40%</th>
            <th class="text-center">Estado Envío</th>
            <th class="text-center">Fecha Último Pago</th>
            <th class="text-center">Acciones</th>
          </tr>
        </thead>
        <tbody id="commissionsTableBody">
          <!-- Los datos se cargarán dinámicamente por AJAX -->
        </tbody>
      </table>
    </div>

    <!-- Resumen general -->
    <div class="row mt-4">
      <div class="col-md-12">
        <div class="card border-primary">
          <div class="card-body">
            <h6 class="card-title text-primary">
              <i class="fas fa-chart-bar"></i> Resumen General del Período
            </h6>
            <div class="row">
              <div class="col-sm-3">
                <strong>Total Cobradores:</strong><br>
                <span id="total_collectors" class="h5">0</span>
              </div>
              <div class="col-sm-3">
                <strong>Envíos Completados:</strong><br>
                <span id="completed_sends" class="text-success h5">0</span>
              </div>
              <div class="col-sm-3">
                <strong>Pendientes:</strong><br>
                <span id="pending_sends" class="text-warning h5">0</span>
              </div>
              <div class="col-sm-3">
                <strong>Total a Pagar:</strong><br>
                <span id="total_to_pay" class="text-primary h5">$0</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal para detalles -->
<div class="modal fade" id="detailsModal" tabindex="-1" role="dialog" aria-labelledby="detailsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="detailsModalLabel">Detalles de Comisiones</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="detailsModalBody">
        <!-- Contenido dinámico -->
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  console.log('Página cargada, inicializando...');
  // Cargar datos iniciales
  loadCommissionData();

  // Evento para filtrar
  document.getElementById('filter_btn').addEventListener('click', function() {
    console.log('Botón filtrar clickeado');
    loadCommissionData();
  });

  // También permitir filtrado con Enter en los campos de fecha
  document.getElementById('start_date').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') loadCommissionData();
  });
  document.getElementById('end_date').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') loadCommissionData();
  });
  document.getElementById('collector_filter').addEventListener('change', function() {
    loadCommissionData();
  });

  // Función para cargar datos de estado de envíos
  function loadCommissionData() {
    const collectorId = document.getElementById('collector_filter').value;
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;

    console.log('Cargando datos con:', { collectorId, startDate, endDate });

    // Mostrar indicador de carga
    const filterBtn = document.getElementById('filter_btn');
    const originalText = filterBtn.innerHTML;
    filterBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cargando...';
    filterBtn.disabled = true;

    fetch('<?php echo site_url('admin/reports/get_collector_commissions_summary'); ?>?' + new URLSearchParams({
      collector_id: collectorId || '',
      start_date: startDate || '',
      end_date: endDate || ''
    }), {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    })
    .then(response => {
      console.log('Respuesta HTTP:', response.status, response.statusText);
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }
      return response.json();
    })
    .then(response => {
      console.log('Datos recibidos:', response);

      if (response.error) {
        alert('Error: ' + response.error);
        return;
      }

      updateTable(response.collectors || []);
      updateSummary(response.summary || {});
    })
    .catch(error => {
      console.error('Error completo:', error);
      alert('Error al cargar los datos: ' + error.message);
    })
    .finally(() => {
      // Restaurar botón
      filterBtn.innerHTML = originalText;
      filterBtn.disabled = false;
    });
  }

  // Función para actualizar tabla
  function updateTable(collectors) {
    let html = '';
    if (collectors && collectors.length > 0) {
      collectors.forEach(function(collector) {
        html += `
          <tr>
            <td>${collector.collector_name || ''}</td>
            <td class="text-right">${collector.total_interest_formatted || '$0'}</td>
            <td class="text-right">${collector.commission_40_formatted || '$0'}</td>
            <td class="text-center">${collector.status_badge || '<span class="badge badge-warning">Pendiente</span>'}</td>
            <td class="text-center">${collector.last_payment_date ? new Date(collector.last_payment_date).toLocaleDateString('es-CO') : 'N/A'}</td>
            <td class="text-center">
              <button class="btn btn-sm btn-info view-details" data-collector-id="${collector.collector_id}" data-collector-name="${collector.collector_name}">
                <i class="fas fa-eye"></i> Ver
              </button>
            </td>
          </tr>
        `;
      });
    } else {
      html = '<tr><td colspan="6" class="text-center">No hay datos disponibles para el período seleccionado</td></tr>';
    }
    document.getElementById('commissionsTableBody').innerHTML = html;

    // Agregar event listeners para los botones "Ver"
    document.querySelectorAll('.view-details').forEach(button => {
      button.addEventListener('click', function() {
        const collectorId = this.getAttribute('data-collector-id');
        const collectorName = this.getAttribute('data-collector-name');
        showCollectorDetails(collectorId, collectorName);
      });
    });
  }

  // Función para actualizar resumen
  function updateSummary(summary) {
    document.getElementById('total_collectors').textContent = summary.total_collectors || 0;
    document.getElementById('completed_sends').textContent = summary.completed_sends || 0;
    document.getElementById('pending_sends').textContent = summary.pending_sends || 0;
    document.getElementById('total_to_pay').textContent = '$' + formatNumber(summary.total_to_pay || 0);
  }

  // Función para mostrar detalles del cobrador
  function showCollectorDetails(collectorId, collectorName) {
    console.log('Mostrando detalles para cobrador:', collectorId, collectorName);

    // Actualizar título del modal
    document.getElementById('detailsModalLabel').textContent = `Detalles de Comisiones - ${collectorName}`;

    // Mostrar modal con indicador de carga
    document.getElementById('detailsModalBody').innerHTML = `
      <div class="text-center">
        <i class="fas fa-spinner fa-spin fa-2x"></i>
        <p class="mt-2">Cargando detalles...</p>
      </div>
    `;
    $('#detailsModal').modal('show');

    // Obtener fechas actuales para el filtro
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;

    // Hacer petición AJAX para obtener detalles
    fetch('<?php echo site_url('admin/reports/get_user_interest_details'); ?>?' + new URLSearchParams({
      user_id: collectorId,
      start_date: startDate || '',
      end_date: endDate || ''
    }), {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    })
    .then(response => {
      console.log('Respuesta detalles HTTP:', response.status, response.statusText);
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }
      return response.json();
    })
    .then(data => {
      console.log('Datos de detalles recibidos:', data);

      if (data.error) {
        document.getElementById('detailsModalBody').innerHTML = `
          <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i> Error: ${data.error}
          </div>
        `;
        return;
      }

      // Construir contenido del modal
      let html = '';

      // Resumen general
      html += `
        <div class="row mb-4">
          <div class="col-md-6">
            <div class="card border-primary">
              <div class="card-body text-center">
                <h5 class="card-title text-primary">Interés Total Pagado</h5>
                <h3 class="text-primary">$ ${formatNumber(data.total_interest || 0)}</h3>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="card border-success">
              <div class="card-body text-center">
                <h5 class="card-title text-success">Comisión 40%</h5>
                <h3 class="text-success">$ ${formatNumber(data.total_commission || 0)}</h3>
              </div>
            </div>
          </div>
        </div>
      `;

      // Estado de envío
      const statusClass = data.send_status === 'enviado' ? 'success' : 'warning';
      const statusText = data.send_status === 'enviado' ? 'Enviado' : 'Pendiente';
      html += `
        <div class="alert alert-${statusClass} mb-4">
          <h6><i class="fas fa-info-circle"></i> Estado de Envío</h6>
          <p class="mb-0">Estado actual: <strong>${statusText}</strong></p>
        </div>
      `;

      // Tabla de clientes
      if (data.clients && data.clients.length > 0) {
        html += `
          <h6>Detalles por Cliente:</h6>
          <div class="table-responsive">
            <table class="table table-striped table-bordered">
              <thead class="table-dark">
                <tr>
                  <th>Cliente</th>
                  <th>Cédula</th>
                  <th>ID Préstamo</th>
                  <th>Monto Original</th>
                  <th>Pagos Realizados</th>
                  <th>Interés Pagado</th>
                  <th>Comisión 40%</th>
                  <th>Último Pago</th>
                </tr>
              </thead>
              <tbody>
        `;

        data.clients.forEach(client => {
          html += `
            <tr>
              <td>${client.customer_name || 'N/A'}</td>
              <td>${client.dni || 'N/A'}</td>
              <td>${client.loan_id || 'N/A'}</td>
              <td class="text-right">$ ${formatNumber(client.credit_amount || 0)}</td>
              <td class="text-center">${client.payments_made || 0}</td>
              <td class="text-right">$ ${formatNumber(client.total_interest_paid || 0)}</td>
              <td class="text-right">$ ${formatNumber(client.interest_commission_40 || 0)}</td>
              <td class="text-center">${client.last_payment_date ? new Date(client.last_payment_date).toLocaleDateString('es-CO') : 'N/A'}</td>
            </tr>
          `;
        });

        html += `
              </tbody>
            </table>
          </div>
        `;
      } else {
        html += `
          <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> No hay detalles de clientes disponibles para este período.
          </div>
        `;
      }

      document.getElementById('detailsModalBody').innerHTML = html;
    })
    .catch(error => {
      console.error('Error al cargar detalles:', error);
      document.getElementById('detailsModalBody').innerHTML = `
        <div class="alert alert-danger">
          <i class="fas fa-exclamation-triangle"></i> Error al cargar los detalles: ${error.message}
        </div>
      `;
    });
  }

  // Funciones auxiliares
  function formatNumber(num) {
    return new Intl.NumberFormat('es-CO').format(num);
  }
});
</script>