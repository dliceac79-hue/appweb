<?php
$pdo = db();
$invoice_id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT i.*, c.name AS client_name FROM invoices i LEFT JOIN clients c ON c.client_id=i.client_id WHERE i.invoice_id=?');
$stmt->execute([$invoice_id]);
$invoice = $stmt->fetch();
if (!$invoice) { echo '<div class="alert alert-danger">Factura no encontrada</div>'; return; }
$items = $pdo->prepare('SELECT * FROM invoice_items WHERE invoice_id=?');
$items->execute([$invoice_id]);
$items = $items->fetchAll();
?>
<h3>Factura <?php echo h($invoice['invoice_number']); ?></h3>
<p>Cliente: <?php echo h($invoice['client_name']); ?> | Fecha: <?php echo h($invoice['issue_date']); ?> | Total: $<?php echo number_format($invoice['total'],2); ?> | Status: <?php echo badge_status($invoice['status']); ?></p>
<table class="table table-striped">
    <thead><tr><th>Descripción</th><th>Cantidad</th><th>Unidad</th><th>Precio</th><th>Importe</th></tr></thead>
    <tbody>
    <?php foreach ($items as $it): ?>
        <tr>
            <td><?php echo h($it['description']); ?></td>
            <td><?php echo h($it['qty']); ?></td>
            <td><?php echo h($it['unit']); ?></td>
            <td>$<?php echo number_format($it['unit_price'],2); ?></td>
            <td>$<?php echo number_format($it['amount'],2); ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
