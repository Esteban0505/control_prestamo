# Plan de Reorganización del Módulo de Reportes

## Objetivo
Reorganizar el módulo de reportes para permitir filtrar y mostrar información detallada de cobranzas por usuario, incluyendo el progreso de cuotas cobradas.

## Análisis Actual
- El controlador `Reports.php` carga datos generales sin filtros por usuario
- El modelo `Reports_m.php` tiene métodos para obtener datos de cobranzas pero no filtrados por usuario
- La vista `index.php` muestra estadísticas generales pero no permite selección de usuario
- Hay errores de JavaScript en gráficos cuando los datos son null

## Cambios Requeridos

### 1. Controlador Reports.php
- Agregar método para manejar filtros por usuario
- Modificar método `index()` para aceptar parámetro de usuario
- Agregar método AJAX para actualizar datos dinámicamente

### 2. Modelo Reports_m.php
- Crear método `get_collections_by_user($user_id)` para obtener cobranzas por usuario
- Crear método `get_user_collection_progress($user_id)` para obtener progreso de cuotas
- Modificar métodos existentes para aceptar filtro por usuario

### 3. Vista index.php
- Agregar selector de usuario en la parte superior
- Crear sección de información detallada por usuario
- Mostrar progreso: "ha cobrado X de Y cuotas"
- Corregir errores de JavaScript en gráficos

### 4. JavaScript
- Agregar funcionalidad AJAX para actualizar datos al cambiar usuario
- Corregir manejo de datos null en gráficos
- Actualizar gráficos dinámicamente

## Estructura de Datos Esperada

### Información por Usuario:
- Total de préstamos asignados
- Total de cuotas cobradas
- Monto total cobrado
- Progreso por préstamo (cuotas cobradas/total)
- Lista de clientes con progreso

### Filtros:
- Selector de usuario (todos los usuarios activos)
- Rango de fechas (opcional)

## Implementación Paso a Paso

1. **Agregar selector de usuario** en la vista
2. **Modificar controlador** para manejar filtro por usuario
3. **Actualizar modelo** con métodos filtrados
4. **Crear vista detallada** de cobranzas por usuario
5. **Corregir JavaScript** para gráficos
6. **Agregar AJAX** para actualización dinámica
7. **Probar funcionalidad**

## Diagramas

### Flujo de Datos
```
Usuario selecciona → AJAX → Controlador → Modelo → Datos filtrados → Vista actualizada
```

### Estructura de la Vista
```
┌─────────────────────────────────────┐
│ Selector de Usuario                 │
├─────────────────────────────────────┤
│ Estadísticas Generales              │
├─────────────────────────────────────┤
│ Información Detallada por Usuario   │
│ - Total cobrado                     │
│ - Cuotas cobradas/total             │
│ - Lista de clientes                 │
├─────────────────────────────────────┤
│ Gráficos actualizados               │
└─────────────────────────────────────┘