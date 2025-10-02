<?php
// Los datos ya vienen preparados desde el controlador
$currencyDataJson = $countLC; // Ya es JSON desde el controlador
$loanAmountsByMonthJson = isset($loanAmountsByMonthJson) ? $loanAmountsByMonthJson : '[]';
$receivedPaymentsDataJson = isset($receivedPaymentsDataJson) ? $receivedPaymentsDataJson : '[]';
$expectedPaymentsDataJson = isset($expectedPaymentsDataJson) ? $expectedPaymentsDataJson : '[]';
?>

<!-- Contadores existentes -->
<div class="row">
    <!-- Numero de Clientes -->
    <div class="col-xl-4 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Numero clientes</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $qCts->cantidad ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-user fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Numero de Prestamos -->
    <div class="col-xl-4 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Numero Prestamos</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $qLoans->cantidad ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Numero de Cobranzas -->
    <div class="col-xl-4 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Numero cobranzas
                        </div>
                        <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800"><?php echo $qPaids->cantidad ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Formulario de filtro por fecha -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Filtrar por Rango de Fechas</h6>
            </div>
            <div class="card-body">
                <form method="GET" action="" id="dateFilterForm">
                    <div class="form-row align-items-end">
                        <div class="col-md-5 mb-2">
                            <label for="start_date">Fecha de Inicio</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date ?? ''); ?>" required>
                        </div>
                        <div class="col-md-5 mb-2">
                            <label for="end_date">Fecha de Fin</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date ?? ''); ?>" required>
                        </div>
                        <div class="col-md-2 mb-2">
                            <button type="submit" class="btn btn-primary btn-block">Filtrar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- Gráfico de pastel de Total de préstamos por tipo de moneda (reintegrado) -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Total de prestamos por tipo de moneda</h6>
    </div>
    <div class="card-body">
        <canvas id="currencyChart"></canvas>
    </div>
</div>

<!-- ** Nueva sección de gráficas de línea y barras ** -->
<div class="row mt-5">
    <!-- Nuevo Gráfico de Barras: Monto total de crédito por mes -->
    <div class="col-xl-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Monto Total de Crédito por Mes</h6>
            </div>
            <div class="card-body">
                <canvas id="loanAmountsByMonthChart"></canvas>
            </div>
        </div>
    </div>
    <!-- Nuevo Gráfico de Línea: Pagos recibidos por mes -->
    <div class="col-xl-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Pagos Recibidos por Mes</h6>
            </div>
            <div class="card-body">
                <canvas id="paymentsByMonthChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row mt-5">
    <!-- Nuevo Gráfico de Línea: Pagos Esperados vs. Pagos Recibidos -->
    <div class="col-xl-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Pagos Esperados vs. Pagos Recibidos</h6>
            </div>
            <div class="card-body">
                <canvas id="expectedVsReceivedChart"></canvas>
            </div>
        </div>
    </div>
</div>


<!-- Scripts de Chart.js y datos -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@latest/dist/Chart.min.js"></script>

<script>
    // Variables globales para las gráficas
    let currencyChart, loanAmountsByMonthChart, paymentsByMonthChart, expectedVsReceivedChart;

    // Datos PHP para las gráficas
    var currencyData = <?php echo $currencyDataJson; ?>;
    var loanAmountsByMonthData = <?php echo $loanAmountsByMonthJson; ?>;
    var receivedPaymentsData = <?php echo $receivedPaymentsDataJson; ?>;
    var expectedPaymentsData = <?php echo $expectedPaymentsDataJson; ?>;

    // Función para crear gráfica de pastel
    function createCurrencyChart(data) {
        const $currencyChart = document.querySelector("#currencyChart");
        const currencyLabels = data.label;
        const currencyValues = data.data;
        const currencyBackgroundColors = [
            'rgba(163,221,203,0.2)',
            'rgba(232,233,161,0.2)',
            'rgba(230,181,102,0.2)',
            'rgba(229,112,126,0.2)',
        ];
        const currencyBorderColors = [
            'rgba(163,221,203,1)',
            'rgba(232,233,161,1)',
            'rgba(230,181,102,1)',
            'rgba(229,112,126,1)',
        ];
        if (currencyLabels && currencyLabels.length > 0) {
            currencyChart = new Chart($currencyChart, {
                type: 'pie',
                data: {
                    labels: currencyLabels,
                    datasets: [{
                        data: currencyValues,
                        backgroundColor: currencyBackgroundColors,
                        borderColor: currencyBorderColors,
                        borderWidth: 1,
                    }]
                },
            });
        } else {
            $currencyChart.parentNode.innerHTML = '<p class="text-center">No hay datos disponibles para el rango de fechas seleccionado.</p>';
        }
    }

    // Función para crear gráfica de barras
    function createLoanAmountsChart(data) {
        const $loanAmountsByMonthChart = document.querySelector("#loanAmountsByMonthChart");
        const loanAmountsLabels = data.map(item => item.label);
        const loanAmountsValues = data.map(item => item.data);
        if (loanAmountsLabels && loanAmountsLabels.length > 0) {
            loanAmountsByMonthChart = new Chart($loanAmountsByMonthChart, {
                type: 'bar',
                data: {
                    labels: loanAmountsLabels,
                    datasets: [{
                        label: 'Monto Total de Crédito',
                        data: loanAmountsValues,
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        } else {
            $loanAmountsByMonthChart.parentNode.innerHTML = '<p class="text-center">No hay datos disponibles para el rango de fechas seleccionado.</p>';
        }
    }

    // Función para crear gráfica de línea de pagos recibidos
    function createPaymentsChart(data) {
        const $paymentsByMonthChart = document.querySelector("#paymentsByMonthChart");
        const receivedLabels = data.map(item => item.label);
        const receivedValues = data.map(item => item.data);
        if (receivedLabels && receivedLabels.length > 0) {
            paymentsByMonthChart = new Chart($paymentsByMonthChart, {
                type: 'line',
                data: {
                    labels: receivedLabels,
                    datasets: [{
                        label: 'Total de Pagos por Mes',
                        data: receivedValues,
                        fill: false,
                        borderColor: 'rgb(75, 192, 192)',
                        tension: 0.1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        } else {
            $paymentsByMonthChart.parentNode.innerHTML = '<p class="text-center">No hay datos disponibles para el rango de fechas seleccionado.</p>';
        }
    }

    // Función para crear gráfica de pagos esperados vs recibidos
    function createExpectedVsReceivedChart(receivedData, expectedData) {
        const $expectedVsReceivedChart = document.querySelector("#expectedVsReceivedChart");
        const receivedLabels = receivedData.map(item => item.label);
        const receivedValues = receivedData.map(item => item.data);
        const expectedValues = expectedData.map(item => item.data);

        if ((receivedLabels && receivedLabels.length > 0) || (expectedData && expectedData.length > 0)) {
            expectedVsReceivedChart = new Chart($expectedVsReceivedChart, {
                type: 'line',
                data: {
                    labels: receivedLabels,
                    datasets: [{
                        label: 'Pagos Recibidos',
                        data: receivedValues,
                        fill: false,
                        borderColor: 'rgb(75, 192, 192)',
                        tension: 0.1
                    }, {
                        label: 'Pagos Esperados',
                        data: expectedValues,
                        fill: false,
                        borderColor: 'rgb(255, 99, 132)',
                        tension: 0.1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        } else {
            $expectedVsReceivedChart.parentNode.innerHTML = '<p class="text-center">No hay datos disponibles para el rango de fechas seleccionado.</p>';
        }
    }

    // Inicializar gráficas con datos PHP
    createCurrencyChart(currencyData);
    createLoanAmountsChart(loanAmountsByMonthData);
    createPaymentsChart(receivedPaymentsData);
    createExpectedVsReceivedChart(receivedPaymentsData, expectedPaymentsData);

    // Función para actualizar gráficas vía AJAX
    function updateCharts() {
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;
        const url = '<?php echo base_url("admin/dashboard/ajax_get_chart_data"); ?>?start_date=' + encodeURIComponent(startDate) + '&end_date=' + encodeURIComponent(endDate);

        fetch(url)
            .then(response => response.json())
            .then(data => {
                // Destruir gráficas existentes
                if (currencyChart) currencyChart.destroy();
                if (loanAmountsByMonthChart) loanAmountsByMonthChart.destroy();
                if (paymentsByMonthChart) paymentsByMonthChart.destroy();
                if (expectedVsReceivedChart) expectedVsReceivedChart.destroy();

                // Recrear con nuevos datos
                createCurrencyChart(data.countLC);
                createLoanAmountsChart(data.loanAmountsByMonthJson);
                createPaymentsChart(data.receivedPaymentsDataJson);
                createExpectedVsReceivedChart(data.receivedPaymentsDataJson, data.expectedPaymentsDataJson);
            })
            .catch(error => console.error('Error al actualizar gráficas:', error));
    }

    // Validación del formulario de fechas
    document.getElementById('dateFilterForm').addEventListener('submit', function(e) {
        var startDate = document.getElementById('start_date').value;
        var endDate = document.getElementById('end_date').value;

        if (startDate && endDate) {
            if (new Date(startDate) > new Date(endDate)) {
                e.preventDefault();
                alert('La fecha de inicio no puede ser posterior a la fecha de fin.');
                return false;
            }
        }
    });
</script>
