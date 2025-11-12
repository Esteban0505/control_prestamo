<nav class="navbar navbar-expand navbar-dark topbar mb-4 shadow" 
     style="background: linear-gradient(135deg, #071e3d, #1b2a49); padding:0.5rem 1rem;">

  <!-- Sidebar Toggle (Topbar) -->
  <button id="sidebarToggleTop" 
          class="btn btn-link d-md-none rounded-circle mr-3 text-light" 
          style="font-size:1.2rem;">
    <i class="fa fa-bars"></i>
  </button>

  <!-- Reloj en tiempo real -->
  <div class="mr-auto ml-md-3 my-2 my-md-0">
      <span id="realtime-clock" class=" text-light"
            style="border-radius: 30px; padding: 0.375rem 0.75rem; display: inline-block; border: none;"></span>
  </div>


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
    <div class="dropdown-divider"></div>
    <a class="dropdown-item" href="<?php echo site_url('user/logout'); ?>">
      <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-danger"></i>
      Salir
    </a>
  </div>
</li>


  </ul>
</nav>

<script>
function updateClock() {
    const now = new Date();
    const dateOptions = {
        timeZone: 'America/Bogota',
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    };
    const timeOptions = {
        timeZone: 'America/Bogota',
        hour12: true,
        hour: 'numeric',
        minute: '2-digit',
        second: '2-digit'
    };
    let dateString = now.toLocaleDateString('es-CO', dateOptions);
    dateString = dateString.charAt(0).toUpperCase() + dateString.slice(1);
    const timeString = now.toLocaleTimeString('es-CO', timeOptions);
    document.getElementById('realtime-clock').innerHTML = '<i class="fas fa-clock text-light mr-2"></i>' + dateString + ' - ' + timeString;
}
setInterval(updateClock, 1000);
updateClock(); // Initial call
</script>
