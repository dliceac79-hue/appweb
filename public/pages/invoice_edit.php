<?php
$pdo = db();
$invoice_id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM invoices WHERE invoice_id = ?');
$stmt->execute([$invoice_id]);
$invoice = $stmt->fetch();
if (!$invoice) { echo '<div class="alert alert-danger">Factura no encontrada</div>'; return; }
$clients = $pdo->query('SELECT client_id, name FROM clients ORDER BY name')->fetchAll();
$shipments = $pdo->prepare('SELECT shipment_id, reference FROM shipments WHERE client_id = ?');
$shipments->execute([$invoice['client_id']]);
$shipments = $shipments->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = (int)($_POST['client_id'] ?? 0);
    $shipment_id = $_POST['shipment_id'] ? (int)$_POST['shipment_id'] : null;
    $invoice_number = $_POST['invoice_number'] ?? '';
    $issue_date = $_POST['issue_date'] ?? date('Y-m-d');
    $due_date = $_POST['due_date'] ?? null;
    $currency = $_POST['currency'] ?? 'MXN';
    $status = $_POST['status'] ?? 'draft';
    $notes = $_POST['notes'] ?? null;
    $subtotal = (float)($_POST['subtotal'] ?? 0);
    $tax = (float)($_POST['tax'] ?? 0);
    $total = (float)($_POST['total'] ?? 0);

    $pdo->beginTransaction();
    try {
        $update = $pdo->prepare('UPDATE invoices SET client_id=?, shipment_id=?, invoice_number=?, issue_date=?, due_date=?, currency=?, subtotal=?, tax=?, total=?, status=?, notes=? WHERE invoice_id=?');
        $update->execute([$client_id, $shipment_id, $invoice_number, $issue_date, $due_date, $currency, $subtotal, $tax, $total, $status, $notes, $invoice_id]);

        $pdo->prepare('DELETE FROM invoice_items WHERE invoice_id=?')->execute([$invoice_id]);
        if (!empty($_POST['item_description'])) {
            $itemStmt = $pdo->prepare('INSERT INTO invoice_items (invoice_id, description, qty, unit, unit_price, amount) VALUES (?,?,?,?,?,?)');
            foreach ($_POST['item_description'] as $idx => $desc) {
                $qty = (float)($_POST['item_qty'][$idx] ?? 1);
                $unit = $_POST['item_unit'][$idx] ?? 'svc';
                $price = (float)($_POST['item_price'][$idx] ?? 0);
                $amount = (float)($_POST['item_amount'][$idx] ?? 0);
                $itemStmt->execute([$invoice_id, $desc, $qty, $unit, $price, $amount]);
            }
        }
        $pdo->commit();
        set_flash('ok', 'Factura actualizada');
        redirect(BASE_URL . 'public/index.php?p=invoice_edit&id=' . $invoice_id);
    } catch (Exception $e) {
        $pdo->rollBack();
        set_flash('error', 'Error al guardar: ' . $e->getMessage());
    }
}

$items = $pdo->prepare('SELECT * FROM invoice_items WHERE invoice_id = ?');
$items->execute([$invoice_id]);
$items = $items->fetchAll();
$payments = $pdo->prepare('SELECT * FROM invoice_payments WHERE invoice_id = ? ORDER BY payment_date DESC');
$payments->execute([$invoice_id]);
$payments = $payments->fetchAll();
?>
<h3>Editar factura #<?php echo h($invoice_id); ?></h3>
<form method="post" id="invoice-form">
    <div class="row mb-3">
        <div class="col-md-4">
            <label class="form-label">Cliente</label>
            <select name="client_id" class="form-select" id="client-select" required>
                <?php foreach ($clients as $c): ?>
                    <option value="<?php echo h($c['client_id']); ?>" <?php echo $c['client_id']==$invoice['client_id']?'selected':''; ?>><?php echo h($c['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Embarque</label>
            <select name="shipment_id" class="form-select" id="shipment-select">
                <option value="">Sin embarque</option>
                <?php foreach ($shipments as $s): ?>
                    <option value="<?php echo h($s['shipment_id']); ?>" <?php echo $invoice['shipment_id']==$s['shipment_id']?'selected':''; ?>><?php echo h($s['reference']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">No. factura</label>
            <input type="text" name="invoice_number" class="form-control" value="<?php echo h($invoice['invoice_number']); ?>" required>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-3">
            <label class="form-label">Fecha emisión</label>
            <input type="date" name="issue_date" class="form-control" value="<?php echo h($invoice['issue_date']); ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">Vencimiento</label>
            <input type="date" name="due_date" class="form-control" value="<?php echo h($invoice['due_date']); ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">Moneda</label>
            <select name="currency" class="form-select">
                <?php foreach (['MXN','USD','EUR'] as $cur): ?>
                    <option value="<?php echo h($cur); ?>" <?php echo $invoice['currency']==$cur?'selected':''; ?>><?php echo h($cur); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <?php foreach (['draft','issued','paid','overdue','cancelled','invoiced'] as $st): ?>
                    <option value="<?php echo h($st); ?>" <?php echo $invoice['status']==$st?'selected':''; ?>><?php echo h($st); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label">Notas</label>
        <textarea name="notes" class="form-control" rows="3"><?php echo h($invoice['notes']); ?></textarea>
    </div>
    <h5>Conceptos</h5>
    <table class="table" id="items-table">
        <thead><tr><th>Descripción</th><th>Cantidad</th><th>Unidad</th><th>Precio</th><th>Importe</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($items as $it): ?>
            <tr>
                <td><input name="item_description[]" class="form-control" value="<?php echo h($it['description']); ?>" required></td>
                <td><input type="number" step="0.001" name="item_qty[]" class="form-control item-qty" value="<?php echo h($it['qty']); ?>"></td>
                <td><input name="item_unit[]" class="form-control" value="<?php echo h($it['unit']); ?>"></td>
                <td><input type="number" step="0.01" name="item_price[]" class="form-control item-price" value="<?php echo h($it['unit_price']); ?>"></td>
                <td><input type="number" step="0.01" name="item_amount[]" class="form-control item-amount" value="<?php echo h($it['amount']); ?>" readonly></td>
                <td><button type="button" class="btn btn-sm btn-danger remove-item">X</button></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <button type="button" class="btn btn-sm btn-outline-secondary" id="add-item">Agregar concepto</button>
    <div class="row justify-content-end mt-3">
        <div class="col-md-4">
            <div class="mb-2"><label>Subtotal</label><input type="number" step="0.01" name="subtotal" class="form-control" id="subtotal" value="<?php echo h($invoice['subtotal']); ?>" readonly></div>
            <div class="mb-2"><label>Impuesto</label><input type="number" step="0.01" name="tax" class="form-control" id="tax" value="<?php echo h($invoice['tax']); ?>"></div>
            <div class="mb-2"><label>Total</label><input type="number" step="0.01" name="total" class="form-control" id="total" value="<?php echo h($invoice['total']); ?>" readonly></div>
        </div>
    </div>
    <button class="btn btn-primary mt-3" type="submit">Guardar</button>
</form>

<h5 class="mt-4">Pagos</h5>
<table class="table table-sm">
    <thead><tr><th>Fecha</th><th>Monto</th><th>Método</th><th>Referencia</th><th>Notas</th></tr></thead>
    <tbody>
    <?php foreach ($payments as $p): ?>
        <tr>
            <td><?php echo h($p['payment_date']); ?></td>
            <td>$<?php echo number_format($p['amount'],2); ?></td>
            <td><?php echo h($p['method']); ?></td>
            <td><?php echo h($p['reference']); ?></td>
            <td><?php echo h($p['notes']); ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<div class="card">
    <div class="card-body">
        <form id="payment-form">
            <input type="hidden" name="ajax" value="payment_add">
            <input type="hidden" name="invoice_id" value="<?php echo h($invoice_id); ?>">
            <div class="row g-2">
                <div class="col-md-3"><input type="date" name="payment_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required></div>
                <div class="col-md-2"><input type="number" step="0.01" name="amount" class="form-control" placeholder="Monto" required></div>
                <div class="col-md-2"><input type="text" name="method" class="form-control" placeholder="Método"></div>
                <div class="col-md-2"><input type="text" name="reference" class="form-control" placeholder="Referencia"></div>
                <div class="col-md-3"><input type="text" name="notes" class="form-control" placeholder="Notas"></div>
            </div>
            <button class="btn btn-sm btn-success mt-2" type="submit">Agregar pago</button>
        </form>
    </div>
</div>
<script>
function recalc() {
    let subtotal = 0;
    document.querySelectorAll('#items-table tbody tr').forEach(function(row){
        const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
        const price = parseFloat(row.querySelector('.item-price').value) || 0;
        const amount = qty * price;
        row.querySelector('.item-amount').value = amount.toFixed(2);
        subtotal += amount;
    });
    document.getElementById('subtotal').value = subtotal.toFixed(2);
    const tax = parseFloat(document.getElementById('tax').value) || 0;
    document.getElementById('total').value = (subtotal + tax).toFixed(2);
}

document.getElementById('add-item').addEventListener('click', function(){
    const tbody = document.querySelector('#items-table tbody');
    const tr = document.createElement('tr');
    tr.innerHTML = `<td><input name="item_description[]" class="form-control" required></td>
        <td><input type="number" step="0.001" name="item_qty[]" class="form-control item-qty" value="1"></td>
        <td><input name="item_unit[]" class="form-control" value="svc"></td>
        <td><input type="number" step="0.01" name="item_price[]" class="form-control item-price" value="0"></td>
        <td><input type="number" step="0.01" name="item_amount[]" class="form-control item-amount" value="0" readonly></td>
        <td><button type="button" class="btn btn-sm btn-danger remove-item">X</button></td>`;
    tbody.appendChild(tr);
    tr.querySelectorAll('.item-qty, .item-price').forEach(el => el.addEventListener('input', recalc));
    tr.querySelector('.remove-item').addEventListener('click', function(){ tr.remove(); recalc(); });
    recalc();
});

document.querySelectorAll('.item-qty, .item-price').forEach(el => el.addEventListener('input', recalc));
document.getElementById('tax').addEventListener('input', recalc);

$('#payment-form').on('submit', function(e){
    e.preventDefault();
    $.post('ajax/invoices.php', $(this).serialize(), function(){
        location.reload();
    });
});
</script>
