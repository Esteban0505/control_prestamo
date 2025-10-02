<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="icon" type="image/png" href="<?php echo base_url('assets/img/favicon.png'); ?>">

  <!-- FontAwesome + Google Fonts -->
  <link href="<?php echo base_url('assets/vendor/fontawesome-free/css/all.min.css'); ?>" rel="stylesheet">
  <title>CREDITOS VALU</title>
<link rel="icon" href="<?php echo base_url('assets/img/log.png'); ?>" type="image/x-icon" />

  <!-- Estilos embebidos -->
  <style>
    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      padding: 0;
      font-family: 'Nunito', sans-serif;
      background: linear-gradient(135deg, #071e3d, #1b2a49);
      color: #fff;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }

    .login-container {
      background: #0f1f3b;
      padding: 40px 30px;
      border-radius: 15px;
      max-width: 400px;
      width: 90%;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
      text-align: center;
    }

    .login-container img {
      width: 120px;
      margin-bottom: 15px;
    }

    .login-container h1 {
      font-size: 24px;
      color: #f2c94c;
      margin: 0;
      letter-spacing: 1px;
    }

    .login-container p {
      font-size: 13px;
      color: #ccc;
      margin-bottom: 30px;
    }

    .form-group {
      text-align: left;
      margin-bottom: 18px;
    }

    .input-wrapper {
      position: relative;
    }

    .input-icon {
      position: absolute;
      padding: 11px;
      color: #f2c94c;
      pointer-events: none;
    }

    .form-control {
      width: 100%;
      padding: 11px 10px 11px 38px;
      background: #0f1f3b;
      border: 1px solid #f2c94c;
      border-radius: 8px;
      color: white;
      font-size: 14px;
    }

    .form-control:focus {
      outline: none;
      border-color: #f2c94c;
      box-shadow: 0 0 5px #f2c94c;
    }

    .btn-login {
      width: 100%;
      background: linear-gradient(135deg, #f2c94c, #d4af37);
      color: #000;
      font-weight: bold;
      border: none;
      border-radius: 8px;
      padding: 12px;
      margin-top: 10px;
      transition: 0.3s ease;
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .btn-login:hover {
      opacity: 0.9;
      transform: scale(1.02);
    }

    .login-links {
      display: flex;
      justify-content: space-between;
      margin-top: 15px;
      font-size: 13px;
    }

    .login-links a {
      color: #f2c94c;
      text-decoration: none;
    }

    .login-links a:hover {
      text-decoration: underline;
    }

    .alert {
      margin-bottom: 20px;
      color: #fff;
      background: #c0392b;
      padding: 10px;
      border-radius: 8px;
      font-size: 13px;
    }

    .loader {
      display: none;
      border: 3px solid #fff;
      border-top: 3px solid #d4af37;
      border-radius: 50%;
      width: 18px;
      height: 18px;
      animation: spin 1s linear infinite;
      margin-left: 10px;
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }

    @media (max-width: 480px) {
      .login-container {
        padding: 30px 20px;
      }

      .login-container img {
        width: 100px;
      }

      .login-links {
        flex-direction: column;
        align-items: center;
        gap: 8px;
      }
    }
  </style>
</head>

<body>

  <div class="login-container">
    <!-- ✅ Logo: ahora desde /assets/img/login.jpg -->
    <img src="<?php echo base_url('assets/img/log.png'); ?>" alt="Logo Creditos Valu">

    <h1>CREDITOS VALU</h1>
    <p>PRESTAMOS A TU MEDIDA, SOLUCIONES A TU ALCANCE</p>

    <?php if ($this->session->flashdata('msg')): ?>
      <div class="alert text-center">
        <?= $this->session->flashdata('msg') ?>
      </div>
    <?php endif ?>

    <?php if(validation_errors()) { ?>
      <div class="alert">
        <?php echo validation_errors(); ?>
      </div>
    <?php } ?>

    <form id="loginForm" action="<?php echo site_url('user/login'); ?>" method="post">
      <div class="form-group">
        <div class="input-wrapper">
          <span class="input-icon"><i class="fas fa-user"></i></span>
          <input type="email" name="email" class="form-control" placeholder="Usuario o Correo Electrónico" required>
        </div>
      </div>

      <div class="form-group">
        <div class="input-wrapper">
          <span class="input-icon"><i class="fas fa-lock"></i></span>
          <input type="password" name="password" class="form-control" placeholder="Contraseña" required>
        </div>
      </div>

      <button type="submit" class="btn-login">
        Iniciar Sesión
        <div class="loader" id="loader"></div>
      </button>

      <div class="login-links">
        <a href="#">¿Olvidaste tu contraseña?</a>
        <a href="#">Registrarse</a>
      </div>
    </form>
  </div>

  <!-- Scripts -->
  <script src="<?php echo base_url('assets/vendor/jquery/jquery.min.js'); ?>"></script>
  <script src="<?php echo base_url('assets/vendor/bootstrap/js/bootstrap.bundle.min.js'); ?>"></script>

  <script>
    document.getElementById('loginForm').addEventListener('submit', function () {
      document.getElementById('loader').style.display = 'inline-block';
    });
  </script>

</body>
</html>
