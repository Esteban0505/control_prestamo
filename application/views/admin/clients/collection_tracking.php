<div class="container-fluid mt-4">
  <h3 class="mb-3 text-center">📋 Seguimiento de Cobranza</h3>

  <!-- Acciones Rápidas -->
  <div class="row mb-4">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header bg-primary text-white">
          <h5 class="mb-0"><i class="fas fa-tasks"></i> Acciones de Seguimiento</h5>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-3">
              <button type="button" class="btn btn-success btn-block" onclick="showAssignCollectorModal()">
                <i class="fas fa-user-plus"></i><br>Asignar Cobrador
              </button>
            </div>
            <div class="col-md-3">
              <button type="button" class="btn btn-info btn-block" onclick="showLogActionModal()">
                <i class="fas fa-clipboard-list"></i><br>Registrar Acción
              </button>
            </div>
            <div class="col-md-3">
              <button type="button" class="btn btn-warning btn-block" onclick="showPendingFollowups()">
                <i class="fas fa-clock"></i><br>Seguimientos Pendientes
              </button>
            </div>
            <div class="col-md-3">
              <button type="button" class="btn btn-danger btn-block" onclick="showEscalatedCases()">
                <i class="fas fa-exclamation-triangle"></i><br>Casos Escalados
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Lista de Seguimientos Pendientes -->
  <div class="card shadow">
    <div class="card-header bg-warning text-white d-flex justify-content-between align-items-center">
      <h5 class="mb-0"><i class="fas fa-clock"></i> Seguimientos Pendientes (<?= count($pending_followups ?? []) ?> casos)</h5>
      <button type="button" class="btn btn-light btn-sm" onclick="refreshData()">
        <i class="fas fa-sync-alt"></i> Actualizar
      </button>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-striped" id="followups-table">
          <thead>
            <tr>
              <th>Prioridad</th>
              <th>Cliente</th>
              <th>Cédula</th>
              <th>Teléfono</th>
              <th>Deuda Total</th>
              <th>Próximo Seguimiento</th>
              <th>Cobrador Asignado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($pending_followups)): ?>
              <?php foreach ($pending_followups as $followup): ?>
                <?php
                $priority_class = '';
                $priority_icon = '';
                switch ($followup->priority) {
                  case 'critical':
                    $priority_class = 'table-danger';
                    $priority_icon = '🔴';
                    break;
                  case 'high':
                    $priority_class = 'table-warning';
                    $priority_icon = '🟡';
                    break;
                  case 'medium':
                    $priority_class = 'table-info';
                    $priority_icon = '🟢';
                    break;
                  default:
                    $priority_class = 'table-light';
                    $priority_icon = '⚪';
                }
                ?>
                <tr class="<?= $priority_class ?>">
                  <td>
                    <span class="badge badge-pill" title="Prioridad <?= ucfirst($followup->priority) ?>">
                      <?= $priority_icon ?> <?= ucfirst($followup->priority) ?>
                    </span>
                  </td>
                  <td>
                    <a href="#" onclick="viewClientDetails(<?= $followup->customer_id ?>)">
                      <?= htmlspecialchars($followup->first_name . ' ' . $followup->last_name) ?>
                    </a>
                  </td>
                  <td><?= htmlspecialchars($followup->dni) ?></td>
                  <td><?= htmlspecialchars($followup->mobile) ?></td>
                  <td>$<?= number_format($followup->total_debt, 2, ',', '.') ?></td>
                  <td>
                    <?php
                    $next_date = new DateTime($followup->next_followup_date);
                    $now = new DateTime();
                    $diff = $now->diff($next_date);
                    $is_overdue = $next_date < $now;
                    ?>
                    <span class="<?= $is_overdue ? 'text-danger font-weight-bold' : 'text-success' ?>">
                      <?= $next_date->format('d/m/Y H:i') ?>
                      <?php if ($is_overdue): ?>
                        <br><small>(Atrasado)</small>
                      <?php endif; ?>
                    </span>
                  </td>
                  <td>
                    <?php if ($followup->assigned_user_id): ?>
                      <span class="badge badge-primary">
                        <i class="fas fa-user"></i> Asignado
                      </span>
                    <?php else: ?>
                      <span class="badge badge-secondary">
                        <i class="fas fa-user-times"></i> Sin asignar
                      </span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div class="btn-group" role="group">
                      <button type="button" class="btn btn-sm btn-outline-primary" onclick="logAction(<?= $followup->customer_id ?>)">
                        <i class="fas fa-plus"></i>
                      </button>
                      <button type="button" class="btn btn-sm btn-outline-info" onclick="viewHistory(<?= $followup->customer_id ?>)">
                        <i class="fas fa-history"></i>
                      </button>
                      <button type="button" class="btn btn-sm btn-outline-success" onclick="markResolved(<?= $followup->customer_id ?>)">
                        <i class="fas fa-check"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="8" class="text-center text-muted">
                  <i class="fas fa-check-circle fa-2x mb-2"></i><br>
                  No hay seguimientos pendientes 🎉
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal para Asignar Cobrador -->
<div class="modal fade" id="assignCollectorModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Asignar Cobrador</h5>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="assignCollectorForm">
          <div class="form-group">
            <label for="customer_select">Seleccionar Cliente:</label>
            <select class="form-control" id="customer_select" name="customer_id" required>
              <option value="">Buscar cliente...</option>
            </select>
          </div>
          <div class="form-group">
            <label for="collector_select">Seleccionar Cobrador:</label>
            <select class="form-control" id="collector_select" name="user_id" required>
              <option value="">Seleccionar cobrador...</option>
              <!-- Opciones cargadas dinámicamente -->
            </select>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" onclick="assignCollector()">Asignar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal para Registrar Acción -->
<div class="modal fade" id="logActionModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Registrar Acción de Cobranza</h5>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="logActionForm">
          <input type="hidden" id="action_tracking_id" name="tracking_id">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label for="action_customer">Cliente:</label>
                <select class="form-control" id="action_customer" name="customer_id" required>
                  <option value="">Seleccionar cliente...</option>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label for="action_type">Tipo de Acción:</label>
                <select class="form-control" id="action_type" name="action_type" required>
                  <option value="call">Llamada telefónica</option>
                  <option value="email">Correo electrónico</option>
                  <option value="sms">Mensaje SMS</option>
                  <option value="visit">Visita personal</option>
                  <option value="payment">Pago recibido</option>
                  <option value="negotiation">Negociación</option>
                  <option value="escalation">Escalamiento</option>
                  <option value="resolution">Resolución</option>
                </select>
              </div>
            </div>
          </div>
          <div class="form-group">
            <label for="action_description">Descripción de la Acción:</label>
            <textarea class="form-control" id="action_description" name="action_description" rows="3" required></textarea>
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label for="contact_result">Resultado del Contacto:</label>
                <select class="form-control" id="contact_result" name="contact_result">
                  <option value="">Seleccionar resultado...</option>
                  <option value="contacted">Contactado exitosamente</option>
                  <option value="no_answer">Sin respuesta</option>
                  <option value="wrong_number">Número incorrecto</option>
                  <option value="callback_requested">Solicitó devolución de llamada</option>
                  <option value="payment_promise">Promesa de pago</option>
                  <option value="refusal">Rechazo/Negación</option>
                  <option value="negotiation">Negociación en curso</option>
                  <option value="escalated">Caso escalado</option>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label for="amount_collected">Monto Recaudado ($):</label>
                <input type="number" class="form-control" id="amount_collected" name="amount_collected" step="0.01" min="0">
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label for="next_action_date">Próxima Acción:</label>
                <input type="datetime-local" class="form-control" id="next_action_date" name="next_action_date">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label for="action_notes">Notas Adicionales:</label>
                <textarea class="form-control" id="action_notes" name="notes" rows="2"></textarea>
              </div>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" onclick="saveAction()">Guardar Acción</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal para Ver Historial -->
<div class="modal fade" id="historyModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Historial de Cobranza</h5>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div id="historyContent">
          Cargando historial...
        </div>
      </div>
    </div>
  </div>
</div>

<script>
$(document).ready(function(){
  loadCollectors();
  initializeCustomerSearch();
});

function refreshData() {
  location.reload();
}

function showAssignCollectorModal() {
  $('#assignCollectorModal').modal('show');
}

function showLogActionModal() {
  $('#logActionModal').modal('show');
}

function showPendingFollowups() {
  // Ya estamos en la vista de seguimientos pendientes
  $('html, body').animate({ scrollTop: 0 }, 'slow');
}

function showEscalatedCases() {
  alert('Funcionalidad de casos escalados próximamente disponible');
}

function loadCollectors() {
  $.ajax({
    url: '<?= site_url("admin/users/get_active_users") ?>',
    type: 'POST',
    success: function(response) {
      if (response.success) {
        let options = '<option value="">Seleccionar cobrador...</option>';
        response.users.forEach(function(user) {
          options += `<option value="${user.id}">${user.first_name} ${user.last_name}</option>`;
        });
        $('#collector_select').html(options);
      }
    }
  });
}

function initializeCustomerSearch() {
  $('#customer_select, #action_customer').select2({
    placeholder: 'Buscar cliente...',
    ajax: {
      url: '<?= site_url("admin/payments/search_customers") ?>',
      dataType: 'json',
      delay: 300,
      data: function (params) {
        return {
          q: params.term
        };
      },
      processResults: function (data) {
        return {
          results: data.results
        };
      },
      cache: true
    },
    minimumInputLength: 2
  });
}

function assignCollector() {
  const formData = new FormData(document.getElementById('assignCollectorForm'));

  $.ajax({
    url: '<?= site_url("admin/customers/assign_collector") ?>',
    type: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    success: function(response) {
      if (response.success) {
        alert('Cobrador asignado exitosamente');
        $('#assignCollectorModal').modal('hide');
        refreshData();
      } else {
        alert('Error: ' + response.error);
      }
    },
    error: function() {
      alert('Error de conexión');
    }
  });
}

function logAction(customerId = null) {
  if (customerId) {
    // Cargar datos del cliente si se proporciona ID
    $('#action_customer').val(customerId).trigger('change');
  }
  $('#logActionModal').modal('show');
}

function saveAction() {
  const formData = new FormData(document.getElementById('logActionForm'));

  $.ajax({
    url: '<?= site_url("admin/customers/log_collection_action") ?>',
    type: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    success: function(response) {
      if (response.success) {
        alert('Acción registrada exitosamente');
        $('#logActionModal').modal('hide');
        $('#logActionForm')[0].reset();
        refreshData();
      } else {
        alert('Error: ' + response.error);
      }
    },
    error: function() {
      alert('Error de conexión');
    }
  });
}

function viewHistory(customerId) {
  $.ajax({
    url: '<?= site_url("admin/customers/get_collection_details") ?>',
    type: 'POST',
    data: { customer_id: customerId },
    success: function(response) {
      if (response.success) {
        displayHistory(response.tracking);
        $('#historyModal').modal('show');
      } else {
        alert('Error: ' + response.error);
      }
    },
    error: function() {
      alert('Error al cargar historial');
    }
  });
}

function displayHistory(tracking) {
  let html = `
    <div class="row mb-3">
      <div class="col-md-6">
        <h6>Información del Caso</h6>
        <p><strong>Estado:</strong> <span class="badge badge-${getStatusBadge(tracking.status)}">${tracking.status}</span></p>
        <p><strong>Prioridad:</strong> <span class="badge badge-${getPriorityBadge(tracking.priority)}">${tracking.priority}</span></p>
        <p><strong>Cobrador Asignado:</strong> ${tracking.assigned_user_name || 'No asignado'}</p>
      </div>
      <div class="col-md-6">
        <h6>Estadísticas</h6>
        <p><strong>Deuda Total:</strong> $${parseFloat(tracking.total_debt).toLocaleString('es-CO')}</p>
        <p><strong>Monto Recaudado:</strong> $${parseFloat(tracking.collected_amount).toLocaleString('es-CO')}</p>
        <p><strong>Último Contacto:</strong> ${tracking.last_contact_date || 'Nunca'}</p>
      </div>
    </div>
    <h6>Historial de Acciones</h6>
    <div class="timeline">`;

  if (tracking.actions && tracking.actions.length > 0) {
    tracking.actions.forEach(function(action) {
      html += `
        <div class="timeline-item">
          <div class="timeline-marker bg-primary"></div>
          <div class="timeline-content">
            <h6 class="timeline-title">${getActionTypeLabel(action.action_type)}</h6>
            <p class="text-muted mb-1">${new Date(action.created_at).toLocaleString('es-CO')}</p>
            <p>${action.action_description}</p>
            ${action.contact_result ? `<p><strong>Resultado:</strong> ${getContactResultLabel(action.contact_result)}</p>` : ''}
            ${action.amount_collected > 0 ? `<p><strong>Monto Recaudado:</strong> $${parseFloat(action.amount_collected).toLocaleString('es-CO')}</p>` : ''}
            ${action.notes ? `<p><strong>Notas:</strong> ${action.notes}</p>` : ''}
            <small class="text-muted">Por: ${action.performed_by_name || 'Sistema'}</small>
          </div>
        </div>`;
    });
  } else {
    html += '<p class="text-muted">No hay acciones registradas para este caso.</p>';
  }

  html += '</div>';
  $('#historyContent').html(html);
}

function getStatusBadge(status) {
  const badges = {
    'active': 'primary',
    'resolved': 'success',
    'escalated': 'warning',
    'legal': 'danger'
  };
  return badges[status] || 'secondary';
}

function getPriorityBadge(priority) {
  const badges = {
    'low': 'secondary',
    'medium': 'info',
    'high': 'warning',
    'critical': 'danger'
  };
  return badges[priority] || 'secondary';
}

function getActionTypeLabel(type) {
  const labels = {
    'call': 'Llamada Telefónica',
    'email': 'Correo Electrónico',
    'sms': 'Mensaje SMS',
    'visit': 'Visita Personal',
    'payment': 'Pago Recibido',
    'negotiation': 'Negociación',
    'escalation': 'Escalamiento',
    'resolution': 'Resolución'
  };
  return labels[type] || type;
}

function getContactResultLabel(result) {
  const labels = {
    'contacted': 'Contactado exitosamente',
    'no_answer': 'Sin respuesta',
    'wrong_number': 'Número incorrecto',
    'callback_requested': 'Solicitó devolución de llamada',
    'payment_promise': 'Promesa de pago',
    'refusal': 'Rechazo/Negación',
    'negotiation': 'Negociación en curso',
    'escalated': 'Caso escalado'
  };
  return labels[result] || result;
}

function markResolved(customerId) {
  if (confirm('¿Marcar este caso como resuelto?')) {
    $.ajax({
      url: '<?= site_url("admin/customers/update_collection_status") ?>',
      type: 'POST',
      data: {
        customer_id: customerId,
        status: 'resolved',
        notes: 'Caso marcado como resuelto desde la interfaz'
      },
      success: function(response) {
        if (response.success) {
          alert('Caso marcado como resuelto');
          refreshData();
        } else {
          alert('Error: ' + response.error);
        }
      },
      error: function() {
        alert('Error de conexión');
      }
    });
  }
}

function viewClientDetails(customerId) {
  // Implementar vista de detalles del cliente
  alert('Vista de detalles del cliente próximamente disponible. ID: ' + customerId);
}
</script>

<style>
.timeline {
  position: relative;
  padding-left: 30px;
}

.timeline::before {
  content: '';
  position: absolute;
  left: 15px;
  top: 0;
  bottom: 0;
  width: 2px;
  background: #e9ecef;
}

.timeline-item {
  position: relative;
  margin-bottom: 20px;
}

.timeline-marker {
  position: absolute;
  left: -22px;
  top: 5px;
  width: 12px;
  height: 12px;
  border-radius: 50%;
  border: 2px solid #fff;
}

.timeline-title {
  margin-bottom: 5px;
  font-weight: bold;
}

.timeline-content {
  background: #f8f9fa;
  padding: 15px;
  border-radius: 5px;
  border-left: 3px solid #007bff;
}
</style>