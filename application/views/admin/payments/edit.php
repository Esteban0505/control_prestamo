<div class="card shadow mb-4">
  <div class="card-header py-3">Pagar cuotas del prestamo </div>
  <div class="card-body">

    <?php echo form_open('admin/payments/ticket'); ?>

      <div class="form-row">
        <div class="form-group col-12 col-md-8">
          <label class="small mb-1" for="dni_c">Buscar cliente por N. Cédula</label>
          <div class="input-group">
            <input type="text" id="dni_c" class="form-control">
            <input type="hidden" name="loan_id" id="loan_id">
            <input type="hidden" name="customer_id" id="customer_id">
            <div class="input-group-append">
              <button type="button" id="btn_buscar_c" class="btn btn-primary">
                <i class="fa fa-search"></i>
              </button>
            </div>
          </div>
          <div id="customer_suggestions" class="dropdown-menu w-100" style="display: none;"></div>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group col-12 col-md-4">
          <label class="small mb-1" for="dni_cst">N. Cédula</label>
          <input class="form-control" id="dni_cst" type="text" disabled>
        </div>
        <div class="form-group col-12 col-md-4">
          <label class="small mb-1" for="name_cst">Nombre completo</label>
          <input class="form-control" id="name_cst" name="name_cst" type="text" readonly>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group col-12 col-md-4">
          <label class="small mb-1" for="credit_amount">Monto prestado</label>
          <input class="form-control" id="credit_amount" type="text" disabled>
        </div>
        <div class="form-group col-12 col-md-4">
          <label class="small mb-1" for="payment_m">Forma de pago</label>
          <input class="form-control" id="payment_m" type="text" disabled>
        </div>
        <div class="form-group col-12 col-md-4">
          <label class="small mb-1" for="coin">Tipo moneda</label>
          <input class="form-control" id="coin" name="coin" type="text" readonly>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group col-12 col-md-8">
          <table class="table table-bordered" id="quotas" width="100%" cellspacing="0">
            <thead>
              <tr>
                <th><input type="checkbox"></th>
                <th>N° cuota</th>
                <th>Fecha de pago</th>
                <th>Monto cuota</th>
                <th>Estado</th>
              </tr>
            </thead>
            <tbody>

            </tbody>
          </table>
        </div>
        <div class="form-group col-12 col-md-4 text-center">
          <label class="small mb-1" for="total_amount">Monto Total</label>
          <input class="form-control mb-3 text-center" style="font-weight: bold; font-size: 1.2rem;"  id="total_amount" type="text" disabled>

          <div class="card text-left mb-3">
            <div class="card-body">
              <h6 class="card-title">Pago manual</h6>
              <div class="form-group">
                <label class="small mb-1" for="tipo_pago">Tipo de pago</label>
                <select name="tipo_pago" id="tipo_pago" class="form-control">
                  <option value="">Seleccionar</option>
                  <option value="cuota_completa">Cuota completa</option>
                  <option value="solo_capital">Solo capital</option>
                  <option value="solo_intereses">Solo intereses</option>
                </select>
              </div>
              <div class="form-group">
                <label class="small mb-1" for="manual_payment_amount">Monto manual de pago</label>
                <input type="number" name="manual_payment_amount" id="manual_payment_amount" class="form-control" placeholder="0.00" step="0.01" min="0">
              </div>
              <div class="form-group">
                <label class="small mb-1" for="manual_payment_description">Descripción</label>
                <input type="text" name="manual_payment_description" id="manual_payment_description" class="form-control" placeholder="Pago parcial / Pago total / nota">
              </div>
              <div class="form-group">
                <label class="small mb-1" for="manual_result">Resultado</label>
                <input type="text" id="manual_result" class="form-control" readonly>
              </div>
              <button type="button" id="btn_manual_pay" class="btn btn-primary btn-block">Pagar manual</button>
            </div>
          </div>

          <div class="row">
            <div class="col-6">
              <button class="btn btn-success btn-block" id="register_loan" type="submit" disabled>Registrar Pago</button>
            </div>
            <div class="col-6">
              <a href="<?php echo site_url('admin/payments/'); ?>" class="btn btn-dark btn-block">Cancelar</a>
            </div>  
          </div>
        </div>
      </div>

    <?php echo form_close() ?>

    </div>
  </div>