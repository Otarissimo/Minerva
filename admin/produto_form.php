<?php

declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
exigir_admin($pdo);

function parse_preco(string $s): ?float
{
    $s = (string)preg_replace('/[^\d,.]/', '', $s);
    if ($s === '') return null;
    if (strpos($s, ',') !== false) {
        $s = str_replace('.', '', $s);
        $s = str_replace(',', '.', $s);
    }
    return is_numeric($s) ? round((float)$s, 2) : null;
}

function salvar_foto(array $f): ?string
{
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if ($f['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Não foi possível enviar a foto (arquivo grande demais?).');
    }
    if ($f['size'] > 8 * 1024 * 1024) {
        throw new RuntimeException('A foto deve ter no máximo 8 MB.');
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $ext  = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
    if ($ext === null || @getimagesize($f['tmp_name']) === false) {
        throw new RuntimeException('Formato de foto inválido. Use JPG, PNG ou WebP.');
    }

    $dir = dirname(__DIR__) . '/uploads/produtos';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        file_put_contents($dir . '/.htaccess', "Options -Indexes -ExecCGI\n<FilesMatch \"\\.(php|phtml|phar)$\">\n  Require all denied\n</FilesMatch>\n");
    }

    $nome = bin2hex(random_bytes(12)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $nome)) {
        throw new RuntimeException('Falha ao salvar a foto no servidor.');
    }
    return $nome;
}

function apagar_foto(?string $arquivo): void
{
    if ($arquivo && preg_match('/^[a-f0-9]{24}\.(jpg|png|webp)$/', $arquivo)) {
        @unlink(dirname(__DIR__) . '/uploads/produtos/' . $arquivo);
    }
}

$id = (int)($_GET['id'] ?? 0);
$produto = null;
if ($id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM produtos WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $produto = $stmt->fetch() ?: null;
    if (!$produto) {
        header('Location: produtos.php', true, 303);
        exit;
    }
}

$v = [
    'nome'      => $produto['nome'] ?? '',
    'categoria' => $produto['categoria'] ?? '',
    'preco'     => $produto ? number_format((float)$produto['preco'], 2, ',', '') : '',
    'descricao' => $produto['descricao'] ?? '',
];
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valido($_POST['csrf'] ?? null)) {
        $erros[] = 'Sessão expirada. Tente novamente.';
    }

    foreach (['nome', 'categoria', 'preco', 'descricao'] as $k) {
        $v[$k] = trim((string)($_POST[$k] ?? ''));
    }

    if (mb_strlen($v['nome']) < 2 || mb_strlen($v['nome']) > 80) $erros[] = 'O nome deve ter entre 2 e 80 caracteres.';
    if ($v['categoria'] === '' || mb_strlen($v['categoria']) > 40) $erros[] = 'Informe a categoria (até 40 caracteres).';
    if (mb_strlen($v['descricao']) > 150) $erros[] = 'A descrição deve ter no máximo 150 caracteres.';
    $preco = parse_preco($v['preco']);
    if ($preco === null || $preco < 0 || $preco > 99999) $erros[] = 'Informe um preço válido. Ex: 12,50';

    $novaFoto = null;
    if (!$erros) {
        try {
            $novaFoto = salvar_foto($_FILES['foto'] ?? ['error' => UPLOAD_ERR_NO_FILE]);
        } catch (RuntimeException $ex) {
            $erros[] = $ex->getMessage();
        }
    }

    if (!$erros) {
        if ($produto) {
            $pdo->prepare('UPDATE produtos SET nome=:n, categoria=:c, preco=:p, descricao=:d, foto=:f WHERE id=:id')
                ->execute([
                    ':n' => $v['nome'],
                    ':c' => $v['categoria'],
                    ':p' => $preco,
                    ':d' => $v['descricao'],
                    ':f' => $novaFoto ?? $produto['foto'],
                    ':id' => $produto['id']
                ]);
            if ($novaFoto) apagar_foto($produto['foto']);
        } else {
            $pdo->prepare('INSERT INTO produtos (nome, categoria, preco, descricao, foto) VALUES (:n,:c,:p,:d,:f)')
                ->execute([':n' => $v['nome'], ':c' => $v['categoria'], ':p' => $preco, ':d' => $v['descricao'], ':f' => $novaFoto]);
        }
        header('Location: produtos.php', true, 303);
        exit;
    }
}

$categorias = $pdo->query('SELECT DISTINCT categoria FROM produtos ORDER BY categoria COLLATE NOCASE')->fetchAll(PDO::FETCH_COLUMN);
?>
<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex">
    <title><?= $produto ? 'Editar produto' : 'Novo produto' ?> | Painel</title>
    <link rel="stylesheet" href="assets/admin.css">
</head>

<body>
    <main class="wrap">
        <header class="topo">
            <h1><?= $produto ? 'Editar produto' : 'Novo produto' ?></h1>
            <a class="btn peq" href="produtos.php">Voltar</a>
        </header>

        <?php if ($erros): ?>
            <div class="alerta erro" role="alert">
                <?php foreach ($erros as $er): ?><div><?= e($er) ?></div><?php endforeach ?>
            </div>
        <?php endif ?>

        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

            <label for="nome">Nome *</label>
            <input id="nome" name="nome" maxlength="80" required value="<?= e($v['nome']) ?>">

            <label for="categoria">Categoria *</label>
            <input id="categoria" name="categoria" maxlength="40" required list="lista-categorias"
                placeholder="Ex: Bebidas, Pratos, Sobremesas" value="<?= e($v['categoria']) ?>">
            <datalist id="lista-categorias">
                <?php foreach ($categorias as $c): ?><option value="<?= e($c) ?>"><?php endforeach ?>
            </datalist>

            <label for="preco">Preço (R$) *</label>
            <input id="preco" name="preco" inputmode="decimal" required placeholder="0,00" value="<?= e($v['preco']) ?>">

            <label for="descricao">Descrição curta</label>
            <textarea id="descricao" name="descricao" rows="3" maxlength="150"><?= e($v['descricao']) ?></textarea>

            <label>Foto</label>
            <img id="preview" class="preview" alt="Pré-visualização da foto"
                <?= !empty($produto['foto']) ? 'src="../uploads/produtos/' . e($produto['foto']) . '"' : 'hidden' ?>>

            <div class="duas">
                <label class="btn" for="foto-camera">Tirar foto</label>
                <label class="btn" for="foto-galeria">Galeria</label>
            </div>
            <input class="oculto foto-origem" id="foto-camera" type="file" accept="image/*" capture="environment">
            <input class="oculto foto-origem" id="foto-galeria" type="file" accept="image/*">
            <input class="oculto" id="foto" name="foto" type="file">

            <button type="submit" class="btn primario"><?= $produto ? 'Salvar alterações' : 'Cadastrar produto' ?></button>
        </form>
    </main>

    <script>
        const alvo = document.getElementById('foto');
        const prev = document.getElementById('preview');

        async function reduzir(file) {
            const bmp = await createImageBitmap(file);
            const escala = Math.min(1, 1200 / Math.max(bmp.width, bmp.height));
            const c = document.createElement('canvas');
            c.width = Math.round(bmp.width * escala);
            c.height = Math.round(bmp.height * escala);
            c.getContext('2d').drawImage(bmp, 0, 0, c.width, c.height);
            const blob = await new Promise(r => c.toBlob(r, 'image/jpeg', 0.85));
            return new File([blob], 'foto.jpg', {
                type: 'image/jpeg'
            });
        }

        document.querySelectorAll('.foto-origem').forEach(input => {
            input.addEventListener('change', async () => {
                const original = input.files[0];
                if (!original) return;
                let final = original;
                try {
                    final = await reduzir(original);
                } catch (e) {
                    /* envia o original */ }
                const dt = new DataTransfer();
                dt.items.add(final);
                alvo.files = dt.files;
                prev.src = URL.createObjectURL(final);
                prev.hidden = false;
                input.value = '';
            });
        });
    </script>
</body>

</html>