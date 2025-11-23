<?php
function credit_get_client(PDO $pdo, int $client_id): array
{
    $stmt = $pdo->prepare('SELECT * FROM clients WHERE client_id = ?');
    $stmt->execute([$client_id]);
    $client = $stmt->fetch();
    return $client ?: [];
}

function credit_client_statement(PDO $pdo, int $client_id): array
{
    $stmt = $pdo->prepare('SELECT invoice_id, invoice_number, issue_date, due_date, total, amount_paid, status FROM invoices WHERE client_id = ? ORDER BY issue_date DESC');
    $stmt->execute([$client_id]);
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
