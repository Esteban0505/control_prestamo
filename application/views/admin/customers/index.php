<!-- Header con estadísticas rápidas -->
<div class="row mb-4">
  <div class="col-xl-3 col-md-6 mb-4">
    <div class="card border-left-primary shadow h-100 py-2">
      <div class="card-body">
        <div class="row no-gutters align-items-center">
          <div class="col mr-2">
            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
              <i class="fas fa-users mr-1"></i>Total Clientes
            </div>
            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo count($customers) ?></div>
          </div>
          <div class="col-auto">
            <i class="fas fa-users fa-2x text-primary"></i>
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
            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
              <i class="fas fa-user-check mr-1"></i>Sin Crédito
            </div>
            <div class="h5 mb-0 font-weight-bold text-gray-800">
              <?php echo count(array_filter($customers, function($c) { return !$c->loan_status; })) ?>
            </div>
          </div>
          <div class="col-auto">
            <i class="fas fa-user-check fa-2x text-success"></i>
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
            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
              <i class="fas fa-user-clock mr-1"></i>Con Crédito
            </div>
            <div class="h5 mb-0 font-weight-bold text-gray-800">
              <?php echo count(array_filter($customers, function($c) { return $c->loan_status; })) ?>
            </div>
          </div>
          <div class="col-auto">
            <i class="fas fa-user-clock fa-2x text-warning"></i>
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
            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
              <i class="fas fa-star mr-1"></i>Clientes Especiales
            </div>
            <div class="h5 mb-0 font-weight-bold text-gray-800">
              <?php echo count(array_filter($customers, function($c) { return $c->tipo_cliente == 'especial'; })) ?>
            </div>
          </div>
          <div class="col-auto">
            <i class="fas fa-star fa-2x text-info"></i>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Filtros de búsqueda -->
<div class="card shadow mb-4">
  <div class="card-header py-3">
    <h6 class="m-0 font-weight-bold text-primary">
      <i class="fas fa-filter mr-2"></i>Filtros de Búsqueda
    </h6>
  </div>
  <div class="card-body">
    <div class="row">
      <div class="col-md-3 mb-3">
        <input type="text" class="form-control" id="searchDni" placeholder="Buscar por Cédula...">
      </div>
      <div class="col-md-3 mb-3">
        <input type="text" class="form-control" id="searchName" placeholder="Buscar por Nombre...">
      </div>
      <div class="col-md-3 mb-3">
        <select class="form-control" id="filterStatus">
          <option value="">Todos los estados</option>
          <option value="0">Sin Crédito</option>
          <option value="1">Con Crédito</option>
        </select>
      </div>
      <div class="col-md-3 mb-3">
        <select class="form-control" id="filterType">
          <option value="">Todos los tipos</option>
          <option value="normal">Normal</option>
          <option value="especial">Especial</option>
        </select>
      </div>
    </div>
    <div class="row">
      <div class="col-12">
        <button class="btn btn-primary btn-sm" id="clearFilters">
          <i class="fas fa-eraser mr-1"></i>Limpiar Filtros
        </button>
        <span class="float-right text-muted" id="resultsCount">
          Mostrando <?php echo count($customers) ?> clientes
        </span>
      </div>
    </div>
  </div>
</div>

<!-- Tabla de clientes -->
<div class="card shadow mb-4">
  <div class="card-header d-flex align-items-center justify-content-between py-3">
    <h6 class="m-0 font-weight-bold text-primary">
      <i class="fas fa-users mr-2"></i>Listado de Clientes
    </h6>
    <a class="d-sm-inline-block btn btn-sm btn-success shadow-sm" href="<?php echo site_url('admin/customers/edit'); ?>">
      <i class="fas fa-plus-circle fa-sm mr-1"></i>Nuevo Cliente
    </a>
  </div>
  <div class="card-body">
    <?php if ($this->session->flashdata('msg')): ?>
      <div class="alert alert-success alert-dismissible fade show text-center" role="alert">
        <i class="fas fa-check-circle mr-2"></i>
        <?= $this->session->flashdata('msg') ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
    <?php endif ?>

    <div class="table-responsive">
      <table class="table table-hover" id="customersTable" width="100%" cellspacing="0">
        <thead class="thead-light">
          <tr>
            <th><i class="fas fa-id-card mr-1"></i>Cédula</th>
            <th><i class="fas fa-user mr-1"></i>Nombre Completo</th>
            <th><i class="fas fa-venus-mars mr-1"></i>Género</th>
            <th><i class="fas fa-phone mr-1"></i>Contacto</th>
            <th><i class="fas fa-user-tie mr-1"></i>Asesor</th>
            <th><i class="fas fa-crown mr-1"></i>Tipo</th>
            <th><i class="fas fa-info-circle mr-1"></i>Estado</th>
            <th><i class="fas fa-cogs mr-1"></i>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php if(count($customers)): foreach($customers as $ct): ?>
            <tr data-dni="<?php echo strtolower($ct->dni) ?>"
                data-name="<?php echo strtolower($ct->first_name . ' ' . $ct->last_name) ?>"
                data-status="<?php echo $ct->loan_status ?>"
                data-type="<?php echo $ct->tipo_cliente ?>">
              <td>
                <span class="font-weight-bold"><?php echo $ct->dni ?></span>
              </td>
              <td>
                <div class="d-flex align-items-center">
                  <div class="avatar-circle bg-primary text-white mr-2" style="width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: bold;">
                    <?php echo strtoupper(substr($ct->first_name, 0, 1) . substr($ct->last_name, 0, 1)) ?>
                  </div>
                  <div>
                    <div class="font-weight-bold"><?php echo $ct->first_name . ' ' . $ct->last_name ?></div>
                    <small class="text-muted"><?php echo $ct->company ?: 'Sin empresa' ?></small>
                  </div>
                </div>
              </td>
              <td>
                <?php if($ct->gender == 'masculino'): ?>
                  <span class="badge badge-primary"><i class="fas fa-mars mr-1"></i>Masculino</span>
                <?php elseif($ct->gender == 'femenino'): ?>
                  <span class="badge badge-danger"><i class="fas fa-venus mr-1"></i>Femenino</span>
                <?php else: ?>
                  <span class="badge badge-secondary"><i class="fas fa-genderless mr-1"></i>No especificado</span>
                <?php endif; ?>
              </td>
              <td>
                <div>
                  <div><i class="fas fa-mobile-alt mr-1"></i><?php echo $ct->mobile ?: 'No registrado' ?></div>
                  <?php if($ct->phone): ?>
                    <small class="text-muted"><i class="fas fa-envelope mr-1"></i><?php echo $ct->phone ?></small>
                  <?php endif; ?>
                </div>
              </td>
              <td>
                <?php if($ct->user_name): ?>
                  <span class="badge badge-info"><i class="fas fa-user-tie mr-1"></i><?php echo $ct->user_name ?></span>
                <?php else: ?>
                  <span class="badge badge-warning"><i class="fas fa-exclamation-triangle mr-1"></i>Sin asignar</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if($ct->tipo_cliente == 'especial'): ?>
                  <span class="badge badge-warning"><i class="fas fa-star mr-1"></i>Especial</span>
                <?php else: ?>
                  <span class="badge badge-secondary"><i class="fas fa-user mr-1"></i>Normal</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if($ct->loan_status): ?>
                  <span class="badge badge-danger badge-lg">
                    <i class="fas fa-exclamation-circle mr-1"></i>Con Crédito Activo
                  </span>
                <?php else: ?>
                  <span class="badge badge-success badge-lg">
                    <i class="fas fa-check-circle mr-1"></i>Sin Crédito
                  </span>
                <?php endif; ?>
              </td>
              <td>
                <div class="btn-group" role="group">
                  <a href="<?php echo site_url('admin/customers/edit/'.$ct->id); ?>"
                     class="btn btn-sm btn-outline-primary"
                     title="Editar cliente">
                    <i class="fas fa-edit"></i>
                  </a>
                  <button type="button"
                          class="btn btn-sm btn-outline-info"
                          title="Ver detalles"
                          onclick="viewCustomerDetails(<?php echo $ct->id ?>)">
                    <i class="fas fa-eye"></i>
                  </button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="8" class="text-center py-5">
                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No existen clientes registrados</h5>
                <p class="text-muted">Comienza agregando tu primer cliente al sistema.</p>
                <a href="<?php echo site_url('admin/customers/edit'); ?>" class="btn btn-primary">
                  <i class="fas fa-plus-circle mr-1"></i>Agregar Primer Cliente
                </a>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal para detalles del cliente -->
<div class="modal fade" id="customerDetailsModal" tabindex="-1" role="dialog" aria-labelledby="customerDetailsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="customerDetailsModalLabel">
          <i class="fas fa-user mr-2"></i>Detalles del Cliente
        </h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="customerDetailsContent">
        <!-- Contenido cargado dinámicamente -->
      </div>
    </div>
  </div>
</div>

<script>
// Función para filtrar la tabla
function filterTable() {
  const searchDni = $('#searchDni').val().toLowerCase();
  const searchName = $('#searchName').val().toLowerCase();
  const filterStatus = $('#filterStatus').val();
  const filterType = $('#filterType').val();

  let visibleCount = 0;

  $('#customersTable tbody tr').each(function() {
    const row = $(this);
    const dni = row.data('dni') || '';
    const name = row.data('name') || '';
    const status = row.data('status') || '';
    const type = row.data('type') || '';

    const matchesDni = !searchDni || dni.includes(searchDni);
    const matchesName = !searchName || name.includes(searchName);
    const matchesStatus = !filterStatus || status == filterStatus;
    const matchesType = !filterType || type == filterType;

    if (matchesDni && matchesName && matchesStatus && matchesType) {
      row.show();
      visibleCount++;
    } else {
      row.hide();
    }
  });

  $('#resultsCount').text(`Mostrando ${visibleCount} clientes`);
}

// Función para ver detalles del cliente
function viewCustomerDetails(customerId) {
  // Aquí puedes implementar la carga de detalles adicionales
  // Por ahora, solo redirigimos a la edición
  window.location.href = '<?php echo site_url('admin/customers/edit/'); ?>' + customerId;
}

$(document).ready(function() {
  // Filtros en tiempo real
  $('#searchDni, #searchName, #filterStatus, #filterType').on('input change', function() {
    filterTable();
  });

  // Limpiar filtros
  $('#clearFilters').on('click', function() {
    $('#searchDni, #searchName').val('');
    $('#filterStatus, #filterType').val('');
    filterTable();
  });

  // Inicializar contador
  filterTable();
});
</script>

<style>
.avatar-circle {
  font-size: 12px;
}

.badge-lg {
  font-size: 0.75rem;
  padding: 0.5rem 0.75rem;
}

.table-hover tbody tr:hover {
  background-color: rgba(0, 0, 0, 0.075);
}

.card-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.text-primary {
  color: #5a5c69 !important;
}
</style>