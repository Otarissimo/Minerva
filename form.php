<?php

declare(strict_types=1);
session_start();

require_once __DIR__ . '/classes/Carrinho.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/loja.php';
$statusLoja = lojaStatus($pdo);
$lojaAberta = $statusLoja['aberto'];

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Identificação do pedido | Lanchonete Minerva</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,700;1,500&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/form.css">
</head>

<body>
    <main class="wrap-form">
        <h1>Identificação do pedido</h1>
        <p>Total: <strong>R$ <?= number_format($carrinho->calcularTotal(), 2, ',', '.') ?></strong></p>

        <?php if (!$lojaAberta): ?>
            <div class="error" role="alert">
                <strong>Pedidos pausados.</strong> <?= e($statusLoja['mensagem']) ?>
            </div>
        <?php endif ?>

        <?php if ($errors): ?>
            <div class="error">
                <strong>Verifique os campos:</strong>
                <ul>
                    <?php foreach ($errors as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach ?>
                </ul>
            </div>
        <?php endif ?>

        <?php if (isset($_GET['sucesso'])): ?>
            <p style="color: #4ade80;">Identificação registrada. Caso o WhatsApp não tenha aberto, utilize o link exibido após a confirmação.</p>
        <?php endif ?>

        <form action="includes/identificacao.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

            <label for="nome">Nome *</label>
            <input id="nome" name="nome" maxlength="100" required value="<?= e((string)($old['nome'] ?? '')) ?>">

            <fieldset>
                <legend>Como deseja receber? *</legend>
                <label><input type="radio" name="tipo_pedido" value="entrega" <?= $tipo === 'entrega' ? 'checked' : '' ?>> Entrega</label>
                <label><input type="radio" name="tipo_pedido" value="retirada" <?= $tipo === 'retirada' ? 'checked' : '' ?>> Retirada no local</label>
            </fieldset>

            <div id="endereco">
                <strong>Endereço para entrega</strong>
                <?php foreach (['rua' => 'Rua/Avenida', 'numero' => 'Número', 'complemento' => 'Complemento (opcional)', 'bairro' => 'Bairro', 'cidade' => 'Cidade'] as $key => $label): ?>
                    <label for="<?= $key ?>"><?= e($label) ?></label>
                    <input id="<?= $key ?>" name="<?= $key ?>" maxlength="100" value="<?= e((string)($old[$key] ?? '')) ?>">
                <?php endforeach ?>
            </div>

            <label for="pagamento">Forma de pagamento *</label>
            <select id="pagamento" name="pagamento" required>
                <option value="">Selecione</option>
                <?php foreach (['dinheiro' => 'Dinheiro', 'pix' => 'PIX', 'credito' => 'Cartão de Crédito', 'debito' => 'Cartão de Débito'] as $key => $label): ?>
                    <option value="<?= $key ?>" <?= $pag === $key ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach ?>
            </select>

            <!-- Campo dinâmico de troco (aparece apenas se Dinheiro for selecionado) -->
            <div id="campo-troco" style="display: none; margin-top: 12px;">
                <label for="troco">Troco para quanto?</label>
                <input type="text" id="troco" name="troco" placeholder="Ex: R$ 50,00 (ou deixe em branco se não precisar)" value="<?= e((string)($old['troco'] ?? '')) ?>">
            </div>

            <label for="observacao">Observação (opcional)</label>
            <textarea id="observacao" name="observacao" rows="3" maxlength="255" placeholder="Ex: sem cebola, ponto da carne, caprichar no molho..."><?= e((string)($old['observacao'] ?? '')) ?></textarea>

            <p class="muted">O pedido será registrado e, se houver itens no carrinho, será preparado para envio pelo WhatsApp.</p>

            <button type="submit" class="btn-enviar" <?= $lojaAberta ? '' : 'disabled' ?>>
                <?= $lojaAberta ? 'Confirmar pedido' : 'Loja fechada' ?>
            </button>
        </form>
    </main>

    <script>
        function toggleEndereco() {
            let entrega = document.querySelector('input[name="tipo_pedido"]:checked')?.value === 'entrega';
            document.getElementById('endereco').hidden = !entrega;
            for (const id of ['rua', 'numero', 'bairro', 'cidade']) document.getElementById(id).required = entrega;
        }
        document.querySelectorAll('input[name="tipo_pedido"]').forEach(x => x.addEventListener('change', toggleEndereco));
        toggleEndereco();

        function toggleTroco() {
            let pagamento = document.getElementById('pagamento').value;
            let campoTroco = document.getElementById('campo-troco');
            campoTroco.style.display = (pagamento === 'dinheiro') ? 'block' : 'none';
        }
        document.getElementById('pagamento').addEventListener('change', toggleTroco);
        toggleTroco();

        // Formatação automática do campo de troco para R$ XX,XX
        const inputTroco = document.getElementById('troco');
        if (inputTroco) {
            function formatarMoeda() {
                let valorLimpo = inputTroco.value.replace(/[^\d,.-]/g, '').replace(',', '.');
                let numero = parseFloat(valorLimpo);

                if (!isNaN(numero)) {
                    // CORREÇÃO: Alterado de 'input.value' para 'inputTroco.value'
                    inputTroco.value = numero.toLocaleString('pt-BR', {
                        style: 'currency',
                        currency: 'BRL'
                    });
                }
            }

            // Formata ao sair do campo (blur)
            inputTroco.addEventListener('blur', formatarMoeda);

            // Formata e impede o envio acidental ao pressionar Enter
            inputTroco.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    formatarMoeda();
                }
            });
        }
    </script>
</body>

</html>