<?php

declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valido($_POST['csrf'] ?? null)) {
    encerrar_lembrar($pdo);
    unset($_SESSION['admin_id']);
    session_regenerate_id(true);
}
header('Location: login.php', true, 303);
exit;
