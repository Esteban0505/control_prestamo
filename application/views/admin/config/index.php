<div class="card shadow mb-4">
    <div class="card-header d-flex align-items-center justify-content-between py-3">
        <h6 class="m-0 font-weight-bold text-primary">Gestión de Usuarios</h6>
        <a class="d-sm-inline-block btn btn-sm btn-success shadow-sm" href="<?php echo site_url('admin/config/create'); ?>">
            <i class="fas fa-plus-circle fa-sm"></i> Agregar Usuario
        </a>
    </div>
    <div class="card-body">
        <?php if ($this->session->flashdata('success')): ?>
            <div class="alert alert-success alert-dismissible fade show text-center" role="alert">
                <?= $this->session->flashdata('success') ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif ?>
        
        <?php if ($this->session->flashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show text-center" role="alert">
                <?= $this->session->flashdata('error') ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif ?>

        <!-- Estadísticas -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Usuarios</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats->total_users ?></div>
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
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Usuarios Activos</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats->active_users ?></div>
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
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Usuarios Inactivos</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats->inactive_users ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-user-times fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Barra de búsqueda -->
        <div class="row mb-3">
            <div class="col-md-6">
                <form method="GET" action="<?php echo site_url('admin/config'); ?>" class="form-inline">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" placeholder="Buscar usuarios..." value="<?= $search ?>">
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nombre</th>
                        <th>Usuario</th>
                        <th>Perfil</th>
                        <th>Estado</th>
                        <th>Último Login</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($users)): ?>
                        <?php foreach($users as $user): ?>
                            <tr>
                                <td><?= $user->id ?></td>
                                <td><?= $user->first_name . ' ' . $user->last_name ?></td>
                                <td><?= $user->email ?></td>
                                <td>
                                    <span class="badge badge-info"><?= ucfirst(isset($user->role) ? $user->role : $user->perfil) ?></span>
                                </td>
                                <td>
                                    <?php if($user->estado == 1): ?>
                                        <span class="badge badge-success user-state-badge">Activo</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger user-state-badge">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($user->ultimo_login && strtotime($user->ultimo_login) > 0): ?>
                                        <?= date('d/m/Y H:i', strtotime($user->ultimo_login)) ?>
                                    <?php else: ?>
                                        <span class="text-muted">Nunca</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <!-- Botón Editar -->
                                        <a href="<?= site_url('admin/config/edit/' . $user->id) ?>" 
                                           class="btn btn-warning btn-sm" 
                                           title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        
                                        <?php if($this->session->userdata('perfil') == 'admin' || $this->session->userdata('role') == 'admin'): ?>
                                        <!-- Botón Permisos -->
                                        <button type="button" 
                                                class="btn btn-info btn-sm btn-permissions" 
                                                data-id="<?= $user->id ?>"
                                                data-name="<?= $user->first_name . ' ' . $user->last_name ?>"
                                                data-role="<?= isset($user->role) ? $user->role : $user->perfil ?>"
                                                title="Gestionar Permisos">
                                            <i class="fas fa-key"></i>
                                        </button>
                                        
                                        <!-- ✅ BOTÓN DE ESTADO MEJORADO -->
                                        <button type="button" 
                                                class="btn btn-<?= $user->estado == 1 ? 'warning' : 'success' ?> btn-sm btn-toggle-state" 
                                                data-id="<?= $user->id ?>"
                                                data-current-state="<?= $user->estado ?>"
                                                title="<?= $user->estado == 1 ? 'Desactivar Usuario' : 'Activar Usuario' ?>">
                                            <?php if($user->estado == 1): ?>
                                                <i class="fas fa-toggle-on text-danger"></i> Desactivar
                                            <?php else: ?>
                                                <i class="fas fa-toggle-off text-success"></i> Activar
                                            <?php endif; ?>
                                        </button>
                                        
                                        <!-- Botón Eliminar -->
                                        <button type="button" 
                                                class="btn btn-danger btn-sm btn-delete-user" 
                                                data-id="<?= $user->id ?>"
                                                data-name="<?= $user->first_name . ' ' . $user->last_name ?>"
                                                title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php else: ?>
                                        <span class="text-muted">Solo Admin</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">No se encontraron usuarios</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <?php if($pagination): ?>
            <div class="d-flex justify-content-center mt-4">
                <?= $pagination ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de confirmación para eliminar -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirmar Eliminación</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que deseas eliminar al usuario <strong id="user-name"></strong>?</p>
                <p class="text-danger"><small>Esta acción no se puede deshacer.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirm-delete">Eliminar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de gestión de permisos -->
<div class="modal fade" id="permissionsModal" tabindex="-1" role="dialog" aria-labelledby="permissionsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="permissionsModalLabel">Gestionar Permisos de Usuario</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formPermissions">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Usuario: <strong id="permission-user-name"></strong></h6>
                            <p class="text-muted">Rol actual: <span id="permission-user-role" class="badge badge-info"></span></p>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="user-role-select">Cambiar Rol:</label>
                                <select class="form-control" name="role" id="user-role-select">
                                    <option value="admin">Administrador</option>
                                    <option value="operador">Operador</option>
                                    <option value="viewer">Visualizador</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h6>Permisos por Sección:</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="permisos[]" value="dashboard" id="permission-dashboard">
                                <label class="form-check-label" for="permission-dashboard">
                                    <i class="fas fa-th-large text-primary"></i> Dashboard
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="permisos[]" value="sidebar" id="permission-sidebar">
                                <label class="form-check-label" for="permission-sidebar">
                                    <i class="fas fa-bars text-info"></i> Sidebar
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="permisos[]" value="sidebar_back" id="permission-sidebar-back">
                                <label class="form-check-label" for="permission-sidebar-back">
                                    <i class="fas fa-arrow-left text-warning"></i> Sidebar Back
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="permisos[]" value="customers" id="permission-customers">
                                <label class="form-check-label" for="permission-customers">
                                    <i class="fas fa-users text-info"></i> Clientes
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="permisos[]" value="coins" id="permission-coins">
                                <label class="form-check-label" for="permission-coins">
                                    <i class="fas fa-coins text-warning"></i> Monedas
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="permisos[]" value="loans" id="permission-loans">
                                <label class="form-check-label" for="permission-loans">
                                    <i class="fas fa-hand-holding-usd text-success"></i> Préstamos
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="permisos[]" value="payments" id="permission-payments">
                                <label class="form-check-label" for="permission-payments">
                                    <i class="fas fa-cash-register text-danger"></i> Cobranzas
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="permisos[]" value="reports" id="permission-reports">
                                <label class="form-check-label" for="permission-reports">
                                    <i class="fas fa-chart-line text-info"></i> Reportes
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="permisos[]" value="config" id="permission-config">
                                <label class="form-check-label" for="permission-config">
                                    <i class="fas fa-cogs text-secondary"></i> Configuración
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-info-circle"></i>
                        <strong>Nota:</strong> Los permisos se actualizan automáticamente al cambiar el rol. 
                        Puedes personalizar permisos individuales después de seleccionar un rol.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ✅ SCRIPT MEJORADO CON DEBUGGING -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Config page loaded');

    // Obtener tokens CSRF
    let csrfName = '';
    let csrfHash = '';

    // Función para obtener tokens CSRF
    function getCsrfTokens() {
        return fetch('<?php echo site_url('admin/config/get_csrf_tokens'); ?>', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                csrfName = data.csrf_name;
                csrfHash = data.csrf_hash;
                console.log('CSRF tokens obtained:', csrfName, csrfHash);
            }
            return data;
        })
        .catch(error => {
            console.error('Error getting CSRF tokens:', error);
        });
    }

    // Inicializar tokens CSRF
    getCsrfTokens();

    // Evento para botones de permisos
    document.querySelectorAll('.btn-permissions').forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.getAttribute('data-id');
            const userName = this.getAttribute('data-name');
            const userRole = this.getAttribute('data-role');

            console.log('Opening permissions modal for user:', userId, userName, userRole);

            // Actualizar modal
            document.getElementById('permission-user-name').textContent = userName;
            document.getElementById('permission-user-role').textContent = userRole;
            document.getElementById('user-role-select').value = userRole;

            // Cargar permisos del usuario
            loadUserPermissions(userId);

            // Mostrar modal
            $('#permissionsModal').modal('show');
        });
    });

    // Función para cargar permisos del usuario
    function loadUserPermissions(userId) {
        console.log('Loading permissions for user:', userId);

        fetch('<?php echo site_url('admin/config/get_permissions'); ?>', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
                [csrfName]: csrfHash,
                'user_id': userId
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log('Permissions loaded:', data);

            if (data.success) {
                // Limpiar checkboxes
                document.querySelectorAll('input[name="permisos[]"]').forEach(checkbox => {
                    checkbox.checked = false;
                });

                // Marcar permisos del usuario
                if (data.permissions && Array.isArray(data.permissions)) {
                    data.permissions.forEach(permission => {
                        const checkbox = document.getElementById('permission-' + permission.permission_name);
                        if (checkbox) {
                            checkbox.checked = permission.value == 1;
                        }
                    });
                }

                // Actualizar tokens CSRF
                if (data.csrf_name && data.csrf_hash) {
                    csrfName = data.csrf_name;
                    csrfHash = data.csrf_hash;
                }
            } else {
                alert('Error al cargar permisos: ' + (data.message || 'Error desconocido'));
            }
        })
        .catch(error => {
            console.error('Error loading permissions:', error);
            alert('Error al cargar permisos');
        });
    }

    // Evento para cambio de rol
    document.getElementById('user-role-select').addEventListener('change', function() {
        const selectedRole = this.value;
        console.log('Role changed to:', selectedRole);

        // Aplicar permisos por defecto del rol
        const defaultPermissions = {
            'admin': ['dashboard', 'sidebar', 'sidebar_back', 'customers', 'coins', 'loans', 'payments', 'reports', 'config'],
            'operador': ['dashboard', 'sidebar', 'customers', 'loans', 'payments', 'reports'],
            'viewer': ['dashboard', 'reports']
        };

        // Limpiar checkboxes
        document.querySelectorAll('input[name="permisos[]"]').forEach(checkbox => {
            checkbox.checked = false;
        });

        // Marcar permisos por defecto del rol
        if (defaultPermissions[selectedRole]) {
            defaultPermissions[selectedRole].forEach(permission => {
                const checkbox = document.getElementById('permission-' + permission);
                if (checkbox) {
                    checkbox.checked = true;
                }
            });
        }
    });

    // Evento para guardar permisos
    document.getElementById('formPermissions').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const userId = document.querySelector('.btn-permissions:focus')?.getAttribute('data-id') ||
                      document.querySelector('.btn-permissions[data-id]')?.getAttribute('data-id');

        if (!userId) {
            alert('Error: No se pudo identificar el usuario');
            return;
        }

        // Preparar datos de permisos
        const permissions = [];
        document.querySelectorAll('input[name="permisos[]"]:checked').forEach(checkbox => {
            permissions.push({
                permission_name: checkbox.value,
                value: 1
            });
        });

        // Agregar permisos no marcados como 0
        document.querySelectorAll('input[name="permisos[]"]:not(:checked)').forEach(checkbox => {
            permissions.push({
                permission_name: checkbox.value,
                value: 0
            });
        });

        const submitData = {
            [csrfName]: csrfHash,
            'user_id': userId,
            'role': document.getElementById('user-role-select').value,
            'permissions': permissions
        };

        console.log('Saving permissions:', submitData);

        // Mostrar loading
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Guardando...';
        submitBtn.disabled = true;

        fetch('<?php echo site_url('admin/config/save_permissions'); ?>', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(submitData)
        })
        .then(response => response.json())
        .then(data => {
            console.log('Permissions saved:', data);

            if (data.success) {
                alert('Permisos guardados exitosamente');
                $('#permissionsModal').modal('hide');

                // Actualizar tokens CSRF
                if (data.csrf_name && data.csrf_hash) {
                    csrfName = data.csrf_name;
                    csrfHash = data.csrf_hash;
                }

                // Recargar página para reflejar cambios
                location.reload();
            } else {
                alert('Error al guardar permisos: ' + (data.message || 'Error desconocido'));
            }
        })
        .catch(error => {
            console.error('Error saving permissions:', error);
            alert('Error al guardar permisos');
        })
        .finally(() => {
            // Restaurar botón
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        });
    });

    // Evento para botones de estado
    document.querySelectorAll('.btn-toggle-state').forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.getAttribute('data-id');
            const currentState = this.getAttribute('data-current-state');

            if (confirm('¿Estás seguro de que deseas cambiar el estado de este usuario?')) {
                toggleUserState(userId);
            }
        });
    });

    // Función para cambiar estado del usuario
    function toggleUserState(userId) {
        console.log('Toggling state for user:', userId);

        fetch('<?php echo site_url('admin/config/ajax_toggle_status'); ?>', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
                [csrfName]: csrfHash,
                'user_id': userId
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log('State toggled:', data);

            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'Error desconocido'));
            }
        })
        .catch(error => {
            console.error('Error toggling state:', error);
            alert('Error al cambiar el estado');
        });
    }

    // Evento para botones de eliminar
    document.querySelectorAll('.btn-delete-user').forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.getAttribute('data-id');
            const userName = this.getAttribute('data-name');

            document.getElementById('user-name').textContent = userName;
            document.getElementById('confirm-delete').setAttribute('data-id', userId);

            $('#deleteModal').modal('show');
        });
    });

    // Evento para confirmar eliminación
    document.getElementById('confirm-delete').addEventListener('click', function() {
        const userId = this.getAttribute('data-id');

        fetch('<?php echo site_url('admin/config/ajax_delete'); ?>', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
                [csrfName]: csrfHash,
                'user_id': userId
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log('User deleted:', data);

            if (data.success) {
                alert(data.message);
                $('#deleteModal').modal('hide');
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'Error desconocido'));
            }
        })
        .catch(error => {
            console.error('Error deleting user:', error);
            alert('Error al eliminar usuario');
        });
    });
});
</script>