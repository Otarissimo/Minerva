<?php

declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
exigir_admin($pdo);

$produtos = $pdo->query('SELECT * FROM produtos ORDER BY categoria COLLATE NOCASE, nome COLLATE NOCASE')->fetchAll();

$grupos = [];
foreach ($produtos as $p) {
    $grupos[$p['categoria']][] = $p;
}
?>
<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex">
    <meta name="csrf" content="<?= e(csrf_token()) ?>">
    <title>Cardápio | Painel</title>
    <link rel="stylesheet" href="assets/admin.css">
</head>

<body>
    <main class="wrap">
        <header class="topo">
            <h1>Cardápio</h1>
            <a class="btn peq" href="index.php">Painel</a>
        </header>

        <a class="btn primario bloco" href="produto_form.php">+ Novo produto</a>

        <?php if (!$grupos): ?>
            <p class="mut centro">Nenhum produto cadastrado ainda.</p>
        <?php endif ?>

        <?php foreach ($grupos as $categoria => $itens): ?>
            <h2 class="categoria"><?= e($categoria) ?></h2>
            <ul class="lista">
                <?php foreach ($itens as $p): $ativo = (int)$p['disponivel'] === 1; ?>
                    <li class="item">
                        <div class="thumb">
                            <?php if ($p['foto']): ?>
                                <img src="../uploads/produtos/<?= e($p['foto']) ?>" alt="" loading="lazy">
                            <?php else: ?>
                                <span><?= e(mb_strtoupper(mb_substr($p['nome'], 0, 1))) ?></span>
                            <?php endif ?>
                        </div>

                        <div class="info">
                            <strong><?= e($p['nome']) ?></strong>
                            <span class="preco">R$ <?= number_format((float)$p['preco'], 2, ',', '.') ?></span>
                            <span class="estado <?= $ativo ? '' : 'off' ?>"><?= $ativo ? 'Disponível' : 'Esgotado' ?></span>
                        </div>

                        <button type="button" class="switch" data-produto="<?= (int)$p['id'] ?>" role="switch"
                            aria-label="Disponibilidade de <?= e($p['nome']) ?>"
                            aria-checked="<?= $ativo ? 'true' : 'false' ?>"><span></span></button>

                        <div class="acoes">
                            <a class="btn peq" href="produto_form.php?id=<?= (int)$p['id'] ?>">Editar</a>
                            <button type="button" class="btn peq perigo" data-excluir="<?= (int)$p['id'] ?>"
                                data-nome="<?= e($p['nome']) ?>">Excluir</button>
                        </div>
                    </li>
                <?php endforeach ?>
            </ul>
        <?php endforeach ?>
    </main>
    <script src="assets/admin.js"></script>
</body>

</html>