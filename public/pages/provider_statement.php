<?php
$pdo = db();
$provider_id = isset($_GET['provider_id']) ? (int)$_GET['provider_id'] : 0;
$providers = $pdo->query('SELECT provider_id, name FROM providers ORDER BY name')->fetchAll();
$provider = $provider_id ? ap_get_provider($pdo, $provider_id) : null;
$statement = $provider_id ? ap_provider_statement($pdo, $provider_id) : ['rows'=>[],'totals'=>['total'=>0,'paid'=>0,'balance'=>0]];
?>
<h3>Estado de cuenta de proveedor</h3>
<form class="row g-2 mb-3" method="get">
    <input type="hidden" name="p" value="provider_statement">
    <div class="col-md-4">
        <select name="provider_id" class="form-select" onchange="this.form.submit()">
            <option value="">Seleccione proveedor</option>
            <?php foreach ($providers as $p): ?>
                <option value="<?php echo h($p['provider_id']); ?>" <?php echo $provider_id==$p['provider_id']?'selected':''; ?>><?php echo h($p['name']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</form>
<?php if ($provider): ?>
<div class="mb-3">
    <h5><?php echo h($provider['name']); ?></h5>
    <p>Días de crédito: <?php echo h($provider['credit_days']); ?> | Saldo: $<?php echo number_format($statement['totals']['balance'],2); ?></p>
</div>
<table class="table table-striped">
    <thead><tr><th>#</th><th>Emisión</th><th>Vencimiento</th><th>Total</th><th>Pagado</th><th>Saldo</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($statement['rows'] as $row): ?>
        <tr>
            <td><?php echo h($row['bill_id']); ?></td>
            <td><?php echo h($row['issue_date']); ?></td>
            <td><?php echo h($row['due_date']); ?></td>
            <td>$<?php echo number_format($row['total'],2); ?></td>
            <td>$<?php echo number_format($row['amount_paid'],2); ?></td>
            <td>$<?php echo number_format($row['balance'],2); ?></td>
            <td><?php echo badge_status($row['status']); ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr class="fw-bold">
            <td colspan="3">Totales</td>
            <td>$<?php echo number_format($statement['totals']['total'],2); ?></td>
            <td>$<?php echo number_format($statement['totals']['paid'],2); ?></td>
            <td>$<?php echo number_format($statement['totals']['balance'],2); ?></td>
            <td></td>
        </tr>
    </tfoot>
</table>
<?php endif; ?>
