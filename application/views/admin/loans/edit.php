<div class="card shadow mb-4">
  <div class="card-header py-3">Crear préstamo</div>
  <div class="card-body">
    <?php if(validation_errors()) { ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo validation_errors('<li>', '</li>'); ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
    <?php } ?>
    
    <?php if($this->session->flashdata('error')) { ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $this->session->flashdata('error'); ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
    <?php } ?>
    <?php echo form_open('admin/loans/edit', 'id="loan_form"'); ?>

      <!-- Buscar cliente -->
      <div class="form-row">
        <div class="form-group col-12 col-md-8">
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

      <!-- Datos cliente -->
      <div class="form-row">
        <div class="form-group col-12 col-md-4">
          <label class="small mb-1">N. Cédula</label>
          <input class="form-control" id="dni_cst" type="text" disabled>
        </div>
        <div class="form-group col-12 col-md-4">
          <label class="small mb-1">Nombre completo</label>
          <input class="form-control" id="name_cst" type="text" disabled>
        </div>
      </div>

      <!-- Usuario asignado -->
      <div class="form-row">
        <div class="form-group col-12 col-md-4">
          <label class="small mb-1">Usuario asignado</label>
          <select class="form-control" name="assigned_user_id" id="assigned_user_id">
            <option value="">Seleccione usuario</option>
            <?php foreach ($users as $user): ?>
              <option value="<?php echo $user->id ?>"><?php echo $user->first_name . ' ' . $user->last_name ?></option>
            <?php endforeach ?>
          </select>
        </div>
      </div>

      <!-- Datos préstamo -->
      <div class="form-row">
        <div class="form-group col-12 col-md-4">
          <label class="small mb-1">Monto préstamo</label>
          <div class="input-group">
            <div class="input-group-prepend">
              <span class="input-group-text">$</span>
            </div>
            <input class="form-control currency-input monto-prestamo" id="cr_amount" type="text" name="credit_amount" placeholder="0" required>
          </div>
          <small class="form-text text-muted"></small>
        </div>
        <div class="form-group col-12 col-md-4">
          <label class="small mb-1">Interés %</label>
          <div class="input-group">
            <input class="form-control currency-input" id="in_amount" type="text" name="interest_amount" placeholder="0" required>
            <div class="input-group-append">
              <span class="input-group-text">%</span>
            </div>
          </div>
          <small class="form-text text-muted"></small>
        </div>
        <div class="form-group col-12 col-md-4">
          <label class="small mb-1">Nro cuotas</label>
          <input class="form-control" id="fee" type="number" name="num_fee" min="1" max="120" placeholder="0" required>
        </div>
      </div>
       <!-- Tipo de tasa -->
        <div class="form-row">
          <div class="form-group col-12 col-md-4">
            <label class="small mb-1">Tipo de tasa</label>
            <select class="form-control" name="tasa_tipo" id="tasa_tipo" required>
              <option value="TNA" selected="selected">TNA (Tasa Nominal Anual)</option>
              <option value="periodica">Periódica</option>
            </select>
          </div>
        </div>


      <!-- Forma de pago, moneda y fecha -->
      <div class="form-row">
        <div class="form-group col-12 col-md-4">
          <label class="small mb-1">Forma de pago</label>
          <select class="form-control" name="payment_m" required>
            <option value="diario">Diario</option>
            <option value="semanal">Semanal</option>
            <option value="quincenal">Quincenal</option>
            <option value="mensual">Mensual</option>
          </select>
        </div>
        <div class="form-group col-12 col-md-4">
          <label class="small mb-1">Tipo moneda</label>
          <select class="form-control" name="coin_id" required>
            <?php foreach ($coins as $coin): ?>
              <option value="<?php echo $coin->id ?>"><?php echo $coin->short_name ?></option>
            <?php endforeach ?>
          </select>
        </div>
        <div class="form-group col-12 col-md-4">
          <label class="small mb-1">Fecha emisión</label>
          <input class="form-control" type="date" name="date" value="<?php date_default_timezone_set('America/Bogota'); echo date('Y-m-d'); ?>">
        </div>
      </div>

      <!-- Tipo de amortización -->
<div class="form-row">
  <div class="form-group col-12 col-md-4">
    <label class="small mb-1" for="amortization_type">Tipo de Amortización</label>
    <select class="form-control" name="amortization_type" id="amortization_type" required>
      <option value="" disabled>Seleccione...</option>
      <option value="francesa" selected="selected">Francesa</option>
      <option value="estadounidense">Estadounidense</option>
      <option value="mixta">Mixta</option>
    </select>
  </div>
</div>

      <!-- Cálculos -->
      <div class="form-group">
        <button class="btn btn-success" type="button" id="calcular">Calcular</button>
        <button class="btn btn-info" type="button" id="ver_amortizacion" disabled>Ver Amortización</button>
      </div>

      <div class="form-row">
        <div class="form-group col-12 col-md-4">
          <label class="small mb-1">Valor por cuota</label>
          <div class="input-group">
            <div class="input-group-prepend">
              <span class="input-group-text">$</span>
            </div>
            <input class="form-control" id="valor_cuota" type="text" name="fee_amount" readonly>
          </div>
        </div>
        <div class="form-group col-12 col-md-4">
          <label class="small mb-1">Valor interés</label>
          <div class="input-group">
            <div class="input-group-prepend">
              <span class="input-group-text">$</span>
            </div>
            <input class="form-control" id="valor_interes" type="text" name="calc_interest" readonly>
          </div>
        </div>
        <div class="form-group col-12 col-md-4">
          <label class="small mb-1">Monto total</label>
          <div class="input-group">
            <div class="input-group-prepend">
              <span class="input-group-text">$</span>
            </div>
            <input class="form-control" id="monto_total" type="text" name="calc_total" readonly>
          </div>
        </div>
      </div>

      <!-- Botones -->
      <button class="btn btn-primary" id="register_loan" type="submit" disabled>Registrar Préstamo</button>
      <a href="<?php echo site_url('admin/loans/'); ?>" class="btn btn-dark">Cancelar</a>

      <?php echo form_close() ?>
      
      <!-- Contenedor para la tabla de amortización -->
      <div id="amortization_table_container"></div>
  </div>
</div>

<!-- Modal para previsualizar la tabla de amortización -->
<div class="modal fade" id="amortizationModal" tabindex="-1" role="dialog" aria-labelledby="amortizationModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="amortizationModalLabel">Previsualización de Amortización</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div id="amortization_summary" class="row mb-3">
          <!-- Resumen de la amortización -->
        </div>
        <div class="table-responsive">
          <table class="table table-bordered table-striped" id="preview_amortization_table">
            <thead class="thead-dark">
              <tr>
                <th>Período</th>
                <th>Fecha de Pago</th>
                <th class="text-right">Cuota</th>
                <th class="text-right">Capital</th>
                <th class="text-right">Interés</th>
                <th class="text-right">Saldo Restante</th>
              </tr>
            </thead>
            <tbody id="amortization_table_body">
              <!-- Datos de la amortización -->
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-primary" id="confirm_loan">Confirmar Préstamo</button>
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
</style>
