<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/classes/Horario.php';

function garantirTabelaConfig(PDO $pdo): void
{
    $pdo->exec('CREATE TABLE IF NOT EXISTS configuracoes (chave TEXT PRIMARY KEY, valor TEXT NOT NULL)');
}

/** Chaveador do dono. Sem registro = não fechada manualmente. */
function lojaManualAberta(PDO $pdo): bool
{
    garantirTabelaConfig($pdo);
    $v = $pdo->query("SELECT valor FROM configuracoes WHERE chave = 'loja_aberta'")->fetchColumn();
    return $v === false ? true : $v === '1';
}

function definirLojaAberta(PDO $pdo, bool $aberta): void
{
    garantirTabelaConfig($pdo);
    $stmt = $pdo->prepare("INSERT OR REPLACE INTO configuracoes (chave, valor) VALUES ('loja_aberta', :v)");
    $stmt->execute([':v' => $aberta ? '1' : '0']);
}

/** A loja recebe pedidos? Dono não fechou E está dentro do horário. */
function lojaAberta(PDO $pdo): bool
{
    return lojaManualAberta($pdo) && (new Horario())->estaAberto();
}

/** ['aberto' => bool, 'mensagem' => string] para exibir no site do cliente. */
function lojaStatus(PDO $pdo): array
{
    if (!lojaManualAberta($pdo)) {
        return ['aberto' => false, 'mensagem' => 'Fechado no momento'];
    }
    return (new Horario())->getStatus();
}
