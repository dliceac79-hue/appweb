<?php
require_once __DIR__ . '/../../app/config.php';
$pdo = db();
$action = $_GET['ajax'] ?? $_POST['ajax'] ?? '';
header('Content-Type: application/json');

if ($action === 'shipments') {
    $provider_id = (int)($_GET['provider_id'] ?? 0);
    $stmt = $pdo->prepare("SELECT DISTINCT s.shipment_id, s.reference FROM shipments s JOIN shipment_services ss ON ss.shipment_id = s.shipment_id WHERE ss.provider_id = ? AND ss.status = 'open'");
    $stmt->execute([$provider_id]);
    echo json_encode($stmt->fetchAll());
    exit;
}

if ($action === 'items') {
    $provider_id = (int)($_GET['provider_id'] ?? 0);
    $shipment_id = (int)($_GET['shipment_id'] ?? 0);
    $sql = "SELECT shipment_service_id, CONCAT(service_type, ' ', COALESCE(service_ref,'')) AS description, 1 AS qty, 'svc' AS unit, cost_amount AS unit_price, cost_amount AS amount FROM shipment_services WHERE provider_id = ? AND status = 'open'";
    $params = [$provider_id];
    if ($shipment_id) { $sql .= ' AND shipment_id = ?'; $params[] = $shipment_id; }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo json_encode($stmt->fetchAll());
    exit;
}

echo json_encode(['error' => 'Acción no válida']);
