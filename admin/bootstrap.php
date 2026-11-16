<?php
declare(strict_types=1);

date_default_timezone_set('America/Sao_Paulo');

function em_https(): bool
{
    return !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
}

if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => em_https(),
    ]);
    session_start();
}

require_once dirname(__DIR__) . '/config/database.php'; // cria $pdo
require_once dirname(__DIR__) . '/includes/loja.php';

$pdo->exec("
CREATE TABLE IF NOT EXISTS admins (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    login TEXT NOT NULL UNIQUE,
    senha_hash TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS admin_tokens (
    selector TEXT PRIMARY KEY,
    validator_hash TEXT NOT NULL,
    admin_id INTEGER NOT NULL REFERENCES admins(id) ON DELETE CASCADE,
    expira_em INTEGER NOT NULL
);
CREATE TABLE IF NOT EXISTS login_falhas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ip TEXT NOT NULL,
    criado_em INTEGER NOT NULL
);
CREATE TABLE IF NOT EXISTS produtos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome TEXT NOT NULL,
    categoria TEXT NOT NULL,
    preco REAL NOT NULL CHECK (preco >= 0),
    descricao TEXT NOT NULL DEFAULT '',
    foto TEXT,
    disponivel INTEGER NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
");

// ---------- Utilidades ----------

function e(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['admin_csrf'])) {
        $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['admin_csrf'];
}

function csrf_valido($token): bool
{
    return is_string($token)
        && isset($_SESSION['admin_csrf'])
        && hash_equals($_SESSION['admin_csrf'], $token);
}

/** E-mail vira minúsculo; telefone fica só com dígitos. */
function normalizar_login(string $login): string
{
    $login = trim($login);
    if (strpos($login, '@') !== false) {
        return mb_strtolower($login);
    }
    return (string)preg_replace('/\D/', '', $login);
}

// ---------- Proteção contra força bruta ----------

function login_bloqueado(PDO $pdo): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM login_falhas WHERE ip = :ip AND criado_em > :desde');
    $stmt->execute([':ip' => $_SERVER['REMOTE_ADDR'] ?? '', ':desde' => time() - 900]);
    return (int)$stmt->fetchColumn() >= 5;
}

function registrar_falha(PDO $pdo): void
{
    $pdo->prepare('INSERT INTO login_falhas (ip, criado_em) VALUES (:ip, :t)')
        ->execute([':ip' => $_SERVER['REMOTE_ADDR'] ?? '', ':t' => time()]);
}

function limpar_falhas(PDO $pdo): void
{
    $pdo->prepare('DELETE FROM login_falhas WHERE ip = :ip')
        ->execute([':ip' => $_SERVER['REMOTE_ADDR'] ?? '']);
}

// ---------- "Lembrar de mim" (token seguro, a senha nunca é guardada) ----------

function definir_cookie_lembrar(string $valor, int $expira): void
{
    setcookie('lembrar', $valor, [
        'expires'  => $expira,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => em_https(),
    ]);
}

function criar_lembrar(PDO $pdo, int $adminId): void
{
    $pdo->prepare('DELETE FROM admin_tokens WHERE expira_em < :t')->execute([':t' => time()]);

    $selector  = bin2hex(random_bytes(9));
    $validator = bin2hex(random_bytes(32));
    $expira    = time() + 60 * 60 * 24 * 30; // 30 dias

    $pdo->prepare('INSERT INTO admin_tokens (selector, validator_hash, admin_id, expira_em) VALUES (:s, :v, :a, :e)')
        ->execute([':s' => $selector, ':v' => hash('sha256', $validator), ':a' => $adminId, ':e' => $expira]);

    definir_cookie_lembrar($selector . ':' . $validator, $expira);
}

function restaurar_por_cookie(PDO $pdo): bool
{
    $cookie = $_COOKIE['lembrar'] ?? '';
    if (!is_string($cookie) || strpos($cookie, ':') === false) {
        return false;
    }
    [$selector, $validator] = explode(':', $cookie, 2);

    $stmt = $pdo->prepare('SELECT * FROM admin_tokens WHERE selector = :s AND expira_em > :t');
    $stmt->execute([':s' => $selector, ':t' => time()]);
    $tok = $stmt->fetch();

    if (!$tok || !hash_equals($tok['validator_hash'], hash('sha256', $validator))) {
        definir_cookie_lembrar('', time() - 3600);
        return false;
    }

    // Token de uso único: apaga o antigo e emite um novo
    $pdo->prepare('DELETE FROM admin_tokens WHERE selector = :s')->execute([':s' => $selector]);
    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int)$tok['admin_id'];
    criar_lembrar($pdo, (int)$tok['admin_id']);
    return true;
}

function encerrar_lembrar(PDO $pdo): void
{
    $cookie = $_COOKIE['lembrar'] ?? '';
    if (is_string($cookie) && strpos($cookie, ':') !== false) {
        $selector = explode(':', $cookie, 2)[0];
        $pdo->prepare('DELETE FROM admin_tokens WHERE selector = :s')->execute([':s' => $selector]);
    }
    definir_cookie_lembrar('', time() - 3600);
}

// ---------- Autenticação ----------

function admin_logado(PDO $pdo): bool
{
    if (!empty($_SESSION['admin_id'])) {
        return true;
    }
    return restaurar_por_cookie($pdo);
}

function exigir_admin(PDO $pdo): void
{
    if (!admin_logado($pdo)) {
        header('Location: login.php', true, 303);
        exit;
    }
}