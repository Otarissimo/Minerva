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
    <style>
        body {
            font: 16px system-ui, sans-serif;
            background: #f5f6f8;
            color: #222;
            margin: 0;
            padding: 24px
        }

        .wrap {
            max-width: 580px;
            margin: auto;
            background: white;
            padding: 28px;
            border-radius: 12px;
            box-shadow: 0 3px 20px #0001
        }

        label {
            display: block;
            margin: 14px 0 5px
        }

        input:not([type=radio]),
        select,
        textarea {
            box-sizing: border-box;
            width: 100%;
            padding: 11px;
            border: 1px solid #bbb;
            border-radius: 6px;
            font-family: inherit;
        }

        fieldset {
            border: 0;
            padding: 0;
            margin: 14px 0
        }

        fieldset label {
            display: inline-block;
            margin-right: 20px
        }

        button {
            background: #198754;
            color: white;
            padding: 13px 22px;
            border: 0;
            border-radius: 6px;
            cursor: pointer
        }

        .error {
            color: #a40000;
            background: #ffeded;
            padding: 10px;
            border-radius: 6px
        }

        .muted {
            color: #555;
            font-size: 14px
        }
    </style>
</head>

<body>
    <main class="wrap">
        <h1>Identificação do pedido</h1>
        <p><strong>Total: R$ <?= number_format($carrinho->calcularTotal(), 2, ',', '.') ?></strong></p>
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
            <label for="observacao">Observação (opcional)</label>
            <textarea id="observacao" name="observacao" rows="3" maxlength="255" placeholder="Ex: sem cebola, ponto da carne, caprichar no molho..."><?= e((string)($old['observacao'] ?? '')) ?></textarea>
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
