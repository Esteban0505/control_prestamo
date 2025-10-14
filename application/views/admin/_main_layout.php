<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>CREDITOS VALU</title>
    <link rel="shortcut icon" href="<?= base_url('assets/img/log.png'); ?>" type="image/x-icon">



    <!-- Custom fonts for this template--> 
    <link href="<?php echo base_url('assets/vendor/fontawesome-free/css/all.min.css'); ?>" rel="stylesheet" type="text/css">

    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="<?php echo base_url('assets/css/sb-admin-2.min.css'); ?>" rel="stylesheet">
    <link href="<?php echo base_url('assets/vendor/datatables/dataTables.bootstrap4.min.css'); ?>" rel="stylesheet">


    <link href="<?php echo base_url('assets/css/style.css'); ?>" rel="stylesheet">


</head>

<body id="page-top" style="overflow: hidden;">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Sidebar -->
        <?php $this->load->view('admin/components/sidebar'); ?>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column" style="overflow: auto; height: 100vh;">

            <!-- Main Content -->
            <div id="content">

                <!-- Topbar -->
                <?php $this->load->view('admin/components/navbar'); ?>
                <!-- End of Topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">

                  <?php $this->load->view($subview); ?>

                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; Esteban 2025</span>
                    </div>
                </div>
            </footer>
            <!-- End of Footer -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Bootstrap core JavaScript-->
    <script src="<?php echo base_url('assets/vendor/jquery/jquery.min.js'); ?>"></script>

    <script src="<?php echo base_url('assets/vendor/bootstrap/js/bootstrap.bundle.min.js'); ?>"></script>


    <!-- Core plugin JavaScript-->
    <script src="<?php echo base_url('assets/vendor/jquery-easing/jquery.easing.min.js'); ?>"></script>


    <!-- Custom scripts for all pages-->
    <script src="<?php echo base_url('assets/js/sb-admin-2.min.js'); ?>"></script>


    <script src="<?php echo base_url('assets/vendor/datatables/jquery.dataTables.min.js'); ?>"></script>

    <script src="<?php echo base_url('assets/vendor/datatables/dataTables.bootstrap4.min.js'); ?>"></script>


    <!-- ✅ CORREGIDO: Inicializar DataTable solo si no es la página de configuración -->
    <script>
      $(document).ready(function() {
        // Solo inicializar DataTable si no estamos en la página de configuración
        if (!window.location.href.includes('/admin/config') && $('#dataTable').length > 0) {
          $('#dataTable').DataTable({
            "order": [],
          });
        }
      });
    </script> 

    <script type="text/javascript">
        base_url = '<?= base_url();?>';
        // ✅ VARIABLES CSRF AGREGADAS PARA AJAX
        window.csrf_name = '<?= $this->security->get_csrf_token_name(); ?>';
        window.csrf_hash = '<?= $this->security->get_csrf_hash(); ?>';
    </script>
    <script src="<?php echo base_url('assets/js/script.js'); ?>"></script>
    
    <!-- ✅ SCRIPT ESPECÍFICO PARA GESTIÓN DE USUARIOS -->
    <?php if(isset($subview) && strpos($subview, 'config') !== false): ?>
    <script src="<?php echo base_url('assets/js/user-management.js'); ?>"></script>
    <?php endif; ?>

    <!-- ✅ SCRIPT PARA VALIDACIÓN AJAX EN EDITAR CLIENTE -->
    <?php if(isset($subview) && strpos($subview, 'customers/edit') !== false): ?>
    <script>
    $(document).ready(function() {
      $('#dni').on('blur', function() {
        var dni = $(this).val().trim();
        var id = $(this).data('id');
        if (dni === '') {
          $('#dni-error').hide();
          $('button[type="submit"]').prop('disabled', false);
          return;
        }
        $.ajax({
          url: '<?php echo site_url('admin/customers/check_dni_ajax'); ?>',
          type: 'POST',
          data: {dni: dni, id: id},
          dataType: 'json',
          success: function(response) {
            if (response.exists) {
              $('#ajax-error').html('⚠️ El número de cédula ingresado ya existe. Verifica la información antes de continuar.').show();
              $('button[type="submit"]').prop('disabled', true);
            } else {
              $('#ajax-error').hide();
              $('button[type="submit"]').prop('disabled', false);
            }
          }
        });
      });
    });
    </script>
    <?php endif; ?>

</body>

</html>