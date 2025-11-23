<?php
require_once __DIR__ . '/../app/config.php';

$p = $_GET['p'] ?? 'dashboard';
$public_pages = ['login'];
$pages = [
    'dashboard','invoices','invoice_new','invoice_edit','invoice_view','vendor_bills','vendor_bill_new','vendor_bill_edit','client_statement','provider_statement'
];

if ($p === 'logout') {
    logout();
    redirect(BASE_URL . 'public/index.php?p=login');
}

if (!in_array($p, array_merge($pages, $public_pages), true)) {
    $p = 'dashboard';
}

if (!in_array($p, $public_pages, true)) {
    require_login();
}

function render_flash(): void {
    foreach (['ok' => 'success', 'error' => 'danger'] as $key => $cls) {
        if ($msg = flash($key)) {
            echo '<div class="alert alert-' . $cls . ' alert-dismissible fade show" role="alert">' . h($msg) . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>LogiOps</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?php echo BASE_URL; ?>public/index.php">LogiOps</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="?p=dashboard">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="?p=invoices">Facturas</a></li>
                <li class="nav-item"><a class="nav-link" href="?p=vendor_bills">Cuentas por pagar</a></li>
                <li class="nav-item"><a class="nav-link" href="?p=client_statement">Estado de cuenta cliente</a></li>
                <li class="nav-item"><a class="nav-link" href="?p=provider_statement">Estado de cuenta proveedor</a></li>
            </ul>
            <ul class="navbar-nav">
                <?php if (current_user()): ?>
                    <li class="nav-item"><span class="navbar-text text-white me-2">Hola, <?php echo h(current_user()['name']); ?></span></li>
                    <li class="nav-item"><a class="nav-link" href="?p=logout">Salir</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<div class="container mb-5">
    <?php render_flash(); ?>
    <?php include __DIR__ . '/pages/' . $p . '.php'; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</body>
</html>
