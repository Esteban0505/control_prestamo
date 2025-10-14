<div class="card shadow mb-4">
  <div class="card-header py-3">Pagar cuotas del prestamo </div>
  <div class="card-body">

    <?php echo form_open('admin/payments/ticket'); ?>

      <!-- Panel de Búsqueda y Selección -->
      <div class="form-row mb-4">
        <div class="col-12">
          <div class="card shadow">
            <div class="card-header bg-info text-white">
              <h6 class="m-0 font-weight-bold">Búsqueda de Cliente y Configuración</h6>
            </div>
            <div class="card-body">
              <!-- Primera fila: Búsqueda y Usuario -->
              <div class="row mb-3">
                <div class="col-md-8">
                  <div class="form-group">
                    <label class="small mb-1 font-weight-bold" for="dni_c">Buscar cliente por N. Cédula</label>
                    <div class="position-relative">
                      <div class="input-group">
                        <input type="text" id="dni_c" class="form-control" placeholder="Ingresa número de cédula">
                        <input type="hidden" name="loan_id" id="loan_id">
                        <input type="hidden" name="customer_id" id="customer_id">
                        <div class="input-group-append">
                          <button type="button" id="btn_buscar_c" class="btn btn-primary">
                            <i class="fa fa-search"></i> Buscar
                          </button>
                        </div>
                      </div>
                      <div id="customer_suggestions" class="dropdown-menu w-100" style="display: none; position: absolute; top: 100%; left: 0; z-index: 1000; max-height: 200px; overflow-y: auto; box-shadow: 0 4px 8px rgba(0,0,0,0.1); border: 1px solid #ddd;"></div>
                    </div>
                  </div>
                </div>

                <div class="col-md-4">
                  <div class="form-group">
                    <label class="small mb-1 font-weight-bold" for="user_select">Usuario cobrador</label>
                    <select id="user_select" class="form-control">
                      <option value="">Seleccionar usuario</option>
                      <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user->id; ?>"><?php echo $user->first_name . ' ' . $user->last_name; ?></option>
                      <?php endforeach; ?>
                    </select>
                    <small class="form-text text-muted">Registra el pago</small>
                  </div>
                </div>
              </div>

              <!-- Segunda fila: Información del Cliente -->
              <div class="row mb-3">
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="small mb-1 font-weight-bold" for="dni_cst">N. Cédula</label>
                    <input class="form-control bg-light" id="dni_cst" type="text" disabled placeholder="Se autocompletará">
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="small mb-1 font-weight-bold" for="name_cst">Nombre completo</label>
                    <input class="form-control bg-light" id="name_cst" name="name_cst" type="text" readonly placeholder="Se autocompletará">
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="small mb-1 font-weight-bold" for="loan_status">Estado del Préstamo</label>
                    <input class="form-control bg-light" id="loan_status" type="text" disabled placeholder="Se autocompletará">
                  </div>
                </div>
              </div>

              <!-- Tercera fila: Detalles del Préstamo -->
              <div class="row">
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="small mb-1 font-weight-bold" for="credit_amount">Monto prestado</label>
                    <input class="form-control bg-light" id="credit_amount" type="text" disabled placeholder="Se autocompletará">
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="small mb-1 font-weight-bold" for="payment_m">Forma de pago</label>
                    <input class="form-control bg-light" id="payment_m" type="text" disabled placeholder="Se autocompletará">
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="small mb-1 font-weight-bold" for="coin">Tipo moneda</label>
                    <input class="form-control bg-light" id="coin" name="coin" type="text" readonly placeholder="Se autocompletará">
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Tabla de Cuotas - Ancho completo -->
      <div class="form-row mb-4">
        <div class="col-12">
          <div class="card shadow">
            <div class="card-header bg-primary text-white">
              <h6 class="m-0 font-weight-bold">Cuotas/Pagos</h6>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-striped table-bordered" id="quotas" width="100%" cellspacing="0">
                  <thead class="thead-dark">
                    <tr>
                      <th class="text-center"><input type="checkbox" id="select_all"></th>
                      <th class="text-center">N° cuota</th>
                      <th class="text-center">Fecha de pago</th>
                      <th class="text-right">Monto cuota</th>
                      <th class="text-right">Interés</th>
                      <th class="text-right">Capital</th>
                      <th class="text-right">Saldo</th>
                      <th class="text-center">Estado</th>
                    </tr>
                  </thead>
                  <tbody>
                  </tbody>
                  <tfoot>
                    <tr class="table-info">
                      <th colspan="3" class="text-right">Totales seleccionados:</th>
                      <th class="text-right" id="total_cuota">0,00</th>
                      <th class="text-right" id="total_interes">0,00</th>
                      <th class="text-right" id="total_capital">0,00</th>
                      <th class="text-right" id="total_saldo">0,00</th>
                      <th></th>
                    </tr>
                  </tfoot>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Panel de Configuración de Pago -->
      <div class="form-row">
        <div class="col-12">
          <div class="card shadow">
            <div class="card-header bg-success text-white">
              <h6 class="m-0 font-weight-bold">Configuración del Pago</h6>
            </div>
            <div class="card-body">
              <div class="row">
                <!-- Monto Total -->
                <div class="col-md-2 text-center">
                  <div class="form-group">
                    <label class="small mb-1 font-weight-bold" for="total_amount">Monto Total</label>
                    <input class="form-control text-center bg-light" style="font-weight: bold; font-size: 1.3rem; color: #28a745;" id="total_amount" type="text" disabled>
                    <small class="form-text text-muted">Monto calculado automáticamente</small>
                  </div>
                </div>

                <!-- Tipo de Pago -->
                <div class="col-md-2">
                  <div class="form-group">
                    <label class="small mb-1 font-weight-bold" for="tipo_pago">Tipo de Pago</label>
                    <select name="tipo_pago" id="tipo_pago" class="form-control" required>
                      <option value="">Seleccionar tipo de pago</option>
                      <option value="full">Cuota completa</option>
                      <option value="interest">Solo interés</option>
                      <option value="capital">Pago a capital</option>
                      <option value="both">Interés y capital</option>
                      <option value="custom">Monto personalizado</option>
                    </select>
                    <small class="form-text text-muted">Selecciona cómo aplicar el pago</small>
                  </div>
                </div>

                <!-- Monto Personalizado -->
                <div class="col-md-2" id="custom_amount_group" style="display: none;">
                  <div class="form-group">
                    <label class="small mb-1 font-weight-bold" for="custom_amount">Monto Personalizado</label>
                    <input type="number" name="custom_amount" id="custom_amount" class="form-control" placeholder="0.00" step="0.01" min="0">
                    <small class="form-text text-muted">Ingresa monto específico</small>
                  </div>
                </div>

                <!-- Tipo de Pago Personalizado -->
                <div class="col-md-2" id="custom_payment_type_group" style="display: none;">
                  <div class="form-group">
                    <label class="small mb-1 font-weight-bold" for="custom_payment_type">Aplicar a</label>
                    <select name="custom_payment_type" id="custom_payment_type" class="form-control">
                      <option value="">Seleccionar tipo</option>
                      <option value="cuota">Cuota</option>
                      <option value="interes">Interés</option>
                      <option value="capital">Capital</option>
                    </select>
                    <small class="form-text text-muted">¿Dónde aplicar el pago?</small>
                  </div>
                </div>

                <!-- Descripción -->
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="small mb-1 font-weight-bold" for="payment_description">Descripción</label>
                    <textarea name="payment_description" id="payment_description" class="form-control" rows="2" placeholder="Descripción opcional del pago..."></textarea>
                    <small class="form-text text-muted">Opcional - Documenta el pago</small>
                  </div>
                </div>
              </div>

              <!-- Botones de Acción -->
              <div class="row mt-3">
                <div class="col-12 text-center">
                  <div class="btn-group" role="group">
                    <button class="btn btn-success btn-lg px-4" id="register_loan" type="submit" disabled>
                      <i class="fa fa-check"></i> Registrar Pago
                    </button>
                    <a href="<?php echo site_url('admin/payments/'); ?>" class="btn btn-secondary btn-lg px-4">
                      <i class="fa fa-times"></i> Cancelar
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

    <?php echo form_close() ?>

    </div>
  </div>