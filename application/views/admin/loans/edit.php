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
              <input type="text" id="dni" class="form-control" placeholder="Buscar por DNI o Nombre completo">
              <input type="hidden" name="customer_id" id="customer">
              <div class="input-group-append">
                <button type="button" id="btn_buscar" class="btn btn-primary">
                  <i class="fa fa-search"></i>
                </button>
              </div>
            </div>
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
              <option value="TNA" <?php echo isset($loan) && $loan->tasa_tipo == 'TNA' ? 'selected' : 'selected'; ?>>TNA (Tasa Nominal Anual)</option>
              <option value="periodica" <?php echo isset($loan) && $loan->tasa_tipo == 'periodica' ? 'selected' : ''; ?>>Periódica</option>
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
            <label class="small mb-1">Fecha emisión</label>
            <input class="form-control" type="date" name="date" value="<?php echo isset($loan) ? $loan->date : date('Y-m-d'); ?>">
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

      <!-- Botones de acción -->
      <div class="form-section">
        <div class="row">
          <div class="col-12">
            <button class="btn btn-primary btn-lg" id="register_loan" type="submit" disabled>
              <i class="fas fa-save mr-1"></i><?php echo isset($is_edit) && $is_edit ? 'Actualizar Préstamo' : 'Registrar Préstamo'; ?>
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
