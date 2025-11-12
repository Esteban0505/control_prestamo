<?php if(validation_errors()) { ?>
  <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
    <?php echo validation_errors('<li>', '</li>'); ?>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
      <span aria-hidden="true">&times;</span>
    </button>
  </div>
<?php } ?>

<?php if(form_error('credit_amount')) { ?>
  <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
    <?php echo form_error('credit_amount'); ?>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
      <span aria-hidden="true">&times;</span>
    </button>
  </div>
<?php } ?>

<?php if(form_error('amortization_type')) { ?>
  <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
    <?php echo form_error('amortization_type'); ?>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
      <span aria-hidden="true">&times;</span>
    </button>
  </div>
<?php } ?>

<?php if($this->session->flashdata('error')) { ?>
  <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
    <?php echo $this->session->flashdata('error'); ?>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
      <span aria-hidden="true">&times;</span>
    </button>
  </div>
<?php } ?>

<div class="card shadow mb-4">
  <div class="card-header py-3"><?php echo isset($is_edit) && $is_edit ? 'Editar Préstamo' : 'Crear préstamo'; ?></div>
  <div class="card-body">
    <?php echo form_open('admin/loans/edit' . (isset($is_edit) && $is_edit ? '?id=' . $loan->id : ''), 'id="loan_form"'); ?>

      <!-- Alertas arriba del formulario -->
      <?php if(validation_errors()) { ?>
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
          <?php echo validation_errors('<li>', '</li>'); ?>
          <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
      <?php } ?>

      <?php if(form_error('credit_amount')) { ?>
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
          <?php echo form_error('credit_amount'); ?>
          <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
      <?php } ?>

      <?php if(form_error('amortization_type')) { ?>
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
          <?php echo form_error('amortization_type'); ?>
          <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
      <?php } ?>

      <?php if($this->session->flashdata('error')) { ?>
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
          <?php echo $this->session->flashdata('error'); ?>
          <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
      <?php } ?>

      <!-- Alerta de error de límite de crédito (JavaScript) -->
      <div id="loanErrorBox" class="alert alert-danger d-none mb-4" role="alert"></div>

      <!-- Información del Cliente -->
      <div class="form-section mb-4">
        <h5 class="text-primary mb-3"><i class="fas fa-user"></i> Información del Cliente</h5>

        <!-- Buscar cliente -->
        <?php if (!isset($is_edit) || !$is_edit): ?>
        <div class="row mb-3">
          <div class="col-12 col-lg-8">
            <label class="small mb-1">Buscar cliente por N. Cédula</label>
            <div class="input-group">
              <input type="text" id="dni" class="form-control" placeholder="Buscar por DNI o Nombre completo" autocomplete="off">
              <input type="hidden" name="customer_id" id="customer">
              <div class="input-group-append">
                <button type="button" id="btn_buscar" class="btn btn-primary">
                  <i class="fa fa-search"></i>
                </button>
              </div>
            </div>
            <!-- Dropdown para sugerencias -->
            <div id="customer_suggestions_loans" class="dropdown-menu w-100" style="display: none; max-height: 200px; overflow-y: auto;"></div>
          </div>
        </div>
        <?php endif; ?>

        <!-- Datos cliente -->
        <div class="row">
          <div class="col-12 col-lg-4 mb-3">
            <label class="small mb-1">N. Cédula</label>
            <input class="form-control" id="dni_cst" type="text" value="<?php echo isset($customer) ? $customer->dni : ''; ?>" <?php echo isset($is_edit) && $is_edit ? 'disabled' : ''; ?>>
            <?php if (isset($is_edit) && $is_edit): ?>
              <input type="hidden" name="customer_id" value="<?php echo $customer->id; ?>">
            <?php endif; ?>
          </div>
          <div class="col-12 col-lg-4 mb-3">
            <label class="small mb-1">Nombre completo</label>
            <input class="form-control" id="name_cst" type="text" value="<?php echo isset($customer) ? $customer->first_name . ' ' . $customer->last_name : ''; ?>" <?php echo isset($is_edit) && $is_edit ? 'disabled' : ''; ?>>
          </div>
          <div class="col-12 col-lg-4 mb-3">
            <label class="small mb-1">Usuario asignado</label>
            <select class="form-control" name="assigned_user_id" id="assigned_user_id"
              <?php echo (isset($_GET['id']) ? 'disabled' : ''); ?>>
              <option value="">Seleccione usuario</option>
              <?php foreach ($users as $user): ?>
                <option value="<?php echo $user->id ?>"
                  <?php echo isset($loan) && $loan->assigned_user_id == $user->id ? 'selected' : ''; ?>>
                  <?php echo $user->first_name . ' ' . $user->last_name ?>
                </option>
              <?php endforeach ?>
            </select>
          </div>
        </div>


        <div class="row">
          <div class="col-12 col-lg-6 mb-3">
            <label class="small mb-1" for="tipo_cliente">Tipo de Cliente</label>
            <select class="form-control" id="tipo_cliente" name="tipo_cliente"
              <?php echo (isset($_GET['id']) ? 'disabled' : ''); ?>>
              <option value="">Seleccione tipo de cliente</option>
              <option value="normal" <?php echo isset($customer) && $customer->tipo_cliente == 'normal' ? 'selected' : ''; ?>>Normal</option>
              <option value="especial" <?php echo isset($customer) && strtolower($customer->tipo_cliente) == 'especial' ? 'selected' : ''; ?>>Especial</option>
            </select>
            <?php if (isset($_GET['id'])): ?>
              <input type="hidden" name="tipo_cliente" value="<?php echo (isset($customer) && stripos(strtolower($customer->tipo_cliente), 'especial') !== false) ? 'especial' : 'normal'; ?>">
            <?php endif; ?>
          </div>
          <?php if (isset($customer) && isset($customer->id)): ?>
          <div class="col-12 col-lg-6 mb-3">
            <label class="small mb-1">Estado del Cliente</label>
            <div class="d-flex align-items-center">
              <?php 
              $customer_status = isset($customer->status) ? $customer->status : 1;
              $status_class = $customer_status == 1 ? 'success' : 'danger';
              $status_text = $customer_status == 1 ? 'Activo' : 'Inactivo';
              $status_icon = $customer_status == 1 ? 'check-circle' : 'times-circle';
              ?>
              <span class="badge badge-<?php echo $status_class; ?> badge-lg mr-2" id="customer_status_badge">
                <i class="fas fa-<?php echo $status_icon; ?> mr-1"></i><?php echo $status_text; ?>
              </span>
              <button type="button" class="btn btn-sm btn-outline-<?php echo $customer_status == 1 ? 'danger' : 'success'; ?>" 
                      id="toggle_customer_status" 
                      data-customer-id="<?php echo $customer->id; ?>"
                      data-current-status="<?php echo $customer_status; ?>">
                <i class="fas fa-<?php echo $customer_status == 1 ? 'ban' : 'check'; ?> mr-1"></i>
                <?php echo $customer_status == 1 ? 'Desactivar' : 'Activar'; ?>
              </button>
            </div>
            <small class="form-text text-muted">
              <?php if ($customer_status == 0): ?>
                <span class="text-danger"><i class="fas fa-exclamation-triangle mr-1"></i>Cliente desactivado. No puede realizar préstamos.</span>
              <?php else: ?>
                <span class="text-success"><i class="fas fa-check-circle mr-1"></i>Cliente activo. Puede realizar préstamos.</span>
              <?php endif; ?>
            </small>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Condiciones del Préstamo -->
      <div class="form-section mb-4">
        <h5 class="text-primary mb-3"><i class="fas fa-calculator"></i> Condiciones del Préstamo</h5>

        <div class="row">
          <div class="col-12 col-lg-4 mb-3">
            <label class="small mb-1">Monto préstamo</label>
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text">$</span>
              </div>
              <input class="form-control currency-input monto-prestamo" id="cr_amount" type="text" name="credit_amount" placeholder="0" value="<?php echo isset($loan) ? number_format($loan->credit_amount, 0, ',', '.') : ''; ?>" required>
            </div>
            <?php if (isset($current_limit)): ?>
              <small class="form-text text-info">Límite actual: $<?php echo number_format($current_limit, 0, ',', '.'); ?></small>
            <?php endif; ?>
            <small class="form-text text-muted"></small>
          </div>
          <div class="col-12 col-lg-4 mb-3">
            <label class="small mb-1">Interés %</label>
            <div class="input-group">
              <input class="form-control currency-input" id="in_amount" type="text" name="interest_amount" placeholder="0" value="<?php echo isset($loan) ? number_format($loan->interest_amount, 2, ',', '.') : ''; ?>" required>
              <div class="input-group-append">
                <span class="input-group-text">%</span>
              </div>
            </div>
            <small class="form-text text-muted"></small>
          </div>
          <div class="col-12 col-lg-4 mb-3">
            <label class="small mb-1">Plazo en meses</label>
            <input class="form-control" id="fee" type="number" name="num_months" min="1" max="120" placeholder="0" value="<?php echo isset($loan) ? $loan->num_months : ''; ?>" required>
          </div>
        </div>

        <div class="row">
          <div class="col-12 col-lg-4 mb-3">
            <label class="small mb-1">Tipo de tasa</label>
            <select class="form-control" name="tasa_tipo" id="tasa_tipo" required>
              <option value="TNA" <?php echo isset($loan) && $loan->tasa_tipo == 'TNA' ? 'selected' : ''; ?>>TNA (Tasa Nominal Anual)</option>
              <option value="periodica" <?php echo isset($loan) && $loan->tasa_tipo == 'periodica' ? 'selected' : 'selected'; ?>>Periódica</option>
            </select>
          </div>
          <div class="col-12 col-lg-4 mb-3">
            <label class="small mb-1">Tipo de Amortización</label>
            <select class="form-control" name="amortization_type" id="amortization_type" required>
              <option value="" disabled>Seleccione...</option>
              <option value="francesa" <?php echo isset($loan) && $loan->amortization_type == 'francesa' ? 'selected' : ''; ?>>Francesa</option>
              <option value="estaunidense" <?php echo isset($loan) && $loan->amortization_type == 'estaunidense' ? 'selected' : 'selected'; ?>>Estadounidense</option>
              <option value="mixta" <?php echo isset($loan) && $loan->amortization_type == 'mixta' ? 'selected' : ''; ?>>Mixta</option>
            </select>
          </div>
          <div class="col-12 col-lg-4 mb-3">
            <label class="small mb-1">Fecha inicio cobros</label>
            <div class="input-group">
              <input class="form-control" type="text" name="payment_start_date" id="payment_start_date" placeholder="Seleccionar fecha" value="<?php
               // Calcular fecha por defecto: primer día del mes siguiente a la fecha de emisión
               if (isset($loan) && !empty($loan->payment_start_day)) {
                 // Si es edición, usar el día guardado
                 $emission_date = isset($loan) ? strtotime($loan->date) : time();
                 $year = date('Y', $emission_date);
                 $month = date('m', $emission_date);
                 $day = $loan->payment_start_day;

                 // Ajustar si el día no existe en el mes
                 $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
                 if ($day > $days_in_month) {
                   $day = $days_in_month;
                 }

                 echo sprintf('%02d/%02d/%04d', $day, $month, $year);
               } else {
                 // Para nuevos préstamos: primer día del mes siguiente
                 $emission_date = isset($loan) ? strtotime($loan->date) : time();
                 $next_month = strtotime('+1 month', $emission_date);
                 echo date('01/m/Y', $next_month);
               }
             ?>" required>
              <div class="input-group-append">
                <button class="btn btn-outline-secondary" type="button" id="payment_start_date_btn">
                  <i class="fas fa-calendar-alt"></i>
                </button>
              </div>
            </div>
            <small class="form-text text-muted">Seleccionar fecha completa para el inicio de cobros</small>
          </div>
        </div>
      </div>

      <!-- Configuración de Pagos -->
      <div class="form-section mb-4">
        <h5 class="text-primary mb-3"><i class="fas fa-cog"></i> Configuración de Pagos</h5>

        <div class="row">
          <div class="col-12 col-lg-4 mb-3">
            <label class="small mb-1">Forma de pago</label>
            <select class="form-control" name="payment_m" required>
              <option value="diario" <?php echo isset($loan) && $loan->payment_m == 'diario' ? 'selected' : ''; ?>>Diario</option>
              <option value="semanal" <?php echo isset($loan) && $loan->payment_m == 'semanal' ? 'selected' : ''; ?>>Semanal</option>
              <option value="quincenal" <?php echo isset($loan) && $loan->payment_m == 'quincenal' ? 'selected' : ''; ?>>Quincenal</option>
              <option value="mensual" <?php echo isset($loan) && $loan->payment_m == 'mensual' ? 'selected' : ''; ?>>Mensual</option>
            </select>
          </div>
          <div class="col-12 col-lg-4 mb-3">
            <label class="small mb-1">Tipo moneda</label>
            <select class="form-control" name="coin_id" required>
              <?php foreach ($coins as $coin): ?>
                <option value="<?php echo $coin->id ?>" <?php echo isset($loan) && $loan->coin_id == $coin->id ? 'selected' : ''; ?>><?php echo $coin->short_name ?></option>
              <?php endforeach ?>
            </select>
          </div>
          <div class="col-12 col-lg-4 mb-3">
            <label class="small mb-1">Fecha y hora emisión</label>
            <?php
            date_default_timezone_set('America/Bogota');
            $current_datetime = date('Y-m-d\TH:i:s');
            $display_value = isset($loan) ? date('Y-m-d\TH:i:s', strtotime($loan->date)) : $current_datetime;
            ?>
            <input class="form-control bg-light" type="datetime-local" name="date" value="<?php echo $display_value; ?>" required id="datetime-input" readonly>
            <small class="form-text text-muted">
              Zona horaria: Colombia (UTC-5) -
              <span id="current-time-display"><?php echo date('d/m/Y H:i:s'); ?></span>
              <i class="fas fa-clock ml-1"></i>
            </small>
          </div>
        </div>
      </div>

      <!-- Cálculos y Resultados -->
      <div class="form-section mb-4">
        <h5 class="text-primary mb-3"><i class="fas fa-chart-bar"></i> Cálculos y Resultados</h5>

        <div class="form-group mb-3">
          <button class="btn btn-success" type="button" id="calcular">
            <i class="fas fa-calculator mr-1"></i>Calcular
          </button>
          <button class="btn btn-info" type="button" id="ver_amortizacion" disabled>
            <i class="fas fa-eye mr-1"></i>Ver Amortización
          </button>
        </div>

        <div class="row">
          <div class="col-12 col-lg-4 mb-3">
            <label class="small mb-1">Valor por cuota</label>
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text">$</span>
              </div>
              <input class="form-control" id="valor_cuota" type="text" name="fee_amount" readonly>
            </div>
          </div>
          <div class="col-12 col-lg-4 mb-3">
            <label class="small mb-1">Valor interés</label>
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text">$</span>
              </div>
              <input class="form-control" id="valor_interes" type="text" name="calc_interest" readonly>
            </div>
          </div>
          <div class="col-12 col-lg-4 mb-3">
            <label class="small mb-1">Monto total</label>
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text">$</span>
              </div>
              <input class="form-control" id="monto_total" type="text" name="calc_total" readonly>
            </div>
          </div>
        </div>
      </div>

      <!-- Pago Personalizado -->
      <div class="form-section mb-4" id="custom_payment_section" style="display: none;">
        <h5 class="text-primary mb-3"><i class="fas fa-money-bill-wave"></i> Pago Personalizado</h5>

        <div class="alert alert-info">
          <i class="fas fa-info-circle mr-2"></i>
          Seleccione las cuotas a las que desea aplicar el pago. El monto se distribuirá secuencialmente comenzando por la primera cuota seleccionada.
        </div>

        <div class="row mb-3">
          <div class="col-12 col-lg-6 mb-3">
            <label class="small mb-1">Monto personalizado</label>
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text">$</span>
              </div>
              <input class="form-control currency-input" id="custom_amount" type="text" placeholder="0" maxlength="15">
            </div>
            <small class="form-text text-muted">Monto a aplicar a las cuotas seleccionadas</small>
          </div>
          <div class="col-12 col-lg-6 mb-3">
            <label class="small mb-1">Descripción del pago</label>
            <input class="form-control" id="payment_description" type="text" placeholder="Descripción opcional" maxlength="255">
            <small class="form-text text-muted">Opcional - máximo 255 caracteres</small>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-12">
            <label class="small mb-1">Seleccionar cuotas</label>
            <div id="quotas_container" class="border rounded p-3 bg-light">
              <div class="text-center text-muted">
                <i class="fas fa-spinner fa-spin mr-2"></i>Cargando cuotas...
              </div>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-12">
            <button class="btn btn-success btn-lg" id="process_custom_payment" disabled>
              <i class="fas fa-credit-card mr-1"></i>Procesar Pago Personalizado
            </button>
            <button class="btn btn-outline-secondary btn-lg ml-2" id="cancel_custom_payment">
              <i class="fas fa-times mr-1"></i>Cancelar
            </button>
          </div>
        </div>
      </div>

      <!-- Botones de acción -->
      <div class="form-section">
        <div class="row">
          <div class="col-12">
            <button class="btn btn-primary btn-lg" id="register_loan" type="submit" disabled>
              <i class="fas fa-save mr-1"></i><?php echo isset($is_edit) && $is_edit ? 'Actualizar Préstamo' : 'Registrar Préstamo'; ?>
            </button>
            <button class="btn btn-info btn-lg ml-2" id="show_custom_payment" style="display: none;">
              <i class="fas fa-money-bill-wave mr-1"></i>Pago Personalizado
            </button>
            <a href="<?php echo site_url('admin/loans/'); ?>" class="btn btn-secondary btn-lg ml-2">
              <i class="fas fa-times mr-1"></i>Cancelar
            </a>
          </div>
        </div>
      </div>

      <?php echo form_close() ?>

      <!-- Contenedor para la tabla de amortización -->
      <div id="amortization_table_container"></div>
  </div>
</div>

<!-- Modal para previsualizar la tabla de amortización -->
<div class="modal fade" id="amortizationModal" tabindex="-1" role="dialog" aria-labelledby="amortizationModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
    <div class="modal-content border-0 shadow-lg">

      <!-- Header Compacto -->
      <div class="modal-header bg-gradient-primary text-white py-3">
        <h5 class="modal-title mb-0" id="amortizationModalLabel">
          <i class="fas fa-file-invoice-dollar mr-2"></i>Tabla de Amortización
        </h5>
        <div class="ml-auto">
          <button type="button" class="btn btn-light btn-sm mr-2" id="download_pdf" disabled>
            <i class="fas fa-file-pdf mr-1"></i>PDF
          </button>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
      </div>

      <div class="modal-body p-0">

        <!-- Información del Préstamo - Compacta -->
        <div class="bg-light px-4 py-3 border-bottom">
          <div class="row align-items-center" id="loan_info_summary">
            <!-- Información dinámica del préstamo -->
          </div>
        </div>

        <!-- Resumen Financiero - Cards Reorganizadas -->
        <div class="px-4 py-3">
          <div class="row" id="amortization_summary">
            <!-- Primera fila: Información principal -->
            <div class="col-lg-6 col-md-6 mb-3">
              <div class="card border-left-primary shadow h-100">
                <div class="card-body">
                  <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                        <i class="fas fa-calculator mr-1"></i>Total a Pagar
                      </div>
                      <div class="h5 mb-0 font-weight-bold text-gray-800" id="summary_amount">$0</div>
                    </div>
                    <div class="col-auto">
                      <i class="fas fa-dollar-sign fa-2x text-primary"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-lg-6 col-md-6 mb-3">
              <div class="card border-left-success shadow h-100">
                <div class="card-body">
                  <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                        <i class="fas fa-coins mr-1"></i>Capital Prestado
                      </div>
                      <div class="h5 mb-0 font-weight-bold text-gray-800" id="summary_capital">$0</div>
                    </div>
                    <div class="col-auto">
                      <i class="fas fa-coins fa-2x text-success"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Segunda fila: Información adicional -->
            <div class="col-lg-4 col-md-6 mb-3">
              <div class="card border-left-warning shadow h-100">
                <div class="card-body">
                  <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                        <i class="fas fa-percentage mr-1"></i>Total Intereses
                      </div>
                      <div class="h5 mb-0 font-weight-bold text-gray-800" id="summary_interes">$0</div>
                    </div>
                    <div class="col-auto">
                      <i class="fas fa-percentage fa-2x text-warning"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-lg-4 col-md-6 mb-3">
              <div class="card border-left-info shadow h-100">
                <div class="card-body">
                  <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                        <i class="fas fa-calendar-alt mr-1"></i>Número de Cuotas
                      </div>
                      <div class="h5 mb-0 font-weight-bold text-gray-800" id="summary_cuotas">0</div>
                    </div>
                    <div class="col-auto">
                      <i class="fas fa-calendar-alt fa-2x text-info"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-lg-4 col-md-12 mb-3">
              <div class="card border-left-secondary shadow h-100">
                <div class="card-body">
                  <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
                        <i class="fas fa-chart-line mr-1"></i>Tasa de Interés
                      </div>
                      <div class="h5 mb-0 font-weight-bold text-gray-800" id="summary_rate">0%</div>
                    </div>
                    <div class="col-auto">
                      <i class="fas fa-chart-line fa-2x text-secondary"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Tabla de Amortización - Mejorada -->
        <div class="px-4 pb-3">
          <div class="bg-white rounded shadow-sm p-3">
            <div class="table-responsive">
              <table class="table table-hover mb-0" id="preview_amortization_table" style="font-size: 0.85rem;">
                <thead class="bg-gradient-primary text-white">
                  <tr class="text-center">
                    <th class="py-3 font-weight-bold">#</th>
                    <th class="py-3 font-weight-bold">Fecha de Pago</th>
                    <th class="py-3 font-weight-bold text-right">Valor Cuota</th>
                    <th class="py-3 font-weight-bold text-right">Abono Capital</th>
                    <th class="py-3 font-weight-bold text-right">Interés</th>
                    <th class="py-3 font-weight-bold text-right">Saldo Restante</th>
                  </tr>
                </thead>
                <tbody id="amortization_table_body" class="bg-light">
                  <!-- Datos de la amortización -->
                </tbody>
                <tfoot class="bg-dark text-white font-weight-bold">
                  <tr>
                    <td colspan="2" class="text-center py-3">
                      <i class="fas fa-calculator mr-2"></i>TOTALES DEL PRÉSTAMO
                    </td>
                    <td class="text-right py-3" id="total_cuota">$0</td>
                    <td class="text-right py-3" id="total_capital">$0</td>
                    <td class="text-right py-3" id="total_interes">$0</td>
                    <td class="text-center py-3">-</td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
        </div>

        <!-- Información adicional - Mejorada -->
        <div class="bg-gradient-light px-4 py-3 border-top">
          <div class="row align-items-center">
            <div class="col-md-8">
              <div class="alert alert-info border-0 mb-0 py-2">
                <i class="fas fa-info-circle text-info mr-2"></i>
                <strong>Información importante:</strong> Esta tabla muestra la proyección de pagos basada en las condiciones actuales del préstamo.
                Los valores reales pueden variar según la forma de pago y posibles modificaciones posteriores.
              </div>
            </div>
            <div class="col-md-4 text-right">
              <small class="text-muted">
                <i class="fas fa-clock mr-1"></i>
                Generado: <?php echo date('d/m/Y H:i'); ?>
              </small>
            </div>
          </div>
        </div>

      </div>

      <!-- Footer Compacto -->
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-dismiss="modal">
          <i class="fas fa-times mr-1"></i>Cerrar
        </button>
        <button type="button" class="btn btn-success btn-sm" id="download_pdf_footer" disabled>
          <i class="fas fa-file-pdf mr-1"></i>Descargar PDF
        </button>
        <button type="button" class="btn btn-primary btn-sm" id="confirm_loan">
          <i class="fas fa-check mr-1"></i>Confirmar Préstamo
        </button>
      </div>
    </div>
  </div>
</div>

<style>
.currency-input.is-invalid {
  border-color: #dc3545;
  box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}

.currency-input.is-invalid:focus {
  border-color: #dc3545;
  box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}

.form-text.text-muted {
  font-size: 0.75rem;
  color: #6c757d;
}

.form-section {
  border: 1px solid #e3e6f0;
  border-radius: 0.35rem;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
  background-color: #f8f9fc;
}

.form-section h5 {
  color: #5a5c69;
  font-weight: 600;
  margin-bottom: 1rem;
  border-bottom: 2px solid #e3e6f0;
  padding-bottom: 0.5rem;
}

/* Modal de Amortización - Diseño Mejorado */
#amortizationModal .modal-dialog {
  max-width: 95vw;
  margin: 1rem auto;
}

#amortizationModal .bg-gradient-primary {
  background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
}

#amortizationModal .bg-gradient-primary.text-white {
  color: white !important;
}

#amortizationModal .table {
  margin-bottom: 0;
  border-radius: 0.5rem;
  overflow: hidden;
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

#amortizationModal .table thead th {
  border: none;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  font-size: 0.75rem;
  padding: 1rem 0.5rem;
}

#amortizationModal .table tbody td {
  padding: 0.75rem 0.5rem;
  border: none;
  border-bottom: 1px solid #f8f9fc;
  vertical-align: middle;
}

#amortizationModal .table tbody tr:hover {
  background-color: #f8f9fc;
}

#amortizationModal .table tfoot td {
  border: none;
  font-weight: 700;
  padding: 1rem 0.5rem;
}

#amortizationModal .table-responsive {
  max-height: 60vh;
  overflow-y: auto;
  border-radius: 0.5rem;
}

#amortizationModal .card {
  border: none;
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
  border-radius: 0.75rem;
}

#amortizationModal .card-body {
  padding: 1.5rem;
}

#amortizationModal .btn {
  border-radius: 0.375rem;
  font-weight: 600;
  padding: 0.5rem 1rem;
}

@media (max-width: 768px) {
  #amortizationModal .modal-dialog {
    max-width: 98vw;
    margin: 0.5rem;
  }

  #amortizationModal .table-responsive {
    max-height: 50vh;
  }

  #amortizationModal .card-body {
    padding: 1rem;
  }

  #amortizationModal .table thead th,
  #amortizationModal .table tbody td,
  #amortizationModal .table tfoot td {
    padding: 0.5rem 0.25rem;
    font-size: 0.8rem;
  }
}

@media (max-width: 576px) {
  #amortizationModal .table thead th,
  #amortizationModal .table tbody td,
  #amortizationModal .table tfoot td {
    font-size: 0.75rem;
    padding: 0.375rem 0.125rem;
  }

  #amortizationModal .modal-header .modal-title {
    font-size: 1rem;
  }

  #amortizationModal .btn {
    font-size: 0.8rem;
    padding: 0.375rem 0.75rem;
  }
}
</style>

<!-- jQuery (necesario para Bootstrap Datepicker) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap Datepicker CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
<!-- Bootstrap Datepicker JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/locales/bootstrap-datepicker.es.min.js"></script>

<!-- Script de inicialización inmediata -->
<script>
(function() {
    'use strict';

    console.log('Script de inicialización cargado');

    // Verificar que jQuery esté disponible
    function checkJQuery() {
        if (typeof window.jQuery === 'undefined') {
            console.log('jQuery no está disponible, esperando...');
            setTimeout(checkJQuery, 50);
        } else {
            console.log('jQuery cargado correctamente');
            checkDatepicker();
        }
    }

    // Verificar que Bootstrap Datepicker esté disponible
    function checkDatepicker() {
        if (typeof window.jQuery.fn.datepicker === 'undefined') {
            console.log('Bootstrap Datepicker no está disponible, esperando...');
            setTimeout(checkDatepicker, 50);
        } else {
            console.log('Bootstrap Datepicker cargado correctamente');
            initializeDatepicker();
        }
    }

    // Inicializar datepicker
    function initializeDatepicker() {
        try {
            console.log('Inicializando datepicker...');

            // Destruir instancia previa si existe
            if ($('#payment_start_date').hasClass('hasDatepicker')) {
                $('#payment_start_date').datepicker('destroy');
            }

            $('#payment_start_date').datepicker({
                format: 'dd/mm/yyyy',
                startDate: new Date(),
                endDate: '+2y',
                autoclose: true,
                todayHighlight: true,
                todayBtn: 'linked',
                language: 'es',
                orientation: 'bottom auto',
                defaultViewDate: new Date(),
                beforeShowDay: function(date) {
                    var today = new Date();
                    today.setHours(0, 0, 0, 0);
                    return date >= today;
                }
            }).on('changeDate', function(e) {
                console.log('Fecha seleccionada:', e.format());
            });
            
            console.log('Datepicker inicializado correctamente');
        } catch (error) {
            console.error('Error inicializando datepicker:', error);
        }
    }

    // Iniciar verificación
    checkJQuery();

})();
</script>
<script>
(function($) {
    $(document).ready(function() {
        console.log('jQuery version:', $.fn.jquery);
        console.log('Bootstrap Datepicker loaded:', typeof $.fn.datepicker !== 'undefined');

        // Esperar a que Bootstrap Datepicker esté disponible con timeout
        var waitForDatepicker = function() {
            if (typeof $.fn.datepicker !== 'undefined') {
                console.log('Bootstrap Datepicker está disponible, inicializando...');
                initializeDatepicker();
            } else {
                console.log('Esperando Bootstrap Datepicker...');
                setTimeout(waitForDatepicker, 100);
            }
        };

        // Timeout de seguridad para evitar espera infinita
        var datepickerTimeout = setTimeout(function() {
            console.error('Timeout esperando Bootstrap Datepicker, intentando inicializar de todas formas...');
            if (typeof $ !== 'undefined' && typeof $.fn !== 'undefined') {
                // Intentar cargar manualmente si es posible
                console.log('Intentando carga manual de datepicker...');
                // Aquí podríamos intentar cargar dinámicamente, pero por ahora solo log
            }
        }, 5000);

        waitForDatepicker();

    // Función para inicializar el datepicker con Bootstrap Datepicker
    function initializeDatepicker() {
        try {
            // Verificar que Bootstrap Datepicker esté disponible
            if (typeof $.fn.datepicker === 'undefined') {
                console.error('Bootstrap Datepicker no está disponible');
                // Intentar cargar dinámicamente si no está disponible
                setTimeout(function() {
                    if (typeof $.fn.datepicker === 'undefined') {
                        console.error('Bootstrap Datepicker sigue sin estar disponible después del timeout');
                        $('#payment_start_date').after('<small class="text-danger">Error: Bootstrap Datepicker no se pudo cargar. Recargue la página.</small>');
                        return;
                    }
                    initializeDatepicker();
                }, 2000);
                return;
            }

            // Destruir instancia previa si existe
            if ($('#payment_start_date').hasClass('hasDatepicker')) {
                $('#payment_start_date').datepicker('destroy');
            }

            $('#payment_start_date').datepicker({
                format: 'dd/mm/yyyy',
                startDate: new Date(),
                endDate: '+2y',
                autoclose: true,
                todayHighlight: true,
                todayBtn: 'linked',
                language: 'es',
                orientation: 'bottom auto',
                defaultViewDate: new Date(), // Mostrar mes actual por defecto
                beforeShowDay: function(date) {
                    // Validación adicional: no permitir fechas pasadas
                    var today = new Date();
                    today.setHours(0, 0, 0, 0);
                    return date >= today;
                }
            }).on('changeDate', function(e) {
                // Cuando se selecciona una fecha, recalcular amortización
                console.log('Fecha seleccionada en datepicker:', e.format());
                recalculateAmortizationDates();
            }).on('show', function(e) {
                console.log('Datepicker mostrado');
            }).on('hide', function(e) {
                console.log('Datepicker ocultado');
            });
            
            console.log('Bootstrap Datepicker inicializado correctamente para #payment_start_date');
        } catch (error) {
            console.error('Error inicializando Bootstrap datepicker:', error);
            // Fallback: mostrar mensaje de error al usuario
            $('#payment_start_date').after('<small class="text-danger">Error cargando selector de fecha. Recargue la página.</small>');
        }
    }

    // Función para actualizar el reloj en tiempo real usando solo JavaScript local
    function updateCurrentTime() {
        try {
            var now = new Date();
            // Ajustar a zona horaria de Bogotá (UTC-5)
            var bogotaTime = new Date(now.getTime() - (now.getTimezoneOffset() * 60000) - (5 * 3600000));

            // Formato para el span de texto (d/m/Y H:i:s)
            var day = String(bogotaTime.getDate()).padStart(2, '0');
            var month = String(bogotaTime.getMonth() + 1).padStart(2, '0');
            var year = bogotaTime.getFullYear();
            var hours = String(bogotaTime.getHours()).padStart(2, '0');
            var minutes = String(bogotaTime.getMinutes()).padStart(2, '0');
            var seconds = String(bogotaTime.getSeconds()).padStart(2, '0');

            var formattedTime = day + '/' + month + '/' + year + ' ' + hours + ':' + minutes + ':' + seconds;
            $('#current-time-display').text(formattedTime);

            // Formato para el input datetime-local (Y-m-dTH:i:s)
            var datetimeValue = year + '-' +
                                String(bogotaTime.getMonth() + 1).padStart(2, '0') + '-' +
                                String(bogotaTime.getDate()).padStart(2, '0') + 'T' +
                                String(bogotaTime.getHours()).padStart(2, '0') + ':' +
                                String(bogotaTime.getMinutes()).padStart(2, '0') + ':' +
                                String(bogotaTime.getSeconds()).padStart(2, '0');

            // Actualizar el campo datetime-local SOLO si es un nuevo préstamo (no edit)
            var dateInput = $('#datetime-input');
            var isEdit = <?php echo isset($is_edit) && $is_edit ? 'true' : 'false'; ?>;
            if (!isEdit && !dateInput.hasClass('user-modified')) {
                dateInput.val(datetimeValue);
                console.log('Tiempo actualizado:', formattedTime);
            }

        } catch (error) {
            console.error('Error updating time:', error);
            // Fallback: mostrar hora del servidor si hay error
            $('#current-time-display').text('<?php echo date('d/m/Y H:i:s'); ?>');
        }
    }

    // Marcar el input como modificado cuando el usuario lo cambia manualmente
    $('#datetime-input').on('input change', function() {
        $(this).addClass('user-modified');
    });

    // Función para sincronizar el input datetime-local con el span de texto
    function syncDatetimeDisplay() {
        var inputValue = $('#datetime-input').val();
        if (inputValue) {
            // Convertir formato datetime-local (Y-m-dTH:i:s) a formato de texto (d/m/Y H:i:s)
            var dateObj = new Date(inputValue);
            var day = String(dateObj.getDate()).padStart(2, '0');
            var month = String(dateObj.getMonth() + 1).padStart(2, '0');
            var year = dateObj.getFullYear();
            var hours = String(dateObj.getHours()).padStart(2, '0');
            var minutes = String(dateObj.getMinutes()).padStart(2, '0');
            var seconds = String(dateObj.getSeconds()).padStart(2, '0');

            var formattedTime = day + '/' + month + '/' + year + ' ' + hours + ':' + minutes + ':' + seconds;
            $('#current-time-display').text(formattedTime);
        }
    }

    // Sincronizar cuando el usuario cambia el input datetime-local
    $('#datetime-input').on('input change', function() {
        $(this).addClass('user-modified');
        syncDatetimeDisplay();
    });

    // Actualizar el reloj cada segundo para tiempo real
    updateCurrentTime(); // Ejecutar inmediatamente
    var timeInterval = setInterval(updateCurrentTime, 1000); // Actualizar cada segundo

    // Limpiar intervalo cuando la página se descarga
    $(window).on('beforeunload', function() {
        if (timeInterval) {
            clearInterval(timeInterval);
        }
    });

    // Función para manejar el cambio en el tipo de amortización
    $('#amortization_type').on('change', function() {
        var amortizationType = $(this).val();
        var paymentSelect = $('select[name="payment_m"]');

        if (amortizationType === 'mixta') {
            // Seleccionar automáticamente "Quincenal" (15 Dias) y deshabilitar el campo
            paymentSelect.val('quincenal');
            paymentSelect.prop('disabled', true);
            // Mostrar mensaje informativo
            if (!$('#mixed_payment_warning').length) {
                paymentSelect.after('<small id="mixed_payment_warning" class="form-text text-info"><i class="fas fa-info-circle"></i> Para amortización mixta, la forma de pago se establece automáticamente en quincenal.</small>');
            }
        } else {
            // Habilitar el campo si no es mixta
            paymentSelect.prop('disabled', false);
            // Remover mensaje informativo
            $('#mixed_payment_warning').remove();
        }
    });

    // Función para recalcular fechas de amortización cuando cambian los parámetros
    function recalculateAmortizationDates() {
        var paymentStartDate = $('#payment_start_date').val();
        var startDate = $('#datetime-input').val();

        if (paymentStartDate && startDate) {
            console.log('Recalculando fechas de amortización con fecha de inicio de cobros:', paymentStartDate, 'y fecha de emisión:', startDate);

            // Validar que la fecha de inicio de cobros no sea anterior a hoy
            var today = new Date();
            today.setHours(0, 0, 0, 0);
            var selectedDate = parseDate(paymentStartDate);

            if (selectedDate < today) {
                console.warn('Fecha de inicio de cobros no puede ser anterior a hoy');
                showFieldValidationError('#payment_start_date', 'La fecha de inicio de cobros no puede ser anterior a hoy');
                return;
            }

            // Si hay una tabla de amortización visible, actualizarla automáticamente
            if ($('#amortizationModal').hasClass('show')) {
                // Simular clic en el botón "Calcular" para actualizar la tabla
                $('#calcular').trigger('click');
            }
        }
    }

    // Función auxiliar para parsear fechas en formato dd/mm/yyyy
    function parseDate(dateString) {
        var parts = dateString.split('/');
        if (parts.length === 3) {
            return new Date(parts[2], parts[1] - 1, parts[0]);
        }
        return new Date();
    }

    // Escuchar cambios en el campo "Fecha inicio cobros" para recalcular fechas
    $('#payment_start_date').on('change', function() {
        console.log('Cambio detectado en campo Fecha inicio cobros:', $(this).val());
        recalculateAmortizationDates();
    });

    // Escuchar cambios en el campo "Fecha inicio cobros" desde el datepicker
    $('#payment_start_date').on('changeDate', function(e) {
        console.log('Fecha cambiada desde datepicker:', e.format());
        recalculateAmortizationDates();
    });

    // Escuchar cambios en el campo "Fecha y hora emisión" para recalcular fechas
    $('#datetime-input').on('change', function() {
        recalculateAmortizationDates();
    });

    // Función para inicializar el datepicker con Bootstrap Datepicker
    function initializeDatepicker() {
        try {
            // Verificar que Bootstrap Datepicker esté disponible
            if (typeof $.fn.datepicker === 'undefined') {
                console.error('Bootstrap Datepicker no está disponible');
                return;
            }

            $('#payment_start_date').datepicker({
                format: 'dd/mm/yyyy',
                startDate: new Date(),
                endDate: '+2y',
                autoclose: true,
                todayHighlight: true,
                todayBtn: 'linked',
                language: 'es',
                orientation: 'bottom auto',
                beforeShowDay: function(date) {
                    // Validación adicional: no permitir fechas pasadas
                    var today = new Date();
                    today.setHours(0, 0, 0, 0);
                    return date >= today;
                }
            }).on('changeDate', function(e) {
                // Cuando se selecciona una fecha, recalcular amortización
                console.log('Fecha seleccionada en datepicker:', e.format());
                recalculateAmortizationDates();
            }).on('show', function(e) {
                console.log('Datepicker mostrado');
            }).on('hide', function(e) {
                console.log('Datepicker ocultado');
            });

            console.log('Bootstrap Datepicker inicializado correctamente para #payment_start_date');
        } catch (error) {
            console.error('Error inicializando Bootstrap datepicker:', error);
            // Fallback: mostrar mensaje de error al usuario
            $('#payment_start_date').after('<small class="text-danger">Error cargando selector de fecha. Recargue la página.</small>');
        }
    }

    // Abrir datepicker al hacer clic en el botón
    $('#payment_start_date_btn').on('click', function() {
        $('#payment_start_date').datepicker('show');
    });

    // Inicializar el datepicker inmediatamente
    initializeDatepicker();

    // Verificar que el datepicker se inicializó correctamente con múltiples intentos
    var initAttempts = 0;
    var maxAttempts = 5;

    function checkDatepickerInitialization() {
        initAttempts++;
        if (!$('#payment_start_date').hasClass('hasDatepicker')) {
            console.warn('Datepicker no se inicializó correctamente, intento ' + initAttempts + ' de ' + maxAttempts);
            if (initAttempts < maxAttempts) {
                setTimeout(function() {
                    initializeDatepicker();
                    checkDatepickerInitialization();
                }, 500 * initAttempts); // Aumentar delay progresivamente
            } else {
                console.error('Datepicker no se pudo inicializar después de ' + maxAttempts + ' intentos');
                $('#payment_start_date').after('<small class="text-danger">Error: No se pudo inicializar el selector de fecha. Recargue la página.</small>');
            }
        } else {
            console.log('Datepicker inicializado correctamente en el intento ' + initAttempts);
        }
    }

    setTimeout(checkDatepickerInitialization, 1000);

    // Ejecutar al cargar la página por si ya está seleccionado 'mixta'
    $('#amortization_type').trigger('change');

    // Función para mostrar errores de validación en el campo específico
    function showFieldValidationError(fieldSelector, message) {
        var field = $(fieldSelector);
        field.addClass('is-invalid');

        // Remover mensaje de error anterior si existe
        field.next('.invalid-feedback').remove();

        // Agregar mensaje de error
        field.after('<div class="invalid-feedback">' + message + '</div>');

        // Auto-remover después de 5 segundos
        setTimeout(function() {
            field.removeClass('is-invalid');
            field.next('.invalid-feedback').remove();
        }, 5000);
    }

    // === FUNCIONALIDAD DE PAGO PERSONALIZADO ===

    // Variables globales para pago personalizado
    var currentLoanId = <?php echo isset($loan) ? $loan->id : 'null'; ?>;
    var loanQuotas = [];

    // Mostrar/ocultar sección de pago personalizado
    $('#show_custom_payment').on('click', function() {
        if (currentLoanId) {
            loadLoanQuotas(currentLoanId);
            $('#custom_payment_section').slideDown();
            $(this).hide();
        } else {
            showAlert('Debe guardar el préstamo primero antes de realizar pagos personalizados.', 'warning');
        }
    });

    $('#cancel_custom_payment').on('click', function() {
        $('#custom_payment_section').slideUp();
        $('#show_custom_payment').show();
        resetCustomPaymentForm();
    });

    // Cargar cuotas del préstamo
    function loadLoanQuotas(loanId) {
        $('#quotas_container').html('<div class="text-center text-muted"><i class="fas fa-spinner fa-spin mr-2"></i>Cargando cuotas...</div>');

        $.ajax({
            url: '<?php echo site_url("admin/loans/ajax_get_loan_quotas"); ?>',
            type: 'POST',
            data: { loan_id: loanId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    loanQuotas = response.data;
                    renderQuotasSelection(loanQuotas);
                } else {
                    $('#quotas_container').html('<div class="alert alert-danger">' + response.error + '</div>');
                }
            },
            error: function() {
                $('#quotas_container').html('<div class="alert alert-danger">Error al cargar las cuotas del préstamo.</div>');
            }
        });
    }

    // Renderizar selección de cuotas
    function renderQuotasSelection(quotas) {
        if (!quotas || quotas.length === 0) {
            $('#quotas_container').html('<div class="alert alert-warning">No hay cuotas pendientes para este préstamo.</div>');
            return;
        }

        var html = '<div class="row">';
        html += '<div class="col-12 mb-2"><small class="text-muted">Seleccione las cuotas a las que desea aplicar el pago:</small></div>';

        quotas.forEach(function(quota) {
            var statusClass = quota.status == 0 ? 'success' : (quota.balance < quota.fee_amount ? 'warning' : 'danger');
            var statusText = quota.status == 0 ? 'Pagada' : (quota.balance < quota.fee_amount ? 'Parcial' : 'Pendiente');
            var checkboxDisabled = quota.status == 0 ? 'disabled' : '';

            html += '<div class="col-12 col-md-6 col-lg-4 mb-2">';
            html += '<div class="card border-' + statusClass + ' h-100">';
            html += '<div class="card-body p-2">';
            html += '<div class="form-check">';
            html += '<input class="form-check-input quota-checkbox" type="checkbox" value="' + quota.id + '" id="quota_' + quota.id + '" ' + checkboxDisabled + '>';
            html += '<label class="form-check-label w-100" for="quota_' + quota.id + '">';
            html += '<strong>Cuota #' + quota.num_quota + '</strong><br>';
            html += '<small class="text-muted">Fecha: ' + formatDate(quota.date) + '</small><br>';
            html += '<small>Monto: $' + formatCurrency(quota.fee_amount) + '</small><br>';
            html += '<small>Saldo: $' + formatCurrency(quota.balance) + '</small><br>';
            html += '<span class="badge badge-' + statusClass + '">' + statusText + '</span>';
            html += '</label>';
            html += '</div>';
            html += '</div>';
            html += '</div>';
            html += '</div>';
            html += '</div>';
            html += '</div>';
            html += '</div>';
            html += '</div>';
        });

        html += '</div>';
        $('#quotas_container').html(html);

        // Actualizar validaciones cuando cambian los checkboxes
        $('.quota-checkbox').on('change', updateCustomPaymentValidation);
        $('#custom_amount').on('input', updateCustomPaymentValidation);
    }

    // Actualizar validaciones del pago personalizado
    function updateCustomPaymentValidation() {
        var selectedQuotas = $('.quota-checkbox:checked').length;
        var customAmount = parseFloat($('#custom_amount').val().replace(/[^\d.,]/g, '').replace(',', '.')) || 0;

        var isValid = selectedQuotas > 0 && customAmount > 0;
        $('#process_custom_payment').prop('disabled', !isValid);

        // Calcular total pendiente de cuotas seleccionadas
        var totalPending = 0;
        $('.quota-checkbox:checked').each(function() {
            var quotaId = $(this).val();
            var quota = loanQuotas.find(q => q.id == quotaId);
            if (quota) {
                totalPending += parseFloat(quota.balance);
            }
        });

        // Mostrar información adicional
        if (selectedQuotas > 0) {
            var infoText = 'Cuotas seleccionadas: ' + selectedQuotas + ' | Total pendiente: $' + formatCurrency(totalPending);
            if (customAmount > totalPending) {
                infoText += ' | <span class="text-warning">Monto excede el total pendiente</span>';
            }
            $('#quotas_container').find('.col-12.mb-2 small').html(infoText);
        }
    }

    // Procesar pago personalizado
    $('#process_custom_payment').on('click', function() {
        var selectedQuotas = [];
        $('.quota-checkbox:checked').each(function() {
            selectedQuotas.push($(this).val());
        });

        var customAmount = parseFloat($('#custom_amount').val().replace(/[^\d.,]/g, '').replace(',', '.')) || 0;
        var paymentDescription = $('#payment_description').val().trim();

        if (selectedQuotas.length === 0) {
            showAlert('Debe seleccionar al menos una cuota.', 'warning');
            return;
        }

        if (customAmount <= 0) {
            showAlert('El monto debe ser mayor a 0.', 'warning');
            return;
        }

        // Confirmar pago
        if (!confirm('¿Está seguro de procesar el pago personalizado?\n\nMonto: $' + formatCurrency(customAmount) + '\nCuotas seleccionadas: ' + selectedQuotas.length + '\n\nEsta acción no se puede deshacer.')) {
            return;
        }

        // Deshabilitar botón mientras procesa
        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Procesando...');

        $.ajax({
            url: '<?php echo site_url("admin/loans/ajax_custom_payment"); ?>',
            type: 'POST',
            data: {
                loan_item_ids: selectedQuotas,
                custom_amount: customAmount,
                payment_description: paymentDescription
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showAlert('Pago personalizado procesado exitosamente.', 'success');
                    resetCustomPaymentForm();
                    $('#custom_payment_section').slideUp();
                    $('#show_custom_payment').show();

                    // Recargar la página para mostrar cambios
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    showAlert('Error: ' + response.error, 'danger');
                }
            },
            error: function() {
                showAlert('Error al procesar el pago personalizado.', 'danger');
            },
            complete: function() {
                $('#process_custom_payment').prop('disabled', false).html('<i class="fas fa-credit-card mr-1"></i>Procesar Pago Personalizado');
            }
        });
    });

    // Resetear formulario de pago personalizado
    function resetCustomPaymentForm() {
        $('#custom_amount').val('');
        $('#payment_description').val('');
        $('#quotas_container').html('');
        $('#process_custom_payment').prop('disabled', true);
        loanQuotas = [];
    }

    // Funciones auxiliares
    function formatCurrency(amount) {
        return new Intl.NumberFormat('es-CO').format(amount);
    }

    function formatDate(dateString) {
        var date = new Date(dateString);
        return date.toLocaleDateString('es-CO');
    }

    function showAlert(message, type) {
        var alertClass = 'alert-' + (type || 'info');
        var alertHtml = '<div class="alert ' + alertClass + ' alert-dismissible fade show" role="alert">' +
            message +
            '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
            '<span aria-hidden="true">&times;</span>' +
            '</button>' +
            '</div>';

        // Insertar al inicio del contenedor principal
        $('.card-body').prepend(alertHtml);

        // Auto-remover después de 5 segundos
        setTimeout(function() {
            $('.alert').fadeOut();
        }, 5000);
    }

    // Mostrar botón de pago personalizado solo para préstamos existentes
    <?php if (isset($is_edit) && $is_edit && isset($loan)): ?>
    $('#show_custom_payment').show();
    <?php endif; ?>

    // Validación adicional en el botón "Calcular"
    $('#calcular').on('click', function(e) {
        var amortizationType = $('#amortization_type').val();
        var paymentFrequency = $('select[name="payment_m"]').val();

        if (amortizationType === 'mixta' && paymentFrequency !== 'quincenal') {
            e.preventDefault();
            showFieldValidationError('select[name="payment_m"]', 'Para amortización mixta, solo se permite frecuencia de pago quincenal.');
            return false;
        }
    });

    // Validación adicional en el submit del formulario
    $('#loan_form').on('submit', function(e) {
        var amortizationType = $('#amortization_type').val();
        var paymentFrequency = $('select[name="payment_m"]').val();
        var paymentStartDate = $('#payment_start_date').val();
        var emissionDateTime = $('#datetime-input').val();

        // Validación de amortización mixta
        if (amortizationType === 'mixta' && paymentFrequency !== 'quincenal') {
            e.preventDefault();
            showFieldValidationError('select[name="payment_m"]', 'Para amortización mixta, solo se permite frecuencia de pago quincenal.');
            return false;
        }

        // Validación de fecha y hora de emisión
        if (!emissionDateTime) {
            e.preventDefault();
            showFieldValidationError('#datetime-input', 'La fecha y hora de emisión es requerida.');
            return false;
        }

        // Validar formato datetime-local
        var datetimePattern = /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2})?$/;
        if (!datetimePattern.test(emissionDateTime)) {
            e.preventDefault();
            showFieldValidationError('#datetime-input', 'Formato de fecha y hora inválido. Use formato: YYYY-MM-DDTHH:MM:SS');
            return false;
        }

        // Validación de fecha de inicio de cobros
        if (!paymentStartDate) {
            e.preventDefault();
            showFieldValidationError('#payment_start_date', 'La fecha de inicio de cobros es requerida.');
            return false;
        }

        // Validar formato dd/mm/yyyy
        var datePattern = /^\d{2}\/\d{2}\/\d{4}$/;
        if (!datePattern.test(paymentStartDate)) {
            e.preventDefault();
            showFieldValidationError('#payment_start_date', 'Formato de fecha inválido. Use formato: DD/MM/YYYY');
            return false;
        }

        // Validar que fecha de inicio de cobros no sea anterior a fecha de emisión (solo comparar fechas, no horas)
        if (emissionDateTime && paymentStartDate) {
            var emissionDateOnly = new Date(emissionDateTime);
            emissionDateOnly.setHours(0, 0, 0, 0); // Reset time to start of day

            var paymentDateOnly = parseDate(paymentStartDate);
            paymentDateOnly.setHours(0, 0, 0, 0); // Reset time to start of day

            if (paymentDateOnly < emissionDateOnly) {
                e.preventDefault();
                showFieldValidationError('#payment_start_date', 'La fecha de inicio de cobros no puede ser anterior a la fecha de emisión.');
                return false;
            }
        }

        // Validación adicional: fecha de emisión dentro de rango razonable (±4 horas para coincidir con backend)
        var emissionDate = new Date(emissionDateTime);
        var now = new Date();
        var fourHoursAgo = new Date(now.getTime() - (4 * 60 * 60 * 1000)); // 4 horas atrás
        var fourHoursFuture = new Date(now.getTime() + (4 * 60 * 60 * 1000)); // 4 horas adelante

        console.log('JavaScript validation - emissionDate:', emissionDate.toISOString(), 'now:', now.toISOString(), 'fourHoursAgo:', fourHoursAgo.toISOString(), 'fourHoursFuture:', fourHoursFuture.toISOString());

        if (emissionDate < fourHoursAgo) {
            console.log('JavaScript validation FAILED: emission date too old');
            e.preventDefault();
            showFieldValidationError('#datetime-input', 'La fecha y hora de emisión no puede ser anterior a hace 4 horas.');
            return false;
        }

        if (emissionDate > fourHoursFuture) {
            console.log('JavaScript validation FAILED: emission date too future');
            e.preventDefault();
            showFieldValidationError('#datetime-input', 'La fecha y hora de emisión no puede ser posterior a dentro de 4 horas.');
            return false;
        }

        console.log('JavaScript validation for emission date PASSED');

        // Validación adicional: fecha de inicio de cobros no demasiado futura (máximo 2 años)
        var paymentDate = parseDate(paymentStartDate);
        var maxFuture = new Date();
        maxFuture.setFullYear(maxFuture.getFullYear() + 2);
        if (paymentDate > maxFuture) {
            e.preventDefault();
            showFieldValidationError('#payment_start_date', 'La fecha de inicio de cobros no puede ser posterior a 2 años.');
            return false;
        }

        console.log('Formulario validado correctamente antes del envío');
    });
})(jQuery);
</script>

<!-- Script adicional para asegurar carga correcta -->
<script>
    // Verificación final de que todo está cargado
    setTimeout(function() {
        console.log('=== VERIFICACIÓN FINAL ===');
        console.log('jQuery loaded:', typeof jQuery !== 'undefined');
        console.log('Bootstrap Datepicker loaded:', typeof $.fn.datepicker !== 'undefined');
        console.log('Datepicker initialized:', $('#payment_start_date').hasClass('hasDatepicker'));

        if (!$('#payment_start_date').hasClass('hasDatepicker')) {
            console.error('CRÍTICO: Datepicker no se inicializó correctamente');
            // Intentar inicializar una vez más como último recurso
            if (typeof $.fn.datepicker !== 'undefined') {
                $('#payment_start_date').datepicker({
                    format: 'dd/mm/yyyy',
                    startDate: new Date(),
                    autoclose: true,
                    language: 'es',
                    defaultViewDate: new Date()
                });
                console.log('Intento de recuperación realizado');
            }
        } else {
            console.log('✅ Datepicker funcionando correctamente');
        }
    }, 3000);
</script>
</body>
</html>
