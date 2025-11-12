console.log('script.js cargado — build-debug:', 'v-debug-' + (new Date()).toISOString());

$(document).ready(function() {
  // Función para generar HTML de cuotas
  function generateQuotasHtml(quotas) {
    console.log('PAYMENTS_DIAGNOSIS: ========== INICIANDO generateQuotasHtml ==========');
    console.log('PAYMENTS_DIAGNOSIS: Cuotas recibidas:', quotas);
    console.log('PAYMENTS_DIAGNOSIS: Número de cuotas:', quotas ? quotas.length : 'UNDEFINED');

    // ALERT TEMPORAL PARA DIAGNOSTICAR: Verificar que la función se ejecuta
    console.log('🚨 ALERT DIAGNÓSTICO 🚨 FUNCIÓN generateQuotasHtml EJECUTADA - Número de cuotas:', (quotas ? quotas.length : 'UNDEFINED'));

    console.log('🚨 DIAGNÓSTICO INICIADO 🚨 Función generateQuotasHtml ejecutada - Número de cuotas:', (quotas ? quotas.length : 'UNDEFINED'));

    if (!Array.isArray(quotas)) {
      console.error("PAYMENTS_DIAGNOSIS: generateQuotasHtml recibió algo inválido:", quotas);
      return "";
    }

    var html = '';
    quotas.forEach(function(quota, index) {
      console.log('PAYMENTS_DIAGNOSIS: Procesando cuota #' + index + ':', quota);

      // DIAGNÓSTICO: Verificar formato de fecha antes del formateo
      var fechaOriginal = quota.date;
      console.log('PAYMENTS_DIAGNOSIS: Fecha original de cuota #' + index + ':', fechaOriginal);

      var fecha = quota.date ? new Date(quota.date).toLocaleDateString('es-ES') : 'Sin fecha';
      console.log('PAYMENTS_DIAGNOSIS: Fecha formateada para cuota #' + index + ':', fecha);

      // Verificar si la fecha se formateó correctamente
      if (fecha === 'Sin fecha' && quota.date) {
        console.error('PAYMENTS_DIAGNOSIS: ERROR - Fecha inválida para cuota #' + index + ':', quota.date);
      }

      var monto = parseFloat(quota.fee_amount).toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2});
      var intereses = parseFloat(quota.interest_amount || 0).toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2});
      var capital = parseFloat(quota.capital_amount || 0).toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2});
      var saldo = parseFloat(quota.balance || 0).toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2});

      // CORRECCIÓN CRÍTICA: Calcular montos pendientes para determinar si la cuota debe estar habilitada
      var interestPending = parseFloat(quota.interest_amount || 0) - parseFloat(quota.interest_paid || 0);
      var capitalPending = parseFloat(quota.capital_amount || 0) - parseFloat(quota.capital_paid || 0);
      var totalPending = interestPending + capitalPending;

      var feeAmount = parseFloat(quota.fee_amount || 0);
      var balance = parseFloat(quota.balance || 0);

      // LOGS DE CONSOLA PARA DEBUGGING: Información detallada de cada cuota
      console.log('PAYMENTS_DIAGNOSIS: Procesando cuota #' + quota.num_quota + ' (ID: ' + quota.id + ')');
      console.log('PAYMENTS_DIAGNOSIS:   - Monto cuota (fee_amount):', feeAmount);
      console.log('PAYMENTS_DIAGNOSIS:   - Saldo (balance):', balance);
      console.log('PAYMENTS_DIAGNOSIS:   - Interés pendiente:', interestPending, '(amount:', quota.interest_amount, '- paid:', (quota.interest_paid || 0), ')');
      console.log('PAYMENTS_DIAGNOSIS:   - Capital pendiente:', capitalPending, '(amount:', quota.capital_amount, '- paid:', (quota.capital_paid || 0), ')');
      console.log('PAYMENTS_DIAGNOSIS:   - Total pendiente:', totalPending);
      // No usar hasPendingByFeeAmount para habilitar cuotas ya pagadas

      // ALERT DIAGNÓSTICO DETALLADO PARA CADA CUOTA
      var hasAnyPendingAmount = (parseFloat(quota.interest_amount || 0) - parseFloat(quota.interest_paid || 0) > 0) ||
                               (parseFloat(quota.capital_amount || 0) - parseFloat(quota.capital_paid || 0) > 0);

      console.log('🔍 DIAGNÓSTICO CUOTA #' + quota.num_quota, {
        id: quota.id,
        fee_amount: feeAmount,
        balance: balance,
        interest_amount: (quota.interest_amount || 0),
        interest_paid: (quota.interest_paid || 0),
        capital_amount: (quota.capital_amount || 0),
        capital_paid: (quota.capital_paid || 0),
        interest_pending: interestPending,
        capital_pending: capitalPending,
        totalPending: totalPending,
        hasAnyPendingAmount: hasAnyPendingAmount,
        shouldBeEnabled: (totalPending > 0)
      });


      // Solo habilitar si realmente hay algo pendiente
      var isEnabled = (totalPending > 0);

      // VALIDACIÓN ADICIONAL: Verificar si hay algún monto pendiente real en la cuota
      var hasAnyPendingAmount = (parseFloat(quota.interest_amount || 0) - parseFloat(quota.interest_paid || 0) > 0) ||
                                (parseFloat(quota.capital_amount || 0) - parseFloat(quota.capital_paid || 0) > 0);

      // No forzar habilitación por fee_amount; evitar seleccionar cuotas ya pagadas

      // DIAGNÓSTICO GENERAL: Mostrar estado de todas las cuotas
      console.log('PAYMENTS_DIAGNOSIS: CUOTA #' + quota.num_quota + ' - isEnabled:', isEnabled, '- feeAmount:', feeAmount, '- totalPending:', totalPending);

      // ALERT FINAL PARA RESULTADO DE HABILITACIÓN
      // Log final por cuota (sin overrides)
      var disabled = !isEnabled;
      var btnClass = !isEnabled ? 'btn-outline-success' : 'btn-outline-danger';
      var estado = !isEnabled ? 'Pagado' : 'Pendiente';

      var checkboxValue = !isEnabled ? '' : quota.id;
      var checked = isEnabled ? '' : ''; // no seleccionar por defecto

      console.log('PAYMENTS_DIAGNOSIS: RESULTADO - Cuota #' + quota.num_quota + ' isEnabled:', isEnabled, '(totalPending:', totalPending, ')');

      // DIAGNÓSTICO VISUAL: Agregar indicador visual para debugging
      var visualIndicator = isEnabled ? '🟢 HABILITADA' : '🔴 DESHABILITADA';
      console.log('PAYMENTS_DIAGNOSIS: INDICADOR VISUAL - Cuota #' + quota.num_quota + ': ' + visualIndicator);

      // FORZAR HABILITACIÓN VISUAL PARA DEBUGGING
      var visualEnabled = isEnabled;
      var visualChecked = '';
      var visualDisabled = !isEnabled;
      var visualBtnClass = !isEnabled ? 'btn-outline-success' : 'btn-outline-danger';
      var visualEstado = !isEnabled ? 'Pagado' : 'Pendiente';
      var visualCheckboxValue = !isEnabled ? '' : quota.id;

      html += '<tr>';
      html += '<td><input type="checkbox" name="quota_id[]" ' + visualChecked + ' data-fee="' + quota.fee_amount + '" data-interes="' + (quota.interest_amount || 0) + '" data-capital="' + (quota.capital_amount || 0) + '" data-capital-paid="' + (quota.capital_paid || 0) + '" data-interest-paid="' + (quota.interest_paid || 0) + '" data-saldo="' + (quota.balance || 0) + '" value="' + visualCheckboxValue + '" ' + (visualDisabled ? 'disabled' : '') + '></td>';
      html += '<td>' + quota.num_quota + '</td>';
      html += '<td>' + fecha + '</td>';
      html += '<td>' + monto + '</td>';
      html += '<td>' + intereses + '</td>';
      html += '<td>' + capital + '</td>';
      html += '<td>' + saldo + '</td>';
      html += '<td><button type="button" class="btn btn-sm ' + visualBtnClass + '">' + visualEstado + '</button></td>';
      html += '</tr>';
    });

    console.log('PAYMENTS_DIAGNOSIS: HTML generado con', quotas.length, 'cuotas');
    console.log('PAYMENTS_DIAGNOSIS: ========== FIN generateQuotasHtml ==========');

    console.log('✅ DIAGNÓSTICO COMPLETADO - Se procesaron', quotas.length, 'cuotas');

    return html;
  }
  // Expresión regular provista para validar moneda
  var moneyRegex = /^(\d{1,3}(\.\d{3})*|\d+)(,\d{1,2})?$/;

  var calculationTimeout;
  var isCalculating = false;
  var currentAjax = null;
  var isCalculatingAmortization = false;
  var currentAjaxAmortization = null;
  var isValidLimit = true; // Variable para controlar si el límite de crédito es válido
  var isCheckingLimit = false; // Bandera para prevenir ejecuciones simultáneas de validación AJAX
  var currentAjaxLimit = null; // Para abortar AJAX de límite si es necesario

  $("#department_id").change(function(){
    dp_id = $("#department_id").val()
    $.get(base_url + "admin/customers/ajax_getProvinces/" + dp_id, function(data){
      $("#province_id").html(data);
    });
  });

  $("#province_id").change(function(){
    pr_id = $("#province_id").val()
    $.get(base_url + "admin/customers/ajax_getDistricts/" + pr_id, function(data){
      $("#district_id").html(data);
    });
  });

  // buscar cliente en loans con sugerencias
  var search_customers_loans = function(query) {
    console.log('Buscando clientes en loans para query:', query);
    if (query.length < 2) {
      $('#customer_suggestions_loans').hide();
      return;
    }
    $.post(base_url + "admin/loans/ajax_searchCst/", {dni: query, suggest: '1'}, function(data) {
        console.log('sin parse', data);
        data = JSON.parse(data);
        console.log('con parse', data);
        if (Array.isArray(data.cst)) {
            var suggestions = '';
            data.cst.forEach(function(cst) {
                suggestions += '<a class="dropdown-item" href="#" data-customer=\'' + JSON.stringify(cst) + '\'>' + cst.dni + ' - ' + cst.cst_name + '</a>';
            });
            $('#customer_suggestions_loans').html(suggestions).show();
        } else {
            $('#customer_suggestions_loans').hide();
        }
    });
  };

  var callback = function() {
    console.log('Iniciando búsqueda de cliente por DNI');
    var dni = $('#dni').val()
    if (dni == "") {
      alert('ingresar dni')
      return false
    } else {
      $.post(base_url + "admin/loans/ajax_searchCst/", {dni : dni}, function(data){
        console.log('sin parse', data)
        if (data == 'null'){
          $("#dni").val('');
          alert('No existe el cliente');
          $("#dni_cst").val('');
          $("#name_cst").val('');
          $("#customer").val('');
          $("#assigned_user_id").val('');
          $("#assigned_user_id").prop('disabled', false);
          $("#tipo_cliente").prop('disabled', false);
        }
        else {
            $("#dni").val('');
            data = JSON.parse(data);
            console.log('con parse', data)
            $("#assigned_user_id").val(data.user_id);
            $("#tipo_cliente").val(data.tipo_cliente || 'normal');
            
            // Verificar estado del cliente
            var customerStatus = data.status !== undefined ? data.status : 1;
            if (customerStatus == 0) {
                alert('El cliente está desactivado y no puede realizar préstamos. Active el cliente primero.');
                $("#dni_cst").val('');
                $("#name_cst").val('');
                $("#customer").val('');
                $("#assigned_user_id").prop('disabled', false);
                $("#tipo_cliente").prop('disabled', false);
                return;
            }
            
            if (data.loan_status == '0') {
                $("#customer").val(data.id);
                $("#dni_cst").val(data.dni);
                $("#name_cst").val(data.first_name + ' ' +data.last_name);
                // Deshabilitar campos mostrados del cliente cuando se encuentra un cliente válido
                $("#dni_cst").prop('disabled', true);
                $("#name_cst").prop('disabled', true);
                $("#assigned_user_id").prop('disabled', true);
                $("#tipo_cliente").prop('disabled', true);
                
                // Actualizar estado del cliente si existe el contenedor
                if ($('#customer_status_badge').length) {
                    updateCustomerStatusDisplay(data.id, customerStatus);
                }
                
                // Validar límite de crédito al seleccionar cliente
                validateCreditLimit();
                // Limpiar mensajes de error al seleccionar cliente válido
                hideErrorMessage();
            } else {
                alert('persona con prestamo pendiente')
                $("#dni_cst").val('');
                $("#name_cst").val('');
                $("#customer").val('');
                $("#assigned_user_id").prop('disabled', false);
                $("#tipo_cliente").prop('disabled', false);
            }
         }
      })
    }
    console.log('Búsqueda de cliente completada');
  };

  // Event listeners para búsqueda de clientes en loans
  $("#dni").on('input', function() {
    var query = $(this).val();
    search_customers_loans(query);
  });

  $("#dni").keypress(function(event) {
    if (event.which == 13) callback();
  });

  $('#btn_buscar').click(callback);

  // Seleccionar sugerencia en loans
  $(document).on('click', '#customer_suggestions_loans .dropdown-item', function(e) {
    e.preventDefault();
    var customerData = $(this).data('customer');
    $('#dni').val(customerData.dni + ' - ' + customerData.cst_name).data('selected-customer', customerData);
    $('#customer_suggestions_loans').hide();
    load_customer_data_loans(customerData);
  });

  // Ocultar sugerencias al hacer click fuera
  $(document).click(function(e) {
    if (!$(e.target).closest('#dni, #customer_suggestions_loans').length) {
      $('#customer_suggestions_loans').hide();
    }
  });

  // Función para cargar datos de cliente en loans
  var load_customer_data_loans = function(cst) {
    console.log('Cargando datos de cliente en loans:', cst.dni);
    $("#dni").val('');
    $("#customer").val(cst.id);
    $("#dni_cst").val(cst.dni);
    $("#name_cst").val(cst.cst_name);
    $("#assigned_user_id").val(cst.user_id);
    $("#tipo_cliente").val(cst.tipo_cliente || 'normal');

    // Deshabilitar campos mostrados del cliente cuando se encuentra un cliente válido
    $("#dni_cst").prop('disabled', true);
    $("#name_cst").prop('disabled', true);
    $("#assigned_user_id").prop('disabled', true);
    $("#tipo_cliente").prop('disabled', true);

    // Mostrar/actualizar estado del cliente si existe el contenedor
    if ($('#customer_status_badge').length) {
      var customerStatus = cst.status !== undefined ? cst.status : 1;
      updateCustomerStatusDisplay(cst.id, customerStatus);
    }

    // Validar límite de crédito al seleccionar cliente válido
    validateCreditLimit();
    // Limpiar mensajes de error al seleccionar cliente válido
    hideErrorMessage();

    console.log('Cliente cargado en loans:', cst.dni);
  };

  // Función para actualizar la visualización del estado del cliente
  function updateCustomerStatusDisplay(customerId, status) {
    var statusClass = status == 1 ? 'success' : 'danger';
    var statusText = status == 1 ? 'Activo' : 'Inactivo';
    var statusIcon = status == 1 ? 'check-circle' : 'times-circle';
    var buttonClass = status == 1 ? 'danger' : 'success';
    var buttonIcon = status == 1 ? 'ban' : 'check';
    var buttonText = status == 1 ? 'Desactivar' : 'Activar';
    var messageClass = status == 1 ? 'success' : 'danger';
    var messageIcon = status == 1 ? 'check-circle' : 'exclamation-triangle';
    var messageText = status == 1 ? 'Cliente activo. Puede realizar préstamos.' : 'Cliente desactivado. No puede realizar préstamos.';

    $('#customer_status_badge').removeClass('badge-success badge-danger').addClass('badge-' + statusClass);
    $('#customer_status_badge').html('<i class="fas fa-' + statusIcon + ' mr-1"></i>' + statusText);
    
    if ($('#toggle_customer_status').length) {
      $('#toggle_customer_status').removeClass('btn-outline-success btn-outline-danger').addClass('btn-outline-' + buttonClass);
      $('#toggle_customer_status').attr('data-current-status', status);
      $('#toggle_customer_status').html('<i class="fas fa-' + buttonIcon + ' mr-1"></i>' + buttonText);
    }

    // Actualizar mensaje de estado
    var statusMessage = $('#customer_status_badge').closest('.col-12').find('.form-text');
    if (statusMessage.length) {
      statusMessage.html('<span class="text-' + messageClass + '"><i class="fas fa-' + messageIcon + ' mr-1"></i>' + messageText + '</span>');
    }
  }

  // Manejar toggle de estado del cliente
  $(document).on('click', '#toggle_customer_status', function() {
    var customerId = $(this).data('customer-id');
    var currentStatus = $(this).data('current-status');
    
    if (!customerId) {
      alert('Error: No se pudo identificar el cliente');
      return;
    }

    var action = currentStatus == 1 ? 'desactivar' : 'activar';
    if (!confirm('¿Está seguro de ' + action + ' este cliente?')) {
      return;
    }

    var $btn = $(this);
    var originalHtml = $btn.html();
    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Procesando...');

    $.ajax({
      url: base_url + 'admin/customers/ajax_toggle_customer_status',
      type: 'POST',
      data: {
        customer_id: customerId
      },
      success: function(response) {
        if (typeof response === 'string') {
          response = JSON.parse(response);
        }
        
        if (response.success) {
          var newStatus = response.status;
          updateCustomerStatusDisplay(customerId, newStatus);
          
          // Mostrar mensaje de éxito
          alert(response.message);
        } else {
          alert('Error: ' + (response.error || 'No se pudo cambiar el estado del cliente'));
        }
      },
      error: function() {
        alert('Error de conexión. Intente nuevamente.');
      },
      complete: function() {
        $btn.prop('disabled', false).html(originalHtml);
      }
    });
  });

  // ================================
  // FORMATEO EN TIEMPO REAL DE MONEDA
  // ================================
  
  // Ya no auto-formateamos en blur para no agregar ceros ni modificar la entrada del usuario
  $('.currency-input').on('blur', function() {
    // Solo limpiamos resultados para forzar recálculo manual
    clearPreviousResults();
  });

  // Validar formato mientras el usuario escribe
  $('.currency-input').on('input', function() {
    var value = $(this).val();
    var isValid = validateColombianCurrency(value);
    
    if (value && !isValid) {
      $(this).addClass('is-invalid');
    } else {
      $(this).removeClass('is-invalid');
    }
    
    // Limpiar resultados siempre
    clearPreviousResults();
  });

  // Función para verificar si los campos están listos para cálculo (sin mostrar errores)
  function isCalculationReady() {
    var contador = 0;

    if ($("#cr_amount").val()=="") contador++;
    if ($("#in_amount").val()=="") contador++;
    if ($("#fee").val() == "" || parseInt($("#fee").val()) <= 0) contador++;
    if ($("#tasa_tipo").val()=="" || $("#tasa_tipo").val()==null) contador++;
    if ($('select[name="payment_m"]').val() == "") contador++;
    if ($('select[name="coin_id"]').val() == "") contador++;
    if ($('select[name="amortization_type"]').val() == "") contador++;
    if ($("#date").val()=="") contador++;
    if ($("#payment_start_date").val()=="") contador++;

    return contador == 0;
  }

  // Función para manejar selección automática de forma de pago para amortización mixta
  function handleAmortizationTypeChange() {
    var amortizationType = $('select[name="amortization_type"]').val();
    var paymentMethodSelect = $('select[name="payment_m"]');

    if (amortizationType === 'mixta') {
      // Forzar selección automática de quincenal para amortización mixta
      paymentMethodSelect.val('quincenal');
      console.log('Amortización mixta seleccionada - forma de pago forzada a quincenal');

      // Mostrar mensaje informativo
      showErrorMessage('Para amortización mixta, la forma de pago se ha configurado automáticamente como quincenal (15 días).', '#loanErrorBox');
      setTimeout(function() {
        hideErrorMessage('#loanErrorBox');
      }, 3000);
    }
  }

  // Evento específico para cambios en tipo de amortización
  $('select[name="amortization_type"]').on('change', function() {
    handleAmortizationTypeChange();
    // Limpiar resultados y recalcular
    clearPreviousResults();
    // Auto-calcular si todos los campos están llenos
    clearTimeout(calculationTimeout);
    calculationTimeout = setTimeout(function() {
      if (isCalculationReady()) {
        calculateAmortization();
      }
    }, 500);
  });

  // Recalcular automáticamente cuando cambien campos
  $('#cr_amount, #in_amount, #fee, select[name="tasa_tipo"], select[name="payment_m"], select[name="coin_id"], #date, #payment_start_date').on('change input', function() {
    console.log('Campo cambiado, limpiando resultados previos');
    clearPreviousResults();
    // Validar límite de crédito si cambió el monto
    if ($(this).is('#cr_amount')) {
      validateCreditLimit();
      // Limpiar mensajes de error al cambiar el monto
      hideErrorMessage();
    }
    // Auto-calcular si todos los campos están llenos
    clearTimeout(calculationTimeout);
    calculationTimeout = setTimeout(function() {
      console.log('Timeout ejecutado, verificando si campos están listos');
      if (isCalculationReady()) {
        console.log('Campos listos, iniciando cálculo');
        calculateAmortization();
      } else {
        console.log('Campos no listos, esperando más input');
      }
    }, 500);
  });

  // ================================
  // VALIDACIÓN DEL CÁLCULO
  // ================================
  $('#calcular').on('click', function(){ 
    // Validar todos los campos requeridos
    var isValid = validateCalculationFields();
    
    if (isValid) {
      // Calcular usando la librería de amortización
      calculateAmortization();
    }
  });

  // Función para validar campos de cálculo
  function validateCalculationFields() {
    var contador = 0;

    if ($("#cr_amount").val()=="") {
      contador=1
      showErrorMessage("Ingresar monto")
      $("#cr_amount").focus()
      return false;
    }
    // Validar que el monto sea entero mayor a 0
    var monto = parseCOPInput($("#cr_amount").val());
    if (monto <= 0 || !Number.isInteger(monto)) {
      showErrorMessage("El monto del préstamo debe ser un número entero mayor a 0")
      $("#cr_amount").focus()
      return false;
    }
    if ($("#in_amount").val()=="") {
      contador=1
      showErrorMessage("Ingresar interés")
      $("#in_amount").focus()
      return false;
    }
    var periodsValue = $("#fee").val();
    if (periodsValue == "" || parseInt(periodsValue) <= 0) {
      contador = 1;
      showErrorMessage("Ingresar plazo en meses mayor a 0");
      $("#fee").focus();
      return false;
    }
    if ($("#date").val()=="") {
      contador=1
      showErrorMessage("Ingresar fecha emisión")
      return false;
    }
    if ($("#payment_start_date").val()=="") {
      contador=1
      showErrorMessage("Ingresar fecha inicio cobros")
      return false;
    }
    // Validación para tipo de tasa
    if ($("#tasa_tipo").val()=="" || $("#tasa_tipo").val()==null) {
      contador=1
      showErrorMessage("Seleccionar tipo de tasa")
      $("#tasa_tipo").focus()
      return false;
    }

    // Validación para forma de pago
    if ($('select[name="payment_m"]').val() == "") {
      showErrorMessage("Seleccionar forma de pago")
      $('select[name="payment_m"]').focus()
      return false;
    }

    // Validación para tipo de moneda
    if ($('select[name="coin_id"]').val() == "") {
      showErrorMessage("Seleccionar tipo de moneda")
      $('select[name="coin_id"]').focus()
      return false;
    }

    // Validación para tipo de amortización
    var amortizationType = $('select[name="amortization_type"]').val();
    if (amortizationType == "") {
      showErrorMessage("Seleccionar tipo de amortización")
      $('select[name="amortization_type"]').focus()
      return false;
    }

    // Validación específica para amortización mixta: debe ser quincenal
    if (amortizationType === 'mixta') {
      var paymentMethod = $('select[name="payment_m"]').val();
      if (paymentMethod !== 'quincenal') {
        showErrorMessage("Para amortización mixta, solo se permite frecuencia de pago quincenal (15 días)")
        $('select[name="payment_m"]').focus()
        return false;
      }
    }

    return contador == 0;
  }

  // Función para formatear número a formato colombiano
  function formatColombianCurrency(value) {
    if (isNaN(value) || value === null || value === '') {
      return '';
    }
    return Math.round(parseFloat(value)).toLocaleString('es-CO', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
  }

  // Función auxiliar para formatear en COP (robusta)
  function formatCOP(value) {
    console.log('formatCOP llamado con valor:', value);
    if (value === null || value === undefined || value === '') {
      console.warn('formatCOP: valor vacío o nulo, retornando cadena vacía');
      return '';
    }
    const num = parseFloat(value);
    if (isNaN(num)) {
      console.error('formatCOP: valor no numérico:', value);
      return '0';
    }
    try {
      return '$ ' + num.toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' COP';
    } catch (error) {
      console.error('formatCOP: error al formatear:', error);
      return '$ 0,00 COP';
    }
  }

  // Función auxiliar para mostrar mensajes de error
  function showErrorMessage(message, containerId = '#loanErrorBox') {
    console.error('showErrorMessage:', message);
    const container = $(containerId);
    if (container.length) {
      container.removeClass('d-none').html(message);
    } else {
      // No mostrar alert que bloquee la edición - solo loggear
      console.warn('showErrorMessage: No container found for message, logged only:', message);
    }
  }

  // Función auxiliar para ocultar mensajes de error
  function hideErrorMessage(containerId = '#loanErrorBox') {
    const container = $(containerId);
    if (container.length) {
      container.addClass('d-none').empty();
    }
  }

  // Función para validar límite de crédito vía AJAX
  function validateCreditLimit() {
    if (isCheckingLimit) {
      console.log('validateCreditLimit: validación ya en progreso, abortando');
      return Promise.resolve();
    }

    const customerId = $('#customer').val();
    const amountInput = $('#cr_amount').val();

    if (!customerId || !amountInput) {
      console.log('validateCreditLimit: faltan datos - customerId:', customerId, 'amount:', amountInput);
      return Promise.resolve(); // No validar si faltan datos
    }

    // Parsear monto
    const amount = parseCOPInput(amountInput);
    if (amount === false || amount <= 0) {
      console.log('validateCreditLimit: monto inválido');
      return Promise.resolve();
    }

    // Abortar AJAX anterior si existe
    if (currentAjaxLimit) {
      console.log('Abortando AJAX anterior de límite');
      currentAjaxLimit.abort();
    }

    console.log('Validando límite de crédito para cliente:', customerId, 'monto:', amount);
    isCheckingLimit = true;

    return new Promise((resolve, reject) => {
      currentAjaxLimit = $.ajax({
        url: base_url + 'admin/loans/ajax_get_credit_limit',
        type: 'POST',
        data: {
          customer_id: customerId
        },
        dataType: 'json',
        timeout: 10000, // 10 segundos
        success: function(response) {
          try {
            console.log('Respuesta de límite de crédito:', response);
            if (response && typeof response.limit !== 'undefined') {
              const limit = parseFloat(response.limit);
              if (amount > limit && limit > 0) {
                const message = `El monto solicitado (${formatNumber(amount)}) excede el límite de crédito permitido (${formatNumber(limit)}).`;
                showErrorMessage(message);
                isValidLimit = false;
                $('#register_loan').prop('disabled', true);
              } else {
                // Limpiar mensaje de error si existe
                hideErrorMessage();
                isValidLimit = true;
                // No habilitar automáticamente, dejar que otros validadores lo hagan
              }
            } else {
              throw new Error('Respuesta inválida del servidor');
            }
            resolve();
          } catch (error) {
            console.error('Error procesando respuesta de límite:', error);
            reject(error);
          }
        },
        error: function(xhr, status, error) {
          console.error('Error en AJAX de límite de crédito:', xhr.responseText, 'status:', status, 'error:', error);
          if (status === 'timeout') {
            showErrorMessage('La validación del límite de crédito tardó demasiado tiempo. Intente nuevamente.');
            isValidLimit = false;
            $('#register_loan').prop('disabled', true);
          } else if (status === 'abort') {
            console.log('AJAX de límite abortado');
            // No setear isValidLimit = false para abort
          } else if (xhr.status === 0) {
            // Canal cerrado o conexión perdida
            showErrorMessage('Error de conexión: canal cerrado. Verifique su conexión a internet.');
            isValidLimit = false;
            $('#register_loan').prop('disabled', true);
          } else {
            showErrorMessage('Error al validar límite de crédito: ' + error);
            isValidLimit = false;
            $('#register_loan').prop('disabled', true);
          }
          reject(new Error('AJAX error: ' + error));
        },
        complete: function() {
          currentAjaxLimit = null;
          isCheckingLimit = false;
        }
      });
    }).catch(error => {
      console.error('Error en validación de límite:', error);
      // No relanzar, ya manejado
    });
  }

  // Función para formatear moneda sin ,00 si es entero
  function formatNumber(value) {
    if (isNaN(value) || value === null || value === '') {
      return '0';
    }
    let num = Math.round(parseFloat(value) * 100) / 100;
    let formatted = num.toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    if (formatted.endsWith(',00')) {
      formatted = formatted.slice(0, -3);
    }
    return formatted;
  }

  // Función auxiliar para mostrar resumen
  function showSummary(data) {
    console.log('showSummary llamado con:', data);
    if (!data || typeof data !== 'object') {
      console.error('showSummary: data inválida');
      showErrorMessage('Resumen de cálculo no disponible');
      return;
    }

    const cuota = data.cuota || 0;
    const totalInteres = data.totalInteres || 0;
    const totalCuotas = data.totalCuotas || 0;

    console.log('Valores extraídos:', { cuota, totalInteres, totalCuotas });

    $('#valor_cuota').val(formatNumber(cuota));
    $('#valor_interes').val(formatNumber(totalInteres));
    $('#monto_total').val(formatNumber(totalCuotas));

    console.log('Resumen mostrado exitosamente');
  }

  // Función para convertir valor de moneda a decimal admitiendo:
  // - Formato colombiano (1.234.567,89)
  // - Número plano (1000 o 1000.50)
  function parseColombianCurrency(value) {
    if (!value || value === '') return 0;
    
    // Remover símbolos y espacios
    value = value.toString().replace(/\$/g, '').replace(/\s/g, '');
    
    // Número plano con punto decimal opcional (ej: 1000 o 1000.50)
    if (/^\d+(\.\d{1,2})?$/.test(value)) {
      return parseFloat(value);
    }
    
    // Validar formato colombiano
    if (!/^[\d]{1,3}(\.[\d]{3})*(,[\d]{1,2})?$/.test(value)) {
      return false;
    }
    
    // Convertir a decimal
    value = value.replace(/\./g, ''); // Remover separadores de miles
    value = value.replace(',', '.'); // Convertir coma decimal a punto
    
    return parseFloat(value);
  }

  // Función para validar valor de moneda con la regex provista
  function validateColombianCurrency(value) {
    if (!value || value === '') return true; // Vacío es válido (se maneja con required)

    value = value.toString().replace(/\$/g, '').replace(/\s/g, '');
    return moneyRegex.test(value);
  }

  // Función para convertir valor de moneda a decimal admitiendo:
  // - Formato colombiano (1.234.567,89)
  // - Número plano (1000 o 1000.50)
  function parseCOPInput(value) {
    if (!value || value === '') return 0;
    
    // Remover símbolos y espacios
    value = value.toString().replace(/\$/g, '').replace(/\s/g, '');
    
    // Número plano con punto decimal opcional (ej: 1000 o 1000.50)
    if (/^\d+(\.\d{1,2})?$/.test(value)) {
      return parseFloat(value);
    }
    
    // Validar formato colombiano
    if (!/^[\d]{1,3}(\.[\d]{3})*(,[\d]{1,2})?$/.test(value)) {
      return false;
    }
    
    // Convertir a decimal
    value = value.replace(/\./g, ''); // Remover separadores de miles
    value = value.replace(',', '.'); // Convertir coma decimal a punto
    
    return parseFloat(value);
  }

  // Ya no re-escribimos el valor del input automáticamente
  function formatNumberInput(input) {
    return; // Intencionalmente sin acción
  }

  // ================================
  // FORMATEO EN TIEMPO REAL PARA #cr_amount (SOLO ENTEROS, SIN DECIMALES)
  // ================================
  (function attachIntegerMoneyFormatter() {
    var input = $('#cr_amount');
    if (!input.length) return;

    var toPlainNumber = function(v) {
      if (v == null) return '';
      v = String(v).replace(/\./g, '').replace(/\s/g, '').replace(/\$/g, '');
      // Remover cualquier coma o punto decimal, solo dígitos
      v = v.replace(/[^0-9]/g, '');
      return v;
    };

    var formatLatamInteger = function(numStr) {
      if (numStr === '' || isNaN(Number(numStr))) return '';
      // Insertar separador de miles "." sin decimales
      return Number(numStr).toLocaleString('es-CO');
    };

    input.on('input keyup', function() {
      var before = $(this).val();
      // Convertir a número plano y volver a formatear
      var plain = toPlainNumber(before);
      var formatted = formatLatamInteger(plain);
      $(this).val(formatted);
      // Siempre válido si es numérico
      $(this).removeClass('is-invalid');
    });

    // Normalizar antes de enviar: sin puntos para backend
    $('#loan_form').on('submit', function() {
      var raw = input.val() || '';
      var normalized = raw.replace(/\./g, '');
      // Si no numérico, vaciar para que el backend falle con required
      if (normalized !== '' && !isNaN(Number(normalized))) {
        input.val(normalized);
      }
    });
  })();

  // Inicializar tablas de reportes si DataTables está disponible
  if ($.fn && $.fn.DataTable) {
    ['#tbl_payments_by_customer', '#tbl_top_collectors', '#tbl_longest_streak', '#tbl_commissions'].forEach(function(sel){
      if ($(sel).length && !$.fn.DataTable.isDataTable(sel)) {
        try { $(sel).DataTable({ paging: true, searching: true, info: true, order: [] }); } catch(e) {}
      }
    });
  }

  // ================================
  // Pago flexible global en payments/edit
  // ================================
  // Habilitar Registrar Pago solo con cuotas seleccionadas
  function updateRegisterButtonState() {
    var anyQuota = $('input[name="quota_id[]"]:enabled:checked').length > 0;
    var tipoPago = $('#tipo_pago').val();
    var customAmount = parseFloat($('#custom_amount').val()) || 0;

    // Calcular total según tipo de pago
    var total = 0;
    var totalInteres = 0;
    var totalCapital = 0;
    var totalSaldo = 0;

    if (tipoPago === 'custom') {
      // Para pagos personalizados, mostrar el monto personalizado
      total = customAmount > 0 ? customAmount : 0;
    } else if (tipoPago === 'total' || tipoPago === 'total_condonacion') {
      // Para pago total o total_condonacion, calcular el saldo + monto cuota de la primera cuota seleccionada
      $('input:checkbox:enabled:checked').first().each(function(){
        var saldoAmount = parseFloat($(this).attr('data-saldo')) || 0;
        var feeAmount = parseFloat($(this).attr('data-fee')) || 0;
        total += saldoAmount + feeAmount; // Sumar saldo + monto cuota
        totalSaldo += saldoAmount;
      });
    } else if (tipoPago && anyQuota) {
      // Calcular según tipo de pago seleccionado
      $('input:checkbox:enabled:checked').each(function(){
        var feeAmount = parseFloat($(this).attr('data-fee')) || 0;
        var interestAmount = parseFloat($(this).attr('data-interes')) || 0;
        var capitalAmount = parseFloat($(this).attr('data-capital')) || 0;
        var saldoAmount = parseFloat($(this).attr('data-saldo')) || 0;

        switch(tipoPago) {
          case 'full':
            total += feeAmount;
            totalInteres += interestAmount;
            totalCapital += capitalAmount;
            totalSaldo += saldoAmount;
            break;
          case 'interest':
            total += interestAmount;
            totalInteres += interestAmount;
            break;
          case 'capital':
            total += capitalAmount;
            totalCapital += capitalAmount;
            totalSaldo += saldoAmount;
            break;
          case 'both':
            total += interestAmount + capitalAmount;
            totalInteres += interestAmount;
            totalCapital += capitalAmount;
            totalSaldo += saldoAmount;
            break;
        }
      });
    }

    // Actualizar campos de totales
    $('#total_amount').val(total ? total.toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '');
    $('#total_cuota').text(total ? total.toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '0,00');
    $('#total_interes').text(totalInteres ? totalInteres.toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '0,00');
    $('#total_capital').text(totalCapital ? totalCapital.toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '0,00');
    $('#total_saldo').text(totalSaldo ? totalSaldo.toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '0,00');

    // Habilitar/deshabilitar botón de registro
    var shouldEnable = false;
    if (tipoPago === 'custom') {
      shouldEnable = anyQuota && customAmount > 0;
    } else if (tipoPago === 'total') {
      shouldEnable = anyQuota && $('input[name="quota_id[]"]:enabled:checked').length === 1;
    } else {
      shouldEnable = anyQuota && tipoPago;
    }

    $('#register_loan').prop('disabled', !shouldEnable);

    console.log('Totales calculados:', {total, totalInteres, totalCapital, totalSaldo, tipoPago, shouldEnable});
  }

  // Mostrar/ocultar campo de monto personalizado
  $('#tipo_pago').on('change', function() {
    var tipoPago = $(this).val();
    console.log('DEBUG: Tipo de pago cambiado a:', tipoPago);

    // Validar tipo_pago seleccionado - Validación mejorada
    if (!tipoPago) {
      showErrorMessage('Debe seleccionar un tipo de pago válido.', '#loanErrorBox');
      $('#register_loan').prop('disabled', true);
      $('#custom_amount_group').hide();
      $('#custom_amount').val('');
      updateRegisterButtonState();
      return;
    }

    // Limpiar mensajes de error previos
    hideErrorMessage('#loanErrorBox');

    if (tipoPago === 'custom') {
      $('#custom_amount_group').show();
      $('#custom_payment_options').show();
      $('#custom_amount').focus();
      // Limpiar total_amount cuando se selecciona custom
      $('#total_amount').val('');
      console.log('DEBUG: Mostrando campo de monto personalizado con prioridad automática');
    } else if (tipoPago === 'total' || tipoPago === 'total_condonacion') {
      // Para pago total, ocultar campos personalizados y calcular monto de cuota específica
      $('#custom_amount_group').hide();
      $('#custom_payment_options').hide();
      $('#custom_amount').val('');
      // Calcular automáticamente el monto de la cuota específica seleccionada
      calcularMontoTotalPago();
      console.log('DEBUG: Ocultando campos personalizados y calculando monto total');
    } else {
      $('#custom_amount_group').hide();
      $('#custom_payment_options').hide();
      $('#custom_amount').val('');
      // Recalcular total cuando se cambia de custom a otro tipo
      updateRegisterButtonState();
      console.log('DEBUG: Ocultando campo personalizado y recalculando totales');
    }
  });

  // Evento para actualizar totales cuando cambia el monto personalizado
  $('#custom_amount').on('input', function() {
    var customAmount = parseFloat($(this).val()) || 0;
    var tipoPago = $('#tipo_pago').val();
    var customPaymentType = $('input[name="custom_payment_type"]:checked').val();

    // Validar formato numérico del monto personalizado
    var inputValue = $(this).val().trim();
    if (inputValue !== '' && !validateCustomAmount(inputValue)) {
      $(this).addClass('is-invalid');
      showErrorMessage('El monto personalizado debe ser un número válido mayor a 0 (ej: 1000,50 o 1000).', '#loanErrorBox');
      $('#register_loan').prop('disabled', true);
      return;
    } else {
      $(this).removeClass('is-invalid');
      hideErrorMessage('#loanErrorBox');
    }

    // Validar límites razonables
    if (customAmount > 0) {
      var maxReasonableAmount = 10000000; // 10 millones como límite superior razonable
      if (customAmount > maxReasonableAmount) {
        showErrorMessage('El monto personalizado no puede exceder $10.000.000.', '#loanErrorBox');
        $('#register_loan').prop('disabled', true);
        return;
      }

      // Validar que no sea menor a un centavo
      if (customAmount < 0.01) {
        showErrorMessage('El monto personalizado debe ser al menos $0,01.', '#loanErrorBox');
        $('#register_loan').prop('disabled', true);
        return;
      }
    }

    if (tipoPago === 'custom' && customAmount > 0) {
      // Para pagos personalizados, aplicar el monto directamente
      $('#total_amount').val(customAmount.toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
      $('#total_cuota').text(customAmount.toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
      $('#total_interes').text('0,00');
      $('#total_capital').text(customAmount.toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
      $('#total_saldo').text('0,00');
      $('#register_loan').prop('disabled', false);
      console.log('Monto personalizado aplicado:', customAmount);
    } else {
      // Si no es custom o faltan datos, recalcular normalmente
      updateRegisterButtonState();
    }
  });

  // Evento para actualizar totales cuando cambia el tipo de pago personalizado (REMOVED)

  // Listeners para cambios de cuotas y tipo de pago
  $(document).on('change', 'input[name="quota_id[]"]', function() {
    var tipoPago = $('#tipo_pago').val();
    if (tipoPago === 'custom') {
      var customAmount = parseFloat($('#custom_amount').val()) || 0;
      var customPaymentType = $('input[name="custom_payment_type"]:checked').val();
      if (customAmount > 0 && customPaymentType) {
        updateCustomPaymentTotals(customAmount, customPaymentType);
      } else {
        updateRegisterButtonState();
      }
    } else if (tipoPago === 'total' || tipoPago === 'total_condonacion') {
      // Para pago total, recalcular cuando cambia la selección de cuotas
      calcularMontoTotalPago();
    } else {
      updateRegisterButtonState();
    }
  });
  $('#tipo_pago').on('change', updateRegisterButtonState);
  $('#custom_amount').on('input', function() {
    var tipoPago = $('#tipo_pago').val();
    if (tipoPago === 'custom') {
      var customAmount = parseFloat($(this).val()) || 0;
      if (customAmount > 0) {
        $('#total_amount').val(customAmount.toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
        $('#total_cuota').text(customAmount.toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
        $('#total_interes').text('0,00');
        $('#total_capital').text(customAmount.toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
        $('#total_saldo').text('0,00');
        $('#register_loan').prop('disabled', false);
      } else {
        updateRegisterButtonState();
      }
    } else {
      updateRegisterButtonState();
    }
  });

  // Para pago total, recalcular cuando cambia el monto personalizado (aunque no se use)
  $('#custom_amount').on('input', function() {
    var tipoPago = $('#tipo_pago').val();
    if (tipoPago === 'total' || tipoPago === 'total_condonacion') {
      calcularMontoTotalPago();
    }
  });

  // Para pago total, recalcular cuando cambia la selección de cuotas
  $(document).on('change', 'input[name="quota_id[]"]', function() {
    var tipoPago = $('#tipo_pago').val();
    if (tipoPago === 'total' || tipoPago === 'total_condonacion') {
      calcularMontoTotalPago();
    }
  });

  // Función para validar selección de cuotas para pago total
  function validarPagoTotal() {
    var tipoPago = $('#tipo_pago').val();
    var selectedQuotas = $('input[name="quota_id[]"]:enabled:checked').length;

    if (tipoPago === 'total' || tipoPago === 'total_condonacion') {
      if (selectedQuotas === 0) {
        showErrorMessage('Para pago total, debe seleccionar exactamente una cuota.');
        $('#register_loan').prop('disabled', true);
        return false;
      } else if (selectedQuotas > 1) {
        showErrorMessage('Para pago total, seleccione solo una cuota. Se cancelarán automáticamente las demás.');
        // Permitir continuar pero mostrar advertencia
        $('#register_loan').prop('disabled', false);
        return true;
      }
    }

    return true;
  }

  // Validar selección de cuotas para pago total
  $('#tipo_pago').on('change', function() {
    validarPagoTotal();
  });

  $(document).on('change', 'input[name="quota_id[]"]', function() {
    var tipoPago = $('#tipo_pago').val();
    if (tipoPago === 'total' || tipoPago === 'total_condonacion') {
      validarPagoTotal();
    }
  });

  // Función para calcular el monto de la cuota seleccionada para pago total
  function calcularMontoTotalPago() {
    console.log('Calculando monto de la cuota seleccionada para pago total');

    // Obtener cuotas seleccionadas
    var selectedQuotas = $('input[name="quota_id[]"]:enabled:checked');
    if (selectedQuotas.length === 0) {
      console.log('No hay cuotas seleccionadas para calcular pago total');
      $('#total_amount').val('');
      $('#total_cuota').text('0,00');
      $('#total_interes').text('0,00');
      $('#total_capital').text('0,00');
      $('#total_saldo').text('0,00');
      $('#register_loan').prop('disabled', true);
      return;
    }

    // Para pago total, usar solo la primera cuota seleccionada
    var selectedQuota = selectedQuotas.first();
    var dataFee = parseFloat(selectedQuota.attr('data-fee')) || 0;
    var dataInteres = parseFloat(selectedQuota.attr('data-interes')) || 0;
    var dataCapital = parseFloat(selectedQuota.attr('data-capital')) || 0;
    var dataSaldo = parseFloat(selectedQuota.attr('data-saldo')) || 0;

    console.log('Cuota seleccionada para pago total:', {
      id: selectedQuota.val(),
      fee: dataFee,
      interes: dataInteres,
      capital: dataCapital,
      saldo: dataSaldo
    });

    // Para pago total_condonacion, usar el saldo + monto cuota de la cuota seleccionada
    var tipoPago = $('#tipo_pago').val();
    var totalAmount = dataSaldo + dataFee; // Sumar saldo + monto cuota

    console.log('Pago total_condonacion - Usando saldo + cuota de cuota seleccionada:', dataSaldo, '+', dataFee, '=', totalAmount);

    // Actualizar la interfaz con el monto calculado
    $('#total_amount').val(totalAmount.toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    $('#total_cuota').text(totalAmount.toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    $('#total_interes').text('0,00'); // En pago total, todo es capital
    $('#total_capital').text(totalAmount.toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    $('#total_saldo').text('0,00'); // Después del pago total, el saldo será 0

    // Deseleccionar otras cuotas si hay más de una seleccionada
    if (selectedQuotas.length > 1) {
      selectedQuotas.slice(1).prop('checked', false);
      $('#select_all').prop('checked', false);
      console.log('Deseleccionadas cuotas adicionales para pago total');
    }

    // Habilitar botón de registro
    $('#register_loan').prop('disabled', false);

    console.log('Pago total configurado para cuota específica - tipo:', tipoPago, '- monto:', totalAmount);
  }

  // Función para actualizar totales de pago personalizado
  function updateCustomPaymentTotals(customAmount, customPaymentType) {
    console.log('PAYMENTS_DIAGNOSIS: updateCustomPaymentTotals llamado con:', customAmount, 'tipo:', customPaymentType);
    var remainingAmount = customAmount;
    var totalCuota = 0;
    var totalInteres = 0;
    var totalCapital = 0;
    var totalSaldo = 0;

    // Obtener cuotas seleccionadas
    var selectedQuotas = $('input[name="quota_id[]"]:enabled:checked');
    console.log('PAYMENTS_DIAGNOSIS: Cuotas seleccionadas para cálculo:', selectedQuotas.length);

    selectedQuotas.each(function() {
      var feeAmount = parseFloat($(this).attr('data-fee')) || 0;
      var interestAmount = parseFloat($(this).attr('data-interes')) || 0;
      var capitalAmount = parseFloat($(this).attr('data-capital')) || 0;
      var saldoAmount = parseFloat($(this).attr('data-saldo')) || 0;

      console.log('PAYMENTS_DIAGNOSIS: Procesando cuota:', $(this).val(), 'fee:', feeAmount, 'interes:', interestAmount, 'capital:', capitalAmount, 'saldo:', saldoAmount);

      // Para pagos personalizados, procesar todas las cuotas seleccionadas (incluyendo las ya pagadas)
      // Solo verificar que haya monto restante por aplicar
      if (remainingAmount > 0) {
        var amountToPay = 0;

        // Aplicar pago según el tipo seleccionado
        switch(customPaymentType) {
          case 'cuota':
            // Aplicar a cuota completa (intereses + capital) - PRIORIDAD INTERÉS
            var interesToPay = Math.min(remainingAmount, interestAmount);
            remainingAmount -= interesToPay;
            var capitalToPay = Math.min(remainingAmount, capitalAmount);
            remainingAmount -= capitalToPay;
            amountToPay = interesToPay + capitalToPay;
            totalInteres += interesToPay;
            totalCapital += capitalToPay;
            break;

          case 'interes':
            // Aplicar solo a intereses
            amountToPay = Math.min(remainingAmount, interestAmount);
            remainingAmount -= amountToPay;
            totalInteres += amountToPay;
            break;

          case 'capital':
            // Aplicar solo a capital
            amountToPay = Math.min(remainingAmount, capitalAmount);
            remainingAmount -= amountToPay;
            totalCapital += amountToPay;
            break;

          case 'liquidation':
            // Liquidación anticipada: pagar todo el saldo pendiente
            amountToPay = Math.min(remainingAmount, saldoAmount);
            remainingAmount -= amountToPay;
            totalCapital += amountToPay; // En liquidación, todo es capital
            break;
        }

        totalCuota += amountToPay;
        totalSaldo += (saldoAmount - amountToPay);

        console.log('PAYMENTS_DIAGNOSIS: Pago aplicado a cuota:', $(this).val(), '- amountToPay:', amountToPay, 'tipo:', customPaymentType, 'remainingAmount:', remainingAmount);
      } else {
        console.log('PAYMENTS_DIAGNOSIS: Cuota sin saldo pendiente o sin monto restante, omitiendo:', $(this).val());
      }
    });

    console.log('PAYMENTS_DIAGNOSIS: Totales finales calculados:', {totalCuota, totalInteres, totalCapital, totalSaldo});
    console.log('PAYMENTS_DIAGNOSIS: Monto personalizado original:', customAmount, 'remainingAmount final:', remainingAmount);

    // Actualizar la interfaz
    $('#total_amount').val(customAmount.toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    $('#total_cuota').text(totalCuota.toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    $('#total_interes').text(totalInteres.toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    $('#total_capital').text(totalCapital.toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    $('#total_saldo').text(totalSaldo.toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2}));

    console.log('PAYMENTS_DIAGNOSIS: Interfaz actualizada - total_amount:', customAmount, 'total_cuota:', totalCuota, 'total_interes:', totalInteres, 'total_capital:', totalCapital, 'total_saldo:', totalSaldo);

    // Habilitar botón si hay monto, tipo y cuotas seleccionadas
    var anyQuota = $('input[name="quota_id[]"]:enabled:checked').length > 0;
    $('#register_loan').prop('disabled', !(anyQuota && customAmount > 0 && customPaymentType));

    console.log('PAYMENTS_DIAGNOSIS: updateCustomPaymentTotals completado - anyQuota:', anyQuota, 'customAmount:', customAmount, 'customPaymentType:', customPaymentType, 'botón habilitado:', !(anyQuota && customAmount > 0 && customPaymentType));
  }

  // Función para calcular amortización usando AJAX
  function calculateAmortization() {
    if (isCalculating) {
      console.log('Cálculo ya en progreso, abortando nuevo cálculo');
      return;
    }
    isCalculating = true;
    console.log('Iniciando cálculo de amortización');

    // Abortar AJAX anterior si existe
    if (currentAjax) {
      console.log('Abortando AJAX anterior');
      currentAjax.abort();
    }

    var principal_input = $('#cr_amount').val();
    var interest_input = $('#in_amount').val();
    var periods = parseInt($('#fee').val());
    var payment_frequency = $('select[name="payment_m"]').val();
    var amortization_type = $('select[name="amortization_type"]').val();
    console.log('Valor de amortization_type:', amortization_type);
    console.log("Amortization type en JS: " + amortization_type);
    var start_date = $('input[name="date"]').val();
    var payment_start_date = $('input[name="payment_start_date"]').val();
    var payment_day = $('#payment_day').val();

    // Validar formato colombiano
    if (!validateColombianCurrency(principal_input)) {
      showErrorMessage('Formato de monto inválido. Use formato: 1.000.000,50');
      $('#cr_amount').focus();
      return;
    }

    if (!validateColombianCurrency(interest_input)) {
      showErrorMessage('Formato de tasa de interés inválido. Use formato: 15,50');
      $('#in_amount').focus();
      return;
    }

    // Convertir a decimal
    console.log('Convirtiendo principal:', principal_input, 'a decimal');
    var principal = parseCOPInput(principal_input);
    console.log('Principal convertido:', principal);
    var interest_rate = parseCOPInput(interest_input);
    console.log('Tasa de interés convertida:', interest_rate);

    if (principal === false || principal <= 0) {
      console.log('Error: Principal inválido');
      showErrorMessage('El monto del préstamo debe ser un número mayor a 0');
      $('#cr_amount').focus();
      return;
    }

    if (interest_rate === false || interest_rate < 0) {
      showErrorMessage('La tasa de interés debe ser un número mayor o igual a 0');
      $('#in_amount').focus();
      return;
    }

    if (isNaN(periods) || periods <= 0 || periods > 120) {
      showErrorMessage('El plazo en meses debe estar entre 1 y 120');
      $('#fee').focus();
      return;
    }

    if (!amortization_type) {
      showErrorMessage('Debe seleccionar un tipo de amortización');
      $('#amortization_type').focus();
      return;
    }

    if (!start_date) {
      showErrorMessage('Debe seleccionar una fecha de emisión');
      $('input[name="date"]').focus();
      return;
    }

    if (!payment_start_date) {
      showErrorMessage('Debe seleccionar una fecha de inicio de cobros');
      $('input[name="payment_start_date"]').focus();
      return;
    }

    // Validar que la fecha de inicio de cobros sea igual o posterior a la fecha de emisión
    // Comparar fechas como strings para evitar problemas de zona horaria
    // Extraer solo la fecha de emisión (ignorar hora) para comparación consistente
    var emissionDateOnly = start_date.split('T')[0]; // Obtener solo la parte de fecha (yyyy-mm-dd)
    var paymentStartDateStr = payment_start_date.split('/').reverse().join('-'); // Convertir dd/mm/yyyy a yyyy-mm-dd

    if (paymentStartDateStr < emissionDateOnly) {
      showErrorMessage('La fecha de inicio de cobros no puede ser anterior a la fecha de emisión');
      $('input[name="payment_start_date"]').focus();
      return;
    }

    // Limpiar resultados anteriores
    clearPreviousResults();

    // Mostrar indicador de carga
    $('#calcular').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Calculando...');

    console.log('Enviando datos a ajax_calculate_amortization:', {
      credit_amount: principal,
      interest_amount: interest_rate,
      num_months: periods,
      payment_m: payment_frequency,
      amortization_type: amortization_type,
      date: start_date,
      tasa_tipo: $('select[name="tasa_tipo"]').val() || 'nominal'
    });

    currentAjax = $.ajax({
      url: base_url + 'admin/loans/ajax_calculate_amortization',
      type: 'POST',
      data: {
        credit_amount: principal,
        interest_amount: interest_rate,
        num_months: periods,
        payment_m: payment_frequency,
        amortization_type: amortization_type,
        date: start_date,
        payment_start_date: $('input[name="payment_start_date"]').val(),
        tasa_tipo: $('select[name="tasa_tipo"]').val() || 'TNA'
      },
      dataType: 'json',
      timeout: 10000, // 10 segundos de timeout
      success: function(response) {
        console.log('Respuesta exitosa del AJAX de cálculo');
        console.log('Callback success del AJAX de cálculo de amortización iniciado');
        console.log('Respuesta completa del servidor:', response);

        // Validación robusta de la respuesta
        if (!response || typeof response !== 'object') {
          console.error('Respuesta inválida del servidor:', response);
          showErrorMessage('Respuesta inválida del servidor. Intente nuevamente.');
          return;
        }

        console.log('Response success:', response.success, 'error:', response.error);
        if (response.success === true || response.success === 'true') {
          console.log('Cálculo exitoso, procesando resultados');
        } else {
          console.error('Error en cálculo:', response.error);
        }

        if (response.success === true || response.success === 'true') {
          console.log('Cálculo exitoso, procesando resultados');

          // Mostrar resumen
          if (response.data) {
            console.log('Mostrando resumen:', response.data);
            showSummary(response.data);
          } else {
            console.warn('Data no encontrada en respuesta');
            showSummary({});
          }

          // Habilitar botones
          console.log('Habilitando botones de amortización y registro');
          $('#ver_amortizacion').prop('disabled', false);
          $('#register_loan').prop('disabled', false);

          // Mostrar tabla de amortización
          console.log('Procesando tabla de amortización:', response.data.tabla);
          if (response.data.tabla && Array.isArray(response.data.tabla) && response.data.tabla.length > 0) {
            console.log('Tabla válida, mostrando');
            showAmortizationTable(response.data.tabla);
          } else {
            console.warn('Tabla de amortización inválida o vacía:', response.data.tabla);
            showErrorMessage('No se pudo generar la tabla de amortización. Verifique los datos ingresados.');
          }

          // Guardar datos para recálculo con logs
          console.log('Guardando datos de amortización para recálculo');
          window.currentAmortizationData = {
            credit_amount: principal_input,
            interest_amount: interest_input,
            num_months: periods,
            payment_m: payment_frequency,
            amortization_type: amortization_type,
            date: start_date,
            payment_start_date: $('input[name="payment_start_date"]').val(),
            tasa_tipo: $('select[name="tasa_tipo"]').val() || 'nominal'
          };
          console.log('Datos guardados:', window.currentAmortizationData);

          console.log('Cálculo completado exitosamente');
        } else {
          console.error('Error en cálculo:', response.error);
          const errorMsg = response.error || 'Error desconocido en el cálculo';

          // Si el error es específico de amortización mixta, mostrarlo en el campo y forzar selección automática
          if (errorMsg.includes('Para amortización mixta, solo se permite frecuencia de pago quincenal')) {
            // Forzar selección automática de quincenal
            $('select[name="payment_m"]').val('quincenal');
            console.log('Error detectado - forzando selección automática de quincenal para amortización mixta');

            // Mostrar mensaje informativo en lugar de error
            showErrorMessage('Para amortización mixta, la forma de pago se ha configurado automáticamente como quincenal (15 días).', '#loanErrorBox');
            setTimeout(function() {
              hideErrorMessage('#loanErrorBox');
            }, 3000);

            // Reintentar cálculo automáticamente
            setTimeout(function() {
              calculateAmortization();
            }, 500);
          } else {
            showErrorMessage('Error en el cálculo: ' + errorMsg);
          }
        }

        console.log('Callback success completado');
      },
      error: function(xhr, status, error) {
        console.log('Error en cálculo AJAX:', xhr.responseText, 'status:', status, 'error:', error);
        if (status === 'timeout') {
          showErrorMessage('La operación tardó demasiado tiempo. Por favor, intente nuevamente.');
        } else if (xhr.status === 400) {
          showErrorMessage('Error en los datos enviados. Por favor, verifique los valores ingresados.');
        } else if (xhr.status === 500) {
          showErrorMessage('Error interno del servidor. Por favor, contacte al administrador.');
        } else {
          showErrorMessage('Error al conectar con el servidor: ' + error);
        }
      },
      complete: function() {
        console.log('AJAX completado, restaurando estado');
        // Restaurar botón
        $('#calcular').prop('disabled', false).html('Calcular');
        currentAjax = null;
        isCalculating = false;
      }
    });
  }

  // Función para limpiar resultados anteriores
  function clearPreviousResults() {
    $('#valor_cuota').val('');
    $('#valor_interes').val('');
    $('#monto_total').val('');
    $('#ver_amortizacion').prop('disabled', true);
    $('#register_loan').prop('disabled', true);
    $('#amortization_table_container').empty();
    window.currentAmortizationData = null;
  }

  // Función para mostrar la tabla de amortización (robusta)
  function showAmortizationTable(amortizationTable) {
    console.log('showAmortizationTable llamado con:', amortizationTable);

    // Validación robusta
    if (!amortizationTable || !Array.isArray(amortizationTable)) {
      console.error("Tabla de amortización inválida o no es array:", amortizationTable);
      $('#amortization_table_container').html('<div class="alert alert-danger mt-4">Error: Tabla de amortización no válida.</div>');
      return;
    }

    if (amortizationTable.length === 0) {
      console.warn("Tabla de amortización vacía.");
      $('#amortization_table_container').html('<div class="alert alert-warning mt-4">No se encontraron registros de amortización.</div>');
      return;
    }

    console.log('Generando HTML de tabla para', amortizationTable.length, 'registros');

    var tableHtml = `
      <div class="card mt-4">
        <div class="card-header">
          <h6 class="m-0 font-weight-bold text-primary">Tabla de Amortización</h6>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-bordered table-striped table-sm">
              <thead class="thead-dark text-center">
                <tr>
                  <th>Período</th>
                  <th>Fecha de Pago</th>
                  <th class="text-right">Cuota</th>
                  <th class="text-right">Capital</th>
                  <th class="text-right">Interés</th>
                  <th class="text-right">Saldo Restante</th>
                </tr>
              </thead>
              <tbody>
    `;

    // Procesar cada pago
    var rowsHtml = amortizationTable.map(function(payment, index) {
      console.log('Procesando pago', index + 1, ':', payment);

      if (!payment || typeof payment !== 'object') {
        console.warn('Pago inválido en índice', index, ':', payment);
        return ''; // Devolver cadena vacía para pagos inválidos
      }

      const periodo = payment.periodo || (index + 1);
      const fecha = payment.fecha || '';
      const cuota = payment.cuota || 0;
      const capital = payment.capital || 0;
      const interes = payment.interes || 0;
      const saldo = payment.saldo || 0;

      console.log('Valores extraídos para fila', index + 1, ':', { periodo, fecha, cuota, capital, interes, saldo });

      try {
        return `
          <tr>
            <td class="text-center">${periodo}</td>
            <td class="text-center">${fecha}</td>
            <td class="text-right">${formatNumber(cuota)}</td>
            <td class="text-right">${formatNumber(capital)}</td>
            <td class="text-right">${formatNumber(interes)}</td>
            <td class="text-right">${formatNumber(saldo)}</td>
          </tr>
        `;
      } catch (error) {
        console.error('Error al generar fila para pago', index + 1, ':', error);
        return ''; // Devolver cadena vacía en caso de error
      }
    }).join('');

    tableHtml += rowsHtml;

    tableHtml += `
              </tbody>
            </table>
          </div>
        </div>
      </div>
    `;

    console.log('HTML de tabla generado, insertando en contenedor');
    // Mostrar la tabla
    $('#amortization_table_container').html(tableHtml);
    console.log('Tabla de amortización mostrada exitosamente');
  }

  // Función para mostrar la tabla de amortización en el modal
  function showAmortizationModal(amortizationTable, data) {
    // Actualizar los valores en los elementos específicos del modal
    $('#summary_amount').text('$ ' + formatNumber(data.totalCuotas || 0) + ' COP');
    $('#summary_capital').text('$ ' + formatNumber(data.totalCapital || 0) + ' COP');
    $('#summary_interes').text('$ ' + formatNumber(data.totalInteres || 0) + ' COP');
    $('#summary_cuotas').text(data.nCuotas || 0);
    $('#summary_rate').text((data.tasa_aplicada || 0) + '%');

    // Actualizar totales en el footer de la tabla
    $('#total_cuota').text('$ ' + formatNumber(data.totalCuotas || 0) + ' COP');
    $('#total_capital').text('$ ' + formatNumber(data.totalCapital || 0) + ' COP');
    $('#total_interes').text('$ ' + formatNumber(data.totalInteres || 0) + ' COP');

    // Mostrar tabla
    var tableHtml = '';
    amortizationTable.forEach(function(payment) {
      tableHtml += `
        <tr>
          <td class="text-center">${payment.periodo}</td>
          <td class="text-center">${payment.fecha}</td>
          <td class="text-right">$ ${formatNumber(payment.cuota || 0)} COP</td>
          <td class="text-right">$ ${formatNumber(payment.capital || 0)} COP</td>
          <td class="text-right">$ ${formatNumber(payment.interes || 0)} COP</td>
          <td class="text-right">$ ${formatNumber(payment.saldo || 0)} COP</td>
        </tr>
      `;
    });

    $('#amortization_table_body').html(tableHtml);

    // Habilitar botones de descarga PDF
    $('#download_pdf, #download_pdf_footer').prop('disabled', false);

    $('#amortizationModal').modal('show');
    setTimeout(() => $('#amortizationModal').attr('aria-hidden', 'false'), 100);
  }

  // Evento para el botón "Ver Amortización"
  $('#ver_amortizacion').on('click', function() {
    if (isCalculatingAmortization) {
      console.log('Cálculo de amortización ya en progreso');
      return;
    }
    isCalculatingAmortization = true;
    console.log('Iniciando ver amortización');

    var principal_input = $('#cr_amount').val();
    var interest_input = $('#in_amount').val();
    var periods = parseInt($('#fee').val());
    var payment_frequency = $('select[name="payment_m"]').val();
    var amortization_type = $('select[name="amortization_type"]').val();
    var start_date = $('input[name="date"]').val();

    // Validar formato colombiano
    if (!validateColombianCurrency(principal_input)) {
      showErrorMessage('Formato de monto inválido. Use formato: 1.000.000,50');
      $('#cr_amount').focus();
      return;
    }

    if (!validateColombianCurrency(interest_input)) {
      showErrorMessage('Formato de tasa de interés inválido. Use formato: 15,50');
      $('#in_amount').focus();
      return;
    }

    // Convertir a decimal
    console.log('Convirtiendo principal en ver amortización:', principal_input, 'a decimal');
    var principal = parseCOPInput(principal_input);
    console.log('Principal convertido en ver amortización:', principal);
    var interest_rate = parseCOPInput(interest_input);
    console.log('Tasa de interés convertida en ver amortización:', interest_rate);

    if (principal === false || principal <= 0) {
      showErrorMessage('El monto del préstamo debe ser un número mayor a 0');
      $('#cr_amount').focus();
      return;
    }

    if (interest_rate === false || interest_rate < 0) {
      showErrorMessage('La tasa de interés debe ser un número mayor o igual a 0');
      $('#in_amount').focus();
      return;
    }

    if (isNaN(periods) || periods <= 0 || periods > 120) {
      showErrorMessage('El plazo en meses debe estar entre 1 y 120');
      $('#fee').focus();
      return;
    }

    if (!amortization_type) {
      showErrorMessage('Debe seleccionar un tipo de amortización');
      $('#amortization_type').focus();
      return;
    }

    if (!start_date) {
      showErrorMessage('Debe seleccionar una fecha de emisión');
      $('input[name="date"]').focus();
      return;
    }

    if (!payment_start_date) {
      showErrorMessage('Debe seleccionar una fecha de inicio de cobros');
      $('input[name="payment_start_date"]').focus();
      return;
    }

    // Mostrar indicador de carga
    $('#ver_amortizacion').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Cargando...');

    if (currentAjaxAmortization) {
      console.log('Abortando AJAX anterior de amortización');
      currentAjaxAmortization.abort();
    }
    currentAjaxAmortization = $.ajax({
      url: base_url + 'admin/loans/ajax_calculate_amortization',
      type: 'POST',
      data: {
        credit_amount: principal,
        interest_amount: interest_rate,
        num_months: periods,
        payment_m: payment_frequency,
        amortization_type: amortization_type,
        date: start_date,
        payment_start_date: $('input[name="payment_start_date"]').val(),
        tasa_tipo: $('select[name="tasa_tipo"]').val() || 'TNA'
      },
      dataType: 'json',
      timeout: 10000,
      success: function(response) {
        console.log('Respuesta exitosa de ver amortización');
        if (response.success) {
          showAmortizationModal(response.data.tabla, response.data);
        } else {
          showErrorMessage('Error en el cálculo: ' + response.error);
        }
      },
      error: function(xhr, status, error) {
        console.log('Error en ver amortización:', xhr.responseText, 'status:', status);
        if (status === 'timeout') {
          showErrorMessage('La operación tardó demasiado tiempo. Por favor, intente nuevamente.');
        } else if (xhr.status === 400) {
          showErrorMessage('Error en los datos enviados. Por favor, verifique los valores ingresados.');
        } else if (xhr.status === 500) {
          showErrorMessage('Error interno del servidor. Por favor, contacte al administrador.');
        } else {
          showErrorMessage('Error al conectar con el servidor: ' + error);
        }
      },
      complete: function() {
        console.log('Ver amortización completado');
        // Restaurar botón
        $('#ver_amortizacion').prop('disabled', false).html('Ver Amortización');
        currentAjaxAmortization = null;
        isCalculatingAmortization = false;
      }
    });
  });

  // Evento para el botón "Confirmar Préstamo" en el modal
  $('#confirm_loan').on('click', function() {
    $('#amortizationModal').modal('hide');
    $('#register_loan').click();
  });

  // Función para descargar PDF de la tabla de amortización
  function downloadAmortizationPDF() {
    console.log('Iniciando descarga de PDF de amortización');

    // Verificar si hay datos de amortización
    if (!window.currentAmortizationData) {
      console.error('No hay datos de amortización disponibles');
      alert('⚠️ Primero debe calcular la amortización antes de descargar el PDF.');
      return;
    }

    // Verificar si hay tabla visible
    if (!$('#amortization_table_body').children().length) {
      console.error('No hay tabla de amortización visible');
      alert('⚠️ No hay tabla de amortización para descargar. Calcule primero.');
      return;
    }

    // Mostrar indicador de carga
    $('#download_pdf, #download_pdf_footer').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Generando PDF...');

    // Preparar datos para enviar
    var pdfData = {
      credit_amount: window.currentAmortizationData.credit_amount,
      interest_amount: window.currentAmortizationData.interest_amount,
      num_months: window.currentAmortizationData.num_months,
      payment_m: window.currentAmortizationData.payment_m,
      amortization_type: window.currentAmortizationData.amortization_type,
      date: window.currentAmortizationData.date,
      payment_start_date: window.currentAmortizationData.payment_start_date,
      tasa_tipo: window.currentAmortizationData.tasa_tipo || 'TNA'
    };

    console.log('Enviando datos para PDF:', pdfData);

    // Enviar petición AJAX para generar PDF
    $.ajax({
      url: base_url + 'admin/loans/generate_amortization_pdf',
      type: 'POST',
      data: pdfData,
      xhrFields: {
        responseType: 'blob' // Importante para manejar archivos binarios
      },
      timeout: 30000, // 30 segundos de timeout
      success: function(response, status, xhr) {
        console.log('PDF generado exitosamente');

        // Verificar que la respuesta sea un blob válido
        if (!(response instanceof Blob)) {
          console.error('Respuesta no es un blob válido');
          alert('❌ Error: La respuesta del servidor no es válida.');
          return;
        }

        // Crear URL del blob y descargar
        var blob = new Blob([response], { type: 'application/pdf' });
        var url = window.URL.createObjectURL(blob);

        // Crear enlace temporal para descarga
        var link = document.createElement('a');
        link.href = url;
        link.download = 'tabla_amortizacion_' + new Date().toISOString().split('T')[0] + '_' + new Date().toLocaleTimeString('es-CO', {hour12: false}).replace(/:/g, '-') + '.pdf';

        // Agregar al DOM, hacer click y remover
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        // Liberar URL del blob
        window.URL.revokeObjectURL(url);

        console.log('PDF descargado exitosamente');
        // Mostrar mensaje de éxito
        showSuccessMessage('✅ PDF descargado exitosamente');
      },
      error: function(xhr, status, error) {
        console.error('Error al generar PDF:', xhr.responseText, status, error);

        var errorMessage = '❌ Error al generar el PDF';

        if (status === 'timeout') {
          errorMessage += ': La operación tardó demasiado tiempo. Intente nuevamente.';
        } else if (xhr.status === 0) {
          errorMessage += ': Error de conexión. Verifique su conexión a internet.';
        } else if (xhr.status === 400) {
          errorMessage += ': Datos inválidos enviados al servidor.';
        } else if (xhr.status === 500) {
          errorMessage += ': Error interno del servidor. Contacte al administrador.';
        } else if (xhr.responseText) {
          try {
            var response = JSON.parse(xhr.responseText);
            if (response.error) {
              errorMessage += ': ' + response.error;
            }
          } catch (e) {
            errorMessage += ': ' + xhr.responseText.substring(0, 100) + '...';
          }
        } else {
          errorMessage += ': ' + error;
        }

        alert(errorMessage);
      },
      complete: function() {
        // Restaurar botones
        $('#download_pdf, #download_pdf_footer').prop('disabled', false).html('<i class="fas fa-file-pdf mr-1"></i>PDF');
        console.log('Proceso de descarga de PDF completado');
      }
    });
  }

  // Función auxiliar para mostrar mensajes de éxito
  function showSuccessMessage(message) {
    // Crear alerta temporal
    var alertDiv = $('<div class="alert alert-success alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">' +
      '<i class="fas fa-check-circle mr-2"></i>' + message +
      '<button type="button" class="close" data-dismiss="alert">' +
      '<span>&times;</span>' +
      '</button>' +
      '</div>');

    $('body').append(alertDiv);

    // Auto-remover después de 5 segundos
    setTimeout(function() {
      alertDiv.fadeOut(function() {
        alertDiv.remove();
      });
    }, 5000);
  }

  // ================================
  // VALIDACIONES DE TELÉFONO
  // ================================

  // Función para validar formato de teléfono móvil (9-11 dígitos)
  function validateMobilePhone(value) {
    if (!value || value === '') return true; // Vacío es válido (se maneja con required)
    // Solo números, entre 9 y 11 dígitos
    return /^\d{9,11}$/.test(value.toString().replace(/\s/g, ''));
  }

  // Función para validar formato del monto personalizado
  function validateCustomAmount(value) {
    if (!value || value === '') return true; // Vacío es válido inicialmente

    // Remover espacios y símbolos de moneda
    value = value.toString().replace(/\$/g, '').replace(/\s/g, '');

    // Permitir formato colombiano (1.234.567,89) o número plano con decimal opcional
    var colombianFormat = /^[\d]{1,3}(\.[\d]{3})*(,[\d]{1,2})?$/;
    var plainFormat = /^\d+(\.\d{1,2})?$/;

    return colombianFormat.test(value) || plainFormat.test(value);
  }

  // Función para validar formato de teléfono fijo (7-9 dígitos)
  function validateFixedPhone(value) {
    if (!value || value === '') return true; // Vacío es válido (se maneja con required)
    // Solo números, entre 7 y 9 dígitos
    return /^\d{7,9}$/.test(value.toString().replace(/\s/g, ''));
  }

  // Función para validar obligatoriedad de campos telefónicos
  function validatePhoneRequired(fieldId) {
    var value = $('#' + fieldId).val();
    if (!value || value.trim() === '') {
      showErrorMessage('Este campo es obligatorio: ' + fieldId);
      $('#' + fieldId).focus();
      return false;
    }
    return true;
  }

  // Función para validar formato telefónico
  function validatePhoneFormat(fieldId) {
    var value = $('#' + fieldId).val();
    var isValid = true;

    if (fieldId === 'mobile') {
      isValid = validateMobilePhone(value);
      if (!isValid) {
        showErrorMessage('El celular debe contener solo números y tener entre 9 y 11 dígitos.');
        $('#' + fieldId).focus();
      }
    } else if (fieldId === 'phone_fixed') {
      isValid = validateFixedPhone(value);
      if (!isValid) {
        showErrorMessage('El teléfono fijo debe contener solo números y tener entre 7 y 9 dígitos.');
        $('#' + fieldId).focus();
      }
    }

    return isValid;
  }

  // Event listeners para validación en tiempo real de teléfonos
  $(document).on('input', '#mobile, #phone_fixed', function() {
    var fieldId = $(this).attr('id');
    var value = $(this).val();

    // Limpiar clases de error previas
    $(this).removeClass('is-invalid');

    // Validar formato mientras escribe
    if (value && !validatePhoneFormat(fieldId)) {
      $(this).addClass('is-invalid');
    } else {
      $(this).removeClass('is-invalid');
    }
  });

  // Validación completa de teléfonos (obligatoriedad + formato)
  function validatePhoneFields() {
    console.log('Validating phone fields...');
    var isValid = true;

    // Validar celular (siempre obligatorio)
    console.log('Validating mobile phone...');
    if (!validatePhoneRequired('mobile')) {
      console.log('Mobile phone validation FAILED - required');
      isValid = false;
    } else if (!validatePhoneFormat('mobile')) {
      console.log('Mobile phone validation FAILED - format');
      isValid = false;
    } else {
      console.log('Mobile phone validation PASSED');
    }

    // Validar teléfono fijo (siempre opcional)
    var phoneFixedValue = $('#phone_fixed').val();
    console.log('Validating fixed phone, value:', phoneFixedValue);
    if (phoneFixedValue && phoneFixedValue.trim() !== '') {
      console.log('Fixed phone has value, validating format...');
      if (!validatePhoneFormat('phone_fixed')) {
        console.log('Fixed phone validation FAILED - format');
        isValid = false;
      } else {
        console.log('Fixed phone validation PASSED');
      }
    } else {
      console.log('Fixed phone is empty, skipping validation');
    }

    console.log('Phone fields validation result:', isValid);
    return isValid;
  }

  // Evento para descargar PDF desde el modal
  $('#download_pdf, #download_pdf_footer').on('click', function(e) {
    e.preventDefault();
    downloadAmortizationPDF();
  });

  // Corregir aria-hidden en modal
  $('#amortizationModal').on('hidden.bs.modal', function() {
    $(this).attr('aria-hidden', 'true');
  });

  $("#loan_form").submit(function (e) {
    console.log('Enviando formulario de préstamo');
    console.log("Amortization enviado:", $('select[name="amortization_type"]').val());
    console.log("Datos del formulario antes de envío:", $(this).serializeArray());
    // Agregar logs para debugging de pagos
    if ($(this).attr('action').includes('payments/ticket')) {
      console.log('DEBUG PAGOS: Enviando formulario de pagos');
      console.log('DEBUG PAGOS: Cuotas seleccionadas:', $('input[name="quota_id[]"]:checked').map(function(){return $(this).val();}).get());
      console.log('DEBUG PAGOS: Tipo de pago:', $('#tipo_pago').val());
      console.log('DEBUG PAGOS: Monto:', $('#total_amount').val());
      console.log('DEBUG PAGOS: Usuario:', $('#user_select').val());
    }
    e.preventDefault(); // Prevenir envío por defecto

    var form = $(this); // Guardar referencia al form

    // Validaciones adicionales para pagos
    if (form.attr('action').includes('payments/ticket')) {
      // Validar que se haya seleccionado al menos una cuota
      var selectedQuotas = $('input[name="quota_id[]"]:checked').length;
      if (selectedQuotas === 0) {
        alert('Por favor, selecciona al menos una cuota para registrar el pago.');
        return false;
      }

      // Validar que se haya seleccionado un usuario cobrador
      var selectedUser = $('#user_select').val();
      if (!selectedUser || selectedUser === '') {
        alert('Por favor, selecciona un usuario cobrador.');
        return false;
      }
    }

    // Skip phone validation since fields don't exist in this form
    console.log('Skipping phone validation - fields not present in this form');

    if($("#customer").val() == "") {
      console.log('Error: Cliente no seleccionado');
      alert("Buscar un cliente");
      return false;
    }

    // Verificar si validación está en progreso
    if (isCheckingLimit) {
      console.log('Error: Validación de límite en progreso');
      showErrorMessage("Validación de límite de crédito en progreso, espere.");
      return false;
    }

    // Verificar límite de crédito
    if (!isValidLimit) {
      console.log('Error: Límite de crédito excedido');
      showErrorMessage("El monto excede el límite de crédito permitido. Corrija el monto antes de continuar.");
      return false;
    }
    console.log('Cliente seleccionado, procediendo con envío');

    // Log adicional para verificar parsing de moneda
    console.log('Valor cr_amount antes de envío:', $('#cr_amount').val());
    console.log('Valor in_amount antes de envío:', $('#in_amount').val());

    // Preparar datos con tasa_tipo asegurado
    var data = form.serializeArray();
    console.log("Datos serializados:", data);
    // Asegurar que credit_amount se incluya
    var creditAmount = data.find(item => item.name === 'credit_amount');
    if (!creditAmount) {
        data.push({name: 'credit_amount', value: $('#cr_amount').val() || ''});
        console.log("Agregando credit_amount:", $('#cr_amount').val() || '');
    }
    data.push({name: 'tasa_tipo', value: $('select[name="tasa_tipo"]').val() || 'TNA'});
    var existing = data.find(item => item.name === 'amortization_type');
    if (existing) {
        existing.value = $('select[name="amortization_type"]').val() || 'francesa';
        console.log("Actualizando amortization_type existente a:", existing.value);
    } else {
        data.push({name: 'amortization_type', value: $('select[name="amortization_type"]').val() || 'francesa'});
        console.log("Agregando amortization_type:", $('select[name="amortization_type"]').val() || 'francesa');
    }
    console.log("Datos finales a enviar:", data);

    // Agregar logs para debugging
    console.log('Valor de credit_amount antes de envío:', $('#cr_amount').val());
    console.log('Datos serializados completos:', data);

    // Enviar por AJAX
    console.log('DEBUG PAGOS: Enviando AJAX a:', form.attr('action'));
    console.log('DEBUG PAGOS: Datos finales enviados:', data);
    $.ajax({
      url: form.attr('action'),
      type: 'POST',
      data: data,
      dataType: 'text', // Cambiar a text para manejar manualmente
      success: function(data) {
        try {
          var response = JSON.parse(data);
          if (response.success) {
            // Mostrar mensaje de éxito si es pago
            if (form.attr('action').includes('payments/ticket')) {
              alert('✅ Pago registrado correctamente. Comisión del 40% asignada al cobrador.');
            }
            // Redirigir a admin/loans si success
            window.location.href = base_url + 'admin/loans';
          } else {
            alert(response.error || 'Error al registrar el préstamo');
          }
        } catch (e) {
          console.error('Error parseando respuesta JSON:', e, data);
          alert('Error en la respuesta del servidor. Ver consola para detalles.');
        }
      },
      error: function(xhr, status, error) {
        console.log('Error en registro:', xhr.responseText, status, error);
        alert('Error al conectar con el servidor: ' + error);
      }
    });
  });

  $(document).on("click", '[data-toggle="ajax-modal"]', function (t) {
    t.preventDefault();
    var url = $(this).attr("href");
    $.get(url).done(function (data) {
      $("#myModal").html(data).modal({ backdrop: "static" });
    })
  })

  // buscar cliente cobranza con sugerencias
  var search_customers = function(query) {
    console.log('Buscando clientes con sugerencias para query:', query);
    if (query.length < 2) {
      $('#customer_suggestions').hide();
      return;
    }
    $.post(base_url + "admin/payments/ajax_searchCst/", {dni: query, suggest: '1'}, function(data) {
        console.log('sin parse', data);
        data = JSON.parse(data);
        console.log('con parse', data);
        if (Array.isArray(data.cst)) {
            var suggestions = '';
            data.cst.forEach(function(cst) {
                suggestions += '<a class="dropdown-item" href="#" data-customer=\'' + JSON.stringify(cst) + '\'>' + cst.dni + ' - ' + cst.cst_name + '</a>';
            });
            $('#customer_suggestions').html(suggestions).show();
        } else {
            $('#customer_suggestions').hide();
        }
    });
  };

  var callback_cobranza = function() {
    var dni_c = $('#dni_c').val();
    if (dni_c == "") {
      alert('ingresar dni');
      return false;
    } else {
      // Si hay un cliente seleccionado, proceder como antes
      var selectedCustomer = $('#dni_c').data('selected-customer');
      if (selectedCustomer) {
        load_customer_data(selectedCustomer);
      } else {
        // Buscar y mostrar sugerencias
        search_customers(dni_c);
      }
    }
  };

  var load_customer_data = function(cst) {
    console.log('Cargando datos de cliente:', cst.dni);
    console.log('assigned_user_id en load_customer_data:', cst.assigned_user_id);
    $("#dni_c").val('');
    $("#dni_cst").val(cst.dni);
    $("#name_cst").val(cst.cst_name);
    $("#loan_status").val(cst.loan_status == '1' ? 'Activo' : 'Completado');
    $("#customer_id").val(cst.customer_id);
    $("#loan_id").val(cst.loan_id);
    $("#credit_amount").val(parseFloat(cst.credit_amount).toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    $("#payment_m").val(cst.payment_m);
    $("#coin").val(cst.coin_name);
    // Pre-seleccionar el usuario asignado al préstamo
    if (cst.assigned_user_id != null && cst.assigned_user_id != '') {
      console.log('Seteando user_select a:', cst.assigned_user_id);
      $("#user_select").val(cst.assigned_user_id).prop('disabled', true);
      console.log('Valor actual de user_select después de setear:', $("#user_select").val());
      // Inhabilitar el campo para que no se pueda modificar
      $("#user_select").prop('disabled', true);
    } else {
      console.log('assigned_user_id es null o vacío, no se setea user_select');
    }
    // Cargar cuotas
    alert('🚨 AJAX CALL: ajax_get_quotas called with loan_id: ' + cst.loan_id);
    alert('🚨 AJAX CALL: About to call ajax_get_quotas with loan_id: ' + cst.loan_id);
    $.post(base_url + "admin/payments/ajax_get_quotas/", {loan_id: cst.loan_id, status: 1}, function(data) {
      alert('🚨 AJAX RESPONSE: ajax_get_quotas returned data.quotas.length = ' + (data.quotas ? data.quotas.length : 'UNDEFINED'));
      console.log(data);
      if (Array.isArray(data.quotas)) {
        if (data.quotas.length > 0) {
          $('#quotas tbody').html(generateQuotasHtml(data.quotas));
          // Agregar logs para diagnosticar select_all
          console.log('Configurando listener para checkboxes individuales');
          $('input[name="quota_id[]"]').on('change', function() {
            console.log('Checkbox individual cambiado:', $(this).val(), 'checked:', $(this).is(':checked'), 'enabled:', $(this).is(':enabled'));
            var total = 0;
            var totalInteres = 0;
            var totalCapital = 0;
            var totalSaldo = 0;
            $('input[name="quota_id[]"]:enabled:checked').each(function() {
              total += isNaN(parseFloat($(this).attr('data-fee'))) ? 0 : parseFloat($(this).attr('data-fee'));
              totalInteres += isNaN(parseFloat($(this).attr('data-interes'))) ? 0 : parseFloat($(this).attr('data-interes'));
              totalCapital += isNaN(parseFloat($(this).attr('data-capital'))) ? 0 : parseFloat($(this).attr('data-capital'));
              totalSaldo += isNaN(parseFloat($(this).attr('data-saldo'))) ? 0 : parseFloat($(this).attr('data-saldo'));
            });
            console.log('Totales calculados:', {total, totalInteres, totalCapital, totalSaldo});
            $("#total_amount").val(total.toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            $("#total_cuota").text(total.toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            $("#total_interes").text(totalInteres.toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            $("#total_capital").text(totalCapital.toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            $("#total_saldo").text(totalSaldo.toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            // Sincronizar select_all
            var allChecked = $('input[name="quota_id[]"]:enabled').length === $('input[name="quota_id[]"]:enabled:checked').length;
            $('#select_all').prop('checked', allChecked);
            if (total != 0) {
              $('#register_loan').attr('disabled', false);
            } else {
              $('#register_loan').attr('disabled', true);
            }
          });

          // Agregar funcionalidad para select_all
          $('#select_all').on('change', function() {
            console.log('select_all cambiado, checked:', $(this).is(':checked'));
            var isChecked = $(this).is(':checked');
            $('input[name="quota_id[]"]:enabled').each(function() {
              console.log('Cambiando checkbox:', $(this).val(), 'a', isChecked);
              $(this).prop('checked', isChecked).trigger('change');
            });
          });
        
          // Función para recargar cuotas después de generar adicionales
          function reloadQuotas() {
            var loan_id = $('#loan_id').val();
            if (loan_id) {
              console.log('Recargando cuotas para loan_id:', loan_id);
              $.post(base_url + "admin/payments/ajax_get_quotas/", {loan_id: loan_id}, function(data) {
                console.log('Cuotas recargadas:', data);
                if (Array.isArray(data.quotas)) {
                  $('#quotas tbody').html(generateQuotasHtml(data.quotas));
        
                  // Re-inicializar listeners después de recargar
                  $('input[name="quota_id[]"]').on('change', function() {
                    console.log('Checkbox individual cambiado:', $(this).val(), 'checked:', $(this).is(':checked'), 'enabled:', $(this).is(':enabled'));
                    var total = 0;
                    var totalInteres = 0;
                    var totalCapital = 0;
                    var totalSaldo = 0;
                    $('input[name="quota_id[]"]:enabled:checked').each(function() {
                      total += isNaN(parseFloat($(this).attr('data-fee'))) ? 0 : parseFloat($(this).attr('data-fee'));
                      totalInteres += isNaN(parseFloat($(this).attr('data-interes'))) ? 0 : parseFloat($(this).attr('data-interes'));
                      totalCapital += isNaN(parseFloat($(this).attr('data-capital'))) ? 0 : parseFloat($(this).attr('data-capital'));
                      totalSaldo += isNaN(parseFloat($(this).attr('data-saldo'))) ? 0 : parseFloat($(this).attr('data-saldo'));
                    });
                    console.log('Totales calculados:', {total, totalInteres, totalCapital, totalSaldo});
                    $("#total_amount").val(total.toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                    $("#total_cuota").text(total.toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                    $("#total_interes").text(totalInteres.toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                    $("#total_capital").text(totalCapital.toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                    $("#total_saldo").text(totalSaldo.toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                    // Sincronizar select_all
                    var allChecked = $('input[name="quota_id[]"]:enabled').length === $('input[name="quota_id[]"]:enabled:checked').length;
                    $('#select_all').prop('checked', allChecked);
                    if (total != 0) {
                      $('#register_loan').attr('disabled', false);
                    } else {
                      $('#register_loan').attr('disabled', true);
                    }
                  });
        
                  // Re-inicializar select_all
                  $('#select_all').on('change', function() {
                    console.log('select_all cambiado, checked:', $(this).is(':checked'));
                    var isChecked = $(this).is(':checked');
                    $('input[name="quota_id[]"]:enabled').each(function() {
                      console.log('Cambiando checkbox:', $(this).val(), 'a', isChecked);
                      $(this).prop('checked', isChecked).trigger('change');
                    });
                  });
        
                  // Mostrar mensaje de cuotas adicionales generadas
                  if (data.quotas.length > 0) {
                    var lastQuota = data.quotas[data.quotas.length - 1];
                    if (lastQuota.num_quota > 10) { // Asumiendo que las cuotas originales son <= 10
                      showErrorMessage('Se han generado cuotas adicionales para completar el pago.', '#loanErrorBox');
                    }
                  }
                } else {
                  console.error("data.quotas no es un array");
                  $('#quotas tbody').html('<tr><td colspan="8">Error al recargar cuotas</td></tr>');
                }
              }, 'json').fail(function(xhr, status, error) {
                console.error('Error al recargar cuotas:', error);
                showErrorMessage('Error al recargar cuotas: ' + error, '#loanErrorBox');
              });
            }
          }
        } else {
          // Verificar si el préstamo está completamente pagado
          if (cst.total_balance == 0) {
            $('#quotas tbody').html('<tr><td colspan="8">El préstamo está completamente pagado. No hay cuotas pendientes.</td></tr>');
          } else {
            $('#quotas tbody').html('<tr><td colspan="8">No se encontraron cuotas pendientes para este préstamo.</td></tr>');
          }
        }
      } else {
        console.error("data.quotas no es un array");
        $('#quotas tbody').html('<tr><td colspan="8">Error al cargar cuotas</td></tr>');
      }
    }, 'json');
  };

  $("#dni_c").on('input', function() {
    var query = $(this).val();
    search_customers(query);
  });

  $("#dni_c").keypress(function(event) {
    if (event.which == 13) {
      callback_cobranza();
      $('#customer_suggestions').hide();
    }
  });

  $('#btn_buscar_c').click(function() {
    var dni = $('#dni_c').val();
    if (dni == "") {
      alert('ingresar dni');
      return false;
    } else {
      $.post(base_url + "admin/payments/ajax_searchCst/", {dni: dni, suggest: '0'}, function(data){
        console.log(data);
        if (data.cst) {
          $("#dni_cst").val(data.cst.dni);
          $("#name_cst").val(data.cst.cst_name);
          $("#asesor").val(data.cst.asesor_name || 'N/A');
          $("#customer_id").val(data.cst.customer_id);
          $("#loan_id").val(data.cst.loan_id);
          $("#credit_amount").val(data.cst.credit_amount);
          $("#payment_m").val(data.cst.payment_m);
          $("#coin").val(data.cst.coin_name);
          // Pre-seleccionar el usuario asignado al préstamo
          if (data.cst.assigned_user_id) {
            $("#user_select").val(data.cst.assigned_user_id);
          }
          if (Array.isArray(data.quotas) && data.quotas.length > 0) {
            $('#quotas tbody').html(generateQuotasHtml(data.quotas));
          } else {
            // Verificar si el préstamo está completamente pagado
            if (data.cst && data.cst.total_balance == 0) {
              $('#quotas tbody').html('<tr><td colspan="8">El préstamo está completamente pagado. No hay cuotas pendientes.</td></tr>');
            } else {
              $('#quotas tbody').html('<tr><td colspan="8">No se encontraron cuotas pendientes para este préstamo.</td></tr>');
            }
          }
        } else {
          alert('No se encontró cliente con préstamo activo');
          $("#dni_cst").val('');
          $("#name_cst").val('');
          $("#asesor").val('');
          $("#customer_id").val('');
          $("#loan_id").val('');
          $("#credit_amount").val('');
          $("#payment_m").val('');
          $("#coin").val('');
          $('#quotas tbody').html('<tr><td colspan="8">SIN CUOTAS</td></tr>');
        }
      }, 'json');
    }
  });

  // Seleccionar sugerencia
  $(document).on('click', '#customer_suggestions .dropdown-item', function(e) {
    e.preventDefault();
    var customerData = $(this).data('customer');
    $('#dni_c').val(customerData.dni + ' - ' + customerData.cst_name).data('selected-customer', customerData);
    $('#customer_suggestions').hide();
    load_customer_data(customerData);
  });

  // Ocultar sugerencias al hacer click fuera
  $(document).click(function(e) {
    if (!$(e.target).closest('#dni_c, #customer_suggestions').length) {
      $('#customer_suggestions').hide();
    }
  });

  $("#coin_type").change(function(){
    var coin_id = $("#coin_type").val()
    var symbol = $('#coin_type option:selected').data("symbol");
    $.get(base_url + "admin/reports/ajax_getCredits/" + coin_id, function(data){
      data = JSON.parse(data);
      console.log('con parse', data)
      if (data.credits[0].sum_credit == null) {
        var sum_credit = '0 ' + symbol.toUpperCase()
      } else {
        var sum_credit = data.credits[0].sum_credit + ' ' + (data.credits[0].short_name).toUpperCase()
      }
      $("#cr").html(sum_credit)
      if (data.credits[1].cr_interest == null) {
        var cr_interest = '0 ' + symbol.toUpperCase()
      } else {
        var cr_interest = data.credits[1].cr_interest + ' ' + (data.credits[1].short_name).toUpperCase()
      }
      $("#cr_interest").html(cr_interest)
      if (data.credits[2].cr_interestPaid == null) {
        var cr_interestPaid = '0 ' + symbol.toUpperCase()
      }else{
        var cr_interestPaid = data.credits[2].cr_interestPaid + ' ' + data.credits[2].short_name.toUpperCase()
      }
      $("#cr_interestPaid").html(cr_interestPaid)
      if (data.credits[3].cr_interestPay == null) {
        var cr_interestPay = '0 ' + symbol.toUpperCase()
      } else {
        var cr_interestPay = data.credits[3].cr_interestPay + ' ' + (data.credits[3].short_name).toUpperCase()
      }
      $("#cr_interestPay").html(cr_interestPay)
    });
  });

  // Evento submit para el formulario de pagos
  $(document).on('submit', 'form[action*="payments/ticket"]', function(e) {
    console.log('Validando formulario de pagos');

    // Validación: asegurar que se seleccione un usuario
    if (!$('#user_select').val()) {
      alert('Seleccionar usuario');
      e.preventDefault();
      return false;
    }

    // DIAGNÓSTICO: Verificar qué cuotas están seleccionadas
    var selectedQuotas = $('input[name="quota_id[]"]:checked');
    console.log('DIAGNÓSTICO - Cuotas seleccionadas:', selectedQuotas.length);
    selectedQuotas.each(function() {
      console.log('DIAGNÓSTICO - Cuota ID:', $(this).val(), 'data-fee:', $(this).attr('data-fee'));
    });

    // Validación específica para pago total: debe seleccionar exactamente una cuota
    var selectedTipoPago = $('#tipo_pago').val() || 'full';
    if (selectedTipoPago === 'total' || selectedTipoPago === 'total_condonacion') {
      if (selectedQuotas.length === 0) {
        alert('Para pago total, debe seleccionar exactamente una cuota.');
        e.preventDefault();
        return false;
      }
      if (selectedQuotas.length !== 1) {
        alert('Para pago total, debe seleccionar exactamente una cuota.');
        e.preventDefault();
        return false;
      }
    }

    // MEJORA: Validación adicional del lado cliente para tipo_pago
    var tiposPagoValidos = ['full', 'interest', 'capital', 'both', 'total', 'custom', 'early_total', 'total_condonacion'];
    if (!tiposPagoValidos.includes(selectedTipoPago)) {
      alert('Tipo de pago inválido seleccionado.');
      e.preventDefault();
      return false;
    }

    // Validaciones para pagos personalizados
    if (selectedTipoPago === 'custom') {
      var customAmount = parseFloat($('#custom_amount').val()) || 0;

      // Validar que se haya ingresado un monto
      if (customAmount <= 0) {
        alert('Debe ingresar un monto personalizado válido mayor a 0.');
        $('#custom_amount').focus();
        e.preventDefault();
        return false;
      }

      // Validar que el monto no exceda el total de las cuotas seleccionadas
      var totalSelectedAmount = 0;
      selectedQuotas.each(function() {
        totalSelectedAmount += parseFloat($(this).attr('data-fee')) || 0;
      });

      if (customAmount > totalSelectedAmount) {
        alert('El monto personalizado no puede exceder el total de las cuotas seleccionadas ($' + totalSelectedAmount.toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ').');
        $('#custom_amount').focus();
        e.preventDefault();
        return false;
      }

      // Validar que se hayan seleccionado cuotas
      if (selectedQuotas.length === 0) {
        alert('Debe seleccionar al menos una cuota para realizar un pago personalizado.');
        e.preventDefault();
        return false;
      }
    }

    // DIAGNÓSTICO: Verificar datos del formulario antes de enviar
    var formData = $(this).serializeArray();
    console.log('DIAGNÓSTICO - Datos del formulario:', formData);

    // Agregar user_id seleccionado al formulario
    if (!$('input[name="user_id"]').length) {
      $(this).append('<input type="hidden" name="user_id" value="' + $('#user_select').val() + '">');
    } else {
      $('input[name="user_id"]').val($('#user_select').val());
    }

    // El select tipo_pago ya tiene name="tipo_pago", no necesitamos inputs hidden adicionales
    // Solo aseguramos que tenga un valor válido
    var selectedTipoPago = $('#tipo_pago').val() || 'full';
    if (!selectedTipoPago) {
      $('#tipo_pago').val('full');
    }

    // MEJORA: Validación adicional del lado cliente para asegurar valor válido antes del envío
    var tiposPagoValidos = ['full', 'interest', 'capital', 'both', 'total', 'custom', 'early_total', 'total_condonacion'];
    if (!tiposPagoValidos.includes(selectedTipoPago)) {
      alert('Error: Tipo de pago inválido. Refrescando la página.');
      location.reload();
      e.preventDefault();
      return false;
    }

    // Agregar custom_amount y custom_payment_type si es pago personalizado
    if (selectedTipoPago === 'custom') {
      var customAmount = $('#custom_amount').val() || '';
      var customPaymentType = $('input[name="custom_payment_type"]:checked').val() || 'cuota'; // Usar el valor seleccionado del radio button

      console.log('Pago personalizado - customAmount:', customAmount, 'customPaymentType:', customPaymentType);

      if (!$('input[name="custom_amount"]').length) {
        $(this).append('<input type="hidden" name="custom_amount" value="' + customAmount + '">');
      } else {
        $('input[name="custom_amount"]').val(customAmount);
      }

      if (!$('input[name="custom_payment_type"]').length) {
        $(this).append('<input type="hidden" name="custom_payment_type" value="' + customPaymentType + '">');
      } else {
        $('input[name="custom_payment_type"]').val(customPaymentType);
      }

      console.log('DEBUG: Enviando pago personalizado - custom_amount:', customAmount, 'custom_payment_type:', customPaymentType);
    }

    // ALERTA DE CONFIRMACIÓN ANTES DE REGISTRAR
    var confirmationMessage = '¿Está seguro de que desea registrar este pago?\n\n';
    confirmationMessage += 'Tipo de pago: ' + (selectedTipoPago === 'custom' ? 'Personalizado' : selectedTipoPago) + '\n';
    confirmationMessage += 'Cuotas seleccionadas: ' + selectedQuotas.length + '\n';

    if (selectedTipoPago === 'custom') {
      confirmationMessage += 'Monto personalizado: $' + parseFloat($('#custom_amount').val()).toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '\n';
      var customPaymentType = $('input[name="custom_payment_type"]:checked').val();
      confirmationMessage += 'Tipo de pago personalizado: ' + (customPaymentType === 'cuota' ? 'Pago parcial' : 'Pago no completo') + '\n';
    }

    confirmationMessage += '\nEsta acción no se puede deshacer.';

    if (!confirm(confirmationMessage)) {
      e.preventDefault();
      return false;
    }

    console.log('Formulario válido, enviando normalmente');
    // Dejar que el formulario se envíe normalmente
  });

  // Fix for permissions modal accessibility: manage focus on modal show/hide
  var lastFocusedElement;
  $(document).on('click', '.btn-permissions', function() {
    lastFocusedElement = this;
  });

  $('#permissionsModal').on('show.bs.modal', function() {
    $(this).attr('aria-hidden', 'false');
  });

  $('#permissionsModal').on('hide.bs.modal', function() {
    // Move focus back to the trigger button before hiding to avoid aria-hidden focus issue
    if (lastFocusedElement) {
      lastFocusedElement.focus();
    }
  });

  $('#permissionsModal').on('hidden.bs.modal', function() {
    $(this).attr('aria-hidden', 'true');
  });

  // Validar límite de crédito inicial si hay cliente seleccionado (para edición)
  if ($('#customer').val()) {
    validateCreditLimit();
  }

})

function imp_credits(imp1){
  var printContents = document.getElementById('imp1').innerHTML;
  w = window.open();
  w.document.write(printContents);
  w.print();
  w.close();
}

function reportPDF(){
  var start_d = $("#start_d").val();
  var end_d = $("#end_d").val();
  var coin_t = $("#coin_type2").val();
  if (start_d == '' || end_d == '') {
    alert('ingrese las fechas')
  }else{
    window.open(base_url+'admin/reports/dates_pdf/'+coin_t+'/'+start_d+'/'+end_d)
  }
}
