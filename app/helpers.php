<?php
function h($s)
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function set_flash(string $type, string $msg): void
{
    $_SESSION['flash_' . $type] = $msg;
}

function flash(string $type): ?string
{
    $key = 'flash_' . $type;
    if (!empty($_SESSION[$key])) {
        $msg = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $msg;
    }
    return null;
}

function badge_status(string $status): string
{
    $map = [
        'draft' => 'secondary',
        'issued' => 'info',
        'paid' => 'success',
        'overdue' => 'danger',
        'cancelled' => 'secondary',
        'invoiced' => 'primary',
        'open' => 'warning',
        'partial' => 'info',
    ];
    $class = $map[$status] ?? 'secondary';
    return '<span class="badge bg-' . $class . '">' . h($status) . '</span>';
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}
