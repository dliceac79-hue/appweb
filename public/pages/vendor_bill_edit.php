<?php
$pdo = db();
$bill_id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM vendor_bills WHERE bill_id = ?');
$stmt->execute([$bill_id]);
$bill = $stmt->fetch();
if (!$bill) { echo '<div class="alert alert-danger">Factura no encontrada</div>'; return; }
$providers = $pdo->query('SELECT provider_id, name FROM providers ORDER BY name')->fetchAll();
$provider_id = (int)$bill['provider_id'];
$shipments = $pdo->prepare('SELECT shipment_id, reference FROM shipments s JOIN shipment_services ss ON ss.shipment_id = s.shipment_id WHERE ss.provider_id = ? GROUP BY s.shipment_id');
$shipments->execute([$provider_id]);
$shipments = $shipments->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $provider_id = (int)($_POST['provider_id'] ?? 0);
    $currency = $_POST['currency'] ?? 'MXN';
    $bill_number = $_POST['bill_number'] ?? '';
    $issue_date = $_POST['issue_date'] ?? null;
    $due_date = $_POST['due_date'] ?? null;
    $status = $_POST['status'] ?? 'open';
    $notes = $_POST['notes'] ?? null;
    $subtotal = (float)($_POST['subtotal'] ?? 0);
    $tax = (float)($_POST['tax'] ?? 0);
    $total = (float)($_POST['total'] ?? 0);

    $pdo->beginTransaction();
    try {
        $update = $pdo->prepare('UPDATE vendor_bills SET provider_id=?, currency=?, bill_number=?, issue_date=?, due_date=?, subtotal=?, tax=?, total=?, status=?, notes=? WHERE bill_id=?');
        $update->execute([$provider_id, $currency, $bill_number, $issue_date, $due_date, $subtotal, $tax, $total, $status, $notes, $bill_id]);

        $pdo->prepare('UPDATE shipment_services SET vendor_bill_id=NULL, status="open" WHERE vendor_bill_id = ?')->execute([$bill_id]);
        $pdo->prepare('DELETE FROM vendor_bill_items WHERE bill_id=?')->execute([$bill_id]);
        if (!empty($_POST['item_description'])) {
            $itemStmt = $pdo->prepare('INSERT INTO vendor_bill_items (bill_id, description, qty, unit, unit_price, amount, shipment_service_id) VALUES (?,?,?,?,?,?,?)');
            $serviceUpdate = $pdo->prepare('UPDATE shipment_services SET vendor_bill_id=?, status="invoiced" WHERE shipment_service_id=?');
            foreach ($_POST['item_description'] as $idx => $desc) {
                $qty = (float)($_POST['item_qty'][$idx] ?? 1);
                $unit = $_POST['item_unit'][$idx] ?? 'svc';
                $price = (float)($_POST['item_price'][$idx] ?? 0);
                $amount = (float)($_POST['item_amount'][$idx] ?? 0);
                $serviceId = $_POST['shipment_service_id'][$idx] ?? null;
                $itemStmt->execute([$bill_id, $desc, $qty, $unit, $price, $amount, $serviceId ?: null]);
                if ($serviceId) {
                    $serviceUpdate->execute([$bill_id, $serviceId]);
                }
            }
        }

        $pdo->commit();
        set_flash('ok', 'Factura de proveedor actualizada');
        redirect(BASE_URL . 'public/index.php?p=vendor_bill_edit&id=' . $bill_id);
    } catch (Exception $e) {
        $pdo->rollBack();
        set_flash('error', 'Error al guardar: ' . $e->getMessage());
    }
}

$items = $pdo->prepare('SELECT * FROM vendor_bill_items WHERE bill_id = ?');
$items->execute([$bill_id]);
$items = $items->fetchAll();
?>
<h3>Editar factura de proveedor #<?php echo h($bill_id); ?></h3>
<form method="post" id="bill-form">
    <div class="row mb-3">
        <div class="col-md-4">
            <label class="form-label">Proveedor</label>
            <select name="provider_id" class="form-select" id="provider-select" required>
                <?php foreach ($providers as $p): ?>
                    <option value="<?php echo h($p['provider_id']); ?>" <?php echo $p['provider_id']==$bill['provider_id']?'selected':''; ?>><?php echo h($p['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">No. factura</label>
            <input type="text" name="bill_number" class="form-control" value="<?php echo h($bill['bill_number']); ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label">Moneda</label>
            <select name="currency" class="form-select">
                <?php foreach(['MXN','USD','EUR'] as $cur): ?>
                    <option value="<?php echo h($cur); ?>" <?php echo $bill['currency']==$cur?'selected':''; ?>><?php echo h($cur); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <?php foreach (['open','partial','paid','cancelled'] as $st): ?>
                    <option value="<?php echo h($st); ?>" <?php echo $bill['status']==$st?'selected':''; ?>><?php echo h($st); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-3">
            <label class="form-label">Fecha emisión</label>
            <input type="date" name="issue_date" class="form-control" value="<?php echo h($bill['issue_date']); ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">Vencimiento</label>
            <input type="date" name="due_date" class="form-control" value="<?php echo h($bill['due_date']); ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label">Notas</label>
            <textarea name="notes" class="form-control" rows="2"><?php echo h($bill['notes']); ?></textarea>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label">Agregar servicios pendientes</label>
        <div class="row g-2">
            <div class="col-md-4">
                <select id="shipment-select" class="form-select">
                    <option value="">Seleccione embarque</option>
                    <?php foreach ($shipments as $s): ?>
                        <option value="<?php echo h($s['shipment_id']); ?>"><?php echo h($s['reference']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn btn-outline-secondary" type="button" id="load-services">Agregar servicios pendientes</button>
            </div>
        </div>
    </div>
    <h5>Conceptos</h5>
    <table class="table" id="items-table">
        <thead><tr><th>Descripción</th><th>Cantidad</th><th>Unidad</th><th>Precio</th><th>Importe</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($items as $it): ?>
            <tr>
                <td><input name="item_description[]" class="form-control" value="<?php echo h($it['description']); ?>" required>
                    <input type="hidden" name="shipment_service_id[]" value="<?php echo h($it['shipment_service_id']); ?>">
                </td>
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
            <div class="mb-2"><label>Subtotal</label><input type="number" step="0.01" name="subtotal" class="form-control" id="subtotal" value="<?php echo h($bill['subtotal']); ?>" readonly></div>
            <div class="mb-2"><label>Impuesto</label><input type="number" step="0.01" name="tax" class="form-control" id="tax" value="<?php echo h($bill['tax']); ?>"></div>
            <div class="mb-2"><label>Total</label><input type="number" step="0.01" name="total" class="form-control" id="total" value="<?php echo h($bill['total']); ?>" readonly></div>
        </div>
    </div>
    <button class="btn btn-primary mt-3" type="submit">Guardar</button>
</form>
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
    tr.innerHTML = `<td><input name="item_description[]" class="form-control" required><input type="hidden" name="shipment_service_id[]"></td>
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

$('#load-services').on('click', function(){
    const provider = $('#provider-select').val();
    const shipment = $('#shipment-select').val();
    if (!provider) return;
    $.getJSON('ajax/vendor_bills.php', {ajax:'items', provider_id: provider, shipment_id: shipment}, function(data){
        const tbody = $('#items-table tbody');
        data.forEach(function(s){
            const row = `<tr>
                <td><input name="item_description[]" class="form-control" value="${s.description}" required>
                    <input type="hidden" name="shipment_service_id[]" value="${s.shipment_service_id}">
                </td>
                <td><input type="number" step="0.001" name="item_qty[]" class="form-control item-qty" value="${s.qty}"></td>
                <td><input name="item_unit[]" class="form-control" value="svc"></td>
                <td><input type="number" step="0.01" name="item_price[]" class="form-control item-price" value="${s.unit_price}"></td>
                <td><input type="number" step="0.01" name="item_amount[]" class="form-control item-amount" value="${s.amount}" readonly></td>
                <td><button type="button" class="btn btn-sm btn-danger remove-item">X</button></td>
            </tr>`;
            const tr = $(row);
            tr.find('.remove-item').on('click', function(){ $(this).closest('tr').remove(); recalc(); });
            tr.find('.item-qty, .item-price').on('input', recalc);
            tbody.append(tr);
        });
        recalc();
    });
});
</script>
