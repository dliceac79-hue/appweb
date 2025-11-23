<?php
$pdo = db();
$clients = $pdo->query('SELECT client_id, name FROM clients ORDER BY name')->fetchAll();
$shipments = [];

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
        $stmt = $pdo->prepare('INSERT INTO invoices (client_id, shipment_id, invoice_number, issue_date, due_date, currency, subtotal, tax, total, status, notes) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([$client_id, $shipment_id, $invoice_number, $issue_date, $due_date, $currency, $subtotal, $tax, $total, $status, $notes]);
        $invoice_id = (int)$pdo->lastInsertId();

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
        set_flash('ok', 'Factura creada');
        redirect(BASE_URL . 'public/index.php?p=invoice_edit&id=' . $invoice_id);
    } catch (Exception $e) {
        $pdo->rollBack();
        set_flash('error', 'Error al guardar: ' . $e->getMessage());
    }
}
?>
<h3>Nueva factura</h3>
<form method="post" id="invoice-form">
    <div class="row mb-3">
        <div class="col-md-4">
            <label class="form-label">Cliente</label>
            <select name="client_id" class="form-select" id="client-select" required>
                <option value="">Seleccione...</option>
                <?php foreach ($clients as $c): ?>
                    <option value="<?php echo h($c['client_id']); ?>"><?php echo h($c['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Embarque</label>
            <select name="shipment_id" class="form-select" id="shipment-select">
                <option value="">Sin embarque</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">No. factura</label>
            <input type="text" name="invoice_number" class="form-control" required>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-3">
            <label class="form-label">Fecha emisión</label>
            <input type="date" name="issue_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">Vencimiento</label>
            <input type="date" name="due_date" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label">Moneda</label>
            <select name="currency" class="form-select">
                <option>MXN</option>
                <option>USD</option>
                <option>EUR</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <?php foreach (['draft','issued','paid','overdue','cancelled','invoiced'] as $st): ?>
                    <option value="<?php echo h($st); ?>"><?php echo h($st); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label">Notas</label>
        <textarea name="notes" class="form-control" rows="3"></textarea>
    </div>
    <h5>Conceptos</h5>
    <table class="table" id="items-table">
        <thead><tr><th>Descripción</th><th>Cantidad</th><th>Unidad</th><th>Precio</th><th>Importe</th><th></th></tr></thead>
        <tbody></tbody>
    </table>
    <button type="button" class="btn btn-sm btn-outline-secondary" id="add-item">Agregar concepto</button>
    <div class="row justify-content-end mt-3">
        <div class="col-md-4">
            <div class="mb-2"><label>Subtotal</label><input type="number" step="0.01" name="subtotal" class="form-control" id="subtotal" readonly></div>
            <div class="mb-2"><label>Impuesto</label><input type="number" step="0.01" name="tax" class="form-control" id="tax"></div>
            <div class="mb-2"><label>Total</label><input type="number" step="0.01" name="total" class="form-control" id="total" readonly></div>
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

document.getElementById('tax').addEventListener('input', recalc);

$('#client-select').on('change', function(){
    const cid = $(this).val();
    $('#shipment-select').empty().append('<option value="">Sin embarque</option>');
    if (!cid) return;
    $.getJSON('ajax/invoices.php', {ajax:'shipments', client_id: cid}, function(data){
        data.forEach(function(s){
            $('#shipment-select').append(`<option value="${s.shipment_id}">${s.reference}</option>`);
        });
    });
});
</script>
