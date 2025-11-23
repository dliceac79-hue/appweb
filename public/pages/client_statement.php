<?php
$pdo = db();
$client_id = isset($_GET['client_id']) ? (int)$_GET['client_id'] : 0;
$clients = $pdo->query('SELECT client_id, name FROM clients ORDER BY name')->fetchAll();
$client = $client_id ? credit_get_client($pdo, $client_id) : null;
$statement = $client_id ? credit_client_statement($pdo, $client_id) : ['rows'=>[],'totals'=>['total'=>0,'paid'=>0,'balance'=>0]];
?>
<h3>Estado de cuenta de cliente</h3>
<form class="row g-2 mb-3" method="get">
    <input type="hidden" name="p" value="client_statement">
    <div class="col-md-4">
        <select name="client_id" class="form-select" onchange="this.form.submit()">
            <option value="">Seleccione cliente</option>
            <?php foreach ($clients as $c): ?>
                <option value="<?php echo h($c['client_id']); ?>" <?php echo $client_id==$c['client_id']?'selected':''; ?>><?php echo h($c['name']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</form>
<?php if ($client): ?>
<div class="mb-3">
    <h5><?php echo h($client['name']); ?></h5>
    <p>Límite: $<?php echo number_format($client['credit_limit'],2); ?> | Días crédito: <?php echo h($client['credit_days']); ?> | Usado: $<?php echo number_format($statement['totals']['balance'],2); ?> | Disponible: $<?php echo number_format($client['credit_limit'] - $statement['totals']['balance'],2); ?></p>
</div>
<table class="table table-striped">
    <thead><tr><th>#</th><th>Emisión</th><th>Vencimiento</th><th>Total</th><th>Pagado</th><th>Saldo</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($statement['rows'] as $row): ?>
        <tr>
            <td><?php echo h($row['invoice_id']); ?></td>
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
