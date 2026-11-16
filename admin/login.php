<?php

declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

if (admin_logado($pdo)) {
    header('Location: index.php', true, 303);
    exit;
}

$status = lojaStatus($pdo);
$erro = '';
$loginDigitado = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginDigitado = trim((string)($_POST['login'] ?? ''));
    $senha = (string)($_POST['senha'] ?? '');

    if (!csrf_valido($_POST['csrf'] ?? null)) {
        $erro = 'Sessão expirada. Tente novamente.';
    } elseif (login_bloqueado($pdo)) {
        $erro = 'Muitas tentativas. Aguarde 15 minutos.';
    } else {
        $stmt = $pdo->prepare('SELECT id, senha_hash FROM admins WHERE login = :l');
        $stmt->execute([':l' => normalizar_login($loginDigitado)]);
        $adm = $stmt->fetch();

        if ($adm && password_verify($senha, $adm['senha_hash'])) {
            limpar_falhas($pdo);
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int)$adm['id'];
            if (!empty($_POST['lembrar'])) {
                criar_lembrar($pdo, (int)$adm['id']);
            }
            header('Location: index.php', true, 303);
            exit;
        }

        registrar_falha($pdo);
        $erro = 'E-mail/telefone ou senha incorretos.';
    }
}
?>
<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex">
    <title>Entrar | Painel da Lanchonete Minerva</title>
    <link rel="stylesheet" href="assets/admin.css?v=<?= filemtime(__DIR__ . '/assets/admin.css') ?>">
</head>

<body class="entrada">

    <div class="capa">
        <span class="casa">Lanchonete Minerva</span>
        <h1>Painel de Administação</h1>
        <div class="status-loja <?= $status['aberto'] ? 'aberto' : 'fechado' ?>">
            <span class="bolinha"></span>
            <span><?= e($status['mensagem']) ?></span>
        </div>
    </div>

    <main class="wrap">
        <p class="mut">Entre para gerenciar sua lanchonete.</p>

        <?php if ($erro): ?>
            <div class="alerta erro" role="alert"><?= e($erro) ?></div>
        <?php endif ?>

        <form method="post" autocomplete="on">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

            <label for="login">E-mail</label>
            <input id="login" name="login" type="text" inputmode="email" autocomplete="username"
                autocapitalize="none" autocorrect="off" required value="<?= e($loginDigitado) ?>">

            <label for="senha">Senha</label>
            <input id="senha" name="senha" type="password" autocomplete="current-password" required>

            <label class="check">
                <input type="checkbox" name="lembrar" value="1" checked>
                <span>Lembrar minha senha neste aparelho</span>
            </label>

            <button type="submit" class="btn primario">Entrar</button>
        </form>
    </main>

</body>

</html>