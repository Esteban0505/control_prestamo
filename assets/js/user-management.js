/**
 * GESTIÓN DE USUARIOS - JAVASCRIPT
 * Sistema de Préstamo y Cobranzas
 * CodeIgniter 3
 */

// Variables globales para CSRF
var base_url = window.base_url || '';
var csrf_name = window.csrf_name || '';
var csrf_hash = window.csrf_hash || '';

// ✅ CORREGIDO: Inicializar cuando el documento esté listo
$(document).ready(function() {
    console.log('=== INICIALIZANDO GESTIÓN DE USUARIOS ===');
    console.log('Base URL:', base_url);
    console.log('CSRF Name:', csrf_name);
    console.log('CSRF Hash:', csrf_hash);
    
    // Si no tenemos las variables, intentar obtenerlas
    if (!base_url || !csrf_name || !csrf_hash) {
        getCSRFTokens();
    }
    
    // ✅ CORREGIDO: Esperar un poco antes de inicializar DataTable
    setTimeout(function() {
        initializeDataTable();
    }, 100);
    
    // Configurar eventos
    setupEventHandlers();
    
    console.log('=== GESTIÓN DE USUARIOS INICIALIZADA ===');
});

/**
 * Obtener tokens CSRF del servidor
 */
function getCSRFTokens() {
    $.ajax({
        url: base_url + 'admin/config/get_csrf_tokens',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                csrf_name = response.csrf_name;
                csrf_hash = response.csrf_hash;
                console.log('CSRF Config:', {csrf_name: csrf_name, csrf_hash: csrf_hash});
            }
        },
        error: function() {
            console.warn('No se pudieron obtener los tokens CSRF');
        }
    });
}

/**
 * ✅ CORREGIDO: Inicializar DataTable solo si no está ya inicializado
 */
function initializeDataTable() {
    // Verificar que DataTables esté disponible y la tabla exista
    if (!$.fn.DataTable) {
        console.log('DataTables no está disponible');
        return;
    }
    
    if ($('#dataTable').length === 0) {
        console.log('Tabla #dataTable no encontrada');
        return;
    }
    
    // Verificar si ya está inicializada
    if ($.fn.DataTable.isDataTable('#dataTable')) {
        console.log('DataTable ya está inicializado');
        return;
    }
    
    try {
        $('#dataTable').DataTable({
            "paging": false,
            "searching": false,
            "info": false,
            "ordering": true,
            "columnDefs": [
                { "orderable": false, "targets": 6 } // Deshabilitar ordenamiento en columna de acciones
            ]
        });
        console.log('✅ DataTable inicializado correctamente');
    } catch (error) {
        console.error('Error al inicializar DataTable:', error);
    }
}

/**
 * Configurar manejadores de eventos
 */
function setupEventHandlers() {
    // Botón de prueba
    $(document).on('click', '#test-modal', function(e) {
        e.preventDefault();
        e.stopPropagation();
        console.log('=== PROBANDO MODAL ===');
        testModal();
    });
    
    // Botón de permisos
    $(document).on('click', '.btn-permissions', function(e) {
        e.preventDefault();
        e.stopPropagation();
        console.log('=== ABRIENDO MODAL DE PERMISOS ===');
        openPermissionsModal($(this));
    });
    
    // Guardar permisos
    $(document).on('submit', '#formPermissions', function(e) {
        e.preventDefault();
        e.stopPropagation();
        console.log('=== GUARDANDO PERMISOS ===');
        savePermissions();
    });
    
    // Toggle de estado
    $(document).on('click', '.btn-toggle-state', function(e) {
        e.preventDefault();
        e.stopPropagation();
        console.log('=== TOGGLE ESTADO ===');
        toggleUserState($(this));
    });
    
    // Eliminar usuario
    $(document).on('click', '.btn-delete-user', function(e) {
        e.preventDefault();
        e.stopPropagation();
        console.log('=== ELIMINAR USUARIO ===');
        deleteUser($(this));
    });
    
    // Cambio de rol en el modal
    $(document).on('change', '#user-role-select', function() {
        var role = $(this).val();
        console.log('Cambio de rol a:', role);
        updatePermissionsForRole(role);
    });
}

/**
 * Actualizar permisos según el rol seleccionado
 */
function updatePermissionsForRole(role) {
    var rolePermissions = {
        'admin': ['dashboard', 'sidebar', 'sidebar_back', 'customers', 'coins', 'loans', 'payments', 'reports', 'config'],
        'operador': ['dashboard', 'sidebar', 'sidebar_back', 'customers', 'loans', 'payments', 'reports'],
        'viewer': ['dashboard', 'sidebar', 'reports']
    };

    var perms = rolePermissions[role] || [];

    // Uncheck all
    $('#permissionsModal input[name="permisos[]"]').prop('checked', false);

    // Check the ones for the role
    perms.forEach(function(perm) {
        $('#permissionsModal input[name="permisos[]"][value="' + perm + '"]').prop('checked', true);
    });

    console.log('Permisos actualizados para rol:', role, perms);
}

/**
 * Probar modal
 */
function testModal() {
    console.log('Modal existe:', $('#permissionsModal').length > 0);
    
    if ($('#permissionsModal').length === 0) {
        alert('Error: Modal de permisos no encontrado');
        return;
    }
    
    // Configurar modal con datos de prueba
    $('#permission-user-name').text('Usuario de Prueba');
    $('#permission-user-role').text('Admin');
    $('#user-role-select').val('admin');
    $('#permissionsModal').data('user-id', 1);
    
    // Configurar permisos de prueba
    $('#permissionsModal input[name="permisos[]"][value="dashboard"]').prop('checked', true);
    $('#permissionsModal input[name="permisos[]"][value="sidebar"]').prop('checked', true);
    $('#permissionsModal input[name="permisos[]"][value="sidebar_back"]').prop('checked', true);
    $('#permissionsModal input[name="permisos[]"][value="customers"]').prop('checked', true);
    $('#permissionsModal input[name="permisos[]"][value="coins"]').prop('checked', true);
    $('#permissionsModal input[name="permisos[]"][value="loans"]').prop('checked', true);
    $('#permissionsModal input[name="permisos[]"][value="payments"]').prop('checked', true);
    $('#permissionsModal input[name="permisos[]"][value="reports"]').prop('checked', true);
    $('#permissionsModal input[name="permisos[]"][value="config"]').prop('checked', true);
    
    // Mostrar modal
    $('#permissionsModal').modal('show');
    console.log('Modal de prueba mostrado');
}

/**
 * Abrir modal de permisos
 */
function openPermissionsModal($btn) {
    var userId = $btn.data('id');
    var userName = $btn.data('name');
    var userRole = $btn.data('role');
    
    console.log('Usuario:', userId, userName, userRole);
    
    if ($('#permissionsModal').length === 0) {
        console.error('Modal de permisos no encontrado en el DOM');
        alert('Error: Modal de permisos no encontrado');
        return;
    }
    
    // Configurar modal
    $('#permission-user-name').text(userName);
    $('#permission-user-role').text(userRole.charAt(0).toUpperCase() + userRole.slice(1));
    $('#user-role-select').val(userRole);
    $('#permissionsModal').data('user-id', userId);
    
    // Cargar permisos del usuario
    $.ajax({
        url: base_url + 'admin/config/get_permissions',
        type: 'POST',
        data: { 
            user_id: userId, 
            [csrf_name]: csrf_hash 
        },
        dataType: 'json',
        success: function(res) {
            console.log('get_permissions response', res);
            if (res.success) {
                // Poblar modal con permisos
                $('#permissionsModal select[name="role"]').val(res.user.role);
                // Reset all checkboxes
                $('#permissionsModal input[name="permisos[]"]').prop('checked', false);
                // Set permissions from array
                for (var i = 0; i < res.permissions.length; i++) {
                    var perm = res.permissions[i];
                    $('#permissionsModal input[name="permisos[]"][value="' + perm.permission_name + '"]').prop('checked', !!perm.value);
                }

                // Mostrar modal
                $('#permissionsModal').modal('show');
                console.log('Modal de permisos mostrado exitosamente');
            } else {
                console.error('Error al cargar permisos:', res.message);
                alert('Error al cargar permisos: ' + (res.message || 'Error desconocido'));
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error get_permissions', xhr);
            alert('Error de conexión al cargar permisos: ' + error);
        }
    });
}

/**
 * Guardar permisos
 */
function savePermissions() {
    var userId = $('#permissionsModal').data('user-id');
    
    console.log('Guardando permisos para usuario:', userId);

    var allPermissions = ['dashboard', 'sidebar', 'sidebar_back', 'customers', 'coins', 'loans', 'payments', 'reports', 'config'];
    var checkedPermissions = [];

    $('#permissionsModal input[name="permisos[]"]:checked').each(function() {
        checkedPermissions.push($(this).val());
    });

    console.log('Permisos marcados:', checkedPermissions);

    var permissionsArray = [];
    allPermissions.forEach(function(perm) {
        permissionsArray.push({
            permission_name: perm,
            value: checkedPermissions.includes(perm) ? 1 : 0
        });
    });

    var payload = {
        user_id: userId,
        role: $('#permissionsModal select[name="role"]').val(),
        permissions: permissionsArray
    };
    payload[csrf_name] = csrf_hash;
    
    console.log('Payload a enviar:', payload);
    
    $.ajax({
        url: base_url + 'admin/config/save_permissions',
        type: 'POST',
        data: payload,
        dataType: 'json',
        success: function(res) {
            console.log('save_permissions response', res);
            if (res.success) {
                $('#permissionsModal').modal('hide');
                alert('Permisos actualizados correctamente');
                console.log('Permisos guardados exitosamente');
            } else {
                console.error('Error al guardar permisos:', res.message);
                alert('Error al guardar permisos: ' + (res.message || 'Error desconocido'));
            }
        },
        error: function(xhr, status, error) {
            console.error('save_permissions error', xhr);
            alert('Error de conexión al guardar permisos: ' + error);
        }
    });
}

/**
 * Toggle estado de usuario
 */
function toggleUserState($btn) {
    var userId = $btn.data('id');
    var currentState = $btn.data('current-state');
    
    console.log('Toggle estado para usuario:', userId, 'Estado actual:', currentState);
    
    // Deshabilitar botón para evitar múltiples clics
    $btn.prop('disabled', true);
    
    $.ajax({
        url: base_url + 'admin/config/ajax_toggle_status', // ✅ CORREGIDO: Usar método AJAX correcto
        type: 'POST',
        data: { 
            user_id: userId, 
            [csrf_name]: csrf_hash 
        },
        dataType: 'json',
        success: function(res) {
            console.log('ajax_toggle_status response', res);
            $btn.prop('disabled', false);
            
            if (res.success) {
                // Actualizar badge y botón
                var $row = $btn.closest('tr');
                var $badge = $row.find('.user-state-badge');
                
                if (res.estado == 1) {
                    // Usuario activado
                    $badge.removeClass('badge-danger').addClass('badge-success').text('Activo');
                    $btn.removeClass('btn-success').addClass('btn-warning')
                        .data('current-state', 1)
                        .attr('title', 'Desactivar')
                        .html('<i class="fas fa-toggle-on text-danger"></i> Desactivar');
                } else {
                    // Usuario desactivado
                    $badge.removeClass('badge-success').addClass('badge-danger').text('Inactivo');
                    $btn.removeClass('btn-warning').addClass('btn-success')
                        .data('current-state', 0)
                        .attr('title', 'Activar')
                        .html('<i class="fas fa-toggle-off text-success"></i> Activar');
                }
                
                // Mostrar mensaje de confirmación
                showNotification(res.message || 'Estado actualizado correctamente', 'success');
                console.log('Estado actualizado exitosamente');
            } else {
                console.error('Error al actualizar estado:', res.message);
                showNotification('Error al actualizar estado: ' + (res.message || 'Error desconocido'), 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('ajax_toggle_status error', xhr);
            $btn.prop('disabled', false);
            showNotification('Error de conexión al actualizar estado: ' + error, 'error');
        }
    });
}

/**
 * Eliminar usuario
 */
function deleteUser($btn) {
    var userId = $btn.data('id');
    var userName = $btn.data('name');
    
    console.log('Eliminar usuario:', userId, userName);
    
    if (confirm('¿Estás seguro de que deseas eliminar al usuario "' + userName + '"?\n\nEsta acción no se puede deshacer.')) {
        console.log('Confirmando eliminación de usuario:', userId);
        
        $.ajax({
            url: base_url + 'admin/config/delete_user',
            type: 'POST',
            data: { 
                user_id: userId,
                [csrf_name]: csrf_hash
            },
            dataType: 'json',
            success: function(response) {
                console.log('delete response', response);
                if (response.success) {
                    alert(response.message || 'Usuario eliminado correctamente');
                    // Remover la fila del DataTable
                    if ($.fn.DataTable) {
                        var table = $('#dataTable').DataTable();
                        table.row($('button[data-id="' + userId + '"]').closest('tr')).remove().draw();
                    } else {
                        // Fallback si DataTable no está disponible
                        $('button[data-id="' + userId + '"]').closest('tr').remove();
                    }
                    console.log('Usuario eliminado exitosamente');
                } else {
                    console.error('Error al eliminar usuario:', response.message);
                    alert('Error al eliminar el usuario: ' + (response.message || 'Error desconocido'));
                }
            },
            error: function(xhr, status, error) {
                console.error('Error AJAX delete:', xhr);
                alert('Error de conexión al eliminar el usuario: ' + error);
            }
        });
    } else {
        console.log('Eliminación cancelada por el usuario');
    }
}

/**
 * Función auxiliar para mostrar alertas
 */
function showAlert(type, message) {
    var alertClass = 'alert-' + type;
    var alertHtml = '<div class="alert ' + alertClass + ' alert-dismissible fade show" role="alert">' +
                   message +
                   '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                   '<span aria-hidden="true">&times;</span>' +
                   '</button>' +
                   '</div>';
    
    // Insertar alerta al inicio del contenido
    $('.card-body').prepend(alertHtml);
    
    // Auto-ocultar después de 5 segundos
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 5000);
}

/**
 * ✅ FUNCIÓN AGREGADA: Mostrar notificaciones elegantes
 */
function showNotification(message, type) {
    var alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    var icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
    
    var notification = $('<div class="alert ' + alertClass + ' alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">' +
        '<i class="fas ' + icon + ' mr-2"></i>' + message +
        '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
        '<span aria-hidden="true">&times;</span>' +
        '</button>' +
        '</div>');
    
    $('body').append(notification);
    
    // Auto-remover después de 5 segundos
    setTimeout(function() {
        notification.alert('close');
    }, 5000);
}