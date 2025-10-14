<?php
// Los datos ya vienen preparados desde el controlador
$currencyDataJson = $countLC; // Ya es JSON desde el controlador
$loanAmountsByMonthJson = isset($loanAmountsByMonthJson) ? $loanAmountsByMonthJson : '[]';
$receivedPaymentsDataJson = isset($receivedPaymentsDataJson) ? $receivedPaymentsDataJson : '[]';
$expectedPaymentsDataJson = isset($expectedPaymentsDataJson) ? $expectedPaymentsDataJson : '[]';
?>

<div class="card shadow mb-4">

<!-- Panel de Control y Filtros -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-filter"></i> Panel de Control - Dashboard Ejecutivo de Préstamos
                </h6>
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="clearFilters()" title="Limpiar filtros">
                        <i class="fas fa-eraser"></i> Limpiar
                    </button>
                    <button type="button" class="btn btn-outline-success btn-sm" onclick="exportToPDF()" title="Exportar a PDF">
                        <i class="fas fa-file-pdf"></i> PDF
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Filtros de Fecha -->
                <div class="row">
                    <div class="col-lg-12">
                        <form method="GET" action="" id="dateFilterForm">
                            <div class="form-row align-items-end">
                                <div class="col-md-5 mb-2">
                                    <label for="start_date" class="text-muted small">
                                        <i class="fas fa-calendar-alt"></i> Fecha de Inicio
                                    </label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date ?? ''); ?>">
                                </div>
                                <div class="col-md-5 mb-2">
                                    <label for="end_date" class="text-muted small">
                                        <i class="fas fa-calendar-alt"></i> Fecha de Fin
                                    </label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date ?? ''); ?>">
                                </div>
                                <div class="col-md-2 mb-2">
                                    <button type="submit" class="btn btn-primary btn-block" id="filterBtn">
                                        <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                                        <i class="fas fa-search"></i> Filtrar
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Dashboard Ejecutivo de Préstamos -->
<div class="container-fluid">

    <!-- Primera Fila: KPIs Principales -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="row">
                <!-- Total Clientes -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total Clientes
                                    </div>
                                    <div class="h3 mb-0 font-weight-bold text-gray-800">
                                        <?php echo number_format($qCts->cantidad ?? 0, 0, ',', '.'); ?>
                                    </div>
                                    <div class="text-xs text-muted mt-1">
                                        <i class="fas fa-users"></i> Clientes activos
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-users fa-2x text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Préstamos Activos -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Préstamos Activos
                                    </div>
                                    <div class="h3 mb-0 font-weight-bold text-gray-800">
                                        <?php echo number_format($qLoans->cantidad ?? 0, 0, ',', '.'); ?>
                                    </div>
                                    <div class="text-xs text-muted mt-1">
                                        <i class="fas fa-file-contract"></i> Activos actualmente
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-file-contract fa-2x text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cobranzas Realizadas -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-info shadow h-100">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Cobranzas
                                    </div>
                                    <div class="h3 mb-0 font-weight-bold text-gray-800">
                                        <?php echo number_format($qPaids->cantidad ?? 0, 0, ',', '.'); ?>
                                    </div>
                                    <div class="text-xs text-muted mt-1">
                                        <i class="fas fa-cash-register"></i> Pagos realizados
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-cash-register fa-2x text-info"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cartera Activa -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-warning shadow h-100">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Cartera Activa
                                    </div>
                                    <div class="h3 mb-0 font-weight-bold text-gray-800">
                                        <?php echo '$' . number_format($totalPortfolio ?? 0, 0, ',', '.'); ?>
                                    </div>
                                    <div class="text-xs text-muted mt-1">
                                        <i class="fas fa-wallet"></i> Monto pendiente por cobrar
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-wallet fa-2x text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Segunda Fila: KPIs Adicionales -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="row">
                <!-- Tasa de Morosidad -->
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card border-left-danger shadow h-100">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                        Tasa de Morosidad
                                    </div>
                                    <div class="h3 mb-0 font-weight-bold text-gray-800">
                                        <?php echo number_format($delinquencyRate ?? 0, 1, ',', '.') . '%'; ?>
                                    </div>
                                    <div class="text-xs text-muted mt-1">
                                        <i class="fas fa-exclamation-triangle"></i> Cuotas vencidas
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-exclamation-triangle fa-2x text-danger"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Promedio por Cliente -->
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card border-left-info shadow h-100">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Promedio por Cliente
                                    </div>
                                    <div class="h3 mb-0 font-weight-bold text-gray-800">
                                        <?php echo '$' . number_format($avgLoanPerCustomer ?? 0, 0, ',', '.'); ?>
                                    </div>
                                    <div class="text-xs text-muted mt-1">
                                        <i class="fas fa-user"></i> Monto promedio prestado
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-chart-line fa-2x text-info"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tasa de Cobranza -->
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Tasa de Cobranza
                                    </div>
                                    <div class="h3 mb-0 font-weight-bold text-gray-800">
                                        <?php
                                        $totalLoans = $qLoans->cantidad ?? 1;
                                        $totalPaid = $qPaids->cantidad ?? 0;
                                        $percentage = $totalLoans > 0 ? round(($totalPaid / $totalLoans) * 100, 1) : 0;
                                        echo number_format($percentage, 1, ',', '.') . '%';
                                        ?>
                                    </div>
                                    <div class="text-xs text-muted mt-1">
                                        <i class="fas fa-percentage"></i> Eficiencia de cobranza
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-percentage fa-2x text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Segunda Fila: Gráficas Principales -->
    <div class="row mb-4">
        <!-- Tendencia de Préstamos -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-success">
                        <i class="fas fa-chart-line"></i> Tendencia de Préstamos Otorgados
                    </h6>
                    <div class="text-muted small">
                        <i class="fas fa-calendar-alt"></i> Evolución Mensual
                    </div>
                </div>
                <div class="card-body">
                    <div style="height: 400px;">
                        <canvas id="loanAmountsByMonthChart"></canvas>
                    </div>
                </div>
                <div class="card-footer text-center">
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i> Monto total de créditos otorgados por mes
                    </small>
                </div>
            </div>
        </div>

        <!-- Distribución por Moneda -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-pie"></i> Distribución por Moneda
                    </h6>
                </div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    <div style="width: 100%; height: 320px;">
                        <canvas id="currencyChart"></canvas>
                    </div>
                </div>
                <div class="card-footer text-center">
                    <small class="text-muted">Composición del portafolio por tipo de moneda</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Tercera Fila: Análisis de Cobranzas -->
    <div class="row">
        <!-- Cobranzas Mensuales -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-info">
                        <i class="fas fa-money-bill-wave"></i> Cobranzas Mensuales
                    </h6>
                    <div class="badge badge-info">
                        <i class="fas fa-hand-holding-usd"></i> Recaudación
                    </div>
                </div>
                <div class="card-body">
                    <div style="height: 350px;">
                        <canvas id="paymentsByMonthChart"></canvas>
                    </div>
                </div>
                <div class="card-footer text-center">
                    <small class="text-muted">
                        <i class="fas fa-chart-line"></i> Tendencia de ingresos por cobranzas mensuales
                    </small>
                </div>
            </div>
        </div>

        <!-- Rendimiento vs Expectativas -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-warning">
                        <i class="fas fa-balance-scale"></i> Rendimiento vs Expectativas
                    </h6>
                    <div class="badge badge-warning">
                        <i class="fas fa-chart-area"></i> Comparativo
                    </div>
                </div>
                <div class="card-body">
                    <div style="height: 350px;">
                        <canvas id="expectedVsReceivedChart"></canvas>
                    </div>
                </div>
                <div class="card-footer text-center">
                    <small class="text-muted">
                        <i class="fas fa-target"></i> Cobranzas efectivas vs proyecciones esperadas
                    </small>
                </div>
            </div>
        </div>
    </div>

</div>



<!-- Scripts de Chart.js y jsPDF -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@latest/dist/Chart.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<script>
    // Variables globales para las gráficas
    let currencyChart, loanAmountsByMonthChart, paymentsByMonthChart, expectedVsReceivedChart;

    // Datos PHP para las gráficas
    var currencyData = <?php echo $currencyDataJson; ?>;
    var loanAmountsByMonthData = <?php echo $loanAmountsByMonthJson; ?>;
    var receivedPaymentsData = <?php echo $receivedPaymentsDataJson; ?>;
    var expectedPaymentsData = <?php echo $expectedPaymentsDataJson; ?>;

    console.log('Datos iniciales cargados:');
    console.log('currencyData:', currencyData);
    console.log('loanAmountsByMonthData:', loanAmountsByMonthData);
    console.log('receivedPaymentsData:', receivedPaymentsData);
    console.log('expectedPaymentsData:', expectedPaymentsData);

    // Función para crear gráfica de pastel
    function createCurrencyChart(data) {
        const $currencyChart = document.querySelector("#currencyChart");
        if (!$currencyChart) {
            console.error('Elemento currencyChart no encontrado');
            return;
        }

        const currencyLabels = data && data.label ? data.label : [];
        const currencyValues = data && data.data ? data.data : [];
        const currencyBackgroundColors = [
            'rgba(0, 123, 255, 0.8)',   // Azul primario
            'rgba(40, 167, 69, 0.8)',   // Verde éxito
            'rgba(255, 193, 7, 0.8)',   // Amarillo warning
            'rgba(220, 53, 69, 0.8)',   // Rojo danger
            'rgba(23, 162, 184, 0.8)',  // Cyan info
            'rgba(108, 117, 125, 0.8)', // Gris secondary
        ];
        const currencyBorderColors = [
            'rgba(0, 123, 255, 1)',
            'rgba(40, 167, 69, 1)',
            'rgba(255, 193, 7, 1)',
            'rgba(220, 53, 69, 1)',
            'rgba(23, 162, 184, 1)',
            'rgba(108, 117, 125, 1)',
        ];

        if (currencyLabels && currencyLabels.length > 0) {
            currencyChart = new Chart($currencyChart, {
                type: 'doughnut',
                data: {
                    labels: currencyLabels,
                    datasets: [{
                        data: currencyValues,
                        backgroundColor: currencyBackgroundColors.slice(0, currencyLabels.length),
                        borderColor: currencyBorderColors.slice(0, currencyLabels.length),
                        borderWidth: 2,
                        hoverBorderWidth: 3,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((context.parsed / total) * 100).toFixed(1);
                                    return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                                }
                            }
                        }
                    },
                    cutout: '60%',
                }
            });
        } else {
            $currencyChart.parentNode.innerHTML = '<div class="text-center p-4"><i class="fas fa-chart-pie fa-3x text-muted mb-3"></i><p class="text-muted">No hay datos disponibles para el rango de fechas seleccionado.</p></div>';
        }
    }

    // Función para crear gráfica de barras
    function createLoanAmountsChart(data) {
        const $loanAmountsByMonthChart = document.querySelector("#loanAmountsByMonthChart");
        if (!$loanAmountsByMonthChart) {
            console.error('Elemento loanAmountsByMonthChart no encontrado');
            return;
        }

        const loanAmountsLabels = data && Array.isArray(data) ? data.map(item => item.label) : [];
        const loanAmountsValues = data && Array.isArray(data) ? data.map(item => item.data) : [];

        if (loanAmountsLabels && loanAmountsLabels.length > 0) {
            // Crear gradiente para las barras
            const ctx = $loanAmountsByMonthChart.getContext('2d');
            const gradient = ctx.createLinearGradient(0, 0, 0, 400);
            gradient.addColorStop(0, 'rgba(0, 123, 255, 0.8)');
            gradient.addColorStop(1, 'rgba(0, 123, 255, 0.3)');

            loanAmountsByMonthChart = new Chart($loanAmountsByMonthChart, {
                type: 'bar',
                data: {
                    labels: loanAmountsLabels,
                    datasets: [{
                        label: 'Monto de Préstamos ($)',
                        data: loanAmountsValues,
                        backgroundColor: gradient,
                        borderColor: 'rgba(0, 123, 255, 1)',
                        borderWidth: 2,
                        borderRadius: 4,
                        borderSkipped: false,
                        hoverBackgroundColor: 'rgba(0, 123, 255, 0.9)',
                        hoverBorderColor: 'rgba(0, 123, 255, 1)',
                        hoverBorderWidth: 3,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: 'white',
                            bodyColor: 'white',
                            callbacks: {
                                label: function(context) {
                                    return 'Monto: $' + context.parsed.y.toLocaleString('es-CO', {
                                        minimumFractionDigits: 0,
                                        maximumFractionDigits: 0
                                    });
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toLocaleString('es-CO', {
                                        minimumFractionDigits: 0,
                                        maximumFractionDigits: 0
                                    });
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    animation: {
                        duration: 2000,
                        easing: 'easeInOutQuart'
                    }
                }
            });
        } else {
            $loanAmountsByMonthChart.parentNode.innerHTML = '<div class="text-center p-4"><i class="fas fa-chart-bar fa-3x text-muted mb-3"></i><p class="text-muted">No hay datos de préstamos disponibles para el rango de fechas seleccionado.</p></div>';
        }
    }

    // Función para crear gráfica de línea de pagos recibidos
    function createPaymentsChart(data) {
        const $paymentsByMonthChart = document.querySelector("#paymentsByMonthChart");
        if (!$paymentsByMonthChart) {
            console.error('Elemento paymentsByMonthChart no encontrado');
            return;
        }

        const receivedLabels = data && Array.isArray(data) ? data.map(item => item.label) : [];
        const receivedValues = data && Array.isArray(data) ? data.map(item => item.data) : [];

        if (receivedLabels && receivedLabels.length > 0) {
            // Crear gradiente para la línea
            const ctx = $paymentsByMonthChart.getContext('2d');
            const gradient = ctx.createLinearGradient(0, 0, 0, 400);
            gradient.addColorStop(0, 'rgba(23, 162, 184, 0.4)');
            gradient.addColorStop(1, 'rgba(23, 162, 184, 0.1)');

            paymentsByMonthChart = new Chart($paymentsByMonthChart, {
                type: 'line',
                data: {
                    labels: receivedLabels,
                    datasets: [{
                        label: 'Cobranzas Mensuales ($)',
                        data: receivedValues,
                        fill: true,
                        backgroundColor: gradient,
                        borderColor: 'rgba(23, 162, 184, 1)',
                        borderWidth: 3,
                        pointBackgroundColor: 'rgba(23, 162, 184, 1)',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 6,
                        pointHoverRadius: 8,
                        pointHoverBackgroundColor: 'rgba(23, 162, 184, 1)',
                        pointHoverBorderColor: '#fff',
                        pointHoverBorderWidth: 3,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: 'white',
                            bodyColor: 'white',
                            callbacks: {
                                label: function(context) {
                                    return 'Cobranzas: $' + context.parsed.y.toLocaleString('es-CO', {
                                        minimumFractionDigits: 0,
                                        maximumFractionDigits: 0
                                    });
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toLocaleString('es-CO', {
                                        minimumFractionDigits: 0,
                                        maximumFractionDigits: 0
                                    });
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    animation: {
                        duration: 2000,
                        easing: 'easeInOutQuart'
                    }
                }
            });
        } else {
            $paymentsByMonthChart.parentNode.innerHTML = '<div class="text-center p-4"><i class="fas fa-chart-line fa-3x text-muted mb-3"></i><p class="text-muted">No hay datos de cobranzas disponibles para el rango de fechas seleccionado.</p></div>';
        }
    }

    // Función para crear gráfica de pagos esperados vs recibidos
    function createExpectedVsReceivedChart(receivedData, expectedData) {
        const $expectedVsReceivedChart = document.querySelector("#expectedVsReceivedChart");
        if (!$expectedVsReceivedChart) {
            console.error('Elemento expectedVsReceivedChart no encontrado');
            return;
        }

        const receivedLabels = receivedData && Array.isArray(receivedData) ? receivedData.map(item => item.label) : [];
        const receivedValues = receivedData && Array.isArray(receivedData) ? receivedData.map(item => item.data) : [];
        const expectedValues = expectedData && Array.isArray(expectedData) ? expectedData.map(item => item.data) : [];

        if ((receivedLabels && receivedLabels.length > 0) || (expectedData && expectedData.length > 0)) {
            expectedVsReceivedChart = new Chart($expectedVsReceivedChart, {
                type: 'line',
                data: {
                    labels: receivedLabels,
                    datasets: [{
                        label: 'Pagos Recibidos',
                        data: receivedValues,
                        fill: true,
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        borderColor: 'rgba(40, 167, 69, 1)',
                        borderWidth: 3,
                        pointBackgroundColor: 'rgba(40, 167, 69, 1)',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        pointHoverBackgroundColor: 'rgba(40, 167, 69, 1)',
                        tension: 0.4
                    }, {
                        label: 'Pagos Esperados',
                        data: expectedValues,
                        fill: true,
                        backgroundColor: 'rgba(255, 193, 7, 0.1)',
                        borderColor: 'rgba(255, 193, 7, 1)',
                        borderWidth: 3,
                        borderDash: [5, 5],
                        pointBackgroundColor: 'rgba(255, 193, 7, 1)',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        pointHoverBackgroundColor: 'rgba(255, 193, 7, 1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 20
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: 'white',
                            bodyColor: 'white',
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': $' + context.parsed.y.toLocaleString('es-CO', {
                                        minimumFractionDigits: 0,
                                        maximumFractionDigits: 0
                                    });
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toLocaleString('es-CO', {
                                        minimumFractionDigits: 0,
                                        maximumFractionDigits: 0
                                    });
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    animation: {
                        duration: 2000,
                        easing: 'easeInOutQuart'
                    }
                }
            });
        } else {
            $expectedVsReceivedChart.parentNode.innerHTML = '<div class="text-center p-4"><i class="fas fa-balance-scale fa-3x text-muted mb-3"></i><p class="text-muted">No hay datos de comparación disponibles para el rango de fechas seleccionado.</p></div>';
        }
    }

    // Inicializar gráficas con datos PHP
    createCurrencyChart(currencyData);
    createLoanAmountsChart(loanAmountsByMonthData);
    createPaymentsChart(receivedPaymentsData);
    createExpectedVsReceivedChart(receivedPaymentsData, expectedPaymentsData);

    // Función para convertir fecha yyyy-mm-dd a dd/mm/aaaa
    function convertToDisplay(dateStr) {
        if (!dateStr || dateStr.length !== 10) return '';
        const parts = dateStr.split('-');
        if (parts.length !== 3) return '';
        return parts[2] + '/' + parts[1] + '/' + parts[0];
    }

    // Función para convertir fecha dd/mm/aaaa a yyyy-mm-dd
    function convertToISO(dateStr) {
        if (!dateStr || dateStr.length !== 10) return '';
        const parts = dateStr.split('/');
        if (parts.length !== 3) return '';
        return parts[2] + '-' + parts[1].padStart(2, '0') + '-' + parts[0].padStart(2, '0');
    }

    // Función para actualizar gráficas vía AJAX
    function updateCharts() {
        const startDateInput = document.getElementById('start_date').value;
        const endDateInput = document.getElementById('end_date').value;

        console.log('Actualizando gráficas con fechas:', startDateInput, 'a', endDateInput);

        // Validación básica
        if (!startDateInput || !endDateInput) {
            showNotification('Por favor seleccione ambas fechas (inicio y fin).', 'warning');
            return;
        }

        // Para campos tipo date, las fechas ya vienen en formato yyyy-mm-dd
        const startDate = startDateInput;
        const endDate = endDateInput;

        // Validación de lógica de fechas
        if (new Date(startDate) > new Date(endDate)) {
            showNotification('La fecha de inicio no puede ser posterior a la fecha de fin.', 'danger');
            return;
        }

        // Validación de fecha futura
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        if (new Date(endDate) > today) {
            showNotification('La fecha de fin no puede ser posterior a hoy.', 'danger');
            return;
        }

        console.log('Fechas para backend:', startDate, 'a', endDate);

        // Mostrar indicador de carga
        const filterBtn = document.getElementById('filterBtn');
        const spinner = filterBtn.querySelector('.spinner-border');
        const originalText = filterBtn.innerHTML;
        filterBtn.disabled = true;
        filterBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Actualizando...';

        const url = '<?php echo base_url("admin/dashboard/ajax_get_chart_data"); ?>?start_date=' + encodeURIComponent(startDate) + '&end_date=' + encodeURIComponent(endDate);

        console.log('URL de petición:', url);

        fetch(url)
            .then(response => {
                console.log('Respuesta del servidor:', response);
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                console.log('Datos recibidos del servidor:', data);

                // Verificar que los datos sean válidos
                if (!data || typeof data !== 'object') {
                    throw new Error('Los datos recibidos no son válidos');
                }

                // Limpiar contenedores de gráficas de manera más segura
                const containers = [
                    'currencyChart',
                    'loanAmountsByMonthChart',
                    'paymentsByMonthChart',
                    'expectedVsReceivedChart'
                ];

                containers.forEach(containerId => {
                    const container = document.getElementById(containerId);
                    if (container) {
                        console.log('Limpiando contenedor:', containerId);
                        // Destruir instancia de Chart.js si existe
                        if (window[containerId + 'Chart']) {
                            window[containerId + 'Chart'].destroy();
                            window[containerId + 'Chart'] = null;
                        }
                        // Recrear el canvas directamente
                        const parentContainer = container.parentElement;
                        const newCanvas = document.createElement('canvas');
                        newCanvas.id = containerId;
                        newCanvas.style.width = '100%';
                        newCanvas.style.height = '100%';
                        parentContainer.replaceChild(newCanvas, container);
                    } else {
                        console.warn('Contenedor no encontrado:', containerId);
                    }
                });

                // Recrear con nuevos datos después de un pequeño delay
                setTimeout(() => {
                    try {
                        console.log('Datos AJAX recibidos:', data);

                        // Parsear datos JSON si vienen como string
                        let parsedData = {};
                        if (typeof data.countLC === 'string') {
                            parsedData.countLC = JSON.parse(data.countLC);
                        } else {
                            parsedData.countLC = data.countLC;
                        }

                        if (typeof data.loanAmountsByMonthJson === 'string') {
                            parsedData.loanAmountsByMonthJson = JSON.parse(data.loanAmountsByMonthJson);
                        } else {
                            parsedData.loanAmountsByMonthJson = data.loanAmountsByMonthJson;
                        }

                        if (typeof data.receivedPaymentsDataJson === 'string') {
                            parsedData.receivedPaymentsDataJson = JSON.parse(data.receivedPaymentsDataJson);
                        } else {
                            parsedData.receivedPaymentsDataJson = data.receivedPaymentsDataJson;
                        }

                        if (typeof data.expectedPaymentsDataJson === 'string') {
                            parsedData.expectedPaymentsDataJson = JSON.parse(data.expectedPaymentsDataJson);
                        } else {
                            parsedData.expectedPaymentsDataJson = data.expectedPaymentsDataJson;
                        }

                        console.log('Datos parseados:', parsedData);

                        console.log('Creando gráfica de moneda...');
                        createCurrencyChart(parsedData.countLC);

                        console.log('Creando gráfica de préstamos...');
                        createLoanAmountsChart(parsedData.loanAmountsByMonthJson);

                        console.log('Creando gráfica de pagos...');
                        createPaymentsChart(parsedData.receivedPaymentsDataJson);

                        console.log('Creando gráfica comparativa...');
                        createExpectedVsReceivedChart(parsedData.receivedPaymentsDataJson, parsedData.expectedPaymentsDataJson);

                        console.log('Todas las gráficas creadas exitosamente');

                        // Restaurar botón
                        filterBtn.disabled = false;
                        filterBtn.innerHTML = originalText;

                        // Mostrar mensaje de éxito
                        showNotification('Gráficas actualizadas correctamente', 'success');

                    } catch (chartError) {
                        console.error('Error al crear gráficas:', chartError);
                        showNotification('Error al procesar las gráficas: ' + chartError.message, 'danger');

                        // Restaurar botón
                        filterBtn.disabled = false;
                        filterBtn.innerHTML = originalText;
                    }
                }, 500);
            })
            .catch(error => {
                console.error('Error al actualizar gráficas:', error);
                showNotification('Error al actualizar las gráficas: ' + error.message, 'danger');

                // Restaurar botón
                filterBtn.disabled = false;
                filterBtn.innerHTML = originalText;
            });
    }

    // Función para mostrar notificaciones
    function showNotification(message, type = 'info') {
        // Crear elemento de notificación
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px; max-width: 500px;';
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-triangle' : type === 'warning' ? 'exclamation-circle' : 'info-circle'}"></i> ${message}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        `;

        document.body.appendChild(notification);

        // Auto-remover después de 5 segundos
        setTimeout(() => {
            if (notification.parentNode) {
                $(notification).alert('close');
            }
        }, 5000);
    }

    // Función para limpiar filtros con confirmación
    function clearFilters() {
        if (confirm('¿Está seguro de que desea limpiar todos los filtros? Esto mostrará todos los datos disponibles.')) {
            // Mostrar indicador de carga
            const clearBtn = document.querySelector('button[onclick="clearFilters()"]');
            const originalText = clearBtn.innerHTML;
            clearBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Limpiando...';
            clearBtn.disabled = true;

            // Limpiar campos
            document.getElementById('start_date').value = '';
            document.getElementById('end_date').value = '';

            // Recargar página sin parámetros
            window.location.href = '<?php echo base_url("admin/dashboard"); ?>';
        }
    }

    // Función para exportar a PDF
    function exportToPDF() {
        // Mostrar indicador de carga
        const pdfBtn = document.querySelector('button[onclick="exportToPDF()"]');
        const originalText = pdfBtn.innerHTML;
        pdfBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando PDF...';
        pdfBtn.disabled = true;

        try {
            // Capturar todas las gráficas como imágenes
            const charts = [
                { id: 'currencyChart', title: 'Distribución por Moneda' },
                { id: 'loanAmountsByMonthChart', title: 'Tendencia de Préstamos Otorgados' },
                { id: 'paymentsByMonthChart', title: 'Cobranzas Mensuales' },
                { id: 'expectedVsReceivedChart', title: 'Rendimiento vs Expectativas' }
            ];

            // Crear documento PDF
            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF('p', 'mm', 'a4');

            // Configurar fuente y colores
            pdf.setTextColor(40, 40, 40);

            // Título del documento con mejor formato
            pdf.setFontSize(22);
            pdf.setFont('helvetica', 'bold');
            pdf.text('Dashboard Ejecutivo de Préstamos', 20, 25);

            // Subtítulo
            pdf.setFontSize(12);
            pdf.setFont('helvetica', 'normal');
            pdf.text('Sistema de Gestión Financiera', 20, 35);

            // Información del período con mejor formato
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            pdf.setFontSize(11);
            pdf.text(`Período del Reporte: ${startDate || 'Sin filtro'} - ${endDate || 'Sin filtro'}`, 20, 50);
            pdf.text(`Fecha de Generación: ${new Date().toLocaleString('es-ES')}`, 20, 58);
            pdf.text(`Usuario: Administrador del Sistema`, 20, 66);

            // Línea separadora
            pdf.setLineWidth(0.5);
            pdf.line(20, 75, 190, 75);

            let yPosition = 85;

            // Procesar cada gráfica
            charts.forEach((chartInfo, index) => {
                const canvas = document.getElementById(chartInfo.id);
                if (canvas && canvas.toDataURL) {
                    try {
                        // Verificar si necesitamos nueva página
                        if (yPosition > 200) {
                            pdf.addPage();
                            yPosition = 25;

                            // Header en nueva página
                            pdf.setFontSize(14);
                            pdf.setFont('helvetica', 'bold');
                            pdf.text('Dashboard Ejecutivo de Préstamos (Continuación)', 20, yPosition);
                            yPosition += 15;
                        }

                        // Título de la gráfica
                        pdf.setFontSize(14);
                        pdf.setFont('helvetica', 'bold');
                        pdf.text(`${index + 1}. ${chartInfo.title}`, 20, yPosition);
                        yPosition += 8;

                        // Descripción breve
                        pdf.setFontSize(9);
                        pdf.setFont('helvetica', 'italic');
                        const descriptions = {
                            'currencyChart': 'Composición del portafolio por tipo de moneda',
                            'loanAmountsByMonthChart': 'Evolución mensual de montos prestados',
                            'paymentsByMonthChart': 'Tendencia de ingresos por cobranzas',
                            'expectedVsReceivedChart': 'Comparativo entre pagos esperados y recibidos'
                        };
                        pdf.text(descriptions[chartInfo.id] || '', 20, yPosition);
                        yPosition += 10;

                        // Convertir canvas a imagen
                        const imgData = canvas.toDataURL('image/png', 0.8); // Compresión para reducir tamaño

                        // Calcular dimensiones optimizadas
                        const pageWidth = pdf.internal.pageSize.getWidth();
                        const imgWidth = pageWidth - 40; // Márgenes de 20mm
                        const imgHeight = (canvas.height * imgWidth) / canvas.width;

                        // Limitar altura máxima por gráfica
                        const maxHeight = 80;
                        const finalHeight = Math.min(imgHeight, maxHeight);
                        const finalWidth = (canvas.width * finalHeight) / canvas.height;

                        // Agregar imagen centrada
                        const xPosition = (pageWidth - finalWidth) / 2;
                        pdf.addImage(imgData, 'PNG', xPosition, yPosition, finalWidth, finalHeight);
                        yPosition += finalHeight + 15;

                    } catch (error) {
                        console.error('Error procesando gráfica:', chartInfo.id, error);
                        pdf.setFontSize(10);
                        pdf.setTextColor(255, 0, 0);
                        pdf.text(`Error al procesar gráfica: ${chartInfo.title}`, 20, yPosition);
                        pdf.setTextColor(40, 40, 40);
                        yPosition += 10;
                    }
                }
            });

            // Agregar página de estadísticas
            pdf.addPage();

            // Título de estadísticas
            pdf.setFontSize(18);
            pdf.setFont('helvetica', 'bold');
            pdf.text('Resumen Ejecutivo', 20, 25);

            pdf.setLineWidth(0.5);
            pdf.line(20, 35, 190, 35);

            // Estadísticas principales
            pdf.setFontSize(12);
            pdf.setFont('helvetica', 'bold');
            pdf.text('Métricas Principales:', 20, 50);

            pdf.setFontSize(11);
            pdf.setFont('helvetica', 'normal');

            const stats = [
                { label: 'Total de Clientes Registrados', value: '<?php echo number_format($qCts->cantidad ?? 0, 0, ',', '.'); ?>' },
                { label: 'Préstamos Activos en Sistema', value: '<?php echo number_format($qLoans->cantidad ?? 0, 0, ',', '.'); ?>' },
                { label: 'Total de Cobranzas Realizadas', value: '<?php echo number_format($qPaids->cantidad ?? 0, 0, ',', '.'); ?>' },
                { label: 'Cartera Activa Total', value: '<?php echo '$' . number_format($totalPortfolio ?? 0, 0, ',', '.'); ?>' },
                { label: 'Tasa de Morosidad', value: '<?php echo number_format($delinquencyRate ?? 0, 1, ',', '.') . '%'; ?>' },
                { label: 'Promedio de Préstamo por Cliente', value: '<?php echo '$' . number_format($avgLoanPerCustomer ?? 0, 0, ',', '.'); ?>' },
                { label: 'Tasa de Cobranza Efectiva', value: '<?php
                    $totalLoans = $qLoans->cantidad ?? 1;
                    $totalPaid = $qPaids->cantidad ?? 0;
                    $percentage = $totalLoans > 0 ? round(($totalPaid / $totalLoans) * 100, 1) : 0;
                    echo number_format($percentage, 1, ',', '.') . '%';
                ?>' }
            ];

            let statY = 65;
            stats.forEach(stat => {
                pdf.text(`${stat.label}:`, 25, statY);
                pdf.setFont('helvetica', 'bold');
                pdf.text(stat.value, 140, statY);
                pdf.setFont('helvetica', 'normal');
                statY += 12;
            });

            // Información adicional
            statY += 10;
            pdf.setFontSize(10);
            pdf.setFont('helvetica', 'italic');
            pdf.text('Este reporte fue generado automáticamente por el sistema de gestión.', 20, statY);
            pdf.text('Para más detalles, consulte el dashboard en línea.', 20, statY + 8);

            // Pie de página
            const pageCount = pdf.internal.getNumberOfPages();
            for (let i = 1; i <= pageCount; i++) {
                pdf.setPage(i);
                pdf.setFontSize(8);
                pdf.setTextColor(128, 128, 128);
                pdf.text(`Página ${i} de ${pageCount}`, 20, pdf.internal.pageSize.getHeight() - 10);
                pdf.text('Sistema de Préstamos - Reporte Confidencial', pdf.internal.pageSize.getWidth() - 80, pdf.internal.pageSize.getHeight() - 10);
            }

            // Guardar PDF con nombre más descriptivo
            const periodText = startDate && endDate ? `${startDate.replace(/-/g, '')}_${endDate.replace(/-/g, '')}` : 'completo';
            const fileName = `dashboard_prestamos_reporte_${periodText}_${new Date().toISOString().split('T')[0]}.pdf`;

            pdf.save(fileName);

            // Mostrar mensaje de éxito mejorado
            showNotification(`PDF generado exitosamente: ${fileName}`, 'success');

        } catch (error) {
            console.error('Error general al generar PDF:', error);
            alert('Error al generar el PDF: ' + error.message);
        } finally {
            // Restaurar botón siempre
            pdfBtn.innerHTML = originalText;
            pdfBtn.disabled = false;
        }
    }

    // Validación del formulario de fechas
    document.getElementById('dateFilterForm').addEventListener('submit', function(e) {
        e.preventDefault(); // Prevenir envío normal del formulario

        var startDate = document.getElementById('start_date').value;
        var endDate = document.getElementById('end_date').value;

        console.log('Validando fechas:', startDate, endDate);

        // Validación básica
        if (!startDate || !endDate) {
            showNotification('Por favor seleccione ambas fechas (inicio y fin).', 'warning');
            return false;
        }

        // Validación de lógica de fechas
        if (new Date(startDate) > new Date(endDate)) {
            showNotification('La fecha de inicio no puede ser posterior a la fecha de fin.', 'danger');
            return false;
        }

        // Validación de fecha futura
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        if (new Date(endDate) > today) {
            showNotification('La fecha de fin no puede ser posterior a hoy.', 'danger');
            return false;
        }

        console.log('Fechas válidas, actualizando gráficas...');
        // Actualizar gráficas vía AJAX
        updateCharts();
    });

    // Función para limpiar filtros con confirmación
    function clearFilters() {
        if (confirm('¿Está seguro de que desea limpiar todos los filtros? Esto mostrará todos los datos disponibles.')) {
            document.getElementById('start_date').value = '';
            document.getElementById('end_date').value = '';
            // Recargar página sin parámetros
            window.location.href = '<?php echo base_url("admin/dashboard"); ?>';
        }
    }

    // Mejorar la experiencia de usuario con atajos de teclado
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + Enter para filtrar
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            e.preventDefault();
            updateCharts();
        }

        // Escape para limpiar filtros
        if (e.key === 'Escape') {
            clearFilters();
        }
    });


    // Auto-formateo de fechas - sugerir fecha fin cuando se selecciona fecha inicio
    document.getElementById('start_date').addEventListener('blur', function() {
        const startDateValue = this.value;
        const endDateInput = document.getElementById('end_date');

        // Si no hay fecha fin y la fecha inicio es válida
        if (!endDateInput.value && startDateValue && startDateValue.length === 10) {
            try {
                const parts = startDateValue.split('/');
                const startDate = new Date(parts[2], parts[1] - 1, parts[0]);

                // Sugerir un mes después
                const suggestedEndDate = new Date(startDate);
                suggestedEndDate.setMonth(suggestedEndDate.getMonth() + 1);

                const day = String(suggestedEndDate.getDate()).padStart(2, '0');
                const month = String(suggestedEndDate.getMonth() + 1).padStart(2, '0');
                const year = suggestedEndDate.getFullYear();

                endDateInput.value = day + '/' + month + '/' + year;
            } catch (e) {
                console.log('Error al sugerir fecha fin:', e);
            }
        }
    });

    // Función para actualizar la hora colombiana en tiempo real
    function updateColombiaTime() {
        const now = new Date();
        // Obtener hora exacta del servidor vía AJAX
        fetch('<?php echo base_url("admin/dashboard/get_server_time"); ?>')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.time) {
                    const timeElement = document.getElementById('currentTime');
                    if (timeElement) {
                        timeElement.textContent = data.time;
                    }
                }
            })
            .catch(error => {
                console.error('Error obteniendo hora del servidor:', error);
                // Fallback a cálculo local
                const colombiaTime = new Date(now.getTime() - (5 * 60 * 60 * 1000));
                const timeString = colombiaTime.toLocaleTimeString('es-CO', {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: true
                });
                const timeElement = document.getElementById('currentTime');
                if (timeElement) {
                    timeElement.textContent = timeString;
                }
            });
    }

    // Actualizar la hora cada segundo
    setInterval(updateColombiaTime, 1000);

    // Mostrar/ocultar consejos de uso y funcionalidades avanzadas
    document.addEventListener('DOMContentLoaded', function() {
        // Actualizar hora inicial
        updateColombiaTime();
        // Funcionalidades avanzadas sin consejos visibles

        // Agregar indicador de estado de conexión
        const statusIndicator = document.createElement('div');
        statusIndicator.id = 'connectionStatus';
        statusIndicator.className = 'position-fixed bottom-0 right-0 p-2';
        statusIndicator.style.cssText = 'z-index: 9999; font-size: 12px;';
        statusIndicator.innerHTML = '<span class="badge badge-success"><i class="fas fa-wifi"></i> Conectado</span>';
        document.body.appendChild(statusIndicator);

        // Monitorear estado de conexión
        window.addEventListener('online', function() {
            document.getElementById('connectionStatus').innerHTML = '<span class="badge badge-success"><i class="fas fa-wifi"></i> Conectado</span>';
        });

        window.addEventListener('offline', function() {
            document.getElementById('connectionStatus').innerHTML = '<span class="badge badge-danger"><i class="fas fa-wifi-slash"></i> Sin conexión</span>';
        });

        // Agregar tooltips a elementos importantes
        const tooltipElements = [
            { selector: '#filterBtn', title: 'Filtrar datos por rango de fechas' },
            { selector: 'button[onclick="clearFilters()"]', title: 'Limpiar todos los filtros aplicados' },
            { selector: 'button[onclick="exportToPDF()"]', title: 'Exportar dashboard completo a PDF' }
        ];

        tooltipElements.forEach(item => {
            const element = document.querySelector(item.selector);
            if (element) {
                element.setAttribute('data-toggle', 'tooltip');
                element.setAttribute('data-placement', 'top');
                element.title = item.title;
            }
        });

        // Inicializar tooltips de Bootstrap
        $('[data-toggle="tooltip"]').tooltip();

        // Agregar funcionalidad de doble clic para maximizar gráficas
        document.querySelectorAll('canvas').forEach(canvas => {
            canvas.addEventListener('dblclick', function() {
                const modal = document.createElement('div');
                modal.className = 'modal fade';
                modal.innerHTML = `
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Vista Ampliada</h5>
                                <button type="button" class="close" data-dismiss="modal">
                                    <span>&times;</span>
                                </button>
                            </div>
                            <div class="modal-body text-center">
                                <canvas id="modalChart" width="800" height="400"></canvas>
                            </div>
                        </div>
                    </div>
                `;
                document.body.appendChild(modal);
                $(modal).modal('show');

                // Clonar la gráfica en el modal
                const modalCanvas = document.getElementById('modalChart');
                const ctx = modalCanvas.getContext('2d');
                ctx.drawImage(this, 0, 0, 800, 400);

                // Limpiar modal del DOM al cerrar
                $(modal).on('hidden.bs.modal', function() {
                    document.body.removeChild(modal);
                });
            });
        });
    });
</script>
