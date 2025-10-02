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
  <li class="nav-item active">
    <a class="nav-link" href="<?php echo site_url('admin/dashboard'); ?>" style="color:#fff;">
      <i class="fas fa-th-large" style="color:#f2c94c;"></i>
      <span>Inicio</span>
    </a>
  </li>

  <!-- Divider -->
  <hr class="sidebar-divider" style="border-color:#f2c94c;">

  <!-- Clientes -->
  <?php if (function_exists('can_view') && can_view($this->session->userdata('perfil'), 'customers')): ?>
  <li class="nav-item">
    <a class="nav-link" href="<?php echo site_url('admin/customers'); ?>" style="color:#fff;">
      <i class="fas fa-users" style="color:#f2c94c;"></i>
      <span>Clientes</span>
    </a>
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
        <a class="collapse-item" href="<?php echo site_url('admin/reports'); ?>" style="color:#ffffff;">Resumen General</a>
        <a class="collapse-item" href="<?php echo site_url('admin/reports/dates'); ?>" style="color:#ffffff;">Entre Fechas</a>
        <a class="collapse-item" href="<?php echo site_url('admin/reports/customers'); ?>" style="color:#ffffff;">General x Cliente</a>
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
        <a class="collapse-item" href="<?php echo site_url('admin/config'); ?>"style="color:#ecf0f1;">Editar datos</a>
        <a class="collapse-item" href="<?php echo site_url('admin/config/change_password'); ?>"style="color:#ecf0f1;">Cambiar Contraseña</a>
      </div>
    </div>
  </li>
  <?php endif; ?>

  <!-- Divider -->
  <hr class="sidebar-divider d-none d-md-block" style="border-color:#f2c94c;">

  <!-- Sidebar Toggler (Sidebar) -->
  <div class="text-center d-none d-md-inline">
    <button class="rounded-circle border-0" id="sidebarToggle"></button>
  </div>

</ul>


