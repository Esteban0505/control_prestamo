<nav class="navbar navbar-expand navbar-dark topbar mb-4 shadow" 
     style="background: linear-gradient(135deg, #071e3d, #1b2a49); padding:0.5rem 1rem;">

  <!-- Sidebar Toggle (Topbar) -->
  <button id="sidebarToggleTop" 
          class="btn btn-link d-md-none rounded-circle mr-3 text-light" 
          style="font-size:1.2rem;">
    <i class="fa fa-bars"></i>
  </button>

  <!-- Barra de búsqueda -->
<form class="form-inline mr-auto ml-md-3 my-2 my-md-0">
  <div class="input-group" style="position: relative;">
    <input type="text" class="form-control bg-dark text-light border-0" 
           placeholder="Buscar..." 
           style="padding-left: 2.2rem; border-radius: 30px; background:#0f1f3b;">
    <span style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#f2c94c;">
      <i class="fas fa-search"></i>
    </span>
  </div>
</form>


  <!-- Topbar Navbar -->
  <ul class="navbar-nav ml-auto">

    <!-- Separador -->
    <div class="topbar-divider d-none d-sm-block"></div>

<!-- Usuario -->
<li class="nav-item dropdown no-arrow">
  <a class="nav-link dropdown-toggle text-light" href="#" id="userDropdown" role="button" 
     data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" 
     style="display:flex; align-items:center; gap:8px;">
     
    <!-- Nombre del usuario -->
    <span class="d-none d-lg-inline small" style="color:#f2c94c; font-weight:600;">
      <?php echo $this->session->userdata('first_name').' '.$this->session->userdata('last_name'); ?>
    </span>

    <!-- Ícono moderno de usuario -->
    <i class="fas fa-user-circle" 
       style="font-size:28px; color:#f2c94c;"></i>
  </a>

  <!-- Dropdown -->
  <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="userDropdown">
    <a class="dropdown-item" href="#">
      <i class="fas fa-user fa-sm fa-fw mr-2 text-secondary"></i>
      Perfil
    </a>
    <a class="dropdown-item" href="#">
      <i class="fas fa-cog fa-sm fa-fw mr-2 text-secondary"></i>
      Configuración
    </a>
    <div class="dropdown-divider"></div>
    <a class="dropdown-item" href="<?php echo site_url('user/logout'); ?>">
      <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-danger"></i>
      Salir
    </a>
  </div>
</li>


  </ul>
</nav>
