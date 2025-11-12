<?php if (function_exists('can_view') && can_view($this->session->userdata('perfil'), 'sidebar')): ?>
<ul class="navbar-nav sidebar sidebar-dark accordion toggled fixed-nav"
    style="background: linear-gradient(180deg, #071e3d 0%, #1b2a49 60%, #0f1f3b 100%);"
    id="accordionSidebar">

  <!-- Sidebar - Brand -->
  <a class="sidebar-brand d-flex align-items-center justify-content-center" href="<?php echo site_url('admin/dashboard'); ?>">
    <div class="sidebar-brand-icon">
      <!-- Logo SVG dorado -->
      <svg viewBox="0 0 100 100" width="32" height="32" aria-hidden="true">
        <defs>
          <linearGradient id="goldGrad" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0%" stop-color="#E8D18E"/>
            <stop offset="50%" stop-color="#D4AF37"/>
            <stop offset="100%" stop-color="#A67C00"/>
          </linearGradient>
        </defs>
        <path d="M50 8 L92 88 H8 Z" fill="none" stroke="url(#goldGrad)" stroke-width="6" stroke-linejoin="round"/>
        <path d="M50 28 C56 34,59 38,62 44 C55 44,50 46,45 50 C56 50,63 54,70 62 C60 60,52 62,44 68 C52 68,58 72,64 78 H36 C44 70,48 64,48 58 C42 60,36 60,30 60 C38 52,44 44,50 28 Z"
              fill="url(#goldGrad)"/>
      </svg>
    </div>
    <div class="sidebar-brand-text mx-3" style="color:#f2c94c; font-weight:bold;">CREDITOS VALU</div>
  </a>

  <!-- Divider -->
  <hr class="sidebar-divider my-0" style="border-color:#f2c94c;">

  <!-- Inicio -->
  <?php if (function_exists('can_view') && can_view($this->session->userdata('perfil'), 'dashboard')): ?>
  <li class="nav-item active">
    <a class="nav-link" href="<?php echo site_url('admin/dashboard'); ?>" style="color:#fff;">
      <i class="fas fa-th-large" style="color:#f2c94c;"></i>
      <span>Inicio</span>
    </a>
  </li>
  <?php endif; ?>

  <!-- Divider -->
  <hr class="sidebar-divider" style="border-color:#f2c94c;">

  <!-- Clientes -->
  <?php if (function_exists('can_view') && can_view($this->session->userdata('perfil'), 'customers')): ?>
  <?php
    // Obtener estadísticas de alertas para notificaciones
    $CI =& get_instance();
    $CI->load->model('customers_m');
    $CI->load->model('payments_m');

    $alerts = [
      'high_risk' => $CI->payments_m->count_clients_by_risk('high'),
      'medium_risk' => $CI->payments_m->count_clients_by_risk('medium'),
      'low_risk' => $CI->payments_m->count_clients_by_risk('low'),
      'blacklisted' => $CI->customers_m->get_blacklist_stats()->active_blocks ?? 0
    ];

    $total_alerts = $alerts['high_risk'] + $alerts['medium_risk'] + $alerts['low_risk'];
  ?>
  <li class="nav-item">
    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseClientes" aria-expanded="false" aria-controls="collapseClientes" style="color:#fff;">
      <i class="fas fa-users" style="color:#f2c94c;"></i>
      <span>Clientes</span>
      <?php if ($total_alerts > 0): ?>
        <span class="badge badge-pill badge-danger ml-2" style="font-size: 0.7em;"><?php echo $total_alerts; ?></span>
      <?php endif; ?>
    </a>
    <div id="collapseClientes" class="collapse" aria-labelledby="headingUtilities" data-parent="#accordionSidebar">
      <div class="bg-dark py-2 collapse-inner rounded" style="background:#343a40;">
        <?php if (function_exists('can_view') && can_view($this->session->userdata('perfil'), 'customers_list')): ?>
        <a class="collapse-item" href="<?php echo site_url('admin/customers'); ?>" style="color:#ffffff;">
          Lista de Clientes
          <?php if ($alerts['blacklisted'] > 0): ?>
            <span class="badge badge-pill badge-dark ml-2" style="font-size: 0.6em;"><?php echo $alerts['blacklisted']; ?> bloqueados</span>
          <?php endif; ?>
        </a>
        <?php endif; ?>
        <?php if (function_exists('can_view') && can_view($this->session->userdata('perfil'), 'customers_overdue')): ?>
        <a class="collapse-item" href="<?php echo site_url('admin/customers/overdue'); ?>" style="color:#ffffff;">
          Pagos Vencidos
          <?php if ($total_alerts > 0): ?>
            <div class="mt-1">
              <?php if ($alerts['high_risk'] > 0): ?>
                <span class="badge badge-pill badge-danger mr-1" style="font-size: 0.6em;"><?php echo $alerts['high_risk']; ?> alto</span>
              <?php endif; ?>
              <?php if ($alerts['medium_risk'] > 0): ?>
                <span class="badge badge-pill badge-warning mr-1" style="font-size: 0.6em;"><?php echo $alerts['medium_risk']; ?> medio</span>
              <?php endif; ?>
              <?php if ($alerts['low_risk'] > 0): ?>
                <span class="badge badge-pill badge-info" style="font-size: 0.6em;"><?php echo $alerts['low_risk']; ?> bajo</span>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </a>
        <?php endif; ?>
      </div>
    </div>
  </li>
  <?php endif; ?>

  <!-- Monedas -->
  <?php if (function_exists('can_view') && can_view($this->session->userdata('perfil'), 'coins')): ?>
  <li class="nav-item">
    <a class="nav-link" href="<?php echo site_url('admin/coins'); ?>" style="color:#fff;">
      <i class="fas fa-coins" style="color:#f2c94c;"></i>
      <span>Monedas</span>
    </a>
  </li>
  <?php endif; ?>

  <!-- Préstamos -->
  <?php if (function_exists('can_view') && can_view($this->session->userdata('perfil'), 'loans')): ?>
  <li class="nav-item">
    <a class="nav-link" href="<?php echo site_url('admin/loans'); ?>" style="color:#fff;">
      <i class="fas fa-hand-holding-usd" style="color:#f2c94c;"></i>
      <span>Préstamos</span>
    </a>
  </li>
  <?php endif; ?>

  <!-- Cobranzas -->
  <?php if (function_exists('can_view') && can_view($this->session->userdata('perfil'), 'payments')): ?>
  <li class="nav-item">
    <a class="nav-link" href="<?php echo site_url('admin/payments'); ?>" style="color:#fff;">
      <i class="fas fa-cash-register" style="color:#f2c94c;"></i>
      <span>Cobranzas</span>
    </a>
  </li>
  <?php endif; ?>

  <!-- Reportes -->
  <?php if (function_exists('can_view') && can_view($this->session->userdata('perfil'), 'reports')): ?>
  <li class="nav-item">
    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseReportes" aria-expanded="false" aria-controls="collapseReportes" style="color:#fff;">
      <i class="fas fa-chart-line" style="color:#f2c94c;"></i>
      <span>Reportes</span>
    </a>
    <div id="collapseReportes" class="collapse" aria-labelledby="headingUtilities" data-parent="#accordionSidebar">
      <div class="bg-dark py-2 collapse-inner rounded" style="background:#343a40;">
        <?php if (function_exists('can_view') && can_view($this->session->userdata('perfil'), 'reports_collector_commissions')): ?>
        <a class="collapse-item" href="<?php echo site_url('admin/reports'); ?>" style="color:#ffffff;">Comisiones por Cobrador</a>
        <?php endif; ?>
        <?php if (function_exists('can_view') && can_view($this->session->userdata('perfil'), 'reports_admin_commissions')): ?>
        <a class="collapse-item" href="<?php echo site_url('admin/reports/dates'); ?>" style="color:#ffffff;">Comisiones por Administrador</a>
        <?php endif; ?>
        <?php if (function_exists('can_view') && can_view($this->session->userdata('perfil'), 'reports_general_customer')): ?>
        <a class="collapse-item" href="<?php echo site_url('admin/reports/customers'); ?>" style="color:#ffffff;">Comisiones General x Cliente</a>
        <?php endif; ?>
      </div>
    </div>
  </li>
  <?php endif; ?>

  <!-- Configuración -->
  <?php if (function_exists('can_view') && can_view($this->session->userdata('perfil'), 'config')): ?>
  <li class="nav-item">
    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseConfiguracion" aria-expanded="false" aria-controls="collapseConfiguracion" style="color:#fff;">
      <i class="fas fa-cogs" style="color:#f2c94c;"></i>
      <span>Configuración</span>
    </a>
    <div id="collapseConfiguracion" class="collapse" aria-labelledby="headingUtilities" data-parent="#accordionSidebar">
      <div class="bg-dark py-2 collapse-inner rounded" style="background:#2c3e50;">
        <?php if (function_exists('can_view') && can_view($this->session->userdata('perfil'), 'config_edit_data')): ?>
        <a class="collapse-item" href="<?php echo site_url('admin/config'); ?>"style="color:#ecf0f1;">Editar datos</a>
        <?php endif; ?>
        <?php if (function_exists('can_view') && can_view($this->session->userdata('perfil'), 'config_change_password')): ?>
         <!-- <a class="collapse-item" href="<?php echo site_url('admin/config/change_password'); ?>"style="color:#ecf0f1;">Cambiar Contraseña</a>-->
        <?php endif; ?>
      </div>
    </div>
  </li>
  <?php endif; ?>

  <!-- Divider -->
  <hr class="sidebar-divider d-none d-md-block" style="border-color:#f2c94c;">

  <!-- Sidebar Toggler (Sidebar) -->
  <?php if (function_exists('can_view') && can_view($this->session->userdata('perfil'), 'sidebar_back')): ?>
  <div class="text-center d-none d-md-inline">
    <button class="rounded-circle border-0" id="sidebarToggle"></button>
  </div>
  <?php endif; ?>

</ul>
<?php endif; ?>


