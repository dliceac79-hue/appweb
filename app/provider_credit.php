<?php
function ap_get_provider(PDO $pdo, int $provider_id): array
{
    $stmt = $pdo->prepare('SELECT * FROM providers WHERE provider_id = ?');
    $stmt->execute([$provider_id]);
    $provider = $stmt->fetch();
    return $provider ?: [];
}

function ap_provider_statement(PDO $pdo, int $provider_id): array
{
    $stmt = $pdo->prepare("SELECT bill_id, bill_number, issue_date, due_date, total, amount_paid, status FROM vendor_bills WHERE provider_id = ? ORDER BY issue_date DESC");
    $stmt->execute([$provider_id]);
    $rows = [];
    $total = $paid = 0;
    while ($row = $stmt->fetch()) {
        $balance = (float)$row['total'] - (float)$row['amount_paid'];
        $row['balance'] = $balance;
        $rows[] = $row;
        $total += (float)$row['total'];
        $paid += (float)$row['amount_paid'];
    }
    return [
        'rows' => $rows,
        'totals' => [
            'total' => $total,
            'paid' => $paid,
            'balance' => $total - $paid,
        ],
    ];
}
