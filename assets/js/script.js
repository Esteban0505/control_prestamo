console.log('script.js cargado — build-debug:', 'v-debug-' + (new Date()).toISOString());

$(document).ready(function() {
  // Función para generar HTML de cuotas
  function generateQuotasHtml(quotas) {
    if (!Array.isArray(quotas)) {
      console.error("generateQuotasHtml recibió algo inválido:", quotas);
      return "";
    }
    var html = '';
    quotas.forEach(function(quota) {
      var fecha = quota.fecha_pago ? new Date(quota.fecha_pago).toLocaleDateString('es-ES') : 'Sin fecha';
      var monto = parseFloat(quota.monto_cuota).toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2});
      var checked = quota.status ? '' : 'disabled checked';
      var btnClass = quota.status ? 'btn-outline-danger' : 'btn-outline-success';
      html += '<tr>';
      html += '<td><input type="checkbox" name="quota_id[]" ' + checked + ' data-fee="' + quota.monto_cuota + '" value="' + quota.id + '"></td>';
      html += '<td>' + quota.n_cuota + '</td>';
      html += '<td>' + fecha + '</td>';
      html += '<td>' + monto + '</td>';
      html += '<td><button type="button" class="btn btn-sm ' + btnClass + '">' + quota.estado + '</button></td>';
      html += '</tr>';
    });
    return html;
  }

  // Expresión regular provista para validar moneda
  var moneyRegex = /^(\d{1,3}(\.\d{3})*|\d+)(,\d{1,2})?$/;

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
        }
        else {
          $("#dni").val('');
          data = JSON.parse(data);
          console.log('con parse', data)
          if (data.loan_status == '0') {
              $("#customer").val(data.id);
              $("#dni_cst").val(data.dni);
              $("#name_cst").val(data.first_name + ' ' +data.last_name);
          } else {
              alert('persona con prestamo pendiente')
              $("#dni_cst").val('');
              $("#name_cst").val('');
              $("#customer").val('');
          }
          $("#assigned_user_id").val(data.user_id);
        }
      })
    }
    console.log('Búsqueda de cliente completada');
  };

  $("#dni").keypress(function(event) {
    if (event.which == 13) callback();
  });

  $('#btn_buscar').click(callback);

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

  // Limpiar resultados cuando cambien otros campos
  $('#fee, #amortization_type, select[name="payment_m"]').on('change', function() {
    clearPreviousResults();
    // Auto-calcular si todos los campos están llenos
    setTimeout(function() {
      if (validateCalculationFields()) {
        calculateAmortization();
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
    if ($("#in_amount").val()=="") {
      contador=1
      showErrorMessage("Ingresar interés")
      $("#in_amount").focus()
      return false;
    }
    if ($("#fee").val()=="") {
      contador=1
      showErrorMessage("Ingresar cuotas")
      $("#fee").focus()
      return false;
    }
    if ($("#date").val()=="") {
      contador=1
      showErrorMessage("Ingresar fecha emisión")
      return false;
    }
    // Validación para amortización
    if ($("#amortization_type").val()=="" || $("#amortization_type").val()==null) {
      contador=1
      showErrorMessage("Seleccionar tipo de amortización")
      $("#amortization_type").focus()
      return false;
    }
    // Validación para tipo de tasa
    if ($("#tasa_tipo").val()=="" || $("#tasa_tipo").val()==null) {
      contador=1
      showErrorMessage("Seleccionar tipo de tasa")
      $("#tasa_tipo").focus()
      return false;
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
  function showErrorMessage(message, containerId = '#error_container') {
    console.error('showErrorMessage:', message);
    const container = $(containerId);
    if (container.length) {
      container.html('<div class="alert alert-danger">' + message + '</div>');
      container.show();
    } else {
      alert(message);
    }
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
  // Pago manual en payments/edit
  // ================================
  // Habilitar Registrar Pago con monto manual o cuotas
  function updateRegisterButtonState() {
    var anyQuota = $('input[name="quota_id[]"]:enabled:checked').length > 0;
    var manualVal = parseFloat($('#manual_payment_amount').val());
    var manualOk = !isNaN(manualVal) && manualVal > 0;
    if (manualOk || anyQuota) {
      $('#register_loan').prop('disabled', false);
    } else {
      $('#register_loan').prop('disabled', true);
    }
    // Priorizar monto manual en el total mostrado
    if (manualOk) {
      $('#total_amount').val(manualVal.toFixed(2));
    } else {
      // si no hay manual, mantener suma por cuotas ya existente
      var total = 0;
      $('input:checkbox:enabled:checked').each(function(){
        total += isNaN(parseFloat($(this).attr('data-fee'))) ? 0 : parseFloat($(this).attr('data-fee'));
      });
      $('#total_amount').val(total ? total : '');
    }
  }

  // Listeners para cambios de cuotas y pago manual
  $(document).on('change', 'input[name="quota_id[]"]', updateRegisterButtonState);
  $(document).on('input', '#manual_payment_amount', updateRegisterButtonState);

  // Botón pagar manual via AJAX (sigue disponible)
  $(document).on('click', '#btn_manual_pay', function() {
    var loanItemId = $('input[name="quota_id[]"]:enabled:checked').first().val();
    if (!loanItemId) { alert('Seleccione al menos una cuota'); return; }

    var amountInput = $('#manual_payment_amount').val();
    var description = $('#manual_payment_description').val();
    var tipoPago = $('#tipo_pago').val();

    // Mapear tipo_pago a payment_type
    var paymentTypeMap = {
      'cuota_completa': 'full',
      'solo_capital': 'capital',
      'solo_intereses': 'interest'
    };
    var payment_type = paymentTypeMap[tipoPago] || 'full'; // default to full

    $.ajax({
      url: base_url + 'admin/payments/manual_pay',
      type: 'POST',
      dataType: 'json',
      data: {
        loan_item_id: loanItemId,
        amount: amountInput,
        description: description,
        tipo_pago: payment_type
      },
      success: function(res) {
        if (res.success) {
          var tipo = res.data.type === 'total' ? 'Pago total' : 'Pago parcial';
          var remaining = res.data.remaining;
          $('#manual_result').val(tipo + ' - Saldo restante: ' + remaining);
          // refrescar búsqueda si es necesario
          $('#btn_buscar_c').click();
          // limpiar monto manual tras éxito
          $('#manual_payment_amount').val('');
          updateRegisterButtonState();
        } else {
          alert(res.error || 'Error al registrar el pago');
        }
      },
      error: function() { alert('Error de conexión'); }
    });
  });

  // Función para calcular amortización usando AJAX
  function calculateAmortization() {
    console.log('Iniciando cálculo de amortización');
    var principal_input = $('#cr_amount').val();
    var interest_input = $('#in_amount').val();
    var periods = parseInt($('#fee').val());
    var payment_frequency = $('select[name="payment_m"]').val();
    var amortization_type = $('select[name="amortization_type"]').val();
    console.log('Valor de amortization_type:', amortization_type);
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
      showErrorMessage('El número de cuotas debe estar entre 1 y 120');
      $('#fee').focus();
      return;
    }

    if (!amortization_type) {
      showErrorMessage('Debe seleccionar un tipo de amortización');
      $('#amortization_type').focus();
      return;
    }

    if (!start_date) {
      showErrorMessage('Debe seleccionar una fecha de inicio');
      $('input[name="date"]').focus();
      return;
    }

    // Limpiar resultados anteriores
    clearPreviousResults();

    // Mostrar indicador de carga
    $('#calcular').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Calculando...');

    $.ajax({
      url: base_url + 'admin/loans/ajax_calculate_amortization',
      type: 'POST',
      data: {
        credit_amount: principal,
        interest_amount: interest_rate,
        num_fee: periods,
        payment_m: payment_frequency,
        amortization_type: amortization_type,
        date: start_date,
        tasa_tipo: $('select[name="tasa_tipo"]').val() || 'nominal'
      },
      dataType: 'json',
      timeout: 10000, // 10 segundos de timeout
      success: function(response) {
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
            num_fee: periods,
            payment_m: payment_frequency,
            amortization_type: amortization_type,
            date: start_date,
            tasa_tipo: $('select[name="tasa_tipo"]').val() || 'nominal'
          };
          console.log('Datos guardados:', window.currentAmortizationData);

          console.log('Cálculo completado exitosamente');
        } else {
          console.error('Error en cálculo:', response.error);
          const errorMsg = response.error || 'Error desconocido en el cálculo';
          showErrorMessage('Error en el cálculo: ' + errorMsg);
        }

        console.log('Callback success completado');
      },
      error: function(xhr, status, error) {
        console.log('Error en cálculo:', xhr.responseText);
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
        // Restaurar botón
        $('#calcular').prop('disabled', false).html('Calcular');
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
    // Mostrar resumen
    var summaryHtml = `
      <div class="col-md-2">
        <div class="card bg-primary text-white">
          <div class="card-body">
            <h6 class="card-title">Total Cuotas</h6>
            <h4>$ ${formatNumber(data.totalCuotas)} COP</h4>
          </div>
        </div>
      </div>
      <div class="col-md-2">
        <div class="card bg-success text-white">
          <div class="card-body">
            <h6 class="card-title">Total Capital</h6>
            <h4>$ ${formatNumber(data.totalCapital)} COP</h4>
          </div>
        </div>
      </div>
      <div class="col-md-2">
        <div class="card bg-warning text-white">
          <div class="card-body">
            <h6 class="card-title">Total Interés</h6>
            <h4>$ ${formatNumber(data.totalInteres)} COP</h4>
          </div>
        </div>
      </div>
      <div class="col-md-2">
        <div class="card bg-info text-white">
          <div class="card-body">
            <h6 class="card-title">Nro Períodos</h6>
            <h4>${data.nCuotas}</h4>
          </div>
        </div>
      </div>
      <div class="col-md-2">
        <div class="card bg-secondary text-white">
          <div class="card-body">
            <h6 class="card-title">Tasa aplicada</h6>
            <h4>${data.tasa_aplicada}%</h4>
          </div>
        </div>
      </div>
    `;

    $('#amortization_summary').html(summaryHtml);

    // Mostrar tabla
    var tableHtml = '';
    amortizationTable.forEach(function(payment) {
      tableHtml += `
        <tr>
          <td class="text-center">${payment.periodo}</td>
          <td class="text-center">${payment.fecha}</td>
          <td class="text-right">$ ${formatNumber(payment.cuota)} COP</td>
          <td class="text-right">$ ${formatNumber(payment.capital)} COP</td>
          <td class="text-right">$ ${formatNumber(payment.interes)} COP</td>
          <td class="text-right">$ ${formatNumber(payment.saldo)} COP</td>
        </tr>
      `;
    });

    $('#amortization_table_body').html(tableHtml);
    $('#amortizationModal').modal('show');
    setTimeout(() => $('#amortizationModal').attr('aria-hidden', 'false'), 100);
  }

  // Evento para el botón "Ver Amortización"
  $('#ver_amortizacion').on('click', function() {
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
      showErrorMessage('El número de cuotas debe estar entre 1 y 120');
      $('#fee').focus();
      return;
    }

    if (!amortization_type) {
      showErrorMessage('Debe seleccionar un tipo de amortización');
      $('#amortization_type').focus();
      return;
    }

    if (!start_date) {
      showErrorMessage('Debe seleccionar una fecha de inicio');
      $('input[name="date"]').focus();
      return;
    }

    // Mostrar indicador de carga
    $('#ver_amortizacion').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Cargando...');

    $.ajax({
      url: base_url + 'admin/loans/ajax_calculate_amortization',
      type: 'POST',
      data: {
        credit_amount: principal,
        interest_amount: interest_rate,
        num_fee: periods,
        payment_m: payment_frequency,
        amortization_type: amortization_type,
        date: start_date,
        tasa_tipo: $('select[name="tasa_tipo"]').val() || 'nominal'
      },
      dataType: 'json',
      timeout: 10000,
      success: function(response) {
        if (response.success) {
          showAmortizationModal(response.data.tabla, response.data);
        } else {
          showErrorMessage('Error en el cálculo: ' + response.error);
        }
      },
      error: function(xhr, status, error) {
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
        // Restaurar botón
        $('#ver_amortizacion').prop('disabled', false).html('Ver Amortización');
      }
    });
  });

  // Evento para el botón "Confirmar Préstamo" en el modal
  $('#confirm_loan').on('click', function() {
    $('#amortizationModal').modal('hide');
    $('#register_loan').click();
  });

  // Corregir aria-hidden en modal
  $('#amortizationModal').on('hidden.bs.modal', function() {
    $(this).attr('aria-hidden', 'true');
  });

  $("#loan_form").submit(function (e) {
    console.log('Enviando formulario de préstamo');
    e.preventDefault(); // Prevenir envío por defecto

    if($("#customer").val() == "") {
      console.log('Error: Cliente no seleccionado');
      alert("Buscar un cliente");
      return false;
    }
    console.log('Cliente seleccionado, procediendo con envío');

    // Preparar datos con tasa_tipo asegurado
    var data = $(this).serializeArray();
    data.push({name: 'tasa_tipo', value: $('select[name="tasa_tipo"]').val() || 'nominal'});

    // Enviar por AJAX
    $.ajax({
      url: $(this).attr('action'),
      type: 'POST',
      data: data,
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          // Redirigir a admin/loans si success
          window.location.href = base_url + 'admin/loans';
        } else {
          alert(response.error || 'Error al registrar el préstamo');
        }
      },
      error: function(xhr, status, error) {
        console.log('Error en registro:', xhr.responseText);
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
    $("#dni_c").val('');
    $("#dni_cst").val(cst.dni);
    $("#name_cst").val(cst.cst_name);
    $("#customer_id").val(cst.customer_id);
    $("#loan_id").val(cst.loan_id);
    $("#credit_amount").val(cst.credit_amount);
    $("#payment_m").val(cst.payment_m);
    $("#coin").val(cst.coin_name);
    // Cargar cuotas
    $.post(base_url + "admin/payments/ajax_get_quotas/", {loan_id: cst.loan_id}, function(data) {
      console.log(data);
      if (Array.isArray(data.quotas)) {
        if (data.quotas.length > 0) {
          $('#quotas tbody').html(generateQuotasHtml(data.quotas));
          $('input:checkbox').on('change', function() {
            var total = 0;
            $('input:checkbox:enabled:checked').each(function() {
              total += isNaN(parseFloat($(this).attr('data-fee'))) ? 0 : parseFloat($(this).attr('data-fee'));
            });
            $("#total_amount").val(total);
            if (total != 0) {
              $('#register_loan').attr('disabled', false);
            } else {
              $('#register_loan').attr('disabled', true);
            }
          });
        } else {
          $('#quotas tbody').html('<tr><td colspan="5">No hay cuotas</td></tr>');
        }
      } else {
        console.error("data.quotas no es un array");
        $('#quotas tbody').html('<tr><td colspan="5">Error al cargar cuotas</td></tr>');
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
          $("#name_cst").val(data.cst.first_name + ' ' + data.cst.last_name);
          $("#customer_id").val(data.cst.id);
          $("#loan_id").val(data.cst.loan_id);
          $("#credit_amount").val(data.cst.credit_amount);
          $("#payment_m").val(data.cst.payment_m);
          $("#coin").val(data.cst.coin_name);
          if (Array.isArray(data.quotas) && data.quotas.length > 0) {
            $('#quotas tbody').html(generateQuotasHtml(data.quotas));
          } else {
            $('#quotas tbody').html('<tr><td colspan="5">No hay cuotas</td></tr>');
          }
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
