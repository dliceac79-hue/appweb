<?php
$pdo = db();
$invoices = $pdo->query("SELECT i.invoice_id, i.invoice_number, i.issue_date, i.due_date, i.total, i.amount_paid, i.status, c.name AS client_name FROM invoices i LEFT JOIN clients c ON c.client_id=i.client_id ORDER BY i.invoice_id DESC LIMIT 200")->fetchAll();
?>
<div class="d-flex justify-content-between mb-3">
    <h3>Facturas de clientes</h3>
    <a class="btn btn-primary" href="?p=invoice_new">Nueva factura</a>
</div>
<table class="table table-striped">
    <thead><tr><th>#</th><th>Cliente</th><th>Emisión</th><th>Vencimiento</th><th>Total</th><th>Pagado</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($invoices as $inv): ?>
        <tr>
            <td><?php echo h($inv['invoice_id']); ?></td>
            <td><?php echo h($inv['client_name']); ?></td>
            <td><?php echo h($inv['issue_date']); ?></td>
            <td><?php echo h($inv['due_date']); ?></td>
            <td>$<?php echo number_format($inv['total'],2); ?></td>
            <td>$<?php echo number_format($inv['amount_paid'],2); ?></td>
            <td><?php echo badge_status($inv['status']); ?></td>
            <td>
                <a class="btn btn-sm btn-outline-secondary" href="?p=invoice_view&id=<?php echo h($inv['invoice_id']); ?>">Ver</a>
                <a class="btn btn-sm btn-outline-primary" href="?p=invoice_edit&id=<?php echo h($inv['invoice_id']); ?>">Editar</a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
