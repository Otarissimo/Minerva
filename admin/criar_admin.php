<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/bootstrap.php';

$login = normalizar_login($argv[1] ?? '');
$senha = $argv[2] ?? '';

if ($login === '' || mb_strlen($senha) < 6) {
    exit("Uso: php admin/criar_admin.php \"email-ou-telefone\" \"senha (mínimo 6 caracteres)\"\n");
}

$hash = password_hash($senha, PASSWORD_DEFAULT);

$stmt = $pdo->prepare('SELECT id FROM admins WHERE login = :l');
$stmt->execute([':l' => $login]);

if ($stmt->fetchColumn()) {
    $pdo->prepare('UPDATE admins SET senha_hash = :h WHERE login = :l')->execute([':h' => $hash, ':l' => $login]);
    echo "Senha atualizada para {$login}\n";
} else {
    $pdo->prepare('INSERT INTO admins (login, senha_hash) VALUES (:l, :h)')->execute([':l' => $login, ':h' => $hash]);
    echo "Dono cadastrado: {$login}\n";
}