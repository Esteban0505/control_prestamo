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

    <style>
      /* Estilos personalizados para select elegante */
      .custom-select-rounded {
        border-radius: 8px !important;
        border: 2px solid #e3e6f0 !important;
        padding: 0.375rem 2.5rem 0.375rem 0.75rem !important;
        background: white !important;
        transition: all 0.3s ease !important;
        font-size: 0.875rem !important;
        background-image: none !important; /* Remover flecha por defecto */
      }

      .custom-select-rounded:focus {
        border-color: #bac8f3 !important;
        box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25) !important;
        background: white !important;
      }

      .custom-select-rounded.is-valid {
        border-color: #28a745 !important;
        background: #f8fff9 !important;
      }

      .custom-select-rounded.is-invalid {
        border-color: #dc3545 !important;
        background: #fff8f8 !important;
      }

      /* Estilo personalizado para la flecha del select */
      .custom-select-rounded {
        position: relative;
      }

      .custom-select-rounded::after {
        content: '';
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        width: 0;
        height: 0;
        border-left: 6px solid transparent;
        border-right: 6px solid transparent;
        border-top: 6px solid #6c757d;
        pointer-events: none;
      }

      .custom-select-rounded:focus::after {
        border-top-color: #007bff;
      }

      .custom-select-rounded.is-valid::after {
        border-top-color: #28a745;
      }

      .custom-select-rounded.is-invalid::after {
        border-top-color: #dc3545;
      }

      /* Quitar el ícono ✓ de los selects con .is-valid o .is-invalid */
      select.form-control.is-valid,
      select.form-control.is-invalid {
        background-image: none !important;
        background-repeat: unset !important;
        background-position: unset !important;
        background-size: unset !important;
      }

      /* Mantener el borde verde/rojo */
      select.form-control.is-valid {
        border-color: #28a745 !important; /* verde Bootstrap */
      }
      select.form-control.is-invalid {
        border-color: #dc3545 !important; /* rojo Bootstrap */
      }
    </style>

    <script>
       console.log('Script de validación de clientes cargado');
       document.addEventListener('DOMContentLoaded', function() {
         console.log('DOMContentLoaded ejecutado para validación de clientes');
         const form = document.querySelector('#customer-edit-form');
         console.log('Formulario encontrado:', form);

         // Definición uniforme de campos requeridos con validación consistente
         const requiredFields = [
           { id: 'dni', name: 'dni', type: 'text', required: true },
           { id: 'first_name', name: 'first_name', type: 'text', required: true },
           { id: 'last_name', name: 'last_name', type: 'text', required: true },
           { id: 'gender', name: 'gender', type: 'select', required: true },
           { id: 'tipo_cliente', name: 'tipo_cliente', type: 'select', required: true },
           { id: 'department_id', name: 'department_id', type: 'select', required: true },
           { id: 'province_id', name: 'province_id', type: 'select', required: true },
           { id: 'district_id', name: 'district_id', type: 'select', required: true },
           { id: 'address', name: 'address', type: 'text', required: true },
           { id: 'mobile', name: 'mobile', type: 'text', required: true },
           { id: 'phone_fixed', name: 'phone_fixed', type: 'text', required: true },
           { id: 'phone', name: 'phone', type: 'email', required: true },
           { id: 'user_id', name: 'user_id', type: 'select', required: true }
         ];

         // Función centralizada para obtener mensajes de error uniformes
         function getErrorMessage(fieldId, value, fieldType) {
           console.log('getErrorMessage llamado para:', fieldId, 'valor:', value, 'tipo:', fieldType);

           // Verificar campo vacío primero
           if (!value || value.trim() === '') {
             return 'Este campo es obligatorio';
           }

           // Validaciones específicas por campo
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
             case 'phone_fixed':
               if (!/^\d{7,9}$/.test(value)) {
                 return 'El teléfono fijo debe contener solo números y tener entre 7 y 9 dígitos.';
               }
               break;
             case 'phone':
               // Validación de email básica
               const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
               if (!emailRegex.test(value)) {
                 return 'Ingrese un correo electrónico válido.';
               }
               break;
             default:
               // Para campos select, verificar que no sea vacío
               if (fieldType === 'select' && (!value || value === '')) {
                 return 'Este campo es obligatorio';
               }
               break;
           }
           return null; // Sin error
         }

         // Función para inicializar validación de campos que ya tienen valores
         function initializeFieldValidation() {
           console.log('Inicializando validación de campos existentes');

           // Lista de campos que pueden tener valores iniciales
           const fieldsWithInitialValues = ['mobile', 'phone_fixed', 'phone'];

           fieldsWithInitialValues.forEach(fieldId => {
             const element = document.getElementById(fieldId);
             if (element && element.value && element.value.trim() !== '') {
               console.log('Campo', fieldId, 'tiene valor inicial:', element.value);
               const value = element.value.trim();

               // Para campos de teléfono, solo validar si parecen tener longitud correcta
               let shouldValidate = true;
               if (fieldId === 'mobile') {
                 shouldValidate = value.length >= 9 && value.length <= 11 && /^\d+$/.test(value);
               } else if (fieldId === 'phone_fixed') {
                 shouldValidate = value.length >= 7 && value.length <= 9 && /^\d+$/.test(value);
               } else if (fieldId === 'phone') {
                 // Para email, validar formato básico
                 const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                 shouldValidate = emailRegex.test(value);
               }

               if (shouldValidate) {
                 console.log('Campo', fieldId, 'parece válido, ejecutando validación inicial');
                 const field = requiredFields.find(f => f.id === fieldId);
                 if (field) {
                   // Pequeño delay para asegurar que el DOM esté listo
                   setTimeout(() => {
                     validateField(field);
                   }, 100);
                 }
               } else {
                 console.log('Campo', fieldId, 'tiene valor inválido, saltando validación inicial para evitar errores falsos');
                 // Para valores inválidos, quitar cualquier estado de validación existente
                 element.classList.remove('is-valid', 'is-invalid');
                 const errorElement = document.getElementById('error-' + fieldId);
                 if (errorElement) {
                   errorElement.style.display = 'none';
                 }
               }
             }
           });
         }

         // Función unificada para validar un campo individual
         function validateField(field, forceValidation = false) {
           console.log('Validando campo:', field.id, '- forceValidation:', forceValidation);
           const element = document.getElementById(field.id);
           const errorElement = document.getElementById('error-' + field.id);

           if (!element) {
             console.error('Elemento no encontrado:', field.id);
             return true; // Considerar válido si no existe
           }

           if (!errorElement) {
             console.error('Elemento de error no encontrado para:', field.id);
             return true; // Considerar válido si no hay donde mostrar error
           }

           const value = element.value || '';
           const errorMessage = getErrorMessage(field.id, value, field.type);

           console.log('Campo', field.id, '- Valor:', value, '- Longitud:', value.length, '- Error:', errorMessage);

           // Para campos vacíos, no mostrar error a menos que se force la validación
           if (!value.trim() && !forceValidation) {
             errorElement.style.display = 'none';
             element.classList.remove('is-invalid', 'is-valid');
             console.log('Campo vacío, no validando:', field.id);
             return true;
           }

           if (errorMessage) {
             // Mostrar error
             errorElement.innerHTML = errorMessage;
             errorElement.style.display = 'block';
             element.classList.add('is-invalid');
             element.classList.remove('is-valid');
             console.log('Mostrando error para:', field.id, '- Mensaje:', errorMessage);
             return false;
           } else if (value.trim()) {
             // Solo marcar como válido si tiene contenido y no hay error
             errorElement.style.display = 'none';
             element.classList.remove('is-invalid');
             element.classList.add('is-valid');
             console.log('Campo válido:', field.id);
             return true;
           } else {
             // Campo vacío después de validación forzada
             errorElement.style.display = 'none';
             element.classList.remove('is-invalid', 'is-valid');
             return true;
           }
         }

         // Función para validar todo el formulario
         function validateForm(event) {
           console.log('validateForm ejecutado');
           let isFormValid = true;
           let firstInvalidField = null;

           // Validar todos los campos requeridos con validación forzada
           requiredFields.forEach(field => {
             if (!validateField(field, true)) { // Forzar validación para campos vacíos
               isFormValid = false;
               if (!firstInvalidField) {
                 firstInvalidField = field;
               }
             }
           });

           console.log('Formulario válido:', isFormValid);

           if (!isFormValid) {
             console.log('Previniendo envío del formulario - Primer campo inválido:', firstInvalidField?.id);
             event.preventDefault();

             // Enfocar primer campo inválido
             if (firstInvalidField) {
               const element = document.getElementById(firstInvalidField.id);
               if (element) {
                 element.focus();
                 element.scrollIntoView({ behavior: 'smooth', block: 'center' });
               }
             }

             return false;
           } else {
             console.log('Formulario válido, permitiendo envío');
             return true;
           }
         }

         // Adjuntar validación al envío del formulario
         if (form) {
           console.log('Adjuntando event listener al formulario');
           form.addEventListener('submit', validateForm);
         } else {
           console.error('Formulario no encontrado para adjuntar validación');
         }

         // Validación en tiempo real para todos los campos
         requiredFields.forEach(field => {
           const element = document.getElementById(field.id);
           if (element) {
             console.log('Adjuntando listeners de validación en tiempo real a:', field.id);

             // Para inputs de texto y email
             if (field.type === 'text' || field.type === 'email') {
               // Validar al perder foco solo si tiene contenido y no está vacío
               element.addEventListener('blur', function() {
                 console.log('Blur en campo:', field.id, '- valor:', element.value);
                 const value = element.value.trim();
                 if (value.length > 0) {
                   // Solo validar si el campo tiene contenido y parece completo
                   if (field.id === 'mobile' && value.length >= 9 && value.length <= 11) {
                     validateField(field);
                   } else if (field.id === 'phone_fixed' && value.length >= 7 && value.length <= 9) {
                     validateField(field);
                   } else if (field.id === 'phone') {
                     // Para email, validar si parece completo
                     if (value.includes('@') && value.includes('.')) {
                       validateField(field);
                     }
                   } else {
                     // Para otros campos, validar normalmente
                     validateField(field);
                   }
                 } else {
                   // Campo vacío, quitar estados de validación y mensajes de error
                   element.classList.remove('is-invalid', 'is-valid');
                   const errorElement = document.getElementById('error-' + field.id);
                   if (errorElement) {
                     errorElement.style.display = 'none';
                   }
                 }
               });

               // Para campos de teléfono, validación permisiva durante escritura
               if (field.id === 'mobile' || field.id === 'phone_fixed') {
                 element.addEventListener('input', function(event) {
                   const value = element.value.trim();
                   console.log('Input en campo teléfono:', field.id, '- valor:', value, '- longitud:', value.length);

                   // Solo validar si parece tener longitud suficiente y formato numérico
                   const minLength = field.id === 'mobile' ? 9 : 7;
                   const maxLength = field.id === 'mobile' ? 11 : 9;

                   if (value.length >= minLength && value.length <= maxLength && /^\d+$/.test(value)) {
                     console.log('Campo teléfono parece válido, validando:', field.id);
                     validateField(field);
                   } else if (value.length > maxLength || (value.length > 0 && !/^\d+$/.test(value))) {
                     console.log('Campo teléfono inválido, mostrando error:', field.id);
                     validateField(field);
                   } else {
                     // Para valores en progreso, quitar estados de validación
                     console.log('Campo teléfono en progreso, limpiando estados:', field.id);
                     element.classList.remove('is-invalid', 'is-valid');
                     const errorElement = document.getElementById('error-' + field.id);
                     if (errorElement) {
                       errorElement.style.display = 'none';
                     }
                   }
                 });

                 // Manejar pegado (paste) específicamente para teléfonos
                 element.addEventListener('paste', function(event) {
                   console.log('Paste detectado en campo teléfono:', field.id);
                   // Pequeño delay para permitir que el valor se actualice después del paste
                   setTimeout(() => {
                     const value = element.value.trim();
                     console.log('Valor después de paste:', value);

                     const minLength = field.id === 'mobile' ? 9 : 7;
                     const maxLength = field.id === 'mobile' ? 11 : 9;

                     if (value.length >= minLength && value.length <= maxLength && /^\d+$/.test(value)) {
                       validateField(field);
                     } else if (value.length > 0) {
                       // Si tiene contenido pero no es válido, mostrar error
                       validateField(field);
                     }
                   }, 10);
                 });
               } else if (field.id === 'phone') {
                 // Para email, validación básica durante escritura
                 element.addEventListener('input', function() {
                   const value = element.value.trim();
                   if (value.includes('@') && value.includes('.')) {
                     validateField(field);
                   } else {
                     element.classList.remove('is-invalid', 'is-valid');
                     const errorElement = document.getElementById('error-' + field.id);
                     if (errorElement) {
                       errorElement.style.display = 'none';
                     }
                   }
                 });

                 // Manejar pegado para email
                 element.addEventListener('paste', function() {
                   setTimeout(() => {
                     const value = element.value.trim();
                     if (value.includes('@') && value.includes('.')) {
                       validateField(field);
                     }
                   }, 10);
                 });
               }
             }

             // Para selects
             if (field.type === 'select') {
               element.addEventListener('change', function() {
                 console.log('Change en select:', field.id, '- valor:', element.value);
                 validateField(field);
               });
               element.addEventListener('blur', function() {
                 validateField(field);
               });
             }
           } else {
             console.warn('Elemento no encontrado para adjuntar listeners:', field.id);
           }
         });

         // Validación inicial al cargar (para campos que ya tienen valores)
         console.log('Ejecutando validación inicial');
         setTimeout(function() {
           console.log('Inicializando validación de campos con valores existentes');
           initializeFieldValidation();
         }, 100);

         console.log('Validación de clientes inicializada completamente');
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
          <select class="form-control custom-select-rounded" id="tipo_cliente" name="tipo_cliente">
            <option value="">Seleccionar tipo de cliente</option>
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
        <div class="col-lg-4 col-md-12 mb-3">
          <label class="small mb-1" for="mobile">Ingresar celular <span class="text-danger">*</span></label>
          <input class="form-control" id="mobile" type="text" name="mobile" value="<?php echo set_value('mobile', $this->input->post('mobile') ? $this->input->post('mobile') : ($customer->mobile ?? '')); ?>">
          <div class="error-message" id="error-mobile" style="display:none; color:red;"></div>
        </div>
        <div class="col-lg-4 col-md-12 mb-3">
          <label class="small mb-1" for="phone_fixed">Ingresar teléfono fijo <span class="text-danger">*</span></label>
          <input class="form-control" id="phone_fixed" type="text" name="phone_fixed" value="<?php echo set_value('phone_fixed', $this->input->post('phone_fixed') ? $this->input->post('phone_fixed') : ($customer->phone_fixed ?? '')); ?>">
          <div class="error-message" id="error-phone_fixed" style="display:none; color:red;"></div>
        </div>
        <div class="col-lg-4 col-md-12 mb-3">
          <label class="small mb-1" for="phone">Correo electrónico<span class="text-danger">*</span></label>
          <input class="form-control" id="phone" type="email" name="phone" value="<?php echo set_value('phone', $this->input->post('phone') ? $this->input->post('phone') : ($customer->phone ?? '')); ?>">
          <div class="error-message" id="error-phone" style="display:none; color:red;"></div>
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