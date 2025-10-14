<?php if ($this->session->flashdata('msg')): ?>
    <div class="alert alert-success alert-dismissible fade show text-center mb-4" role="alert">
        <?= $this->session->flashdata('msg') ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
<?php endif ?>

<!-- Dashboard de Estadísticas -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Préstamos</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo count($loans); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-credit-card fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Pagados</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo count(array_filter($loans, function($l) { return !$l->status; })); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pendientes</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo count(array_filter($loans, function($l) { return $l->status; })); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-clock fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Monto Total</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php
                            $total = 0;
                            foreach($loans as $loan) {
                                $interest_amount = $loan->credit_amount * ($loan->interest_amount / 100);
                                $total += $loan->credit_amount + abs($interest_amount);
                            }
                            echo "$ " . format_to_display($total, false);
                            ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-header d-flex align-items-center justify-content-between py-3">
        <h6 class="m-0 font-weight-bold text-primary">Listar Préstamos</h6>
        <a class="d-sm-inline-block btn btn-sm btn-success shadow-sm" href="<?php echo site_url('admin/loans/edit'); ?>"><i class="fas fa-plus-circle fa-sm"></i> Nuevo préstamo</a>
    </div>
    <div class="card-body">

        <!-- Vista de tarjetas para móviles -->
        <div class="d-block d-md-none">
            <div class="row">
                <?php if(count($loans)): foreach($loans as $loan): ?>
                    <div class="col-12 mb-3">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-8">
                                        <h6 class="card-title mb-1">#<?php echo $loan->id; ?> - <?php echo $loan->customer; ?></h6>
                                        <p class="card-text small text-muted mb-1">
                                            <i class="fas fa-dollar-sign"></i>
                                            <?php
                                            $interest_amount = $loan->credit_amount * ($loan->interest_amount / 100);
                                            echo "Crédito: $ " . format_to_display($loan->credit_amount, false);
                                            ?>
                                        </p>
                                        <p class="card-text small text-muted mb-1">
                                            <i class="fas fa-percentage"></i>
                                            <?php
                                            switch ($loan->amortization_type) {
                                                case 'francesa': echo 'Francesa'; break;
                                                case 'estaunidense': echo 'Estadounidense'; break;
                                                case 'mixta': echo 'Mixta'; break;
                                                default: echo ucfirst($loan->amortization_type);
                                            }
                                            ?>
                                        </p>
                                        <p class="card-text small mb-0">
                                            <strong>Total: $ <?php echo format_to_display($loan->credit_amount + abs($interest_amount), false); ?></strong>
                                        </p>
                                    </div>
                                    <div class="col-4 text-right">
                                        <span class="badge badge-<?php echo $loan->status ? 'warning' : 'success'; ?> mb-2">
                                            <?php echo $loan->status ? 'Pendiente' : 'Pagado'; ?>
                                        </span>
                                        <br>
                                        <a href="<?php echo site_url('admin/loans/view/'.$loan->id); ?>" class="btn btn-sm btn-info" data-toggle="ajax-modal">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fas fa-credit-card fa-3x text-muted mb-3"></i>
                                <h5>No existen préstamos</h5>
                                <p class="text-muted">Agregue un nuevo préstamo para comenzar.</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tabla para desktop -->
        <div class="d-none d-md-block">
            <div class="table-responsive">
                <table class="table table-hover" id="dataTable" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr>
                            <th><i class="fas fa-hashtag mr-1"></i> N° Préstamo</th>
                            <th><i class="fas fa-user mr-1"></i> Cliente</th>
                            <th><i class="fas fa-dollar-sign mr-1"></i> Monto Crédito</th>
                            <th><i class="fas fa-percentage mr-1"></i> Monto Interés</th>
                            <th><i class="fas fa-calculator mr-1"></i> Monto Total</th>
                            <th><i class="fas fa-chart-line mr-1"></i> Amortización</th>
                            <th><i class="fas fa-info-circle mr-1"></i> Estado</th>
                            <th><i class="fas fa-cogs mr-1"></i> Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($loans)): ?>
                            <?php foreach($loans as $loan): ?>
                                <tr>
                                    <td><strong>#<?php echo $loan->id; ?></strong></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div>
                                                <div class="font-weight-bold"><?php echo $loan->customer; ?></div>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="text-success font-weight-bold">
                                        <?php
                                        log_message('debug', 'Raw credit_amount from DB: ' . $loan->credit_amount);
                                        echo "$ " . format_to_display($loan->credit_amount, false);
                                        ?>
                                    </td>

                                    <td class="text-warning">
                                        <?php
                                            $interest_amount = $loan->credit_amount * ($loan->interest_amount / 100);
                                            echo "$ " . format_to_display(abs($interest_amount), false);
                                        ?>
                                    </td>

                                    <td class="text-primary font-weight-bold">
                                        <?php
                                            echo "$ " . format_to_display($loan->credit_amount + abs($interest_amount), false);
                                        ?>
                                    </td>

                                    <td>
                                        <span class="badge badge-secondary">
                                            <i class="fas fa-chart-line mr-1"></i>
                                            <?php
                                                switch ($loan->amortization_type) {
                                                    case 'francesa': echo 'Francesa'; break;
                                                    case 'estaunidense': echo 'Estadounidense'; break;
                                                    case 'mixta': echo 'Mixta'; break;
                                                    default: echo ucfirst($loan->amortization_type);
                                                }
                                            ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="badge badge-<?php echo $loan->status ? 'warning' : 'success'; ?> badge-lg">
                                            <i class="fas fa-<?php echo $loan->status ? 'clock' : 'check-circle'; ?> mr-1"></i>
                                            <?php echo $loan->status ? 'Pendiente' : 'Pagado'; ?>
                                        </span>
                                    </td>

                                    <td>
                                        <a href="<?php echo site_url('admin/loans/view/'.$loan->id); ?>" class="btn btn-sm btn-outline-info" data-toggle="ajax-modal">
                                            <i class="fas fa-eye mr-1"></i>Ver pagos
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <i class="fas fa-credit-card fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No existen préstamos</h5>
                                    <p class="text-muted">Haga clic en "Nuevo préstamo" para agregar el primer préstamo.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="myModal" data-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true"></div>

