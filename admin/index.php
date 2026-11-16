<?php

declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
exigir_admin($pdo);

$aberta = lojaManualAberta($pdo);
$statusHorario = (new Horario())->getStatus();
$total  = (int)$pdo->query('SELECT COUNT(*) FROM produtos')->fetchColumn();
$indisp = (int)$pdo->query('SELECT COUNT(*) FROM produtos WHERE disponivel = 0')->fetchColumn();
?>
<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex">
    <meta name="csrf" content="<?= e(csrf_token()) ?>">
    <title>Painel | Lanchonete</title>
    <link rel="stylesheet" href="assets/admin.css">
</head>

<body>
    <main class="wrap">
        <header class="topo">
            <h1>Painel</h1>
            <form method="post" action="logout.php">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <button class="btn peq" type="submit">Sair</button>
            </form>
        </header>

        <section class="loja <?= $aberta ? 'aberta' : 'fechada' ?>" id="loja-card">
            <p class="rotulo">Status da loja</p>
            <p class="estado-grande" id="loja-texto"><?= $aberta ? 'ABERTA' : 'FECHADA' ?></p>
            <button type="button" class="switch grande" id="loja-switch" role="switch"
                aria-label="Loja aberta" aria-checked="<?= $aberta ? 'true' : 'false' ?>"><span></span></button>
            <p class="dica" id="loja-dica">
                <?= $aberta ? 'Recebendo pedidos. Toque para fechar.' : 'Pedidos pausados. Toque para abrir.' ?>
            </p>
        </section>

        <?php if ($aberta && !$statusHorario['aberto']): ?>
            <div class="alerta erro">Fora do horário: <?= e($statusHorario['mensagem']) ?>. Os clientes não conseguem pedir agora.</div>
        <?php endif ?>

        <a class="btn primario bloco" href="produtos.php">Gerenciar cardápio</a>
        <a class="btn bloco" href="produto_form.php">+ Novo produto</a>

        <p class="mut centro"><?= $total ?> produtos cadastrados, <?= $indisp ?> indisponíveis.</p>
    </main>
    <script src="assets/admin.js"></script>
</body>

</html>