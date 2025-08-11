<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Control Préstamo</title>

  <!-- Fuentes y estilos -->
  <link href="<?php echo site_url() ?>assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css?family=Nunito:400,600,700&display=swap" rel="stylesheet">
  <link href="<?php echo site_url() ?>assets/css/sb-admin-2.min.css" rel="stylesheet">

  <style>
    body {
      margin: 0;
      padding: 0;
      font-family: 'Nunito', sans-serif;
    }
    /* Fondo animado degradado */
    #particles-js {
      position: fixed;
      width: 100%;
      height: 100%;
      background: linear-gradient(135deg, #1e7245, #2a988f);
      z-index: -1;
    }
    /* Card login */
    .login-card {
      border-radius: 15px;
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(8px);
      box-shadow: 0px 5px 20px rgba(0,0,0,0.15);
      animation: fadeInUp 0.8s ease-out;
    }
    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .shake { animation: shake 0.4s; }
    @keyframes shake {
      0%,100% { transform: translateX(0); }
      25% { transform: translateX(-5px); }
      50% { transform: translateX(5px); }
      75% { transform: translateX(-5px); }
    }
    /* Botones */
    .btn-login {
      background: linear-gradient(135deg, #1e7245, #2a988f);
      color: #fff;
      font-weight: bold;
      border: none;
      transition: all 0.3s ease;
    }
    .btn-login:hover {
      transform: scale(1.03);
      background: linear-gradient(135deg, #1b5e37, #257a75);
    }
    .btn-secondary-login {
      background-color: #36b9cc;
      color: #fff;
      font-weight: bold;
      transition: all 0.3s ease;
    }
    .btn-secondary-login:hover {
      background-color: #2a9dad;
      transform: scale(1.03);
    }
    /* Inputs */
    .input-group-text {
      background-color: #fff;
      border-right: 0;
    }
    .form-control {
      border-left: 0;
      font-size: 14px;
      padding: 10px 12px;
    }
    .form-control:focus {
      box-shadow: 0 0 8px rgba(30, 114, 69, 0.5);
      border-color: #1e7245;
    }
    /* Link pequeño */
    .small-link {
      font-size: 13px;
      color: #1e7245;
      text-decoration: none;
    }
    .small-link:hover {
      text-decoration: underline;
    }
    /* Loader */
    .loader {
      display: none;
      border: 4px solid #f3f3f3;
      border-top: 4px solid #1e7245;
      border-radius: 50%;
      width: 20px;
      height: 20px;
      animation: spin 1s linear infinite;
      margin-left: 10px;
    }
    @keyframes spin {
      0% { transform: rotate(0deg);}
      100% { transform: rotate(360deg);}
    }
  </style>
</head>

<body>

  <div id="particles-js"></div>

  <div class="container">
    <div class="row justify-content-center align-items-center" style="min-height: 100vh;">
      <div class="col-xl-4 col-lg-5 col-md-7 col-sm-9">
        <div class="card login-card shadow-lg p-4 <?php if ($this->session->flashdata('msg') || validation_errors()) { echo 'shake'; } ?>">
          <div class="text-center mb-4">
            <h1 class="h4 text-gray-900 font-weight-bold">Bienvenido</h1>
            <p class="text-muted">Inicia sesión para continuar</p>
          </div>

          <?php if ($this->session->flashdata('msg')): ?>
            <div class="alert alert-danger text-center">
              <?= $this->session->flashdata('msg') ?>
            </div>
          <?php endif ?>

          <?php if(validation_errors()) { ?>
            <div class="alert alert-danger">
              <?php echo validation_errors(); ?>
            </div>
          <?php } ?>

          <form id="loginForm" action="<?php echo site_url('user/login'); ?>" method="post">
            <!-- Email -->
            <div class="form-group">
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                </div>
                <input type="email" class="form-control" name="email" placeholder="Ingresar Email" required>
              </div>
            </div>
            <!-- Contraseña -->
            <div class="form-group">
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="fas fa-lock"></i></span>
                </div>
                <input type="password" class="form-control" name="password" placeholder="Ingresar Contraseña" required>
              </div>
            </div>
            <!-- Recordar -->
            <div class="form-group d-flex justify-content-between align-items-center">
              <div class="custom-control custom-checkbox small">
                <input type="checkbox" class="custom-control-input" id="rememberMe" name="remember">
                <label class="custom-control-label" for="rememberMe">Recordar</label>
              </div>
              <a href="#" class="small-link">¿Olvidaste tu contraseña?</a>
            </div>
            <!-- Botones -->
            <button type="submit" class="btn btn-login btn-block d-flex justify-content-center align-items-center">
              Ingresar
              <div class="loader" id="loader"></div>
            </button>
            <a href="#" class="btn btn-secondary-login btn-block mt-2">Registrar Cuenta</a>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="<?php echo site_url() ?>assets/vendor/jquery/jquery.min.js"></script>
  <script src="<?php echo site_url() ?>assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/particles.js"></script>

  <script>
    particlesJS.load('particles-js', 'https://cdn.jsdelivr.net/gh/VincentGarreau/particles.js/particles.json');
    document.getElementById('loginForm').addEventListener('submit', function() {
      document.getElementById('loader').style.display = 'inline-block';
    });
  </script>

</body>
</html>
