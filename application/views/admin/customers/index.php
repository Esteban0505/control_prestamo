<?php if ($this->session->flashdata('msg')): ?>
    <div class="alert alert-success alert-dismissible fade show text-center mb-4" role="alert">
        <?= $this->session->flashdata('msg') ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
<?php endif ?>

<!-- Dashboard de Estadísticas -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Clientes</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo count($customers); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-users fa-2x text-gray-300"></i>
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
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Sin Crédito</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo count(array_filter($customers, function($c) { return !$c->loan_status; })); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-user-check fa-2x text-gray-300"></i>
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
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Con Crédito</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo count(array_filter($customers, function($c) { return $c->loan_status; })); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-user-clock fa-2x text-gray-300"></i>
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
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Clientes Especiales</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo count(array_filter($customers, function($c) { return $c->tipo_cliente == 'especial'; })); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-star fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-header d-flex align-items-center justify-content-between py-3">
        <h6 class="m-0 font-weight-bold text-primary">Listar Clientes</h6>
        <a class="d-sm-inline-block btn btn-sm btn-success shadow-sm" href="<?php echo site_url('admin/customers/edit'); ?>"><i class="fas fa-plus-circle fa-sm"></i> Nuevo cliente</a>
    </div>
    <div class="card-body">

        <!-- Versión móvil: filtros primero -->
        <div class="d-block d-md-none">
          <!-- Filtros y búsqueda -->
          <div class="row mb-3">
            <div class="col-12">
              <form method="GET" action="<?php echo site_url('admin/customers/index'); ?>">
                <div class="row mb-3">
                  <div class="col-12 mb-2">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Buscar por nombre, apellido o cédula" value="<?php echo isset($search) ? htmlspecialchars($search) : ''; ?>">
                  </div>
                  <div class="col-6 mb-2">
                    <select name="status" class="form-control form-control-sm">
                      <option value="">Todos los estados</option>
                      <option value="1" <?php echo (isset($status_filter) && $status_filter == '1') ? 'selected' : ''; ?>>Con Crédito</option>
                      <option value="0" <?php echo (isset($status_filter) && $status_filter == '0') ? 'selected' : ''; ?>>Sin Crédito</option>
                    </select>
                  </div>
                  <div class="col-6 mb-2">
                    <select name="tipo" class="form-control form-control-sm">
                      <option value="">Todos los tipos</option>
                      <option value="normal" <?php echo (isset($tipo_filter) && $tipo_filter == 'normal') ? 'selected' : ''; ?>>Normal</option>
                      <option value="especial" <?php echo (isset($tipo_filter) && $tipo_filter == 'especial') ? 'selected' : ''; ?>>Especial</option>
                    </select>
                  </div>
                  <div class="col-12">
                    <div class="d-flex justify-content-between">
                      <button type="submit" class="btn btn-primary btn-sm flex-fill mr-1">
                        <i class="fas fa-search"></i> Buscar
                      </button>
                      <a href="<?php echo site_url('admin/customers/index'); ?>" class="btn btn-secondary btn-sm flex-fill ml-1">
                        <i class="fas fa-times"></i> Limpiar
                      </a>
                    </div>
                  </div>
                </div>
              </form>
            </div>
          </div>

          <!-- Vista de tarjetas para móviles -->
          <div class="row">
            <?php if(count($customers)): foreach($customers as $ct): ?>
                <div class="col-12 mb-3">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-8">
                                    <h6 class="card-title mb-1"><?php echo $ct->first_name . ' ' . $ct->last_name; ?></h6>
                                    <p class="card-text small text-muted mb-1">
                                        <i class="fas fa-id-card"></i> <?php echo $ct->dni; ?>
                                    </p>
                                    <p class="card-text small text-muted mb-1">
                                        <i class="fas fa-mobile-alt"></i> <?php echo $ct->mobile ?: 'Sin celular'; ?>
                                    </p>
                                    <p class="card-text small mb-0">
                                        <strong><?php echo ucfirst($ct->tipo_cliente); ?></strong>
                                    </p>
                                </div>
                                <div class="col-4 text-right">
                                    <span class="badge badge-<?php echo $ct->loan_status ? 'warning' : 'success'; ?> mb-2">
                                        <?php echo $ct->loan_status ? 'Con Crédito' : 'Sin Crédito'; ?>
                                    </span>
                                    <br>
                                    <a href="<?php echo site_url('admin/customers/view/'.$ct->id); ?>" class="btn btn-sm btn-primary mr-1">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?php echo site_url('admin/customers/edit/'.$ct->id); ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h5>No existen clientes</h5>
                            <p class="text-muted">Agregue un nuevo cliente para comenzar.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Versión tablet (md-xl): tabla simplificada -->
        <div class="d-none d-md-block d-xl-none">
          <!-- Filtros y búsqueda -->
          <div class="row mb-3">
            <div class="col-12">
              <form method="GET" action="<?php echo site_url('admin/customers/index'); ?>" class="form-inline">
                <div class="form-group mr-3">
                  <input type="text" name="search" class="form-control form-control-sm" placeholder="Buscar por nombre, apellido o cédula" value="<?php echo isset($search) ? htmlspecialchars($search) : ''; ?>">
                </div>
                <div class="form-group mr-3">
                  <select name="status" class="form-control form-control-sm">
                    <option value="">Todos los estados</option>
                    <option value="1" <?php echo (isset($status_filter) && $status_filter == '1') ? 'selected' : ''; ?>>Con Crédito</option>
                    <option value="0" <?php echo (isset($status_filter) && $status_filter == '0') ? 'selected' : ''; ?>>Sin Crédito</option>
                  </select>
                </div>
                <div class="form-group mr-3">
                  <select name="tipo" class="form-control form-control-sm">
                    <option value="">Todos los tipos</option>
                    <option value="normal" <?php echo (isset($tipo_filter) && $tipo_filter == 'normal') ? 'selected' : ''; ?>>Normal</option>
                    <option value="especial" <?php echo (isset($tipo_filter) && $tipo_filter == 'especial') ? 'selected' : ''; ?>>Especial</option>
                  </select>
                </div>
                <button type="submit" class="btn btn-primary btn-sm mr-2">
                  <i class="fas fa-search"></i> Buscar
                </button>
                <a href="<?php echo site_url('admin/customers/index'); ?>" class="btn btn-secondary btn-sm">
                  <i class="fas fa-times"></i> Limpiar
                </a>
              </form>
            </div>
          </div>

          <!-- Tabla simplificada para tablet -->
          <div class="table-responsive">
            <table class="table table-hover" width="100%" cellspacing="0">
              <thead class="thead-light">
                <tr>
                  <th class="text-center" style="width: 60px;"><i class="fas fa-hashtag"></i></th>
                  <th><i class="fas fa-user mr-1"></i> Cliente</th>
                  <th class="text-center"><i class="fas fa-info-circle mr-1"></i> Estado</th>
                  <th class="text-center" style="width: 80px;"><i class="fas fa-cogs"></i></th>
                </tr>
              </thead>
              <tbody>
                <?php if(count($customers)): ?>
                  <?php foreach($customers as $ct): ?>
                    <tr>
                      <td class="text-center"><strong><?php echo htmlspecialchars($ct->dni); ?></strong></td>
                      <td>
                        <div class="font-weight-bold"><?php echo htmlspecialchars($ct->first_name . ' ' . $ct->last_name); ?></div>
                        <div class="small text-muted">
                          <?php echo htmlspecialchars($ct->mobile ?: 'Sin celular'); ?> • <?php echo ucfirst($ct->tipo_cliente); ?>
                        </div>
                      </td>
                      <td class="text-center">
                        <span class="badge badge-<?php echo $ct->loan_status ? 'warning' : 'success'; ?>">
                          <i class="fas fa-<?php echo $ct->loan_status ? 'exclamation-circle' : 'check-circle'; ?> mr-1"></i>
                          <?php echo $ct->loan_status ? 'Con Crédito' : 'Sin Crédito'; ?>
                        </span>
                      </td>
                      <td class="text-center">
                        <a href="<?php echo site_url('admin/customers/view/'.$ct->id); ?>" class="btn btn-sm btn-primary mr-1">
                          <i class="fas fa-eye"></i>
                        </a>
                        <a href="<?php echo site_url('admin/customers/edit/'.$ct->id); ?>" class="btn btn-sm btn-outline-primary">
                          <i class="fas fa-edit"></i>
                        </a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="4" class="text-center py-5">
                      <i class="fas fa-users fa-3x text-muted mb-3"></i>
                      <h5 class="text-muted">No existen clientes registrados</h5>
                      <p class="text-muted">Haga clic en "Nuevo cliente" para agregar el primer cliente.</p>
                    </td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Versión desktop (xl+): tabla completa -->
        <div class="d-none d-xl-block">
          <!-- Filtros y búsqueda -->
          <div class="row mb-3">
            <div class="col-12">
              <form method="GET" action="<?php echo site_url('admin/customers/index'); ?>" class="form-inline">
                <div class="form-group mr-3">
                  <input type="text" name="search" class="form-control form-control-sm" placeholder="Buscar por nombre, apellido o cédula" value="<?php echo isset($search) ? htmlspecialchars($search) : ''; ?>">
                </div>
                <div class="form-group mr-3">
                  <select name="status" class="form-control form-control-sm">
                    <option value="">Todos los estados</option>
                    <option value="1" <?php echo (isset($status_filter) && $status_filter == '1') ? 'selected' : ''; ?>>Con Crédito</option>
                    <option value="0" <?php echo (isset($status_filter) && $status_filter == '0') ? 'selected' : ''; ?>>Sin Crédito</option>
                  </select>
                </div>
                <div class="form-group mr-3">
                  <select name="tipo" class="form-control form-control-sm">
                    <option value="">Todos los tipos</option>
                    <option value="normal" <?php echo (isset($tipo_filter) && $tipo_filter == 'normal') ? 'selected' : ''; ?>>Normal</option>
                    <option value="especial" <?php echo (isset($tipo_filter) && $tipo_filter == 'especial') ? 'selected' : ''; ?>>Especial</option>
                  </select>
                </div>
                <button type="submit" class="btn btn-primary btn-sm mr-2">
                  <i class="fas fa-search"></i> Buscar
                </button>
                <a href="<?php echo site_url('admin/customers/index'); ?>" class="btn btn-secondary btn-sm">
                  <i class="fas fa-times"></i> Limpiar
                </a>
              </form>
            </div>
          </div>

          <!-- Tabla completa para desktop -->
          <div class="table-responsive">
            <table class="table table-hover" width="100%" cellspacing="0">
              <thead class="thead-light">
                <tr>
                  <th class="text-center" style="width: 80px;"><i class="fas fa-hashtag"></i></th>
                  <th><i class="fas fa-user mr-1"></i> Nombres</th>
                  <th><i class="fas fa-user mr-1"></i> Apellidos</th>
                  <th class="text-center"><i class="fas fa-venus-mars mr-1"></i> Género</th>
                  <th><i class="fas fa-phone mr-1"></i> Celular</th>
                  <th><i class="fas fa-user-tie mr-1"></i> Asesor</th>
                  <th class="text-center"><i class="fas fa-crown mr-1"></i> Tipo</th>
                  <th class="text-center"><i class="fas fa-info-circle mr-1"></i> Estado</th>
                  <th class="text-center" style="width: 120px;"><i class="fas fa-cogs mr-1"></i> Acciones</th>
                </tr>
              </thead>
              <tbody>
                <?php if(count($customers)): ?>
                  <?php foreach($customers as $ct): ?>
                    <tr>
                      <td class="text-center"><strong><?php echo htmlspecialchars($ct->dni); ?></strong></td>
                      <td><?php echo htmlspecialchars($ct->first_name); ?></td>
                      <td><?php echo htmlspecialchars($ct->last_name); ?></td>
                      <td class="text-center">
                        <?php if($ct->gender == 'masculino'): ?>
                          <span class="badge badge-primary">
                            <i class="fas fa-mars mr-1"></i>Masculino
                          </span>
                        <?php elseif($ct->gender == 'femenino'): ?>
                          <span class="badge badge-danger">
                            <i class="fas fa-venus mr-1"></i>Femenino
                          </span>
                        <?php else: ?>
                          <span class="badge badge-secondary">
                            <i class="fas fa-genderless mr-1"></i>No especificado
                          </span>
                        <?php endif; ?>
                      </td>
                      <td><?php echo htmlspecialchars($ct->mobile ?: 'No registrado'); ?></td>
                      <td>
                        <?php if($ct->user_name): ?>
                          <span class="badge badge-info">
                            <i class="fas fa-user-tie mr-1"></i><?php echo htmlspecialchars($ct->user_name); ?>
                          </span>
                        <?php else: ?>
                          <span class="badge badge-warning">
                            <i class="fas fa-exclamation-triangle mr-1"></i>Sin asignar
                          </span>
                        <?php endif; ?>
                      </td>
                      <td class="text-center">
                        <?php if($ct->tipo_cliente == 'especial'): ?>
                          <span class="badge badge-warning">
                            <i class="fas fa-star mr-1"></i>Especial
                          </span>
                        <?php else: ?>
                          <span class="badge badge-secondary">
                            <i class="fas fa-user mr-1"></i>Normal
                          </span>
                        <?php endif; ?>
                      </td>
                      <td class="text-center">
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
                      <td class="text-center">
                        <a href="<?php echo site_url('admin/customers/view/'.$ct->id); ?>" class="btn btn-sm btn-primary mr-1">
                          <i class="fas fa-eye mr-1"></i>Ver
                        </a>
                        <a href="<?php echo site_url('admin/customers/edit/'.$ct->id); ?>" class="btn btn-sm btn-outline-primary">
                          <i class="fas fa-edit mr-1"></i>Editar
                        </a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="9" class="text-center py-5">
                      <i class="fas fa-users fa-3x text-muted mb-3"></i>
                      <h5 class="text-muted">No existen clientes registrados</h5>
                      <p class="text-muted">Haga clic en "Nuevo cliente" para agregar el primer cliente.</p>
                    </td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
    </div>

    <!-- Paginación moderna -->
    <?php if(isset($total_pages) && $total_pages > 1): ?>
      <!-- Versión móvil: paginación simplificada -->
      <div class="d-block d-md-none">
        <div class="d-flex justify-content-between align-items-center mt-3">
          <div class="text-muted small">
            Página <?php echo $current_page; ?> de <?php echo $total_pages; ?>
          </div>
          <div>
            <?php if($current_page > 1): ?>
              <a class="btn btn-sm btn-outline-primary mr-1" href="<?php echo site_url('admin/customers/index?page=' . ($current_page - 1) . (isset($search) && $search ? '&search=' . urlencode($search) : '') . (isset($status_filter) && $status_filter !== '' ? '&status=' . $status_filter : '') . (isset($tipo_filter) && $tipo_filter !== '' ? '&tipo=' . $tipo_filter : '') . (isset($per_page) ? '&per_page=' . $per_page : '')); ?>">
                <i class="fas fa-chevron-left"></i>
              </a>
            <?php else: ?>
              <button class="btn btn-sm btn-outline-secondary mr-1" disabled>
                <i class="fas fa-chevron-left"></i>
              </button>
            <?php endif; ?>

            <?php if($current_page < $total_pages): ?>
              <a class="btn btn-sm btn-outline-primary" href="<?php echo site_url('admin/customers/index?page=' . ($current_page + 1) . (isset($search) && $search ? '&search=' . urlencode($search) : '') . (isset($status_filter) && $status_filter !== '' ? '&status=' . $status_filter : '') . (isset($tipo_filter) && $tipo_filter !== '' ? '&tipo=' . $tipo_filter : '') . (isset($per_page) ? '&per_page=' . $per_page : '')); ?>">
                <i class="fas fa-chevron-right"></i>
              </a>
            <?php else: ?>
              <button class="btn btn-sm btn-outline-secondary" disabled>
                <i class="fas fa-chevron-right"></i>
              </button>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Versión desktop: paginación completa -->
      <div class="d-none d-md-flex justify-content-between align-items-center mt-4">
        <div class="text-muted small">
          Mostrando página <?php echo $current_page; ?> de <?php echo $total_pages; ?>
        </div>
        <nav aria-label="Navegación de clientes">
          <ul class="pagination pagination-sm mb-0">
            <!-- Anterior -->
            <?php if($current_page > 1): ?>
              <li class="page-item">
                <a class="page-link" href="<?php echo site_url('admin/customers/index?page=' . ($current_page - 1) . (isset($search) && $search ? '&search=' . urlencode($search) : '') . (isset($status_filter) && $status_filter !== '' ? '&status=' . $status_filter : '') . (isset($tipo_filter) && $tipo_filter !== '' ? '&tipo=' . $tipo_filter : '') . (isset($per_page) ? '&per_page=' . $per_page : '')); ?>">
                  <i class="fas fa-chevron-left"></i> Anterior
                </a>
              </li>
            <?php else: ?>
              <li class="page-item disabled">
                <span class="page-link"><i class="fas fa-chevron-left"></i> Anterior</span>
              </li>
            <?php endif; ?>

            <!-- Páginas -->
            <?php
            $start_page = max(1, $current_page - 2);
            $end_page = min($total_pages, $current_page + 2);

            if($start_page > 1): ?>
              <li class="page-item">
                <a class="page-link" href="<?php echo site_url('admin/customers/index?page=1' . (isset($search) && $search ? '&search=' . urlencode($search) : '') . (isset($status_filter) && $status_filter !== '' ? '&status=' . $status_filter : '') . (isset($tipo_filter) && $tipo_filter !== '' ? '&tipo=' . $tipo_filter : '') . (isset($per_page) ? '&per_page=' . $per_page : '')); ?>">1</a>
              </li>
              <?php if($start_page > 2): ?>
                <li class="page-item disabled"><span class="page-link">...</span></li>
              <?php endif; ?>
            <?php endif; ?>

            <?php for($i = $start_page; $i <= $end_page; $i++): ?>
              <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                <a class="page-link" href="<?php echo site_url('admin/customers/index?page=' . $i . (isset($search) && $search ? '&search=' . urlencode($search) : '') . (isset($status_filter) && $status_filter !== '' ? '&status=' . $status_filter : '') . (isset($tipo_filter) && $tipo_filter !== '' ? '&tipo=' . $tipo_filter : '') . (isset($per_page) ? '&per_page=' . $per_page : '')); ?>">
                  <?php echo $i; ?>
                </a>
              </li>
            <?php endfor; ?>

            <?php if($end_page < $total_pages): ?>
              <?php if($end_page < $total_pages - 1): ?>
                <li class="page-item disabled"><span class="page-link">...</span></li>
              <?php endif; ?>
              <li class="page-item">
                <a class="page-link" href="<?php echo site_url('admin/customers/index?page=' . $total_pages . (isset($search) && $search ? '&search=' . urlencode($search) : '') . (isset($status_filter) && $status_filter !== '' ? '&status=' . $status_filter : '') . (isset($tipo_filter) && $tipo_filter !== '' ? '&tipo=' . $tipo_filter : '') . (isset($per_page) ? '&per_page=' . $per_page : '')); ?>">
                  <?php echo $total_pages; ?>
                </a>
              </li>
            <?php endif; ?>

            <!-- Siguiente -->
            <?php if($current_page < $total_pages): ?>
              <li class="page-item">
                <a class="page-link" href="<?php echo site_url('admin/customers/index?page=' . ($current_page + 1) . (isset($search) && $search ? '&search=' . urlencode($search) : '') . (isset($status_filter) && $status_filter !== '' ? '&status=' . $status_filter : '') . (isset($tipo_filter) && $tipo_filter !== '' ? '&tipo=' . $tipo_filter : '') . (isset($per_page) ? '&per_page=' . $per_page : '')); ?>">
                  Siguiente <i class="fas fa-chevron-right"></i>
                </a>
              </li>
            <?php else: ?>
              <li class="page-item disabled">
                <span class="page-link">Siguiente <i class="fas fa-chevron-right"></i></span>
              </li>
            <?php endif; ?>
          </ul>
        </nav>
      </div>
    <?php endif; ?>
  </div>

  <div class="modal fade" id="myModal" data-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true"></div>