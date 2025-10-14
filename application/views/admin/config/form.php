<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary"><?= $form_title ?></h6>
    </div>
    <div class="card-body">
        <?php if(validation_errors()): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo validation_errors('<li>', '</li>'); ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <?php if($this->session->flashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $this->session->flashdata('error') ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <?php echo form_open($form_action, 'id="user_form"'); ?>
        <input type="hidden" name="<?= $csrf_name ?>" value="<?= $csrf_hash ?>" />
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="first_name" class="form-label">Nombre <span class="text-danger">*</span></label>
                    <input type="text" 
                           class="form-control <?= form_error('first_name') ? 'is-invalid' : '' ?>" 
                           id="first_name" 
                           name="first_name" 
                           value="<?= set_value('first_name', isset($user) ? $user->first_name : '') ?>"
                           placeholder="Ingrese el nombre"
                           required>
                    <div class="invalid-feedback">
                        <?= form_error('first_name') ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="form-group">
                    <label for="last_name" class="form-label">Apellido <span class="text-danger">*</span></label>
                    <input type="text" 
                           class="form-control <?= form_error('last_name') ? 'is-invalid' : '' ?>" 
                           id="last_name" 
                           name="last_name" 
                           value="<?= set_value('last_name', isset($user) ? $user->last_name : '') ?>"
                           placeholder="Ingrese el apellido"
                           required>
                    <div class="invalid-feedback">
                        <?= form_error('last_name') ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="email" class="form-label">Correo Electrónico <span class="text-danger">*</span></label>
                    <input type="email" 
                           class="form-control <?= form_error('email') ? 'is-invalid' : '' ?>" 
                           id="email" 
                           name="email" 
                           value="<?= set_value('email', isset($user) ? $user->email : '') ?>"
                           placeholder="usuario@ejemplo.com"
                           required>
                    <div class="invalid-feedback">
                        <?= form_error('email') ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="form-group">
                    <label for="perfil" class="form-label">Perfil <span class="text-danger">*</span></label>
                    <select class="form-control <?= form_error('perfil') ? 'is-invalid' : '' ?>" 
                            id="perfil" 
                            name="perfil" 
                            required>
                        <option value="">Seleccione un perfil</option>
                        <option value="admin" <?= set_select('perfil', 'admin', isset($user) && $user->perfil == 'admin') ?>>Administrador</option>
                        <option value="viewer" <?= set_select('perfil', 'viewer', isset($user) && $user->perfil == 'viewer') ?>>Viewer</option>
                        <option value="operador" <?= set_select('perfil', 'operador', isset($user) && $user->perfil == 'operador') ?>>Operador</option>
                    </select>
                    <div class="invalid-feedback">
                        <?= form_error('perfil') ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="password" class="form-label">
                        Contraseña 
                        <?php if(isset($user)): ?>
                            <small class="text-muted">(Dejar vacío para mantener la actual)</small>
                        <?php else: ?>
                            <span class="text-danger">*</span>
                        <?php endif; ?>
                    </label>
                    <div class="input-group">
                        <input type="password" 
                               class="form-control <?= form_error('password') ? 'is-invalid' : '' ?>" 
                               id="password" 
                               name="password" 
                               placeholder="<?= isset($user) ? 'Nueva contraseña (opcional)' : 'Ingrese la contraseña' ?>"
                               <?= !isset($user) ? 'required' : '' ?>>
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                <i class="fas fa-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>
                    <div class="invalid-feedback">
                        <?= form_error('password') ?>
                    </div>
                    <small class="form-text text-muted">
                        Mínimo 6 caracteres
                    </small>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="form-group">
                    <label for="password_confirm" class="form-label">Confirmar Contraseña</label>
                    <div class="input-group">
                        <input type="password" 
                               class="form-control" 
                               id="password_confirm" 
                               name="password_confirm" 
                               placeholder="Confirme la contraseña">
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary" type="button" id="togglePasswordConfirm">
                                <i class="fas fa-eye" id="toggleIconConfirm"></i>
                            </button>
                        </div>
                    </div>
                    <div class="invalid-feedback" id="password-match-error" style="display: none;">
                        Las contraseñas no coinciden
                    </div>
                </div>
            </div>
        </div>

        <?php if(isset($user)): ?>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label">Estado</label>
                    <div class="form-check form-switch">
                        <input class="form-check-input" 
                               type="checkbox" 
                               id="estado" 
                               name="estado" 
                               value="1" 
                               <?= isset($user) && $user->estado == 1 ? 'checked' : '' ?>>
                        <label class="form-check-label" for="estado">
                            Usuario activo
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label">Último Login</label>
                    <input type="text" 
                           class="form-control" 
                           value="<?= $user->ultimo_login ? date('d/m/Y H:i:s', strtotime($user->ultimo_login)) : 'Nunca' ?>" 
                           readonly>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="form-group mt-4">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> 
                <?= isset($user) ? 'Actualizar Usuario' : 'Crear Usuario' ?>
            </button>
            <a href="<?= site_url('admin/config') ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Cancelar
            </a>
        </div>

        <?php echo form_close(); ?>
    </div>
</div>

<script>
if (typeof $ !== 'undefined') {
$(document).ready(function() {
    // Toggle para mostrar/ocultar contraseña
    $('#togglePassword').on('click', function() {
        var passwordField = $('#password');
        var toggleIcon = $('#toggleIcon');
        
        if (passwordField.attr('type') === 'password') {
            passwordField.attr('type', 'text');
            toggleIcon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            passwordField.attr('type', 'password');
            toggleIcon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    // Toggle para mostrar/ocultar confirmación de contraseña
    $('#togglePasswordConfirm').on('click', function() {
        var passwordField = $('#password_confirm');
        var toggleIcon = $('#toggleIconConfirm');
        
        if (passwordField.attr('type') === 'password') {
            passwordField.attr('type', 'text');
            toggleIcon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            passwordField.attr('type', 'password');
            toggleIcon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    // Validación de coincidencia de contraseñas
    $('#password_confirm').on('blur', function() {
        var password = $('#password').val();
        var passwordConfirm = $(this).val();
        
        if (password && passwordConfirm && password !== passwordConfirm) {
            $(this).addClass('is-invalid');
            $('#password-match-error').show();
        } else {
            $(this).removeClass('is-invalid');
            $('#password-match-error').hide();
        }
    });

    // Validación en tiempo real
    $('#password').on('input', function() {
        var password = $(this).val();
        var passwordConfirm = $('#password_confirm').val();
        
        if (passwordConfirm && password !== passwordConfirm) {
            $('#password_confirm').addClass('is-invalid');
            $('#password-match-error').show();
        } else {
            $('#password_confirm').removeClass('is-invalid');
            $('#password-match-error').hide();
        }
    });

    // Validación del formulario antes de enviar
    $('#user_form').on('submit', function(e) {
        var password = $('#password').val();
        var passwordConfirm = $('#password_confirm').val();
        var isEdit = <?= isset($user) ? 'true' : 'false' ?>;
        
        // Si es edición y no se ingresó contraseña, no validar
        if (isEdit && !password) {
            return true;
        }
        
        // Si se ingresó contraseña, validar coincidencia
        if (password && password !== passwordConfirm) {
            e.preventDefault();
            $('#password_confirm').addClass('is-invalid');
            $('#password-match-error').show();
            showAlert('danger', 'Las contraseñas no coinciden');
            return false;
        }
        
        return true;
    });

    // Función para mostrar alertas
    function showAlert(type, message) {
        var alertHtml = '<div class="alert alert-' + type + ' alert-dismissible fade show text-center" role="alert">' +
                       message +
                       '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                       '<span aria-hidden="true">&times;</span>' +
                       '</button>' +
                       '</div>';
        
        $('.card-body').prepend(alertHtml);
        
        // Auto-ocultar después de 5 segundos
        setTimeout(function() {
            $('.alert').fadeOut();
        }, 5000);
    }

    // Limpiar validaciones al cambiar campos
    $('.form-control').on('input', function() {
        $(this).removeClass('is-invalid');
    });
});
}
</script>

<style>
.form-check-input:checked {
    background-color: #28a745;
    border-color: #28a745;
}

.form-check-input:focus {
    border-color: #28a745;
    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
}

.invalid-feedback {
    display: block;
}

.form-label {
    font-weight: 600;
    color: #5a5c69;
}

.text-danger {
    color: #e74a3b !important;
}
</style>

