<?php

declare(strict_types=1);
session_start();
require_once dirname(__DIR__) . '/classes/Carrinho.php';
require_once dirname(__DIR__) . '/classes/Pedido.php';
$carrinho = new Carrinho();
if ($carrinho->estaVazio()) {
    header('Location: ../index.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método não permitido');
}
$token = $_POST['csrf_token'] ?? null;
if (!is_string($token) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    http_response_code(403);
    exit('Token inválido. Atualize o formulário.');
}
$keys = ['nome', 'tipo_pedido', 'pagamento', 'rua', 'numero', 'complemento', 'bairro', 'cidade'];
$d = [];
$errors = [];
foreach ($keys as $key) {
    $v = $_POST[$key] ?? '';
    if (!is_string($v)) {
        $errors[] = 'Dados inválidos: ' . $key;
        $v = '';
    }
    $d[$key] = trim($v);
}
if (mb_strlen($d['nome']) < 2 || mb_strlen($d['nome']) > 100) $errors[] = 'Nome deve ter entre 2 e 100 caracteres.';
if (!in_array($d['tipo_pedido'], ['entrega', 'retirada'], true)) $errors[] = 'Tipo de pedido inválido.';
if (!in_array($d['pagamento'], ['dinheiro', 'pix', 'credito', 'debito'], true)) $errors[] = 'Pagamento inválido.';
foreach (['rua', 'numero', 'complemento', 'bairro', 'cidade'] as $k) if (mb_strlen($d[$k]) > 100) $errors[] = 'Campo muito longo: ' . $k;
$endereco = null;
if ($d['tipo_pedido'] === 'entrega') {
    foreach (['rua', 'numero', 'bairro', 'cidade'] as $k) if ($d[$k] === '') $errors[] = 'Campo obrigatório: ' . $k;
    $endereco = $d['rua'] . ', ' . $d['numero'] . ($d['complemento'] !== '' ? ' - ' . $d['complemento'] : '') . ', ' . $d['bairro'] . ', ' . $d['cidade'];
    if (mb_strlen($endereco) > 255) $errors[] = 'Endereço completo deve ter no máximo 255 caracteres.';
}
if ($errors) {
    $_SESSION['old'] = $d;
    $_SESSION['errors'] = $errors;
    header('Location: ../form.php', true, 303);
    exit;
}
try {
    require dirname(__DIR__) . '/config/database.php';
    // Migração compatível com o esquema original, preservando os registros.
    $schema = file_get_contents(dirname(__DIR__) . '/database/schema.sql');
    $pdo->exec($schema);
    $sql = $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='identificacoes_pedido'")->fetchColumn();
    if (is_string($sql) && strpos($sql, "'credito'") === false) {
        $pdo->beginTransaction();
        $pdo->exec('ALTER TABLE identificacoes_pedido RENAME TO identificacoes_antigas');
        $pdo->exec($schema);
        $pdo->exec("INSERT INTO identificacoes_pedido(id,nome,tipo_pedido,endereco,forma_pagamento,created_at) SELECT id,nome,tipo_pedido,endereco,CASE WHEN forma_pagamento='cartao' THEN 'credito' ELSE forma_pagamento END,created_at FROM identificacoes_antigas");
        $pdo->exec('DROP TABLE identificacoes_antigas');
        $pdo->commit();
    }
    $stmt = $pdo->prepare('INSERT INTO identificacoes_pedido(nome,tipo_pedido,endereco,forma_pagamento) VALUES (:nome,:tipo,:endereco,:pag)');
    $stmt->execute([':nome' => $d['nome'], ':tipo' => $d['tipo_pedido'], ':endereco' => $endereco, ':pag' => $d['pagamento']]);
    $id = (int)$pdo->lastInsertId();
    unset($_SESSION['csrf_token']);
    $labels = ['dinheiro' => 'Dinheiro', 'pix' => 'PIX', 'credito' => 'Cartão de Crédito', 'debito' => 'Cartão de Débito'];
    $pedido = new Pedido($carrinho);
    $dadosPedido = ['nome' => $d['nome'], 'tipo_entrega' => $d['tipo_pedido'], 'pagamento' => $labels[$d['pagamento']]];
    foreach (['rua', 'numero', 'complemento', 'bairro', 'cidade'] as $k) $dadosPedido[$k] = $d[$k];
    $linkWhatsApp = $pedido->gerarLinkWhatsApp($dadosPedido, (string)$id);
    $carrinho->limpar();
    session_write_close();
    header('Location: ' . $linkWhatsApp, true, 303);
    exit;
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    error_log('Erro ao salvar identificação: ' . $e->getMessage());
    http_response_code(500);
    exit('Não foi possível registrar o pedido. Tente novamente.');
}
