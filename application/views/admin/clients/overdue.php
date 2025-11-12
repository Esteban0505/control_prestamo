<style>
  /* Estilos personalizados - Diseño Limpio y Profesional */
  .module-header {
    background: #ffffff;
    color: #1a1a1a;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.08);
    margin-bottom: 2rem;
    border: 1px solid #e0e0e0;
    border-left: 4px solid #d32f2f;
  }

  .stat-card {
    border-left: 4px solid #d32f2f;
    transition: transform 0.2s, box-shadow 0.2s;
    border-radius: 6px;
    height: 100%;
    background: #ffffff;
    border: 1px solid #e0e0e0;
  }

  .stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1) !important;
  }

  .stat-card.danger { border-left-color: #d32f2f; }
  .stat-card.warning { border-left-color: #f57c00; }
  .stat-card.info { border-left-color: #1976d2; }
  .stat-card.primary { border-left-color: #d32f2f; }

  .risk-high { 
    background-color: #ffffff !important; 
    border-left: 3px solid #d32f2f !important;
  }
  .risk-medium { 
    background-color: #ffffff !important; 
    border-left: 3px solid #f57c00 !important;
  }
  .risk-low { 
    background-color: #ffffff !important; 
    border-left: 3px solid #1976d2 !important;
  }

  .table-client-name {
    font-weight: 600;
    color: #1a1a1a;
  }

  .badge-custom {
    padding: 0.4em 0.7em;
    font-size: 0.85em;
    font-weight: 500;
    border-radius: 4px;
  }

  .filter-card {
    border-radius: 6px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    border: 1px solid #e0e0e0;
    background: #ffffff;
  }

  .btn-action {
    border-radius: 4px;
    padding: 0.4rem 0.8rem;
    font-weight: 500;
    transition: all 0.2s;
  }

  .btn-action:hover {
    transform: scale(1.02);
  }

  .amount-display {
    font-size: 1.1rem;
    font-weight: 700;
    color: #d32f2f;
  }

  .days-badge {
    font-size: 0.9rem;
    padding: 0.4rem 0.8rem;
    border-radius: 4px;
    background: #d32f2f !important;
    color: white !important;
  }

  .module-icon {
    font-size: 2.5rem;
    color: #d32f2f;
  }

  .stat-icon {
    font-size: 2.5rem;
    opacity: 0.6;
  }

  .empty-state {
    padding: 4rem 2rem;
    text-align: center;
  }

  .empty-state-icon {
    font-size: 4rem;
    color: #4caf50;
    margin-bottom: 1rem;
  }

  .table th {
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    background-color: #f5f5f5 !important;
    color: #1a1a1a !important;
    font-weight: 600;
    border-bottom: 2px solid #e0e0e0;
  }

  .table td {
    vertical-align: middle;
    padding: 1rem 0.75rem;
    color: #1a1a1a;
    border-bottom: 1px solid #f0f0f0;
  }

  .table tbody tr:hover {
    background-color: #f9f9f9;
  }

  .btn-group-sm .btn {
    border-radius: 4px;
    margin: 0 2px;
  }

  .modal-header {
    border-radius: 6px 6px 0 0;
    background: #ffffff;
    color: #1a1a1a;
    border-bottom: 2px solid #e0e0e0;
  }

  .input-group-text {
    background-color: #f5f5f5;
    border-color: #d0d0d0;
    color: #1a1a1a;
  }

  .form-control:focus,
  .form-select:focus {
    border-color: #d32f2f;
    box-shadow: 0 0 0 0.2rem rgba(211, 47, 47, 0.15);
  }

  .card {
    border: 1px solid #e0e0e0;
    background: #ffffff;
  }

  .card-header {
    background: #f5f5f5;
    border-bottom: 1px solid #e0e0e0;
    color: #1a1a1a;
  }

  .btn-danger, .btn-primary {
    background-color: #d32f2f;
    border-color: #d32f2f;
    color: white;
  }

  .btn-danger:hover, .btn-primary:hover {
    background-color: #b71c1c;
    border-color: #b71c1c;
    color: white;
  }

  .btn-outline-danger {
    color: #d32f2f;
    border-color: #d32f2f;
  }

  .btn-outline-danger:hover {
    background-color: #d32f2f;
    border-color: #d32f2f;
    color: white;
  }

  .text-danger {
    color: #d32f2f !important;
  }

  .text-muted {
    color: #666666 !important;
  }

  body {
    background-color: #fafafa;
  }

  /* Badges con mejor contraste */
  .badge {
    font-weight: 500;
  }

  @media (max-width: 768px) {
    .module-header {
      padding: 1.5rem;
    }
    
    .module-header h1 {
      font-size: 1.5rem;
    }
    
    .stat-card {
      margin-bottom: 1rem;
    }

    .table th,
    .table td {
      font-size: 0.85rem;
      padding: 0.5rem;
    }

    .btn-action {
      padding: 0.3rem 0.6rem;
      font-size: 0.8rem;
    }
  }

  @keyframes fadeIn {
    from {
      opacity: 0;
      transform: translateY(10px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .card {
    animation: fadeIn 0.3s ease-in;
  }
</style>

<div class="container-fluid py-4">
  <!-- Header del Módulo -->
  <div class="module-header">
    <div class="row align-items-center">
      <div class="col-md-8">
        <h1 class="mb-2" style="font-size: 2rem; font-weight: 700;">
          <i class="fas fa-exclamation-triangle module-icon mr-3"></i>
          Gestión de Clientes Morosos
        </h1>
        <p class="mb-0" style="font-size: 1.1rem; opacity: 0.95;">
          <i class="fas fa-info-circle mr-2"></i>
          Sistema de control y seguimiento de clientes con pagos vencidos
        </p>
      </div>
      <div class="col-md-4 text-md-right mt-3 mt-md-0">
        <div class="d-inline-block bg-light p-3 rounded border">
          <div class="text-muted small mb-1">Total Registros</div>
          <div class="h3 mb-0 font-weight-bold" style="color: #d32f2f;"><?= $total_records ?? count($clients ?? []) ?></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Tarjetas de Estadísticas -->
  <div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
      <div class="card stat-card danger shadow-sm h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start">
            <div class="flex-grow-1">
              <h6 class="text-uppercase text-muted mb-2 small font-weight-bold">Alto Riesgo</h6>
              <h2 class="mb-1 font-weight-bold" style="color: #d32f2f;"><?= isset($statistics['high_risk_count']) ? $statistics['high_risk_count'] : 0 ?></h2>
              <p class="text-muted small mb-0">
                <i class="fas fa-calendar-times mr-1"></i>
                60+ días de atraso
              </p>
            </div>
            <div style="color: #d32f2f; opacity: 0.6;">
              <i class="fas fa-exclamation-triangle stat-icon"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-3 col-md-6 mb-3">
      <div class="card stat-card warning shadow-sm h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start">
            <div class="flex-grow-1">
              <h6 class="text-uppercase text-muted mb-2 small font-weight-bold">Riesgo Medio</h6>
              <h2 class="mb-1 font-weight-bold" style="color: #f57c00;"><?= isset($statistics['medium_risk_count']) ? $statistics['medium_risk_count'] : 0 ?></h2>
              <p class="text-muted small mb-0">
                <i class="fas fa-clock mr-1"></i>
                30-59 días de atraso
              </p>
            </div>
            <div style="color: #f57c00; opacity: 0.6;">
              <i class="fas fa-exclamation-circle stat-icon"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-3 col-md-6 mb-3">
      <div class="card stat-card info shadow-sm h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start">
            <div class="flex-grow-1">
              <h6 class="text-uppercase text-muted mb-2 small font-weight-bold">Riesgo Bajo</h6>
              <h2 class="mb-1 font-weight-bold" style="color: #1976d2;"><?= isset($statistics['low_risk_count']) ? $statistics['low_risk_count'] : 0 ?></h2>
              <p class="text-muted small mb-0">
                <i class="fas fa-info-circle mr-1"></i>
                1-29 días de atraso
              </p>
            </div>
            <div style="color: #1976d2; opacity: 0.6;">
              <i class="fas fa-info-circle stat-icon"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-3 col-md-6 mb-3">
      <div class="card stat-card primary shadow-sm h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start">
            <div class="flex-grow-1">
              <h6 class="text-uppercase text-muted mb-2 small font-weight-bold">Monto Total</h6>
              <h2 class="mb-1 font-weight-bold amount-display">
                $<?= isset($statistics['total_amount']) ? number_format($statistics['total_amount'], 2, ',', '.') : '0.00' ?>
              </h2>
              <p class="text-muted small mb-0">
                <i class="fas fa-dollar-sign mr-1"></i>
                Total adeudado
              </p>
            </div>
            <div style="color: #d32f2f; opacity: 0.6;">
              <i class="fas fa-money-bill-wave stat-icon"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Panel de Filtros -->
  <div class="card filter-card shadow-sm mb-4">
    <div class="card-header bg-white border-bottom" style="border-top: 4px solid #d32f2f;">
      <div class="d-flex justify-content-between align-items-center flex-wrap">
        <h5 class="mb-0 font-weight-bold text-dark">
          <i class="fas fa-filter mr-2" style="color: #d32f2f;"></i>
          Filtros de Búsqueda
        </h5>
        <small class="text-muted">
          <i class="fas fa-search mr-1"></i>
          Refine su búsqueda de clientes morosos
        </small>
      </div>
    </div>
    <div class="card-body">
      <form id="filters-form" method="GET">
        <div class="row g-3">
          <div class="col-lg-4 col-md-6">
            <label for="search" class="form-label font-weight-bold text-dark">
              <i class="fas fa-user mr-1 text-muted"></i>
              Buscar Cliente
            </label>
            <div class="input-group">
              <span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span>
              <input type="text" class="form-control" id="search" name="search" 
                     placeholder="Nombre, cédula o DNI..." 
                     value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
            </div>
          </div>

          <div class="col-lg-2 col-md-6">
            <label for="risk_level" class="form-label font-weight-bold text-dark">
              <i class="fas fa-exclamation-triangle mr-1 text-muted"></i>
              Nivel de Riesgo
            </label>
            <select class="form-control form-select" id="risk_level" name="risk_level">
              <option value="">Todos</option>
              <option value="low" <?= (isset($_GET['risk_level']) && $_GET['risk_level'] == 'low') ? 'selected' : '' ?>>
                Bajo (1-29 días)
              </option>
              <option value="medium" <?= (isset($_GET['risk_level']) && $_GET['risk_level'] == 'medium') ? 'selected' : '' ?>>
                Medio (30-59 días)
              </option>
              <option value="high" <?= (isset($_GET['risk_level']) && $_GET['risk_level'] == 'high') ? 'selected' : '' ?>>
                Alto (60+ días)
              </option>
            </select>
          </div>

          <div class="col-lg-2 col-md-6">
            <label for="min_amount" class="form-label font-weight-bold text-dark">
              <i class="fas fa-dollar-sign mr-1 text-muted"></i>
              Monto Mínimo
            </label>
            <div class="input-group">
              <span class="input-group-text bg-light">$</span>
              <input type="number" class="form-control" id="min_amount" name="min_amount" 
                     placeholder="0.00" step="0.01" 
                     value="<?= isset($_GET['min_amount']) ? htmlspecialchars($_GET['min_amount']) : '' ?>">
            </div>
          </div>

          <div class="col-lg-2 col-md-6">
            <label for="max_amount" class="form-label font-weight-bold text-dark">
              <i class="fas fa-dollar-sign mr-1 text-muted"></i>
              Monto Máximo
            </label>
            <div class="input-group">
              <span class="input-group-text bg-light">$</span>
              <input type="number" class="form-control" id="max_amount" name="max_amount" 
                     placeholder="0.00" step="0.01" 
                     value="<?= isset($_GET['max_amount']) ? htmlspecialchars($_GET['max_amount']) : '' ?>">
            </div>
          </div>

          <div class="col-lg-2 col-md-12">
            <label class="form-label font-weight-bold text-dark">
              <i class="fas fa-cog mr-1 text-muted"></i>
              Acciones
            </label>
            <div class="d-grid gap-2">
              <button type="submit" class="btn btn-sm btn-action btn-danger">
                <i class="fas fa-search mr-1"></i>
                Filtrar
              </button>
              <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearFilters()">
                <i class="fas fa-times mr-1"></i>
                Limpiar
              </button>
            </div>
          </div>
        </div>

        <!-- Botones de Exportación -->
        <div class="row mt-3">
          <div class="col-12">
            <div class="d-flex flex-wrap gap-2">
              <button type="button" class="btn btn-outline-success btn-sm" onclick="exportData('excel')">
                <i class="fas fa-file-excel mr-1"></i>
                Exportar a Excel
              </button>
              <button type="button" class="btn btn-sm btn-outline-danger" onclick="exportData('pdf')">
                <i class="fas fa-file-pdf mr-1"></i>
                Exportar a PDF
              </button>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Tabla de Clientes Morosos -->
  <div class="card shadow-sm mb-4" style="border-radius: 10px;">
    <div class="card-header text-white card-header-gradient" style="border-radius: 10px 10px 0 0;">
      <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div>
          <h5 class="mb-1 font-weight-bold">
            <i class="fas fa-users mr-2"></i>
            Lista de Clientes Morosos
          </h5>
          <small class="text-white-50">
            <i class="fas fa-info-circle mr-1"></i>
            Total: <strong><?= $total_records ?? count($clients ?? []) ?></strong> clientes encontrados
          </small>
        </div>
        <div class="d-flex align-items-center gap-2 mt-2 mt-md-0">
          <button type="button" class="btn btn-light btn-sm" onclick="refreshData()" title="Actualizar datos">
            <i class="fas fa-sync-alt"></i>
            Actualizar
          </button>
        </div>
      </div>
    </div>

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0" id="overdue-table">
          <thead class="table-light" style="background-color: #f8f9fa;">
            <tr>
              <th class="border-0 fw-bold text-dark" style="width: 60px;">#</th>
              <th class="border-0 fw-bold text-dark">
                <i class="fas fa-user mr-1 text-muted"></i>
                Cliente
              </th>
              <th class="border-0 fw-bold text-dark d-none d-md-table-cell">
                <i class="fas fa-id-card mr-1 text-muted"></i>
                Identificación
              </th>
              <th class="border-0 fw-bold text-dark text-center">
                <i class="fas fa-calendar-alt mr-1 text-muted"></i>
                Cuotas
              </th>
              <th class="border-0 fw-bold text-dark text-end">
                <i class="fas fa-dollar-sign mr-1 text-muted"></i>
                Adeudado
              </th>
              <th class="border-0 fw-bold text-dark text-center d-none d-lg-table-cell">
                <i class="fas fa-calendar-times mr-1 text-muted"></i>
                Días Atraso
              </th>
              <th class="border-0 fw-bold text-dark text-center">
                <i class="fas fa-exclamation-triangle mr-1 text-muted"></i>
                Riesgo
              </th>
              <th class="border-0 fw-bold text-dark text-center d-none d-xl-table-cell">
                <i class="fas fa-info-circle mr-1 text-muted"></i>
                Estado
              </th>
              <th class="border-0 fw-bold text-dark text-center" style="min-width: 180px;">
                <i class="fas fa-cog mr-1 text-muted"></i>
                Acciones
              </th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($clients)): ?>
              <?php
              $row_number = 1;
              foreach ($clients as $c):
                // Determinar nivel de riesgo y clases CSS
                $risk_level = '';
                $risk_badge = '';
                $status_badge = '';
                $row_class = '';

                if ($c->max_dias_atraso >= 60) {
                  $risk_level = 'high';
                  $risk_badge = '<span class="badge badge-custom" style="background-color: #d32f2f; color: white;">Alto Riesgo</span>';
                  $status_badge = '<span class="badge" style="background-color: #d32f2f; color: white;">Castigado</span>';
                  $row_class = 'risk-high';
                } elseif ($c->max_dias_atraso >= 30) {
                  $risk_level = 'medium';
                  $risk_badge = '<span class="badge badge-custom" style="background-color: #f57c00; color: white;">Riesgo Medio</span>';
                  $status_badge = '<span class="badge" style="background-color: #f57c00; color: white;">En Mora</span>';
                  $row_class = 'risk-medium';
                } else {
                  $risk_level = 'low';
                  $risk_badge = '<span class="badge badge-custom" style="background-color: #1976d2; color: white;">Riesgo Bajo</span>';
                  $status_badge = '<span class="badge" style="background-color: #1976d2; color: white;">Vencido</span>';
                  $row_class = 'risk-low';
                }

                $customer_status = isset($c->customer_status) ? $c->customer_status : 1;
                $is_active = $customer_status == 1;
                $btn_class = $is_active ? 'danger' : 'success';
                $btn_icon = $is_active ? 'ban' : 'check-circle';
                $btn_text = $is_active ? 'Bloquear' : 'Desbloquear';
                $btn_title = $is_active ? 'Bloquear cliente' : 'Desbloquear cliente';
              ?>
                <tr class="<?= $row_class ?> align-middle" style="border-left: 4px solid transparent;">
                  <td class="text-muted fw-bold"><?= $row_number++ ?></td>
                  
                  <td>
                    <div class="table-client-name">
                      <?= htmlspecialchars($c->client_name) ?>
                    </div>
                    <small class="text-muted d-md-none">
                      <i class="fas fa-id-card mr-1"></i>
                      <?= htmlspecialchars($c->client_cedula) ?>
                    </small>
                  </td>

                  <td class="d-none d-md-table-cell text-muted">
                    <i class="fas fa-id-card mr-1"></i>
                    <?= htmlspecialchars($c->client_cedula) ?>
                  </td>

                  <td class="text-center">
                    <span class="badge badge-custom" style="background-color: #666666; color: white;">
                      <?= $c->cuotas_vencidas ?> cuota(s)
                    </span>
                  </td>

                  <td class="text-end">
                    <span class="amount-display">
                      $<?= number_format($c->total_adeudado, 2, ',', '.') ?>
                    </span>
                  </td>

                  <td class="text-center d-none d-lg-table-cell">
                    <span class="badge days-badge">
                      <i class="fas fa-clock mr-1"></i>
                      <?= $c->max_dias_atraso ?> días
                    </span>
                  </td>

                  <td class="text-center">
                    <?= $risk_badge ?>
                  </td>

                  <td class="text-center d-none d-xl-table-cell">
                    <?= $status_badge ?>
                  </td>

                  <td class="text-center">
                    <div class="btn-group btn-group-sm" role="group">
                      <button type="button" 
                              class="btn btn-outline-info btn-sm btn-action" 
                              onclick="viewLoanDetails('<?= $c->loan_ids ?>', <?= $c->customer_id ?>)"
                              title="Ver detalles">
                        <i class="fas fa-eye"></i>
                        <span class="d-none d-md-inline ml-1">Ver</span>
                      </button>
                      <button type="button" 
                              class="btn btn-sm btn-action <?= $btn_class == 'danger' ? 'btn-danger' : 'btn-success' ?>" 
                              onclick="toggleCustomerStatus(<?= $c->customer_id ?>, <?= $customer_status ?>)" 
                              title="<?= $btn_title ?>"
                              id="toggle_status_<?= $c->customer_id ?>">
                        <i class="fas fa-<?= $btn_icon ?>"></i>
                        <span class="d-none d-md-inline ml-1"><?= $btn_text ?></span>
                      </button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="9" class="empty-state">
                  <div class="empty-state-icon">
                    <i class="fas fa-check-circle"></i>
                  </div>
                  <h5 class="font-weight-bold" style="color: #556B2F;">¡Excelente!</h5>
                  <p class="text-muted mb-0">
                    No hay clientes con pagos vencidos en este momento.<br>
                    Todos los clientes están al día con sus pagos.
                  </p>
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Paginación -->
  <?php if (isset($pagination_links) && !empty($pagination_links)): ?>
  <div class="d-flex justify-content-center mt-4 mb-4">
    <nav aria-label="Navegación de páginas">
      <?= $pagination_links ?>
    </nav>
  </div>
  <?php endif; ?>

  <!-- Botón para abrir Historial de Bloqueos y Desbloqueos -->
  <div class="d-flex justify-content-end mb-3">
    <button type="button" class="btn btn-outline-secondary btn-action" onclick="showBlockHistory()">
      <i class="fas fa-history mr-2"></i>
      Historial
    </button>
  </div>
</div>

<!-- Modal para Historial de Bloqueos y Desbloqueos -->
<div class="modal fade" id="blockHistoryModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="fas fa-history mr-2"></i>
          Historial de Bloqueos y Desbloqueos
        </h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body" id="blockHistoryContent">
        <p class="text-muted mb-4">
          <i class="fas fa-info-circle mr-2"></i>
          Registro completo de todas las activaciones y desactivaciones de clientes en el sistema.
        </p>
        
        <?php if (!empty($block_history)): ?>
          <div class="table-responsive">
            <table class="table table-hover table-sm">
              <thead class="table-light">
                <tr>
                  <th class="fw-bold text-dark">
                    <i class="fas fa-user mr-1 text-muted"></i>
                    Cliente
                  </th>
                  <th class="fw-bold text-dark">
                    <i class="fas fa-id-card mr-1 text-muted"></i>
                    DNI
                  </th>
                  <th class="fw-bold text-dark text-center">
                    <i class="fas fa-arrow-left mr-1 text-muted"></i>
                    Estado Anterior
                  </th>
                  <th class="fw-bold text-dark text-center">
                    <i class="fas fa-arrow-right mr-1 text-muted"></i>
                    Estado Nuevo
                  </th>
                  <th class="fw-bold text-dark text-center">
                    <i class="fas fa-cog mr-1 text-muted"></i>
                    Acción
                  </th>
                  <th class="fw-bold text-dark">
                    <i class="fas fa-comment-alt mr-1 text-muted"></i>
                    Motivo
                  </th>
                  <th class="fw-bold text-dark">
                    <i class="fas fa-user-tie mr-1 text-muted"></i>
                    Realizado por
                  </th>
                  <th class="fw-bold text-dark">
                    <i class="fas fa-calendar mr-1 text-muted"></i>
                    Fecha y Hora
                  </th>
                  <th class="fw-bold text-dark text-center">
                    <i class="fas fa-eye mr-1 text-muted"></i>
                    Acciones
                  </th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($block_history as $block): ?>
                  <?php
                  $status_old_class = $block->old_status == 1 ? 'success' : 'danger';
                  $status_old_text = $block->old_status == 1 ? 'Activo' : 'Inactivo';
                  $status_new_class = $block->new_status == 1 ? 'success' : 'danger';
                  $status_new_text = $block->new_status == 1 ? 'Activo' : 'Inactivo';
                  $action_class = $block->action == 'activated' ? 'success' : 'danger';
                  $action_text = $block->action == 'activated' ? 'Activado' : 'Desactivado';
                  $action_icon = $block->action == 'activated' ? 'check-circle' : 'ban';
                  $current_status = isset($block->current_status) ? $block->current_status : $block->new_status;
                  $current_status_class = $current_status == 1 ? 'success' : 'danger';
                  $current_status_text = $current_status == 1 ? 'Activo' : 'Inactivo';
                  ?>
                  <tr class="<?= $current_status == 0 ? 'table-warning' : '' ?>">
                    <td>
                      <strong><?= htmlspecialchars($block->customer_name) ?></strong>
                      <?php if ($current_status == 0): ?>
                        <span class="badge ml-2" style="background-color: #d32f2f; color: white;">Bloqueado</span>
                      <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($block->dni) ?></td>
                    <td class="text-center">
                      <span class="badge badge-custom" style="background-color: <?= $status_old_class == 'success' ? '#4caf50' : '#d32f2f' ?>; color: white;">
                        <?= $status_old_text ?>
                      </span>
                    </td>
                    <td class="text-center">
                      <span class="badge badge-custom" style="background-color: <?= $status_new_class == 'success' ? '#4caf50' : '#d32f2f' ?>; color: white;">
                        <?= $status_new_text ?>
                      </span>
                    </td>
                    <td class="text-center">
                      <span class="badge badge-custom" style="background-color: <?= $action_class == 'success' ? '#4caf50' : '#d32f2f' ?>; color: white;">
                        <i class="fas fa-<?= $action_icon ?> mr-1"></i>
                        <?= $action_text ?>
                      </span>
                    </td>
                    <td class="text-left" style="max-width: 200px;">
                      <?php if (!empty($block->notes)): ?>
                        <span class="text-muted small d-block" title="<?= htmlspecialchars($block->notes) ?>" style="word-wrap: break-word; overflow-wrap: break-word;">
                          <i class="fas fa-comment-alt mr-1"></i>
                          <?= strlen($block->notes) > 60 ? htmlspecialchars(substr($block->notes, 0, 60)) . '...' : htmlspecialchars($block->notes) ?>
                        </span>
                      <?php else: ?>
                        <span class="text-muted small">
                          <i class="fas fa-minus"></i>
                          Sin motivo
                        </span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?= $block->changed_by_name ? htmlspecialchars($block->changed_by_name) : '<span class="text-muted">Sistema</span>' ?>
                    </td>
                    <td>
                      <small class="text-muted">
                        <i class="fas fa-clock mr-1"></i>
                        <?= date('d/m/Y H:i:s', strtotime($block->changed_at)) ?>
                      </small>
                    </td>
                    <td class="text-center">
                      <span class="badge mr-2 badge-custom" style="background-color: <?= $current_status_class == 'success' ? '#4caf50' : '#d32f2f' ?>; color: white;">
                        Estado: <?= $current_status_text ?>
                      </span>
                      <button type="button" 
                              class="btn btn-sm btn-outline-info btn-action" 
                              onclick="viewCustomerHistory(<?= $block->customer_id ?>)"
                              title="Ver historial completo">
                        <i class="fas fa-eye"></i>
                        <span class="d-none d-md-inline ml-1">Ver</span>
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="empty-state">
            <div class="empty-state-icon text-muted">
              <i class="fas fa-history"></i>
            </div>
            <h6 class="text-muted font-weight-bold">No hay registros en el historial</h6>
            <p class="text-muted small mb-0">
              Los cambios de estado de los clientes aparecerán aquí una vez que se realicen bloqueos o desbloqueos.
            </p>
          </div>
        <?php endif; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal para Detalles de Préstamo (mantener código existente) -->
<div class="modal fade" id="loanDetailsModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="fas fa-file-invoice-dollar mr-2"></i>
          Detalles de Préstamos
        </h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body" id="loanDetailsContent">
        <div class="text-center">
          <i class="fas fa-spinner fa-spin fa-2x" style="color: #d32f2f;"></i>
          <p class="mt-2">Cargando detalles...</p>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<script>
// Funciones JavaScript (mantener todas las funciones existentes)
function clearFilters() {
  document.getElementById('search').value = '';
  document.getElementById('risk_level').value = '';
  document.getElementById('min_amount').value = '';
  document.getElementById('max_amount').value = '';
  document.getElementById('filters-form').submit();
}

function refreshData() {
  location.reload();
}

function exportData(format) {
  var url = '<?= site_url("admin/customers/export_overdue") ?>';
  var params = new URLSearchParams(window.location.search);
  params.set('format', format);
  window.location.href = url + '?' + params.toString();
}

function viewLoanDetails(loanIds, customerId) {
  $('#loanDetailsModal').modal('show');
  $('#loanDetailsContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x" style="color: #d32f2f;"></i><p class="mt-2">Cargando detalles...</p></div>');
  
  $.ajax({
    url: '<?= site_url("admin/customers/get_loan_details") ?>',
    type: 'POST',
    dataType: 'json',
    data: {
      loan_ids: loanIds,
      customer_id: customerId,
      <?php if (isset($this->security) && method_exists($this->security, 'get_csrf_token_name')): ?>
      <?= $this->security->get_csrf_token_name() ?>: '<?= $this->security->get_csrf_hash() ?>'
      <?php endif; ?>
    },
    success: function(response) {
      if (response.success && response.loans) {
        var html = '<div class="loan-details-container">';
        
        if (response.customer) {
          html += '<div class="mb-4 p-3 bg-light rounded">';
          html += '<h6 class="font-weight-bold mb-2"><i class="fas fa-user mr-2"></i>Cliente</h6>';
          html += '<div class="row">';
          html += '<div class="col-md-6"><p class="mb-1"><strong>Nombre:</strong> ' + escapeHtml(response.customer.first_name + ' ' + response.customer.last_name) + '</p></div>';
          html += '<div class="col-md-6"><p class="mb-1"><strong>DNI:</strong> ' + escapeHtml(response.customer.dni || 'N/A') + '</p></div>';
          html += '<div class="col-md-6"><p class="mb-1"><strong>Teléfono:</strong> ' + escapeHtml(response.customer.mobile || response.customer.phone_fixed || 'N/A') + '</p></div>';
          html += '<div class="col-md-6"><p class="mb-0"><strong>Correo:</strong> ' + escapeHtml(response.customer.phone || 'N/A') + '</p></div>';
          html += '</div>';
          html += '</div>';
        }
        
        response.loans.forEach(function(loan) {
          html += '<div class="loan-card mb-4 p-3 border rounded">';
          html += '<h6 class="font-weight-bold mb-3" style="color: #d32f2f;"><i class="fas fa-file-invoice-dollar mr-2"></i>Préstamo #' + loan.id + '</h6>';
          
          html += '<div class="row mb-3">';
          html += '<div class="col-md-6"><strong>Monto del Crédito:</strong> $' + parseFloat(loan.credit_amount || 0).toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</div>';
          html += '<div class="col-md-6"><strong>Interés:</strong> $' + parseFloat(loan.interest_amount || 0).toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</div>';
          html += '</div>';
          
          html += '<div class="row mb-3">';
          html += '<div class="col-md-6"><strong>Número de Cuotas:</strong> ' + (loan.num_fee || 0) + '</div>';
          html += '<div class="col-md-6"><strong>Estado:</strong> ';
          if (loan.status == 1) {
            html += '<span class="badge" style="background-color: #1976d2; color: white;">Activo</span>';
          } else if (loan.status == 0) {
            html += '<span class="badge" style="background-color: #4caf50; color: white;">Pagado</span>';
          } else {
            html += '<span class="badge" style="background-color: #d32f2f; color: white;">Castigado</span>';
          }
          html += '</div></div>';
          
          if (loan.all_quotas && loan.all_quotas.length > 0) {
            html += '<div class="mt-3">';
            html += '<h6 class="font-weight-bold mb-2"><i class="fas fa-list mr-2"></i>Cuotas del Préstamo</h6>';
            html += '<div class="table-responsive">';
            html += '<table class="table table-sm table-bordered">';
            html += '<thead class="table-light"><tr>';
            html += '<th>#</th><th>Fecha Vencimiento</th><th>Monto</th><th>Estado</th>';
            html += '</tr></thead><tbody>';
            
            loan.all_quotas.forEach(function(quota, index) {
              var quotaDate = quota.date || 'N/A';
              if (quotaDate !== 'N/A' && quotaDate) {
                // Formatear fecha si viene en formato YYYY-MM-DD
                if (quotaDate.match(/^\d{4}-\d{2}-\d{2}/)) {
                  var dateParts = quotaDate.split(' ')[0].split('-');
                  quotaDate = dateParts[2] + '/' + dateParts[1] + '/' + dateParts[0];
                }
              }
              
              html += '<tr>';
              html += '<td>' + (quota.num_quota || (index + 1)) + '</td>';
              html += '<td>' + quotaDate + '</td>';
              html += '<td>$' + parseFloat(quota.fee_amount || 0).toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</td>';
              html += '<td>';
              if (quota.status == 0 || quota.paid == 1) {
                html += '<span class="badge" style="background-color: #4caf50; color: white;">Pagada</span>';
              } else {
                html += '<span class="badge" style="background-color: #d32f2f; color: white;">Pendiente</span>';
              }
              html += '</td>';
              html += '</tr>';
            });
            
            html += '</tbody></table></div></div>';
          } else {
            html += '<p class="text-muted mt-3"><i class="fas fa-info-circle mr-1"></i>No hay cuotas registradas para este préstamo.</p>';
          }
          
          html += '</div>';
        });
        
        html += '</div>';
        $('#loanDetailsContent').html(html);
      } else {
        $('#loanDetailsContent').html('<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> ' + (response.error || 'No se encontraron préstamos para este cliente.') + '</div>');
      }
    },
    error: function(xhr, status, error) {
      console.error('Error loading loan details:', xhr, status, error);
      $('#loanDetailsContent').html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Error al cargar los detalles del préstamo. Por favor, intente nuevamente.</div>');
    }
  });
}

function sendBulkNotifications(riskLevel, type) {
  if (!confirm('¿Está seguro de enviar notificaciones masivas a clientes con riesgo ' + riskLevel + '?')) {
    return;
  }
  
  alert('Funcionalidad de notificaciones masivas en desarrollo.');
}

function checkAlerts() {
  alert('Sistema de alertas en desarrollo.');
}

function showBlockHistory() {
  $('#blockHistoryModal').modal('show');
}

function refreshBlockHistory() {
  location.reload();
}

// Función auxiliar para escapar HTML
function escapeHtml(text) {
  if (!text) return '';
  var map = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
  };
  return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
}

function toggleCustomerStatus(customerId, currentStatus) {
  console.log('toggleCustomerStatus llamado - Cliente ID:', customerId, 'Estado actual:', currentStatus);
  
  var btnId = '#toggle_status_' + customerId;
  var $btn = $(btnId);
  
  if ($btn.length === 0) {
    console.error('Botón no encontrado:', btnId);
    alert('Error: No se encontró el botón. Recarga la página.');
    return;
  }

  // Si se va a bloquear (desactivar), mostrar modal para ingresar motivo
  if (currentStatus == 1) {
    showBlockModal(customerId, currentStatus);
  } else {
    // Si se va a desbloquear (activar), confirmar directamente
    if (confirm('¿Está seguro de desbloquear/activar este cliente?')) {
      performStatusToggle(customerId, currentStatus, '');
    }
  }
}

function showBlockModal(customerId, currentStatus) {
  // Crear modal para ingresar motivo del bloqueo
  var modalId = 'blockModal_' + customerId;
  var modalHtml = '<div class="modal fade" id="' + modalId + '" tabindex="-1" role="dialog">';
  modalHtml += '<div class="modal-dialog" role="document">';
  modalHtml += '<div class="modal-content">';
  modalHtml += '<div class="modal-header">';
  modalHtml += '<h5 class="modal-title"><i class="fas fa-ban"></i> Bloquear Cliente</h5>';
  modalHtml += '<button type="button" class="close" data-dismiss="modal">&times;</button>';
  modalHtml += '</div>';
  modalHtml += '<div class="modal-body">';
  modalHtml += '<div class="form-group">';
  modalHtml += '<label for="blockReason_' + customerId + '"><strong>Motivo del bloqueo:</strong> <span class="text-danger">*</span></label>';
  modalHtml += '<textarea class="form-control" id="blockReason_' + customerId + '" rows="4" placeholder="Ingrese el motivo del bloqueo (ej: Mora excesiva, Incumplimiento de pagos, etc.)" required></textarea>';
  modalHtml += '<small class="form-text text-muted">Este motivo quedará registrado en el historial de bloqueos.</small>';
  modalHtml += '</div>';
  modalHtml += '</div>';
  modalHtml += '<div class="modal-footer">';
  modalHtml += '<button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>';
  modalHtml += '<button type="button" class="btn btn-danger" onclick="confirmBlock(' + customerId + ', \'' + modalId + '\')">';
  modalHtml += '<i class="fas fa-ban"></i> Confirmar Bloqueo';
  modalHtml += '</button>';
  modalHtml += '</div>';
  modalHtml += '</div>';
  modalHtml += '</div>';
  modalHtml += '</div>';
  
  // Remover modal anterior si existe
  $('#' + modalId).remove();
  
  // Agregar modal al body
  $('body').append(modalHtml);
  
  // Mostrar modal
  $('#' + modalId).modal('show');
  
  // Limpiar al cerrar
  $('#' + modalId).on('hidden.bs.modal', function() {
    $(this).remove();
  });
  
  // Permitir enviar con Enter (Ctrl+Enter)
  $('#blockReason_' + customerId).on('keydown', function(e) {
    if (e.ctrlKey && e.key === 'Enter') {
      confirmBlock(customerId, modalId);
    }
  });
}

function confirmBlock(customerId, modalId) {
  var reason = $('#blockReason_' + customerId).val().trim();
  
  if (!reason) {
    alert('Por favor, ingrese el motivo del bloqueo.');
    $('#blockReason_' + customerId).focus();
    return;
  }
  
  // Cerrar modal
  $('#' + modalId).modal('hide');
  
  // Ejecutar bloqueo con motivo
  performStatusToggle(customerId, 1, reason);
}

function performStatusToggle(customerId, currentStatus, notes) {
  var btnId = '#toggle_status_' + customerId;
  var $btn = $(btnId);
  
  var originalHtml = $btn.html();
  var originalClass = $btn.attr('class');
  $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Procesando...');

  // Preparar datos con CSRF token
  var data = {
    customer_id: customerId,
    notes: notes || ''
  };

  // Agregar token CSRF si está disponible
  if (typeof window.csrf_name !== 'undefined' && typeof window.csrf_hash !== 'undefined') {
    data[window.csrf_name] = window.csrf_hash;
    console.log('Token CSRF agregado');
  } else {
    console.warn('Token CSRF no disponible');
  }

  console.log('Enviando petición AJAX:', data);

  $.ajax({
    url: '<?= site_url("admin/customers/ajax_toggle_customer_status") ?>',
    type: 'POST',
    data: data,
    dataType: 'json',
    timeout: 10000,
    success: function(response) {
      console.log('Respuesta recibida:', response);
      
      if (typeof response === 'string') {
        try {
          response = JSON.parse(response);
        } catch (e) {
          console.error('Error parsing JSON:', e);
          alert('Error al procesar la respuesta del servidor: ' + response);
          $btn.prop('disabled', false).html(originalHtml);
          return;
        }
      }

      if (response.success) {
        var newStatus = response.status;
        console.log('Estado cambiado exitosamente. Nuevo estado:', newStatus);
        
        // Actualizar botón
        var newIcon = newStatus == 1 ? 'ban' : 'check-circle';
        var newText = newStatus == 1 ? 'Bloquear' : 'Desbloquear';
        var newTitle = newStatus == 1 ? 'Bloquear cliente' : 'Desbloquear cliente';
        var newClass = newStatus == 1 ? 'btn-danger' : 'btn-success';
        
        $btn.removeClass('btn-danger btn-success').addClass(newClass);
        $btn.html('<i class="fas fa-' + newIcon + '"></i> <span class="d-none d-md-inline ml-1">' + newText + '</span>');
        $btn.attr('title', newTitle);
        $btn.attr('onclick', 'toggleCustomerStatus(' + customerId + ', ' + newStatus + ')');
        
        // Mostrar mensaje de éxito
        alert('✅ ' + response.message);
        
        // Recargar la página para actualizar el historial y la tabla
        console.log('Recargando página en 1.5 segundos...');
        setTimeout(function() {
          location.reload();
        }, 1500);
      } else {
        console.error('Error en la respuesta:', response.error);
        alert('❌ Error: ' + (response.error || 'No se pudo cambiar el estado del cliente'));
        $btn.prop('disabled', false).html(originalHtml);
      }
    },
    error: function(xhr, status, error) {
      console.error('Error AJAX toggleCustomerStatus:', {
        status: status,
        error: error,
        responseText: xhr.responseText,
        statusCode: xhr.status
      });
      
      var errorMsg = 'Error de conexión: ' + error;
      if (xhr.responseText) {
        try {
          var errorResponse = JSON.parse(xhr.responseText);
          if (errorResponse.error) {
            errorMsg = errorResponse.error;
          }
        } catch (e) {
          if (xhr.responseText.length < 200) {
            errorMsg = xhr.responseText;
          }
        }
      }
      
      alert('❌ Error: ' + errorMsg);
      $btn.prop('disabled', false).html(originalHtml);
    }
  });
}

function viewCustomerHistory(customerId) {
  // Preparar datos con CSRF token
  var data = {
    customer_id: customerId
  };

  // Agregar token CSRF si está disponible
  if (typeof window.csrf_name !== 'undefined' && typeof window.csrf_hash !== 'undefined') {
    data[window.csrf_name] = window.csrf_hash;
  }

  $.ajax({
    url: '<?= site_url("admin/customers/ajax_get_customer_block_history") ?>',
    type: 'POST',
    data: data,
    dataType: 'json',
    success: function(response) {
      if (response.success && response.history) {
        var history = response.history;
        var html = '<div class="table-responsive"><table class="table table-sm table-hover">';
        html += '<thead><tr><th>Estado Anterior</th><th>Estado Nuevo</th><th>Acción</th><th>Motivo</th><th>Realizado por</th><th>Fecha y Hora</th></tr></thead>';
        html += '<tbody>';
        
        if (history.length > 0) {
          history.forEach(function(item) {
            var oldStatus = item.old_status == 1 ? '<span class="badge" style="background-color: #4caf50; color: white;">Activo</span>' : '<span class="badge" style="background-color: #d32f2f; color: white;">Inactivo</span>';
            var newStatus = item.new_status == 1 ? '<span class="badge" style="background-color: #4caf50; color: white;">Activo</span>' : '<span class="badge" style="background-color: #d32f2f; color: white;">Inactivo</span>';
            var action = item.action == 'activated' ? '<span class="badge" style="background-color: #4caf50; color: white;">Activado</span>' : '<span class="badge" style="background-color: #d32f2f; color: white;">Desactivado</span>';
            var changedBy = item.changed_by_name || 'Sistema';
            var changedAt = new Date(item.changed_at).toLocaleString('es-CO');
            var notes = item.notes && item.notes.trim() ? '<small class="text-muted"><i class="fas fa-comment-alt" style="color: #666666;"></i> ' + escapeHtml(item.notes) + '</small>' : '<span class="text-muted">-</span>';
            
            html += '<tr>';
            html += '<td>' + oldStatus + '</td>';
            html += '<td>' + newStatus + '</td>';
            html += '<td>' + action + '</td>';
            html += '<td>' + notes + '</td>';
            html += '<td>' + escapeHtml(changedBy) + '</td>';
            html += '<td><small>' + changedAt + '</small></td>';
            html += '</tr>';
          });
        } else {
          html += '<tr><td colspan="6" class="text-center text-muted">No hay historial disponible</td></tr>';
        }
        
        html += '</tbody></table></div>';
        
        // Mostrar en modal
        var modal = '<div class="modal fade" id="historyModal" tabindex="-1">';
        modal += '<div class="modal-dialog modal-lg"><div class="modal-content">';
        modal += '<div class="modal-header"><h5 class="modal-title">Historial de Cambios de Estado</h5>';
        modal += '<button type="button" class="close" data-dismiss="modal">&times;</button></div>';
        modal += '<div class="modal-body">' + html + '</div>';
        modal += '<div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button></div>';
        modal += '</div></div></div>';
        
        $('body').append(modal);
        $('#historyModal').modal('show');
        $('#historyModal').on('hidden.bs.modal', function() {
          $(this).remove();
        });
      } else {
        alert('No se pudo cargar el historial del cliente');
      }
    },
    error: function(xhr, status, error) {
      console.error('Error AJAX viewCustomerHistory:', xhr, status, error);
      alert('Error al cargar el historial del cliente');
    }
  });
}
</script>
