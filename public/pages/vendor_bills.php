<?php
$pdo = db();
$q = $_GET['q'] ?? '';
$status = $_GET['status'] ?? 'all';
$ref = $_GET['ref'] ?? '';
$sql = "SELECT b.bill_id,b.bill_number,b.issue_date,b.due_date,b.total,b.amount_paid,b.status,p.name AS provider_name,b.currency FROM vendor_bills b LEFT JOIN providers p ON p.provider_id=b.provider_id";
$where = [];$params=[];
if ($q) { $where[] = '(b.bill_number LIKE ? OR p.name LIKE ?)'; $params[]="%$q%"; $params[]="%$q%"; }
if ($status && $status!=='all') { $where[] = 'b.status = ?'; $params[] = $status; }
if ($ref) {
    $sql .= ' LEFT JOIN shipment_services ss ON ss.vendor_bill_id = b.bill_id LEFT JOIN shipments s ON s.shipment_id = ss.shipment_id';
    $where[] = 's.reference LIKE ?';
    $params[] = "%$ref%";
}
if ($where) { $sql .= ' WHERE ' . implode(' AND ', $where); }
$sql .= ' ORDER BY b.bill_id DESC LIMIT 200';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bills = $stmt->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Facturas de proveedor</h3>
    <a class="btn btn-primary" href="?p=vendor_bill_new">Nueva factura</a>
</div>
<form class="row g-2 mb-3" method="get">
    <input type="hidden" name="p" value="vendor_bills">
    <div class="col-md-3"><input type="text" name="q" class="form-control" placeholder="Buscar folio o proveedor" value="<?php echo h($q); ?>"></div>
    <div class="col-md-3"><input type="text" name="ref" class="form-control" placeholder="Referencia embarque" value="<?php echo h($ref); ?>"></div>
    <div class="col-md-3">
        <select name="status" class="form-select">
            <option value="all">Todos</option>
            <?php foreach (['open','partial','paid','cancelled'] as $st): ?>
                <option value="<?php echo h($st); ?>" <?php echo $status==$st?'selected':''; ?>><?php echo h($st); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3"><button class="btn btn-secondary" type="submit">Filtrar</button></div>
</form>
<table class="table table-striped">
    <thead><tr><th>#</th><th>Proveedor</th><th>Emisión</th><th>Vencimiento</th><th>Moneda</th><th>Total</th><th>Pagado</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($bills as $bill): ?>
        <tr>
            <td><?php echo h($bill['bill_id']); ?></td>
            <td><?php echo h($bill['provider_name']); ?></td>
            <td><?php echo h($bill['issue_date']); ?></td>
            <td><?php echo h($bill['due_date']); ?></td>
            <td><?php echo h($bill['currency']); ?></td>
            <td>$<?php echo number_format($bill['total'],2); ?></td>
            <td>$<?php echo number_format($bill['amount_paid'],2); ?></td>
            <td><?php echo badge_status($bill['status']); ?></td>
            <td><a class="btn btn-sm btn-outline-primary" href="?p=vendor_bill_edit&id=<?php echo h($bill['bill_id']); ?>">Editar</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
