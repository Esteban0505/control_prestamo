<div class="card shadow mb-4">
  <div class="card-header py-3"><?php echo empty($customer->first_name) ? 'Nuevo Cliente' : 'Editar Cliente'; ?></div>
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

    <div id="ajax-error" class="alert alert-danger alert-dismissible fade show" style="display:none;" role="alert">
      <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
      </button>
    </div>

    <?php echo form_open(); ?>

    <!-- Información Personal -->
    <div class="form-section mb-4">
      <h5 class="text-primary mb-3"><i class="fas fa-user"></i> Información Personal</h5>
      <div class="row">
        <div class="col-lg-3 col-md-6 mb-3">
          <label class="small mb-1" for="dni">Ingresar N. Cédula  <span class="text-danger">*</span></label>
          <input class="form-control" id="dni" type="text" name="dni" data-id="<?php echo isset($customer->id) ? $customer->id : ''; ?>" value="<?php echo set_value('dni', $this->input->post('dni') ? $this->input->post('dni') : $customer->dni); ?>">
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
          <label class="small mb-1" for="first_name">Ingresar Nombre <span class="text-danger">*</span></label>
          <input class="form-control" id="first_name" type="text" name="first_name" value="<?php echo set_value('first_name', $this->input->post('first_name') ? $this->input->post('first_name') : $customer->first_name); ?>">
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
          <label class="small mb-1" for="last_name">Ingresar Apellidos <span class="text-danger">*</span></label>
          <input class="form-control" id="last_name" type="text" name="last_name" value="<?php echo set_value('last_name', $this->input->post('last_name') ? $this->input->post('last_name') : $customer->last_name); ?>">
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
          <label class="small mb-1" for="gender">Seleccionar Género <span class="text-danger">*</span></label>
          <select class="form-control" id="gender" name="gender">
            <?php if ($customer->gender == 'none'): ?>
              <option value = "" selected>Seleccionar género</option>
            <?php endif ?>
            <option value="masculino" <?php if ($customer->gender == 'masculino') echo "selected" ?>>
              Masculino
            </option>
            <option value="femenino" <?php if ($customer->gender == 'femenino') echo "selected" ?>>
              Femenino
            </option>
          </select>
        </div>
      </div>
      <div class="row">
        <div class="col-lg-6 col-md-12 mb-3">
          <label class="small mb-1" for="tipo_cliente">Tipo de Cliente <span class="text-danger">*</span></label>
          <select class="form-control" id="tipo_cliente" name="tipo_cliente">
            <option value="">Seleccionar</option>
            <option value="normal" <?php if ($customer->tipo_cliente == 'normal') echo "selected" ?>>Normal</option>
            <option value="especial" <?php if ($customer->tipo_cliente == 'especial') echo "selected" ?>>Especial</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Ubicación -->
    <div class="form-section mb-4">
      <h5 class="text-primary mb-3"><i class="fas fa-map-marker-alt"></i> Ubicación</h5>
      <div class="row">
        <div class="col-lg-4 col-md-6 mb-3">
          <label class="small mb-1" for="department_id">Seleccionar departamento <span class="text-danger">*</span></label>
          <select class="form-control" id="department_id" name="department_id">
            <?php if ($customer->department_id == 0): ?>
              <option value = "" selected>Seleccionar departamento</option>
            <?php endif ?>
            <?php foreach ($departments as $dp): ?>
              <option value="<?php echo $dp->id ?>" <?php if ($dp->id == $customer->department_id) echo "selected" ?>><?php echo $dp->name ?></option>
            <?php endforeach ?>
          </select>
        </div>
        <div class="col-lg-4 col-md-6 mb-3">
          <label class="small mb-1" for="province_id">Seleccionar provincia <span class="text-danger">*</span></label>
          <select class="form-control" id="province_id" name="province_id">
            <?php if ($customer->province_id == 0): ?>
              <option value = "" selected>Seleccionar provincia</option>
            <?php else: ?>
              <?php foreach ($provinces as $pr): ?>
                <option value="<?php echo $pr->id ?>" <?php if ($pr->id == $customer->province_id) echo "selected" ?>><?php echo $pr->name ?></option>
              <?php endforeach ?>
            <?php endif ?>
          </select>
        </div>
        <div class="col-lg-4 col-md-12 mb-3">
          <label class="small mb-1" for="district_id">Seleccionar distrito <span class="text-danger">*</span></label>
          <select class="form-control" id="district_id" name="district_id">
            <?php if ($customer->district_id == 0): ?>
              <option value = "" selected>Seleccionar distrito</option>
            <?php else: ?>
              <?php foreach ($districts as $ds): ?>
                <option value="<?php echo $ds->id ?>" <?php if ($ds->id == $customer->district_id) echo "selected" ?>><?php echo $ds->name ?></option>
              <?php endforeach ?>
            <?php endif ?>
          </select>
        </div>
      </div>
      <div class="row">
        <div class="col-12 mb-3">
          <label class="small mb-1" for="address">Ingresar dirección <span class="text-danger">*</span></label>
          <input class="form-control" id="address" type="text" name="address" value="<?php echo set_value('address', $this->input->post('address') ? $this->input->post('address') : $customer->address); ?>">
        </div>
      </div>
    </div>

    <!-- Contacto -->
    <div class="form-section mb-4">
      <h5 class="text-primary mb-3"><i class="fas fa-phone"></i> Información de Contacto</h5>
      <div class="row">
        <div class="col-lg-6 col-md-12 mb-3">
          <label class="small mb-1" for="mobile">Ingresar celular <span class="text-danger">*</span></label>
          <input class="form-control" id="mobile" type="text" name="mobile" value="<?php echo set_value('mobile', $this->input->post('mobile') ? $this->input->post('mobile') : $customer->mobile); ?>">
        </div>
        <div class="col-lg-6 col-md-12 mb-3">
          <label class="small mb-1" for="phone">Correo electrónico<span class="text-danger">*</span></label>
          <input class="form-control" id="phone" type="email" name="phone" value="<?php echo set_value('phone', $this->input->post('phone') ? $this->input->post('phone') : $customer->phone); ?>">
        </div>
      </div>
    </div>

    <!-- Información Adicional -->
    <div class="form-section mb-4">
      <h5 class="text-primary mb-3"><i class="fas fa-building"></i> Información Adicional</h5>
      <div class="row">
        <div class="col-lg-6 col-md-12 mb-3">
          <label class="small mb-1" for="user_id">Seleccionar usuario <span class="text-danger">*</span></label>
          <select class="form-control" id="user_id" name="user_id">
            <option value="">Seleccionar usuario</option>
            <?php foreach ($users as $user): ?>
              <option value="<?php echo $user->id ?>" <?php if ($customer->user_id == $user->id) echo "selected" ?>><?php echo $user->first_name . ' ' . $user->last_name ?></option>
            <?php endforeach ?>
          </select>
        </div>
        <div class="col-lg-6 col-md-12 mb-3">
          <label class="small mb-1" for="company">Ingresar empresa</label>
          <input class="form-control" id="company" type="text" name="company" value="<?php echo set_value('company', $this->input->post('company') ? $this->input->post('company') : $customer->company); ?>">
        </div>
      </div>
    </div>
    <div class="form-section">
      <div class="row">
        <div class="col-12">
          <button class="btn btn-primary btn-lg" type="submit">
            <i class="fas fa-save"></i> <?php echo empty($customer->first_name) ? 'Registrar cliente' : 'Actualizar cliente'; ?>
          </button>
          <a href="<?php echo site_url('admin/customers/'); ?>" class="btn btn-secondary btn-lg ml-2">
            <i class="fas fa-times"></i> Cancelar
          </a>
        </div>
      </div>
    </div>

    <?php echo form_close() ?>
  </div>
</div>