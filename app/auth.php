<?php
function current_user_id(): ?int
{
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

function current_user(): ?array
{
    if (!current_user_id()) {
        return null;
    }
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE user_id = ?');
    $stmt->execute([current_user_id()]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function require_login(): void
{
    if (!current_user_id()) {
        redirect(BASE_URL . 'public/index.php?p=login');
    }
}

function handle_login(string $email, string $password): bool
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    if ($user = $stmt->fetch()) {
        if (password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = (int)$user['user_id'];
            return true;
        }
    }
    return false;
}

function logout(): void
{
    session_destroy();
    session_start();
}
