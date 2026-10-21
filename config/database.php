<?php

declare(strict_types =1);

$database_path = dirname(__DIR__) . '/database/lanchonete.sqlite';

$pdo = new PDO('sqlite:' . $database_path);

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

$pdo->exec('PRAGMA foreign_keys = ON');