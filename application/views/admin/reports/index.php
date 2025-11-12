<!-- Vista del Cobrador - Comisiones del 40% -->
<div class="card shadow mb-4">
  <div class="card-header py-3">
    <h6 class="m-0 font-weight-bold text-primary">
      <i class="fas fa-coins"></i> 📊 Mis Comisiones - Cobrador
    </h6>
    <p class="mb-0 text-muted small">
      Consulta tus comisiones del 40% por intereses cobrados
    </p>
  </div>
  <div class="card-body">
    <!-- Información informativa -->
    <div class="alert alert-info mb-4">
      <h6><i class="fas fa-info-circle"></i> 💬 Envía el 40% de tus intereses al administrador</h6>
      <p class="mb-0">Presiona el botón "Enviar Comisión" para registrar el envío de tu parte correspondiente al administrador.</p>
    </div>

    <!-- Filtros -->
    <div class="row mb-4">
      <div class="col-md-4">
        <label for="collector_filter" class="form-label">Filtrar por Cobrador:</label>
        <select class="form-control" id="collector_filter">
          <option value="">Todos los cobradores</option>
          <?php foreach ($cobradores_list as $cobrador): ?>
            <option value="<?php echo $cobrador->id; ?>" <?php echo ($this->session->userdata('user_id') == $cobrador->id) ? 'selected' : ''; ?>>
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

    <!-- Tabla de detalles de comisiones con DataTables -->
    <div class="table-responsive">
      <table class="table table-bordered" id="commissionsTable" width="100%" cellspacing="0">
        <thead class="table-dark">
          <tr>
            <th class="text-center">
              <input type="checkbox" id="selectAll" title="Seleccionar todos">
            </th>
            <th>Cliente</th>
            <th>Cédula</th>
            <th>Préstamo</th>
            <th>Monto Original</th>
            <th>Progreso</th>
            <th>Pagos Realizados</th>
            <th>Interés Pagado</th>
            <th>Comisión 40%</th>
            <th>Último Pago</th>
            <th>Estado Comisión</th>
          </tr>
        </thead>
        <tbody id="commissionsTableBody">
          <!-- Los datos se cargarán dinámicamente por AJAX -->
        </tbody>
      </table>
    </div>

    <!-- Resumen y botón de envío -->
    <div class="row mt-4">
      <div class="col-md-8">
        <div class="card border-primary">
          <div class="card-body">
            <h6 class="card-title text-primary">
              <i class="fas fa-calculator"></i> Resumen del Período
            </h6>
            <div class="row">
              <div class="col-sm-4">
                <strong>Total Interés:</strong><br>
                <span id="total_interest" class="text-success h5">$0</span>
              </div>
              <div class="col-sm-4">
                <strong>Comisión 40%:</strong><br>
                <span id="total_commission" class="text-warning h5">$0</span>
              </div>
              <div class="col-sm-4">
                <strong>Estado:</strong><br>
                <span id="send_status" class="badge badge-warning">Pendiente</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <button class="btn btn-success btn-lg w-100" id="send_commission_btn">
          <i class="fas fa-paper-plane"></i> Enviar Comisión
        </button>
        <small class="text-muted mt-1 d-block">
          Registra el envío de tu 40% al administrador
        </small>
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

<!-- DataTables CSS -->
<link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap4.min.css" rel="stylesheet">

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap4.min.js"></script>

<script>
let commissionsTable;

document.addEventListener('DOMContentLoaded', function() {
  // Inicializar DataTable
  commissionsTable = $('#commissionsTable').DataTable({
    responsive: true,
    pageLength: 10,
    lengthMenu: [[10, 20, 50, -1], [10, 20, 50, "Todos"]],
    language: {
      "sProcessing": "Procesando...",
      "sLengthMenu": "Mostrar _MENU_ registros por página",
      "sZeroRecords": "No se encontraron resultados",
      "sEmptyTable": "Ningún dato disponible en esta tabla",
      "sInfo": "Mostrando página _PAGE_ de _PAGES_",
      "sInfoEmpty": "No hay registros disponibles",
      "sInfoFiltered": "(filtrado de _MAX_ registros totales)",
      "sInfoPostFix": "",
      "sSearch": "Buscar:",
      "sUrl": "",
      "sInfoThousands": ",",
      "sLoadingRecords": "Cargando...",
      "oPaginate": {
        "sFirst": "Primero",
        "sLast": "Último",
        "sNext": "Siguiente",
        "sPrevious": "Anterior"
      },
      "oAria": {
        "sSortAscending": ": Activar para ordenar la columna de manera ascendente",
        "sSortDescending": ": Activar para ordenar la columna de manera descendente"
      },
      "buttons": {
        "copy": "Copiar",
        "colvis": "Visibilidad"
      }
    },
    columnDefs: [
      { orderable: false, targets: [0] }, // Checkbox column not orderable
      { orderable: true, targets: [1, 2, 3, 4, 5, 7, 8, 9] },
      { searchable: true, targets: [1, 2, 3, 4, 5, 7, 8, 9] }, // Skip checkbox column for search
      { className: "text-center", targets: [0, 4, 5] }, // Center align checkbox, progress, and payments columns
      { visible: false, targets: [6] } // Ocultar columna "Pagos Realizados"
    ],
    order: [[0, 'asc']],
    dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
  });

  // Cargar datos iniciales
  loadCommissionData();

  // Evento para filtrar
  document.getElementById('filter_btn').addEventListener('click', function() {
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

  // Evento para enviar comisión
  document.getElementById('send_commission_btn').addEventListener('click', function() {
    sendCommission();
  });


  // Evento para seleccionar/deseleccionar todos
  document.getElementById('selectAll').addEventListener('change', function() {
    const isChecked = this.checked;
    document.querySelectorAll('.commission-checkbox').forEach(function(checkbox) {
      checkbox.checked = isChecked;
    });
    updateCommissionSummary();
  });

  // Función para cargar datos de comisiones
  function loadCommissionData() {
    const collectorId = document.getElementById('collector_filter').value;
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;

    fetch('<?php echo base_url('api_commissions.php'); ?>?' + new URLSearchParams({
      user_id: collectorId || '<?php echo $this->session->userdata('user_id'); ?>',
      start_date: startDate || '',
      end_date: endDate || ''
    }))
    .then(response => {
      if (!response.ok) {
        throw new Error('HTTP error! status: ' + response.status);
      }
      return response.json();
    })
    .then(response => {
      if (response.error) {
        alert('Error: ' + response.error);
        return;
      }

      updateTable(response.clients || [], response.send_status || 'pendiente');
      updateSummary(response.total_interest || 0, response.total_commission || 0, response.send_status || 'pendiente');
    })
    .catch(error => {
      console.error('Error:', error);
      alert('Error al cargar los datos: ' + error.message);
    });
  }

  // Función para actualizar tabla
  function updateTable(clients, sendStatus) {
    // Limpiar tabla
    commissionsTable.clear();

    if (clients && clients.length > 0) {
      clients.forEach(function(client, index) {
        commissionsTable.row.add([
          '<input type="checkbox" class="commission-checkbox" data-client-id="' + (client.customer_id || index) + '" data-loan-id="' + client.loan_id + '" data-interest="' + (client.total_interest_paid || 0) + '" data-commission="' + (client.interest_commission_40 || 0) + '">',
          client.customer_name || client.client_name || '',
          client.dni || client.client_cedula || '',
          client.loan_id || '',
          '$' + formatNumber(client.credit_amount || client.loan_amount || 0),
          client.progress || '0/0',
          client.payments_made || client.payments_count || 0,
          '$' + formatNumber(client.total_interest_paid || 0),
          '$' + formatNumber(client.interest_commission_40 || 0),
          client.last_payment_date ? new Date(client.last_payment_date).toLocaleDateString('es-CO') : 'N/A',
          '<span class="badge ' + getStatusBadge(client.commission_status || 'pendiente') + '">' +
          getStatusText(client.commission_status || 'pendiente') + '</span>' +
          (client.commission_status === 'enviado' && client.commission_sent_at ?
            '<br><small class="text-muted">' + new Date(client.commission_sent_at).toLocaleDateString('es-CO') + '</small>' : '')
        ]);
      });
    }

    // Redibujar tabla
    commissionsTable.draw();

    // Configurar eventos de checkboxes después de dibujar
    setupCheckboxEvents();
  }

  // Función para actualizar resumen
  function updateSummary(totalInterest, totalCommission, sendStatus) {
    document.getElementById('total_interest').textContent = '$' + formatNumber(totalInterest || 0);
    document.getElementById('total_commission').textContent = '$' + formatNumber(totalCommission || 0);
    const statusElement = document.getElementById('send_status');
    statusElement.className = 'badge ' + getStatusBadge(sendStatus);
    statusElement.textContent = getStatusText(sendStatus);
  }

  // Función para configurar eventos de checkboxes
  function setupCheckboxEvents() {
    // Evento para checkboxes individuales
    document.querySelectorAll('.commission-checkbox').forEach(function(checkbox) {
      checkbox.addEventListener('change', function() {
        updateCommissionSummary();
        updateSelectAllCheckbox();
      });
    });
  }

  // Función para actualizar checkbox "Seleccionar todos"
  function updateSelectAllCheckbox() {
    const totalCheckboxes = document.querySelectorAll('.commission-checkbox').length;
    const checkedCheckboxes = document.querySelectorAll('.commission-checkbox:checked').length;
    document.getElementById('selectAll').checked = totalCheckboxes > 0 && checkedCheckboxes === totalCheckboxes;
    document.getElementById('selectAll').indeterminate = checkedCheckboxes > 0 && checkedCheckboxes < totalCheckboxes;
  }

  // Función para actualizar resumen de comisión basado en selección
  function updateCommissionSummary() {
    const selectedCheckboxes = document.querySelectorAll('.commission-checkbox:checked');
    let totalInterest = 0;
    let totalCommission = 0;

    selectedCheckboxes.forEach(function(checkbox) {
      totalInterest += parseFloat(checkbox.getAttribute('data-interest')) || 0;
      totalCommission += parseFloat(checkbox.getAttribute('data-commission')) || 0;
    });

    // Actualizar resumen visual
    document.getElementById('total_interest').textContent = '$' + formatNumber(totalInterest);
    document.getElementById('total_commission').textContent = '$' + formatNumber(totalCommission);

    // Cambiar texto del botón según selección
    const sendButton = document.getElementById('send_commission_btn');
    if (selectedCheckboxes.length > 0) {
      sendButton.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar Comisión (' + selectedCheckboxes.length + ' seleccionadas)';
      sendButton.disabled = false;
    } else {
      sendButton.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar Comisión';
      sendButton.disabled = true;
    }
  }

// Función para enviar comisión
function sendCommission() {
  const selectedCheckboxes = document.querySelectorAll('.commission-checkbox:checked');

  if (selectedCheckboxes.length === 0) {
    alert('Por favor selecciona al menos una cuota para enviar la comisión.');
    return;
  }

  const collectorId = document.getElementById('collector_filter').value || '<?php echo $this->session->userdata('user_id'); ?>';
  const startDate = document.getElementById('start_date').value;
  const endDate = document.getElementById('end_date').value;

  // Recopilar datos de las cuotas seleccionadas
  const selectedData = [];
  selectedCheckboxes.forEach(function(checkbox) {
    selectedData.push({
      client_id: checkbox.getAttribute('data-client-id'),
      loan_id: checkbox.getAttribute('data-loan-id'),
      interest: checkbox.getAttribute('data-interest'),
      commission: checkbox.getAttribute('data-commission')
    });
  });

  const totalCommission = selectedData.reduce((sum, item) => sum + parseFloat(item.commission), 0);

  if (confirm('¿Estás seguro de que deseas enviar la comisión del 40% para las ' + selectedCheckboxes.length + ' cuotas seleccionadas?\n\nMonto total: $' + formatNumber(totalCommission))) {
    fetch('<?php echo site_url('admin/reports/send_commission'); ?>', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: new URLSearchParams({
        collector_id: collectorId,
        start_date: startDate,
        end_date: endDate,
        selected_commissions: JSON.stringify(selectedData)
      })
    })
    .then(response => {
      if (!response.ok) {
        throw new Error('HTTP error! status: ' + response.status);
      }
      return response.json();
    })
    .then(response => {
      if (response.success) {
        alert('¡Comisión enviada exitosamente!\nMonto: $' + formatNumber(totalCommission) + '\nCuotas procesadas: ' + selectedCheckboxes.length);
        loadCommissionData(); // Recargar datos
      } else {
        alert('Error: ' + (response.message || 'Error desconocido'));
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('Error al enviar la comisión: ' + error.message);
    });
  }
}


  // Funciones auxiliares
  function formatNumber(num) {
    return new Intl.NumberFormat('es-CO').format(num);
  }

  function getStatusBadge(status) {
    return status === 'enviado' ? 'badge-success' : 'badge-warning';
  }

  function getStatusText(status) {
    return status === 'enviado' ? 'Enviado' : 'Pendiente';
  }
});
</script>
