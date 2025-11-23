<?php
require_once __DIR__ . '/../../app/config.php';
$pdo = db();
$action = $_GET['ajax'] ?? $_POST['ajax'] ?? '';
header('Content-Type: application/json');

if ($action === 'shipments') {
    $client_id = (int)($_GET['client_id'] ?? 0);
    $stmt = $pdo->prepare("SELECT shipment_id, reference FROM shipments WHERE client_id = ? AND status != 'invoiced'");
    $stmt->execute([$client_id]);
    echo json_encode($stmt->fetchAll());
    exit;
}

if ($action === 'items') {
    $shipment_id = (int)($_GET['shipment_id'] ?? 0);
    $stmt = $pdo->prepare("SELECT service_type AS description, 1 AS qty, 'svc' AS unit, cost_amount AS unit_price, cost_amount AS amount FROM shipment_services WHERE shipment_id = ?");
    $stmt->execute([$shipment_id]);
    echo json_encode($stmt->fetchAll());
    exit;
}

if ($action === 'payment_add') {
    $invoice_id = (int)($_POST['invoice_id'] ?? 0);
    $payment_date = $_POST['payment_date'] ?? date('Y-m-d');
    $amount = (float)($_POST['amount'] ?? 0);
    $method = $_POST['method'] ?? null;
    $reference = $_POST['reference'] ?? null;
    $notes = $_POST['notes'] ?? null;
    $stmt = $pdo->prepare('INSERT INTO invoice_payments (invoice_id, payment_date, amount, method, reference, notes) VALUES (?,?,?,?,?,?)');
    $stmt->execute([$invoice_id, $payment_date, $amount, $method, $reference, $notes]);
    $pdo->prepare('UPDATE invoices SET amount_paid = amount_paid + ? WHERE invoice_id = ?')->execute([$amount, $invoice_id]);
    echo json_encode(['ok' => true]);
    exit;
}

echo json_encode(['error' => 'Acción no válida']);
