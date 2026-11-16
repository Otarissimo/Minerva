<?php

declare(strict_types=1);

function garantirTabelasCardapio(PDO $pdo): void
{
    $pdo->exec("
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
    CREATE TABLE IF NOT EXISTS categorias (
        nome TEXT PRIMARY KEY,
        descricao TEXT NOT NULL DEFAULT ''
    );
    CREATE TABLE IF NOT EXISTS configuracoes (chave TEXT PRIMARY KEY, valor TEXT NOT NULL);
    ");
}

/** Copia o cardápio original para o banco, uma única vez. */
function importarCardapioInicial(PDO $pdo): void
{
    $flag = $pdo->query("SELECT valor FROM configuracoes WHERE chave = 'cardapio_importado'")->fetchColumn();
    if ($flag !== false) {
        return;
    }

    $vazio = (int)$pdo->query('SELECT COUNT(*) FROM produtos')->fetchColumn() === 0;

    $pdo->beginTransaction();
    try {
        if ($vazio) {
            $insCat  = $pdo->prepare('INSERT OR IGNORE INTO categorias (nome, descricao) VALUES (:n, :d)');
            $insProd = $pdo->prepare('INSERT INTO produtos (nome, categoria, preco, descricao, disponivel) VALUES (:n, :c, :p, :d, :v)');

            foreach (cardapioOriginal() as [$cat, $descCat, $itens]) {
                $insCat->execute([':n' => $cat, ':d' => $descCat]);
                foreach ($itens as [$nome, $desc, $preco, $disp]) {
                    $insProd->execute([':n' => $nome, ':c' => $cat, ':p' => $preco, ':d' => $desc, ':v' => $disp ? 1 : 0]);
                }
            }
        }
        $pdo->exec("INSERT OR REPLACE INTO configuracoes (chave, valor) VALUES ('cardapio_importado', '1')");
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

/**
 * Cardápio no formato que o index.php usa:
 * [ ['nome'=>..., 'descricao'=>..., 'itens'=>[ id => [nome, descricao, preco, disponivel, foto] ]], ... ]
 * Categorias e produtos aparecem na ordem de cadastro (id).
 */
function carregarCardapio(PDO $pdo): array
{
    garantirTabelasCardapio($pdo);
    importarCardapioInicial($pdo);

    $descricoes = $pdo->query('SELECT nome, descricao FROM categorias')->fetchAll(PDO::FETCH_KEY_PAIR);
    $linhas     = $pdo->query('SELECT * FROM produtos ORDER BY id')->fetchAll();

    $cats = [];
    foreach ($linhas as $r) {
        $c = $r['categoria'];
        if (!isset($cats[$c])) {
            $cats[$c] = ['nome' => $c, 'descricao' => $descricoes[$c] ?? '', 'itens' => []];
        }
        $cats[$c]['itens'][(int)$r['id']] = [
            'nome'       => $r['nome'],
            'descricao'  => $r['descricao'],
            'preco'      => (float)$r['preco'],
            'disponivel' => (int)$r['disponivel'] === 1,
            'foto'       => $r['foto'] ? 'uploads/produtos/' . $r['foto'] : null,
        ];
    }
    return array_values($cats);
}

/** Cardápio original, usado só na importação inicial. [categoria, descrição, [[nome, descrição, preço, disponível]]] */
function cardapioOriginal(): array
{
    return [
        ['Cervejas e Chopes', 'Geladas na temperatura certa. Marcas sujeitas à disponibilidade do dia.', [
            ['Long neck Pilsen 355ml', 'Brahma, Skol, Antarctica ou Original.', 13.00, true],
            ['Long neck premium 355ml', 'Heineken, Stella Artois, Budweiser ou Corona.', 17.00, true],
            ['Cerveja lata 350ml', 'Pilsen tradicional: Brahma, Skol, Antarctica ou Amstel.', 7.50, true],
            ['Garrafa 600ml', 'Brahma, Skol, Antarctica ou Original.', 16.00, true],
            ['Chope Pilsen 500ml', 'Brahma ou Antarctica.', 15.00, true],
            ['Chope escuro 430ml', 'Brahma Black.', 15.00, true],
        ]],
        ['Drinks e Destilados', 'Doses de 50ml e coquetéis clássicos da casa. Marcas sujeitas à disponibilidade.', [
            ['Caipirinha de cachaça', 'Limão, cachaça Velho Barreiro ou 51, açúcar e gelo.', 24.00, true],
            ['Caipirinha de saquê', 'Limão, saquê Azuma Kirin, açúcar e gelo.', 25.00, true],
            ['Caipirinha de vodka', 'Limão, vodka Smirnoff ou Absolut, açúcar e gelo.', 26.00, true],
            ['Gin tônica nacional', 'Gin Seagers ou Rock\'s, água tônica, limão siciliano e alecrim.', 26.00, true],
            ['Gin tônica importado', 'Gin Tanqueray, Beefeater ou Bombay Sapphire, água tônica, limão siciliano e alecrim.', 34.00, true],
            ['Dose de cachaça', 'Velho Barreiro ou 51, dose de 50ml.', 7.00, true],
            ['Dose de whisky Red Label', 'Johnnie Walker Red Label, dose de 50ml.', 22.00, true],
            ['Dose de whisky Jack Daniel\'s', 'Jack Daniel\'s Old No. 7, dose de 50ml.', 30.00, false],
            ['Dose de whisky Black Label', 'Johnnie Walker Black Label, dose de 50ml.', 32.00, true],
        ]],
        ['Sem Álcool', 'Para o motorista da rodada e para quem prefere leve.', [
            ['Refrigerante lata 350ml', 'Coca-Cola, Guaraná Antarctica, Fanta ou Sprite.', 7.00, true],
            ['Água mineral 500ml', 'Com ou sem gás.', 5.00, true],
            ['Suco natural jarra 1L', 'Laranja, limão, maracujá ou abacaxi com hortelã.', 22.00, true],
            ['Red Bull 250ml', 'Sabores: Tradicional, Sem açúcar, Tropical, Melancia ou Pêssego.', 15.00, true],
            ['Monster 473ml', 'Sabores: Original, Zero Açúcar, Ultra Violet, Mango Loco ou Pipeline Punch.', 15.00, true],
        ]],
        ['Petiscos e Porções', 'Para compartilhar. Porções de 500g servidas em bandeja, indicadas para 2 ou 3 pessoas.', [
            ['Batata frita 500g', 'Batatas fritas em palito, servidas com cheddar e bacon à parte.', 35.00, true],
            ['Isca de frango 500g', 'Tiras de peito de frango empanadas e fritas, com molho de mostarda e mel.', 42.00, true],
            ['Frango a passarinho 500g', 'Pedaços de frango fritos, temperados com alho, acompanha limão.', 50.00, true],
            ['Calabresa acebolada 500g', 'Rodelas de calabresa grelhada com cebola, acompanha farofa.', 38.00, true],
            ['Bolinho de bacalhau (10 un)', 'Bolinhos fritos de massa de batata recheados com bacalhau desfiado.', 48.00, true],
            ['Torresmo de rolo 400g', 'Barriga de porco enrolada, assada e frita.', 38.00, false],
        ]],
        ['Pratos da Casa', 'Servidos das 11h às 15h e das 18h às 23h.', [
            ['Prato feito do dia', 'Arroz, feijão, proteína (carne, frango ou peixe), salada e fritas.', 38.00, true],
            ['Feijoada individual', 'Com arroz, couve, farofa e laranja.', 45.00, true],
            ['Parmegiana de frango', 'Filé empanado, molho, queijo, arroz e fritas.', 46.00, true],
            ['Picanha na chapa (2 pessoas)', '400g de picanha, fritas, arroz e vinagrete.', 230.00, true],
        ]],
        ['Sobremesas', 'Para fechar a conta com doçura.', [
            ['Pudim de leite', 'Pudim de leite condensado com calda de caramelo.', 16.00, true],
            ['Brownie de chocolate', 'Bolo de chocolate meio amargo servido com calda de chocolate ao leite', 22.00, true],
            ['Petit gâteau', 'Bolinho de chocolate com centro cremoso servido quente com bola de sorvete de creme.', 28.00, false],
        ]],
    ];
}
