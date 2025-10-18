<div class="card shadow mb-4">
  <div class="card-header py-3"><?php echo empty($customer->first_name) ? 'Nuevo Cliente' : 'Editar Cliente'; ?></div>
  <div class="card-body">

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

    <?php echo form_open('', ['id' => 'customer-edit-form']); ?>

    <script>
       console.log('Script de validación cargado');
       document.addEventListener('DOMContentLoaded', function() {
         console.log('DOMContentLoaded ejecutado');
         const form = document.querySelector('#customer-edit-form');
         console.log('Formulario encontrado:', form);
         const requiredFields = [
           { id: 'dni', name: 'dni', type: 'text' },
           { id: 'first_name', name: 'first_name', type: 'text' },
           { id: 'last_name', name: 'last_name', type: 'text' },
           { id: 'gender', name: 'gender', type: 'select' },
           { id: 'tipo_cliente', name: 'tipo_cliente', type: 'select' },
           { id: 'department_id', name: 'department_id', type: 'select' },
           { id: 'province_id', name: 'province_id', type: 'select' },
           { id: 'district_id', name: 'district_id', type: 'select' },
           { id: 'address', name: 'address', type: 'text' },
           { id: 'mobile', name: 'mobile', type: 'text' },
           { id: 'phone', name: 'phone', type: 'text' },
           { id: 'user_id', name: 'user_id', type: 'select' }
         ];

         function getErrorMessage(fieldId, value) {
           if (!value || value.trim() === '') {
             return 'Este campo es obligatorio';
           }

           switch (fieldId) {
             case 'dni':
               if (!/^\d{8,11}$/.test(value)) {
                 return 'La cédula debe contener solo números y tener entre 8 y 11 dígitos.';
               }
               break;
             case 'first_name':
               if (!/^[a-zA-Z\s]{1,10}$/.test(value)) {
                 return 'El nombre debe contener solo letras y tener máximo 10 caracteres.';
               }
               break;
             case 'last_name':
               if (!/^[a-zA-Z\s]{1,15}$/.test(value)) {
                 return 'Los apellidos deben contener solo letras y tener máximo 15 caracteres.';
               }
               break;
             case 'address':
               if (value.length > 30) {
                 return 'La dirección debe tener máximo 30 caracteres.';
               }
               break;
             case 'mobile':
               if (!/^\d{9,11}$/.test(value)) {
                 return 'El celular debe contener solo números y tener entre 9 y 11 dígitos.';
               }
               break;
             default:
               // Para otros campos obligatorios, solo verificar vacío
               break;
           }
           return null; // Sin error
         }

         function validateField(field) {
           console.log('Validando campo:', field.id);
           const element = document.getElementById(field.id);
           console.log('Elemento encontrado:', element);
           const errorElement = document.getElementById('error-' + field.id);
           console.log('Error element encontrado:', errorElement);
           let isValid = true;
           let errorMessage = null;

           if (field.type === 'select') {
             if (!element || !element.value || element.value.trim() === '') {
               errorMessage = 'Este campo es obligatorio';
               isValid = false;
             }
           } else {
             const value = element ? element.value : '';
             errorMessage = getErrorMessage(field.id, value);
             if (errorMessage) {
               isValid = false;
             }
           }
           console.log('Campo válido:', isValid);

           if (errorElement) {
             if (!isValid) {
               errorElement.innerHTML = errorMessage;
               errorElement.style.display = 'block';
               console.log('Mostrando error para:', field.id);
             } else {
               errorElement.style.display = 'none';
               console.log('Ocultando error para:', field.id);
             }
           } else {
             console.log('Error element no encontrado para:', field.id);
           }

           return isValid;
         }

         function validateForm(event) {
           console.log('validateForm ejecutado');
           let isFormValid = true;

           requiredFields.forEach(field => {
             if (!validateField(field)) {
               isFormValid = false;
             }
           });
           console.log('Formulario válido:', isFormValid);

           if (!isFormValid) {
             console.log('Previniendo envío del formulario');
             event.preventDefault();
             return false;
           } else {
             console.log('Formulario válido, permitiendo envío');
           }
         }

         console.log('Adjuntando event listener al formulario');
         form.addEventListener('submit', validateForm);

         // Validación en tiempo real para ocultar mensajes de error cuando se ingresa texto
         requiredFields.forEach(field => {
           const element = document.getElementById(field.id);
           console.log('Adjuntando listeners a:', field.id);
           element.addEventListener('input', function() {
             validateField(field);
           });
           element.addEventListener('change', function() {
             validateField(field);
           });
         });
       });
     </script>

    <!-- Información Personal -->
    <div class="form-section mb-4">
      <h5 class="text-primary mb-3"><i class="fas fa-user"></i> Información Personal</h5>
      <div class="row">
        <div class="col-lg-3 col-md-6 mb-3">
          <label class="small mb-1" for="dni">Ingresar N. Cédula  <span class="text-danger">*</span></label>
          <input class="form-control" id="dni" type="text" name="dni" data-id="<?php echo isset($customer->id) ? $customer->id : ''; ?>" value="<?php echo set_value('dni', $this->input->post('dni') ? $this->input->post('dni') : $customer->dni); ?>">
          <div class="error-message" id="error-dni" style="display:none; color:red;"></div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
          <label class="small mb-1" for="first_name">Ingresar Nombre <span class="text-danger">*</span></label>
          <input class="form-control" id="first_name" type="text" name="first_name" value="<?php echo set_value('first_name', $this->input->post('first_name') ? $this->input->post('first_name') : $customer->first_name); ?>">
          <div class="error-message" id="error-first_name" style="display:none; color:red;"></div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
          <label class="small mb-1" for="last_name">Ingresar Apellidos <span class="text-danger">*</span></label>
          <input class="form-control" id="last_name" type="text" name="last_name" value="<?php echo set_value('last_name', $this->input->post('last_name') ? $this->input->post('last_name') : $customer->last_name); ?>">
          <div class="error-message" id="error-last_name" style="display:none; color:red;"></div>
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
          <div class="error-message" id="error-gender" style="display:none; color:red;">Este campo es obligatorio</div>
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
          <div class="error-message" id="error-tipo_cliente" style="display:none; color:red;">Este campo es obligatorio</div>
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
          <div class="error-message" id="error-department_id" style="display:none; color:red;">Este campo es obligatorio</div>
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
          <div class="error-message" id="error-province_id" style="display:none; color:red;">Este campo es obligatorio</div>
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
          <div class="error-message" id="error-district_id" style="display:none; color:red;">Este campo es obligatorio</div>
        </div>
      </div>
      <div class="row">
        <div class="col-12 mb-3">
          <label class="small mb-1" for="address">Ingresar dirección <span class="text-danger">*</span></label>
          <input class="form-control" id="address" type="text" name="address" value="<?php echo set_value('address', $this->input->post('address') ? $this->input->post('address') : $customer->address); ?>">
          <div class="error-message" id="error-address" style="display:none; color:red;"></div>
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
          <div class="error-message" id="error-mobile" style="display:none; color:red;"></div>
        </div>
        <div class="col-lg-6 col-md-12 mb-3">
          <label class="small mb-1" for="phone">Correo electrónico<span class="text-danger">*</span></label>
          <input class="form-control" id="phone" type="email" name="phone" value="<?php echo set_value('phone', $this->input->post('phone') ? $this->input->post('phone') : $customer->phone); ?>">
          <div class="error-message" id="error-phone" style="display:none; color:red;">Este campo es obligatorio</div>
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
          <div class="error-message" id="error-user_id" style="display:none; color:red;">Este campo es obligatorio</div>
        </div>
        <div class="col-lg-6 col-md-12 mb-3">
          <label class="small mb-1" for="company">Observaciones</label>
          <textarea class="form-control" id="company" name="company"><?php echo set_value('company', $this->input->post('company') ? $this->input->post('company') : $customer->company); ?></textarea>
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