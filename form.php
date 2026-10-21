<?php

declare(strict_types=1);
session_start();
require_once __DIR__ . '/classes/Carrinho.php';
$carrinho = new Carrinho();
if ($carrinho->estaVazio()) {
    header('Location: index.php');
    exit;
}
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
function e(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}
$old = $_SESSION['old'] ?? [];
unset($_SESSION['old']);
$errors = $_SESSION['errors'] ?? [];
unset($_SESSION['errors']);
$tipo = $old['tipo_pedido'] ?? 'entrega';
$pag = $old['pagamento'] ?? '';
?>
<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Identificação do pedido</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;700&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root{--preto:#0c0907;--espresso:#1a120d;--marrom:#5a3822;--caramelo:#c48a55;--creme:#efe3d0;--areia:#b8a48c;--linha:#3a2a1f}
        *{box-sizing:border-box}
        body{font:16px 'DM Sans',system-ui,sans-serif;background:var(--preto);color:var(--creme);margin:0;padding:24px 16px;line-height:1.6}
        .wrap{max-width:580px;margin:auto;background:var(--espresso);padding:28px;border:1px solid var(--linha);border-top:2px solid var(--caramelo)}
        h1{font-family:'Cormorant Garamond',Georgia,serif;font-size:2.2rem;line-height:1.1;margin:8px 0 12px}
        .voltar{color:var(--areia);font-size:.9rem;text-decoration:none}
        .voltar:hover{color:var(--creme)}
        .total{color:var(--caramelo);font-size:1.2rem}
        label{display:block;margin:14px 0 5px}
        input:not([type=radio]),select{width:100%;padding:11px;background:var(--preto);color:var(--creme);border:1px solid var(--linha);border-radius:2px;font:inherit}
        input:focus-visible,select:focus-visible,button:focus-visible,a:focus-visible{outline:2px solid var(--caramelo);outline-offset:2px}
        input[type=radio]{accent-color:var(--caramelo)}
        fieldset{border:0;padding:0;margin:14px 0}
        legend{padding:0}
        fieldset label{display:inline-block;margin-right:20px}
        button{background:var(--caramelo);color:var(--preto);padding:13px 22px;border:0;border-radius:2px;cursor:pointer;font:inherit;font-weight:700;margin-top:6px}
        button:hover{background:var(--creme)}
        .error{color:#f0a0a0;background:#2a1414;border:1px solid #703030;padding:10px;border-radius:2px}
        .muted{color:var(--areia);font-size:14px}
    </style>
</head>

<body>
    <main class="wrap">
        <a class="voltar" href="index.php#carrinho">← Voltar ao carrinho</a>
        <h1>Identificação do pedido</h1>
        <p class="total"><strong>Total: R$ <?= number_format($carrinho->calcularTotal(), 2, ',', '.') ?></strong></p>
        <?php if ($errors): ?><div class="error"><strong>Verifique os campos:</strong>
                <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach ?></ul>
            </div><?php endif ?>
        <?php if (isset($_GET['sucesso'])): ?><p>Identificação registrada. Caso o WhatsApp não tenha aberto, utilize o link exibido após a confirmação.</p><?php endif ?>
        <form action="includes/identificacao.php" method="post"><input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
            <label for="nome">Nome *</label><input id="nome" name="nome" maxlength="100" required value="<?= e((string)($old['nome'] ?? '')) ?>">
            <fieldset>
                <legend>Como deseja receber? *</legend><label><input type="radio" name="tipo_pedido" value="entrega" <?= $tipo === 'entrega' ? 'checked' : '' ?>> Entrega</label><label><input type="radio" name="tipo_pedido" value="retirada" <?= $tipo === 'retirada' ? 'checked' : '' ?>> Retirada no local</label>
            </fieldset>
            <div id="endereco"><strong>Endereço para entrega</strong>
                <?php foreach (['rua' => 'Rua/Avenida', 'numero' => 'Número', 'complemento' => 'Complemento (opcional)', 'bairro' => 'Bairro', 'cidade' => 'Cidade'] as $key => $label): ?><label for="<?= $key ?>"><?= e($label) ?></label><input id="<?= $key ?>" name="<?= $key ?>" maxlength="100" value="<?= e((string)($old[$key] ?? '')) ?>"><?php endforeach ?></div>
            <label for="pagamento">Forma de pagamento *</label><select id="pagamento" name="pagamento" required>
                <option value="">Selecione</option><?php foreach (['dinheiro' => 'Dinheiro', 'pix' => 'PIX', 'credito' => 'Cartão de Crédito', 'debito' => 'Cartão de Débito'] as $key => $label): ?><option value="<?= $key ?>" <?= $pag === $key ? 'selected' : '' ?>><?= $label ?></option><?php endforeach ?>
            </select>
            <p class="muted">O pedido será registrado e, se houver itens no carrinho, será preparado para envio pelo WhatsApp.</p><button type="submit">Confirmar pedido</button>
        </form>
    </main>
    <script>
        function toggle() {
            let entrega = document.querySelector('input[name="tipo_pedido"]:checked')?.value === 'entrega';
            document.getElementById('endereco').hidden = !entrega;
            for (const id of ['rua', 'numero', 'bairro', 'cidade']) document.getElementById(id).required = entrega;
        }
        document.querySelectorAll('input[name="tipo_pedido"]').forEach(x => x.addEventListener('change', toggle));
        toggle();
    </script>
</body>

</html>