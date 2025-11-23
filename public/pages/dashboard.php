<?php
$pdo = db();
$open_invoices = $pdo->query("SELECT SUM(total - amount_paid) AS balance FROM invoices WHERE status NOT IN ('paid','cancelled')")->fetchColumn();
$open_bills = $pdo->query("SELECT SUM(total - amount_paid) AS balance FROM vendor_bills WHERE status NOT IN ('paid','cancelled')")->fetchColumn();
$shipments = $pdo->query("SELECT s.shipment_id, s.reference, c.name AS client_name, s.status FROM shipments s LEFT JOIN clients c ON c.client_id=s.client_id ORDER BY s.shipment_id DESC LIMIT 5")->fetchAll();
?>
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card text-bg-light">
            <div class="card-body">
                <h5 class="card-title">Saldo facturas abiertas</h5>
                <p class="display-6">$<?php echo number_format((float)$open_invoices, 2); ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card text-bg-light">
            <div class="card-body">
                <h5 class="card-title">Saldo cuentas por pagar</h5>
                <p class="display-6">$<?php echo number_format((float)$open_bills, 2); ?></p>
            </div>
        </div>
    </div>
</div>
<div class="card">
    <div class="card-header">Últimos embarques</div>
    <div class="card-body p-0">
        <table class="table mb-0 table-striped">
            <thead><tr><th>#</th><th>Referencia</th><th>Cliente</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($shipments as $s): ?>
                <tr>
                    <td><?php echo h($s['shipment_id']); ?></td>
                    <td><?php echo h($s['reference']); ?></td>
                    <td><?php echo h($s['client_name']); ?></td>
                    <td><?php echo badge_status($s['status']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
