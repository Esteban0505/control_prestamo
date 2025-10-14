<div class="container-fluid mt-4">
  <h3 class="mb-3 text-center">📋 Lista de Clientes con Pagos Vencidos</h3>

  <!-- Estadísticas Rápidas -->
  <div class="row mb-4">
    <div class="col-md-3">
      <div class="card bg-danger text-white">
        <div class="card-body text-center">
          <h5 class="card-title">🔴 Alto Riesgo</h5>
          <h3 id="high-risk-count"><?= isset($statistics['high_risk_count']) ? $statistics['high_risk_count'] : 0 ?></h3>
          <p class="mb-0">60+ días</p>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card bg-warning text-white">
        <div class="card-body text-center">
          <h5 class="card-title">🟡 Riesgo Medio</h5>
          <h3 id="medium-risk-count"><?= isset($statistics['medium_risk_count']) ? $statistics['medium_risk_count'] : 0 ?></h3>
          <p class="mb-0">30-59 días</p>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card bg-info text-white">
        <div class="card-body text-center">
          <h5 class="card-title">🟢 Riesgo Bajo</h5>
          <h3 id="low-risk-count"><?= isset($statistics['low_risk_count']) ? $statistics['low_risk_count'] : 0 ?></h3>
          <p class="mb-0">1-29 días</p>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card bg-secondary text-white">
        <div class="card-body text-center">
          <h5 class="card-title">💰 Total en Riesgo</h5>
          <h3 id="total-risk-amount">$<?= isset($statistics['total_amount']) ? number_format($statistics['total_amount'], 2, ',', '.') : '0.00' ?></h3>
          <p class="mb-0">Monto adeudado</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Filtros y Controles -->
  <div class="card shadow mb-4">
    <div class="card-header bg-primary text-white">
      <h5 class="mb-0"><i class="fas fa-filter"></i> Filtros y Búsqueda</h5>
    </div>
    <div class="card-body">
      <form id="filters-form" method="GET">
        <div class="row">
          <div class="col-md-3">
            <div class="form-group">
              <label for="search">Buscar Cliente:</label>
              <input type="text" class="form-control" id="search" name="search" placeholder="Nombre, cédula o préstamo..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
            </div>
          </div>
          <div class="col-md-2">
            <div class="form-group">
              <label for="risk_level">Nivel de Riesgo:</label>
              <select class="form-control" id="risk_level" name="risk_level">
                <option value="">Todos</option>
                <option value="low" <?= (isset($_GET['risk_level']) && $_GET['risk_level'] == 'low') ? 'selected' : '' ?>>Bajo (1-29 días)</option>
                <option value="medium" <?= (isset($_GET['risk_level']) && $_GET['risk_level'] == 'medium') ? 'selected' : '' ?>>Medio (30-59 días)</option>
                <option value="high" <?= (isset($_GET['risk_level']) && $_GET['risk_level'] == 'high') ? 'selected' : '' ?>>Alto (60+ días)</option>
              </select>
            </div>
          </div>
          <div class="col-md-2">
            <div class="form-group">
              <label for="min_amount">Monto Mínimo:</label>
              <input type="number" class="form-control" id="min_amount" name="min_amount" placeholder="0.00" step="0.01" value="<?= isset($_GET['min_amount']) ? htmlspecialchars($_GET['min_amount']) : '' ?>">
            </div>
          </div>
          <div class="col-md-2">
            <div class="form-group">
              <label for="max_amount">Monto Máximo:</label>
              <input type="number" class="form-control" id="max_amount" name="max_amount" placeholder="0.00" step="0.01" value="<?= isset($_GET['max_amount']) ? htmlspecialchars($_GET['max_amount']) : '' ?>">
            </div>
          </div>
          <div class="col-md-3">
            <div class="form-group">
              <label>Acciones:</label><br>
              <button type="submit" class="btn btn-primary btn-sm mr-2">
                <i class="fas fa-search"></i> Filtrar
              </button>
              <button type="button" class="btn btn-secondary btn-sm mr-2" onclick="clearFilters()">
                <i class="fas fa-times"></i> Limpiar
              </button>
              <button type="button" class="btn btn-success btn-sm" onclick="exportData('excel')">
                <i class="fas fa-file-excel"></i> Excel
              </button>
              <button type="button" class="btn btn-danger btn-sm" onclick="exportData('pdf')">
                <i class="fas fa-file-pdf"></i> PDF
              </button>
            </div>
          </div>
        </div>
      </form>

      <!-- Panel de Alertas y Notificaciones Masivas -->
      <div class="row mt-3">
        <div class="col-12">
          <div class="alert alert-info">
            <h6><i class="fas fa-bell"></i> Sistema de Alertas Automáticas</h6>
            <div class="row">
              <div class="col-md-8">
                <p class="mb-2">Envíe notificaciones masivas a clientes según su nivel de riesgo:</p>
                <div class="btn-group mr-2" role="group">
                  <button type="button" class="btn btn-outline-danger btn-sm" onclick="sendBulkNotifications('high', 'warning')">
                    <i class="fas fa-exclamation-triangle"></i> Alertar Alto Riesgo
                  </button>
                  <button type="button" class="btn btn-outline-warning btn-sm" onclick="sendBulkNotifications('medium', 'reminder')">
                    <i class="fas fa-clock"></i> Recordar Medio Riesgo
                  </button>
                  <button type="button" class="btn btn-outline-info btn-sm" onclick="sendBulkNotifications('low', 'reminder')">
                    <i class="fas fa-info-circle"></i> Notificar Bajo Riesgo
                  </button>
                </div>
              </div>
              <div class="col-md-4 text-right">
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="checkAlerts()">
                  <i class="fas fa-sync"></i> Actualizar Alertas
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Tabla de Resultados -->
  <div class="card shadow">
    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
      <h5 class="mb-0"><i class="fas fa-list"></i> Resultados (<?= $total_records ?? count($clients ?? []) ?> clientes)</h5>
      <div class="d-flex align-items-center">
        <span class="text-light mr-3">
          Página <?= ceil(($current_page ?? 0) / ($per_page ?? 25)) + 1 ?> de <?= ceil(($total_records ?? count($clients ?? [])) / ($per_page ?? 25)) ?>
        </span>
        <div class="btn-group" role="group">
          <button type="button" class="btn btn-outline-light btn-sm" onclick="refreshData()">
            <i class="fas fa-sync-alt"></i> Actualizar
          </button>
        </div>
      </div>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-striped text-center" id="overdue-table">
          <thead class="table-dark">
            <tr>
              <th>#</th>
              <th>Nombre del Cliente</th>
              <th>Cédula</th>
              <th>Cuotas Vencidas</th>
              <th>Total Adeudado</th>
              <th>Máx. Días Atraso</th>
              <th>Riesgo</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($clients)): ?>
              <?php
              $high_risk = 0;
              $medium_risk = 0;
              $low_risk = 0;
              $total_amount = 0;
              foreach ($clients as $i => $c):
                $row_class = '';
                $risk_icon = '';
                $risk_badge = '';
                $status_badge = '';

                if ($c->max_dias_atraso >= 60) {
                  $row_class = 'table-danger';
                  $risk_icon = '🔴';
                  $risk_badge = '<span class="badge badge-danger">Alto</span>';
                  $status_badge = '<span class="badge badge-danger">Castigado</span>';
                  $high_risk++;
                } elseif ($c->max_dias_atraso >= 30) {
                  $row_class = 'table-warning';
                  $risk_icon = '🟡';
                  $risk_badge = '<span class="badge badge-warning">Medio</span>';
                  $status_badge = '<span class="badge badge-warning">En Mora</span>';
                  $medium_risk++;
                } else {
                  $row_class = 'table-info';
                  $risk_icon = '🟢';
                  $risk_badge = '<span class="badge badge-success">Bajo</span>';
                  $status_badge = '<span class="badge badge-info">Vencido</span>';
                  $low_risk++;
                }

                $total_amount += $c->total_adeudado;
                $tooltip = "Cliente con {$c->cuotas_vencidas} cuotas vencidas — Total adeudado: $" . number_format($c->total_adeudado, 2, ',', '.');
              ?>
                <tr class="<?= $row_class ?>" data-toggle="tooltip" data-placement="top" title="<?= htmlspecialchars($tooltip) ?>">
                  <td><?= $i + 1 ?> <?= $risk_icon ?></td>
                  <td>
                    <a href="#" onclick="showClientDetails(<?= $c->customer_id ?>)" class="text-decoration-none">
                      <?= htmlspecialchars($c->client_name) ?>
                    </a>
                  </td>
                  <td><?= htmlspecialchars($c->client_cedula) ?></td>
                  <td><span class="badge badge-secondary"><?= $c->cuotas_vencidas ?></span></td>
                  <td>$<?= number_format($c->total_adeudado, 2, ',', '.') ?></td>
                  <td><span class="badge badge-danger"><?= $c->max_dias_atraso ?> días</span></td>
                  <td><?= $risk_badge ?></td>
                  <td><?= $status_badge ?></td>
                  <td>
                    <div class="btn-group" role="group">
                      <button type="button" class="btn btn-sm btn-outline-primary" onclick="sendNotification(<?= $c->customer_id ?>)">
                        <i class="fas fa-envelope"></i>
                      </button>
                      <button type="button" class="btn btn-sm btn-outline-warning" onclick="applyPenalty(<?= $c->customer_id ?>)">
                        <i class="fas fa-exclamation-triangle"></i>
                      </button>
                      <button type="button" class="btn btn-sm btn-outline-info" onclick="viewLoanDetails('<?= $c->loan_ids ?>')" title="Ver detalles de préstamos">
                        <i class="fas fa-eye" onclick="console.log('Botón clickeado, loan_ids: <?= $c->loan_ids ?>')"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="9">No hay clientes con pagos vencidos 🎉</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Paginación -->
  <?php if (isset($pagination_links) && !empty($pagination_links)): ?>
  <div class="d-flex justify-content-center mt-4">
    <nav aria-label="Navegación de páginas">
      <?= $pagination_links ?>
    </nav>
  </div>
  <?php endif; ?>
</div>

<!-- Modal para Detalles del Cliente -->
<div class="modal fade" id="clientDetailsModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Detalles del Cliente</h5>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body" id="clientDetailsContent">
        Cargando...
      </div>
    </div>
  </div>
</div>

<!-- Modal para Detalles de Préstamos -->
<div class="modal fade" id="loanDetailsModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Detalles de Préstamos</h5>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div id="loanDetailsContent">
          Cargando detalles de préstamos...
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<script>
// Verificar que jQuery esté disponible antes de ejecutar
if (typeof jQuery === 'undefined') {
  console.error('jQuery no está cargado. Cargando manualmente...');
  // Cargar jQuery si no está disponible
  var script = document.createElement('script');
  script.src = '<?php echo base_url('assets/vendor/jquery/jquery.min.js'); ?>';
  script.onload = function() {
    console.log('jQuery cargado manualmente');
    initializeOverduePage();
  };
  document.head.appendChild(script);
} else {
  // jQuery ya está disponible
  jQuery(document).ready(function($) {
    initializeOverduePage();
  });
}

function initializeOverduePage() {
  console.log('Inicializando página de clientes vencidos...');

  // Verificar que jQuery esté disponible
  if (typeof $ === 'undefined') {
    console.error('jQuery no está disponible después de la carga');
    return;
  }

  // Inicializar tooltips de forma segura
  try {
    if (typeof $.fn.tooltip !== 'undefined') {
      $('[data-toggle="tooltip"]').tooltip();
    } else {
      console.warn('Bootstrap tooltip no disponible - funcionalidad básica mantendrá tooltips nativos');
    }
  } catch (e) {
    console.warn('Error inicializando tooltips:', e.message);
  }

  // Debug: Verificar que las funciones están disponibles
  console.log('Funciones disponibles:', {
    viewLoanDetails: typeof viewLoanDetails,
    sendNotification: typeof sendNotification,
    applyPenalty: typeof applyPenalty
  });

  // Actualizar estadísticas
  updateStatistics();

  // Inicializar búsqueda en tiempo real
  $('#search').on('keyup', function() {
    var searchTerm = $(this).val().toLowerCase();
    filterTable(searchTerm);
  });

  // Filtros en tiempo real
  $('#risk_level, #min_amount, #max_amount').on('change input', function() {
    applyFilters();
  });

  console.log('Página de clientes vencidos inicializada correctamente');
}

function updateStatistics() {
  var highRisk = 0, mediumRisk = 0, lowRisk = 0, totalAmount = 0;

  $('#overdue-table tbody tr').each(function() {
    var row = $(this);
    var riskBadge = row.find('td:nth-child(7) .badge');
    var amountText = row.find('td:nth-child(5)').text().replace('$', '').replace(/\./g, '').replace(',', '.');

    if (riskBadge.hasClass('badge-danger')) highRisk++;
    else if (riskBadge.hasClass('badge-warning')) mediumRisk++;
    else if (riskBadge.hasClass('badge-success')) lowRisk++;

    totalAmount += parseFloat(amountText) || 0;
  });

  $('#high-risk-count').text(highRisk);
  $('#medium-risk-count').text(mediumRisk);
  $('#low-risk-count').text(lowRisk);
  $('#total-risk-amount').text('$' + totalAmount.toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
}

function filterTable(searchTerm) {
  $('#overdue-table tbody tr').each(function() {
    var row = $(this);
    var name = row.find('td:nth-child(2)').text().toLowerCase();
    var cedula = row.find('td:nth-child(3)').text().toLowerCase();

    if (name.includes(searchTerm) || cedula.includes(searchTerm) || searchTerm === '') {
      row.show();
    } else {
      row.hide();
    }
  });
  updateStatistics();
}

function applyFilters() {
  var riskLevel = $('#risk_level').val();
  var minAmount = parseFloat($('#min_amount').val()) || 0;
  var maxAmount = parseFloat($('#max_amount').val()) || Infinity;

  $('#overdue-table tbody tr').each(function() {
    var row = $(this);
    var riskBadge = row.find('td:nth-child(7) .badge');
    var amountText = row.find('td:nth-child(5)').text().replace('$', '').replace(/\./g, '').replace(',', '.');
    var amount = parseFloat(amountText) || 0;

    var showRow = true;

    // Filtro por riesgo
    if (riskLevel) {
      if (riskLevel === 'high' && !riskBadge.hasClass('badge-danger')) showRow = false;
      if (riskLevel === 'medium' && !riskBadge.hasClass('badge-warning')) showRow = false;
      if (riskLevel === 'low' && !riskBadge.hasClass('badge-success')) showRow = false;
    }

    // Filtro por monto
    if (amount < minAmount || amount > maxAmount) showRow = false;

    if (showRow) {
      row.show();
    } else {
      row.hide();
    }
  });
  updateStatistics();
}

function clearFilters() {
  $('#filters-form')[0].reset();
  $('#overdue-table tbody tr').show();
  updateStatistics();
}

function exportData(format) {
  var formData = new FormData($('#filters-form')[0]);
  formData.append('format', format);

  // Mostrar indicador de carga
  var btn = $('button[onclick="exportData(\'' + format + '\')"]');
  var originalText = btn.html();
  btn.html('<i class="fas fa-spinner fa-spin"></i> Generando...').prop('disabled', true);

  $.ajax({
    url: '<?= site_url("admin/customers/export_overdue") ?>',
    type: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    success: function(response) {
      if (response.success) {
        window.open(response.file_url, '_blank');
      } else {
        alert('Error al generar el archivo: ' + response.error);
      }
    },
    error: function() {
      alert('Error de conexión. Intente nuevamente.');
    },
    complete: function() {
      btn.html(originalText).prop('disabled', false);
    }
  });
}

function refreshData() {
  location.reload();
}

function showClientDetails(customerId) {
  console.log('showClientDetails llamada con customerId:', customerId);

  $('#clientDetailsModal').modal('show');
  $('#clientDetailsContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Cargando detalles...</div>');

  // Preparar datos con CSRF token
  var data = { customer_id: customerId };

  // Agregar token CSRF si está disponible
  if (typeof window.csrf_name !== 'undefined' && typeof window.csrf_hash !== 'undefined') {
    data[window.csrf_name] = window.csrf_hash;
  }

  $.ajax({
    url: '<?= site_url("admin/customers/get_client_details") ?>',
    type: 'POST',
    data: data,
    success: function(response) {
      console.log('Respuesta de detalles del cliente:', response);
      try {
        // Si la respuesta es un string, intentar parsear como JSON
        if (typeof response === 'string') {
          response = JSON.parse(response);
        }

        if (response.success) {
          $('#clientDetailsContent').html(response.html);
        } else {
          $('#clientDetailsContent').html('<div class="alert alert-danger">Error al cargar detalles: ' + (response.error || 'Error desconocido') + '</div>');
        }
      } catch (e) {
        console.error('Error procesando respuesta JSON:', e);
        $('#clientDetailsContent').html('<div class="alert alert-danger">Error procesando respuesta del servidor</div>');
      }
    },
    error: function(xhr, status, error) {
      console.error('Error AJAX detalles cliente:', xhr, status, error);
      $('#clientDetailsContent').html('<div class="alert alert-danger">Error de conexión. Revisa la consola para más detalles.</div>');
    }
  });
}

function sendNotification(customerId) {
  if (confirm('¿Enviar notificación de recordatorio a este cliente?')) {
    // Preparar datos con CSRF token
    var data = { customer_id: customerId };

    // Agregar token CSRF si está disponible
    if (typeof window.csrf_name !== 'undefined' && typeof window.csrf_hash !== 'undefined') {
      data[window.csrf_name] = window.csrf_hash;
    }

    $.ajax({
      url: '<?= site_url("admin/customers/send_notification") ?>',
      type: 'POST',
      data: data,
      success: function(response) {
        if (response.success) {
          alert('Notificación enviada exitosamente');
        } else {
          alert('Error al enviar notificación: ' + (response.error || 'Error desconocido'));
        }
      },
      error: function(xhr, status, error) {
        console.error('Error AJAX:', xhr, status, error);
        alert('Error de conexión. Revisa la consola para más detalles.');
      }
    });
  }
}

function applyPenalty(customerId) {
  if (confirm('¿Aplicar penalización automática a este cliente?')) {
    // Preparar datos con CSRF token
    var data = { customer_id: customerId };

    // Agregar token CSRF si está disponible
    if (typeof window.csrf_name !== 'undefined' && typeof window.csrf_hash !== 'undefined') {
      data[window.csrf_name] = window.csrf_hash;
    }

    $.ajax({
      url: '<?= site_url("admin/customers/apply_penalty") ?>',
      type: 'POST',
      data: data,
      success: function(response) {
        if (response.success) {
          alert('Penalización aplicada exitosamente');
          refreshData();
        } else {
          alert('Error al aplicar penalización: ' + (response.error || 'Error desconocido'));
        }
      },
      error: function(xhr, status, error) {
        console.error('Error AJAX:', xhr, status, error);
        alert('Error de conexión. Revisa la consola para más detalles.');
      }
    });
  }
}

function sendBulkNotifications(riskLevel, messageType) {
  if (!confirm('¿Enviar notificaciones masivas a todos los clientes de ' + riskLevel + ' riesgo?')) {
    return;
  }

  // Mostrar indicador de carga
  var btn = event.target.closest('button');
  var originalText = btn.innerHTML;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
  btn.disabled = true;

  // Preparar datos con CSRF token
  var data = {
    risk_level: riskLevel,
    message_type: messageType
  };

  // Agregar token CSRF si está disponible
  if (typeof window.csrf_name !== 'undefined' && typeof window.csrf_hash !== 'undefined') {
    data[window.csrf_name] = window.csrf_hash;
  }

  $.ajax({
    url: '<?= site_url("admin/customers/send_bulk_notifications") ?>',
    type: 'POST',
    data: data,
    success: function(response) {
      if (response.success) {
        alert('Notificaciones enviadas exitosamente: ' + response.sent_count + ' mensajes');
        if (response.errors && response.errors.length > 0) {
          console.log('Errores:', response.errors);
        }
      } else {
        alert('Error al enviar notificaciones: ' + (response.error || 'Error desconocido'));
      }
    },
    error: function(xhr, status, error) {
      console.error('Error AJAX:', xhr, status, error);
      alert('Error de conexión al enviar notificaciones. Revisa la consola para más detalles.');
    },
    complete: function() {
      btn.innerHTML = originalText;
      btn.disabled = false;
    }
  });
}

function checkAlerts() {
  $.ajax({
    url: '<?= site_url("admin/customers/get_alerts_summary") ?>',
    type: 'POST',
    success: function(response) {
      if (response.success) {
        // Actualizar estadísticas si es necesario
        updateStatistics();
        alert('Alertas actualizadas:\n' +
              'Alto riesgo: ' + response.alerts.high_risk_count + '\n' +
              'Medio riesgo: ' + response.alerts.medium_risk_count + '\n' +
              'Nuevos hoy: ' + response.alerts.new_overdue_today + '\n' +
              'Total adeudado: $' + response.alerts.total_overdue_amount.toLocaleString('es-CO', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
              }));
      }
    },
    error: function() {
      alert('Error al actualizar alertas');
    }
  });
}

function viewLoanDetails(loanIds) {
  console.log('viewLoanDetails llamada con loanIds:', loanIds);

  // Verificar que el modal existe
  if ($('#loanDetailsModal').length === 0) {
    console.error('Modal loanDetailsModal no encontrado en el DOM');
    alert('Error: Modal de detalles no encontrado. Recarga la página.');
    return;
  }

  // Mostrar modal de detalles de préstamos
  $('#loanDetailsModal').modal('show');
  $('#loanDetailsContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Cargando detalles de préstamos...</div>');

  // Preparar datos con CSRF token
  var data = { loan_ids: loanIds };
  console.log('Datos a enviar:', data);

  // Agregar token CSRF si está disponible
  if (typeof window.csrf_name !== 'undefined' && typeof window.csrf_hash !== 'undefined') {
    data[window.csrf_name] = window.csrf_hash;
    console.log('Token CSRF agregado');
  } else {
    console.warn('Token CSRF no disponible');
  }

  $.ajax({
    url: '<?= site_url("admin/customers/get_loan_details") ?>',
    type: 'POST',
    data: data,
    success: function(response) {
      console.log('Respuesta AJAX recibida:', response);
      try {
        // Si la respuesta es un string, intentar parsear como JSON
        if (typeof response === 'string') {
          response = JSON.parse(response);
        }

        if (response.success) {
          displayLoanDetails(response.loans);
        } else {
          $('#loanDetailsContent').html('<div class="alert alert-danger">Error al cargar detalles: ' + (response.error || 'Error desconocido') + '</div>');
        }
      } catch (e) {
        console.error('Error procesando respuesta JSON:', e);
        $('#loanDetailsContent').html('<div class="alert alert-danger">Error procesando respuesta del servidor</div>');
      }
    },
    error: function(xhr, status, error) {
      console.error('Error AJAX:', xhr, status, error);
      $('#loanDetailsContent').html('<div class="alert alert-danger">Error de conexión. Revisa la consola para más detalles.</div>');
    }
  });
}

function displayLoanDetails(loans) {
  let html = '';

  loans.forEach(function(loan, index) {
    html += `
      <div class="card border-primary mb-4">
        <div class="card-header bg-primary text-white">
          <h5 class="mb-0">
            <i class="fas fa-file-invoice-dollar"></i>
            Préstamo #${loan.id}
            <span class="badge badge-light float-right">${loan.coin_name || 'COP'}</span>
          </h5>
        </div>
        <div class="card-body">
          <!-- Información General del Préstamo -->
          <div class="row mb-4">
            <div class="col-md-3">
              <div class="text-center">
                <h6 class="text-muted">MONTO TOTAL</h6>
                <h4 class="text-primary">$ ${parseFloat(loan.credit_amount).toLocaleString('es-CO', {minimumFractionDigits: 0, maximumFractionDigits: 0})}</h4>
              </div>
            </div>
            <div class="col-md-3">
              <div class="text-center">
                <h6 class="text-muted">ESTADO</h6>
                <span class="badge badge-${loan.status == 1 ? 'success' : 'secondary'} badge-lg">
                  ${loan.status == 1 ? 'Activo' : 'Inactivo'}
                </span>
              </div>
            </div>
            <div class="col-md-3">
              <div class="text-center">
                <h6 class="text-muted">FECHA DE CREACIÓN</h6>
                <p class="mb-0">${new Date(loan.date).toLocaleDateString('es-CO')}</p>
              </div>
            </div>
            <div class="col-md-3">
              <div class="text-center">
                <h6 class="text-muted">FRECUENCIA</h6>
                <p class="mb-0">${loan.num_fee} cuotas (${loan.payment_m})</p>
              </div>
            </div>
          </div>

          <!-- Tabla de Cuotas -->
          <div class="table-responsive">
            <table class="table table-hover">
              <thead class="thead-dark">
                <tr>
                  <th class="text-center">#</th>
                  <th class="text-center">Fecha Vencimiento</th>
                  <th class="text-center">Monto Cuota</th>
                  <th class="text-center">Estado</th>
                  <th class="text-center">Días Atraso</th>
                  <th class="text-center">Acciones</th>
                </tr>
              </thead>
              <tbody>`;

    if (loan.all_quotas && loan.all_quotas.length > 0) {
      loan.all_quotas.forEach(function(quota) {
        const dueDate = new Date(quota.date);
        const today = new Date();
        const isOverdue = dueDate < today && quota.status == 1;
        const daysOverdue = isOverdue ? Math.floor((today - dueDate) / (1000 * 60 * 60 * 24)) : 0;

        let rowClass = '';
        let statusBadge = '';
        let actionBtn = '';

        if (quota.status == 0) {
          rowClass = 'table-success';
          statusBadge = '<span class="badge badge-success">Pagada</span>';
          actionBtn = '<button class="btn btn-sm btn-outline-success" disabled><i class="fas fa-check"></i></button>';
        } else if (isOverdue) {
          rowClass = 'table-danger';
          statusBadge = '<span class="badge badge-danger">Vencida</span>';
          actionBtn = '<button class="btn btn-sm btn-outline-danger" onclick="processPayment(' + quota.id + ', ' + loan.id + ')"><i class="fas fa-credit-card"></i></button>';
        } else {
          rowClass = 'table-warning';
          statusBadge = '<span class="badge badge-warning">Pendiente</span>';
          actionBtn = '<button class="btn btn-sm btn-outline-warning" onclick="processPayment(' + quota.id + ', ' + loan.id + ')"><i class="fas fa-clock"></i></button>';
        }

        html += `
          <tr class="${rowClass}">
            <td class="text-center font-weight-bold">${quota.num_quota}</td>
            <td class="text-center">${dueDate.toLocaleDateString('es-CO')}</td>
            <td class="text-center">$ ${parseFloat(quota.fee_amount).toLocaleString('es-CO', {minimumFractionDigits: 0, maximumFractionDigits: 0})}</td>
            <td class="text-center">${statusBadge}</td>
            <td class="text-center">
              ${isOverdue ? `<span class="badge badge-danger">${daysOverdue} días</span>` : '<span class="text-muted">-</span>'}
            </td>
            <td class="text-center">${actionBtn}</td>
          </tr>`;
      });
    } else {
      html += '<tr><td colspan="6" class="text-center text-muted py-4"><i class="fas fa-info-circle"></i> No hay cuotas registradas para este préstamo</td></tr>';
    }

    html += `
              </tbody>
            </table>
          </div>

          <!-- Resumen -->
          <div class="row mt-3">
            <div class="col-md-4">
              <div class="card bg-light">
                <div class="card-body text-center">
                  <h6>Cuotas Pagadas</h6>
                  <h4 class="text-success">${loan.all_quotas ? loan.all_quotas.filter(q => q.status == 0).length : 0}</h4>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="card bg-light">
                <div class="card-body text-center">
                  <h6>Cuotas Pendientes</h6>
                  <h4 class="text-warning">${loan.all_quotas ? loan.all_quotas.filter(q => q.status == 1).length : 0}</h4>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="card bg-light">
                <div class="card-body text-center">
                  <h6>Cuotas Vencidas</h6>
                  <h4 class="text-danger">${loan.all_quotas ? loan.all_quotas.filter(q => {
                    const dueDate = new Date(q.date);
                    const today = new Date();
                    return dueDate < today && q.status == 1;
                  }).length : 0}</h4>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>`;
  });

  $('#loanDetailsContent').html(html);
}

function processPayment(quotaId, loanId) {
  console.log('processPayment llamada con quotaId:', quotaId, 'loanId:', loanId);

  if (confirm('¿Desea procesar el pago de esta cuota?')) {
    // Mostrar indicador de carga
    var btn = event.target.closest('button');
    var originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    btn.disabled = true;

    // Preparar datos con CSRF token
    var data = {
      quota_id: quotaId,
      loan_id: loanId
    };

    // Agregar token CSRF si está disponible
    if (typeof window.csrf_name !== 'undefined' && typeof window.csrf_hash !== 'undefined') {
      data[window.csrf_name] = window.csrf_hash;
    }

    $.ajax({
      url: '<?= site_url("admin/payments/process_payment") ?>',
      type: 'POST',
      data: data,
      success: function(response) {
        console.log('Respuesta de processPayment:', response);
        try {
          if (typeof response === 'string') {
            response = JSON.parse(response);
          }

          if (response.success) {
            alert('Pago procesado exitosamente');
            // Recargar los detalles del préstamo
            viewLoanDetails(loanId.toString());
            // Actualizar estadísticas
            updateStatistics();
          } else {
            alert('Error al procesar el pago: ' + (response.error || 'Error desconocido'));
          }
        } catch (e) {
          console.error('Error procesando respuesta JSON:', e);
          alert('Error procesando respuesta del servidor');
        }
      },
      error: function(xhr, status, error) {
        console.error('Error AJAX processPayment:', xhr, status, error);
        alert('Error de conexión. Revisa la consola para más detalles.');
      },
      complete: function() {
        // Restaurar botón
        btn.innerHTML = originalText;
        btn.disabled = false;
      }
    });
  }
}
</script>