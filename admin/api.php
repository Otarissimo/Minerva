<?php

declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

function resp(array $dados, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') resp(['ok' => false, 'erro' => 'Método inválido'], 405);
if (!admin_logado($pdo)) resp(['ok' => false, 'erro' => 'Sessão expirada. Entre novamente.'], 401);
if (!csrf_valido($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) resp(['ok' => false, 'erro' => 'Token inválido'], 403);

$in   = json_decode((string)file_get_contents('php://input'), true) ?: [];
$acao = (string)($in['acao'] ?? '');

try {
    switch ($acao) {
        case 'toggle_loja':
            definirLojaAberta($pdo, (bool)($in['aberta'] ?? false));
            resp(['ok' => true, 'aberta' => lojaManualAberta($pdo)]);

        case 'toggle_produto':
            $pdo->prepare('UPDATE produtos SET disponivel = :d WHERE id = :id')
                ->execute([':d' => !empty($in['disponivel']) ? 1 : 0, ':id' => (int)($in['id'] ?? 0)]);
            resp(['ok' => true]);

        case 'excluir_produto':
            $id = (int)($in['id'] ?? 0);
            $stmt = $pdo->prepare('SELECT foto FROM produtos WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $foto = $stmt->fetchColumn();
            $pdo->prepare('DELETE FROM produtos WHERE id = :id')->execute([':id' => $id]);
            if (is_string($foto) && preg_match('/^[a-f0-9]{24}\.(jpg|png|webp)$/', $foto)) {
                @unlink(dirname(__DIR__) . '/uploads/produtos/' . $foto);
            }
            resp(['ok' => true]);

        default:
            resp(['ok' => false, 'erro' => 'Ação desconhecida'], 400);
    }
} catch (Throwable $e) {
    error_log('api admin: ' . $e->getMessage());
    resp(['ok' => false, 'erro' => 'Erro no servidor'], 500);
}
