<?php
// token csrf
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/classes/Carrinho.php';
require_once __DIR__ . '/classes/Horario.php';

$carrinho = new Carrinho();
$status = (new Horario())->getStatus();
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Link do site da Pace (botão "Produzido por Pace" no rodapé)
$urlPace = '/grupos/pacetecnologia/';
require_once __DIR__ . '/includes/footerDinamico.php'; // rodapé: localização, mapa e pagamentos

// ---------------------------------------------------------------
// CARDÁPIO (dados de teste). Para cada item:
//   'foto' => 'assets/img/arquivo.jpg'  (ou null para não exibir foto)
// O id de cada item precisa ser único.
// ---------------------------------------------------------------
$cardapio = [
    ['nome' => 'Cervejas e Chopes', 'descricao' => 'Geladas na temperatura certa.', 'itens' => [
        1 => ['nome' => 'Long neck Pilsen 355ml', 'descricao' => 'Garrafa long neck, servida gelada.', 'preco' => 9.00, 'disponivel' => true, 'foto' => null],
        2 => ['nome' => 'Cerveja lata 350ml', 'descricao' => 'Pilsen tradicional.', 'preco' => 5.50, 'disponivel' => true, 'foto' => null],
        3 => ['nome' => 'Garrafa 600ml', 'descricao' => 'Pilsen, ideal para dividir na mesa.', 'preco' => 12.00, 'disponivel' => true, 'foto' => null],
        4 => ['nome' => 'Chope Pilsen 500ml', 'descricao' => 'Tirado na hora, colarinho cremoso.', 'preco' => 14.00, 'disponivel' => true, 'foto' => null],
        5 => ['nome' => 'IPA long neck 355ml', 'descricao' => 'Lupulada, amargor médio.', 'preco' => 16.00, 'disponivel' => true, 'foto' => null],
    ]],
    ['nome' => 'Drinks e Destilados', 'descricao' => 'Doses e coquetéis clássicos da casa.', 'itens' => [
        6 => ['nome' => 'Caipirinha', 'descricao' => 'Limão, cachaça, açúcar e gelo.', 'preco' => 18.00, 'disponivel' => true, 'foto' => null],
        7 => ['nome' => 'Gin tônica', 'descricao' => 'Gin, água tônica, limão siciliano e alecrim.', 'preco' => 26.00, 'disponivel' => true, 'foto' => null],
        8 => ['nome' => 'Dose de cachaça', 'descricao' => 'Cachaça artesanal envelhecida.', 'preco' => 8.00, 'disponivel' => true, 'foto' => null],
        9 => ['nome' => 'Dose de whisky', 'descricao' => 'Whisky 8 anos, 50ml.', 'preco' => 20.00, 'disponivel' => true, 'foto' => null],
    ]],
    ['nome' => 'Sem Álcool', 'descricao' => 'Para o motorista da rodada e para quem prefere leve.', 'itens' => [
        10 => ['nome' => 'Refrigerante lata 350ml', 'descricao' => 'Cola, guaraná ou limão.', 'preco' => 6.00, 'disponivel' => true, 'foto' => null],
        11 => ['nome' => 'Água mineral 500ml', 'descricao' => 'Com ou sem gás.', 'preco' => 4.00, 'disponivel' => true, 'foto' => null],
        12 => ['nome' => 'Suco natural jarra 1L', 'descricao' => 'Laranja, limão, maracujá ou abacaxi com hortelã.', 'preco' => 16.00, 'disponivel' => true, 'foto' => null],
        13 => ['nome' => 'Energético 250ml', 'descricao' => 'Lata gelada.', 'preco' => 12.00, 'disponivel' => true, 'foto' => null],
    ]],
    ['nome' => 'Petiscos e Porções', 'descricao' => 'Para compartilhar. Porções servidas em bandeja.', 'itens' => [
        14 => ['nome' => 'Batata frita 500g', 'descricao' => 'Crocante, com cheddar e bacon à parte.', 'preco' => 32.00, 'disponivel' => true, 'foto' => null],
        15 => ['nome' => 'Isca de frango 500g', 'descricao' => 'Empanada, acompanha molho de mostarda e mel.', 'preco' => 38.00, 'disponivel' => true, 'foto' => null],
        16 => ['nome' => 'Calabresa acebolada', 'descricao' => 'Calabresa artesanal, cebola e farofa.', 'preco' => 34.00, 'disponivel' => true, 'foto' => null],
        17 => ['nome' => 'Bolinho de bacalhau (10 un)', 'descricao' => 'Massa de batata, recheio generoso.', 'preco' => 42.00, 'disponivel' => true, 'foto' => null],
        18 => ['nome' => 'Torresmo de rolo 400g', 'descricao' => 'Sequinho por fora, macio por dentro.', 'preco' => 36.00, 'disponivel' => false, 'foto' => null],
    ]],
    ['nome' => 'Pratos da Casa', 'descricao' => 'Servidos das 11h às 15h e das 18h às 23h.', 'itens' => [
        19 => ['nome' => 'Prato feito do dia', 'descricao' => 'Arroz, feijão, mistura, salada e fritas.', 'preco' => 28.00, 'disponivel' => true, 'foto' => null],
        20 => ['nome' => 'Feijoada individual', 'descricao' => 'Com arroz, couve, farofa e laranja.', 'preco' => 36.00, 'disponivel' => true, 'foto' => null],
        21 => ['nome' => 'Parmegiana de frango', 'descricao' => 'Filé empanado, molho, queijo, arroz e fritas.', 'preco' => 42.00, 'disponivel' => true, 'foto' => null],
        22 => ['nome' => 'Picanha na chapa (2 pessoas)', 'descricao' => '400g de picanha, fritas, arroz e vinagrete.', 'preco' => 118.00, 'disponivel' => true, 'foto' => null],
    ]],
    ['nome' => 'Sobremesas', 'descricao' => 'Para fechar a conta com açúcar.', 'itens' => [
        23 => ['nome' => 'Pudim de leite', 'descricao' => 'Calda de caramelo.', 'preco' => 14.00, 'disponivel' => true, 'foto' => null],
        24 => ['nome' => 'Brownie com sorvete', 'descricao' => 'Chocolate meio amargo e sorvete de creme.', 'preco' => 16.00, 'disponivel' => true, 'foto' => null],
        25 => ['nome' => 'Petit gâteau', 'descricao' => 'Recheio cremoso de chocolate.', 'preco' => 22.00, 'disponivel' => true, 'foto' => null],
    ]],
];

// Lista plana (id => produto) usada para validar o carrinho
$produtos = [];
foreach ($cardapio as $cat) {
    foreach ($cat['itens'] as $id => $p) $produtos[$id] = $p;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        exit('Token CSRF inválido. Volte ao cardápio e atualize a página antes de tentar novamente.');
    }
    $id = filter_var($_POST['produto_id'] ?? null, FILTER_VALIDATE_INT);
    $acao = $_POST['acao'] ?? '';
    $qtd = filter_var($_POST['quantidade'] ?? 1, FILTER_VALIDATE_INT);
    if (!$id || !isset($produtos[$id]) || !in_array($acao, ['adicionar', 'atualizar', 'remover'], true) || ($acao !== 'remover' && (!$qtd || $qtd < 1 || $qtd > 100))) {
        http_response_code(400);
        exit('Dados do carrinho inválidos');
    }
    if ($acao === 'adicionar' && !$produtos[$id]['disponivel']) {
        http_response_code(400);
        exit('Produto indisponível no momento.');
    }
    $carrinho = new Carrinho();
    if ($acao === 'adicionar') $carrinho->adicionar($id, $produtos[$id]['nome'], $produtos[$id]['preco'], $qtd);
    elseif ($acao === 'atualizar') $carrinho->atualizarQuantidade($id, $qtd);
    else $carrinho->remover($id);
    // volta para o ponto da página onde o cliente estava
    header('Location: index.php#' . ($acao === 'adicionar' ? 'item-' . $id : 'carrinho'), true, 303);
    exit;
}
$itensCarrinho = $carrinho->getItens();
$qtdTotal = 0;
foreach ($itensCarrinho as $it) $qtdTotal += $it['quantidade'];
$totalFmt = number_format($carrinho->calcularTotal(), 2, ',', '.');

function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
function reais(float $v): string { return 'R$ ' . number_format($v, 2, ',', '.'); }
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title>Cardápio | Lanchonete Minerva</title>
<meta name="description" content="Cardápio digital da Lanchonete Minerva.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,700;1,500&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
<style>
:root{
  --preto:#0c0907; --espresso:#1a120d; --marrom:#5a3822; --caramelo:#c48a55;
  --creme:#efe3d0; --areia:#b8a48c; --linha:#3a2a1f;
  --serif:'Cormorant Garamond',Georgia,serif; --sans:'DM Sans',system-ui,sans-serif;
}
*{margin:0;padding:0;box-sizing:border-box}
html{scroll-behavior:smooth;scroll-padding-top:70px}
body{background:var(--preto);color:var(--creme);font-family:var(--sans);line-height:1.6;padding-bottom:84px}
a{color:inherit;text-decoration:none}
ul{list-style:none}
:focus-visible{outline:2px solid var(--caramelo);outline-offset:3px}

/* Capa */
.capa{background:linear-gradient(180deg,var(--marrom) 0,var(--espresso) 100%);padding:56px 20px 40px;text-align:center;border-bottom:1px solid var(--caramelo)}
.capa .casa{font-family:var(--serif);font-style:italic;font-size:1.25rem;color:var(--caramelo)}
.capa h1{font-family:var(--serif);font-weight:700;font-size:clamp(3.2rem,12vw,6.5rem);line-height:.95;margin:8px 0 22px}

/* Status aberto/fechado */
.status-loja{display:inline-flex;align-items:center;gap:8px;padding:7px 16px;border-radius:999px;font-size:.92rem;font-weight:700;border:1px solid}
.status-loja .bolinha{width:10px;height:10px;border-radius:50%}
.status-loja.aberto{background:#14261a;color:#7fd99a;border-color:#2f6b43}
.status-loja.aberto .bolinha{background:#4ade80;animation:piscar 1.5s infinite}
.status-loja.fechado{background:#2a1414;color:#f0a0a0;border-color:#703030}
.status-loja.fechado .bolinha{background:#e05252}
@keyframes piscar{50%{opacity:.3}}

/* Navegação */
.nav{position:sticky;top:0;z-index:10;background:var(--preto);border-bottom:1px solid var(--linha)}
.nav .wrap{max-width:900px;margin:0 auto;display:flex;gap:6px;overflow-x:auto;padding:12px 20px;scrollbar-width:none}
.nav .wrap::-webkit-scrollbar{display:none}
.nav a{white-space:nowrap;padding:6px 14px;border-radius:999px;font-size:.9rem;color:var(--areia);border:1px solid transparent}
.nav a:hover{color:var(--creme)}
.nav a.ativo{background:var(--marrom);color:var(--creme);border-color:var(--caramelo)}

/* Busca */
.busca{max-width:900px;margin:28px auto 0;padding:0 20px}
.busca input{width:100%;background:var(--espresso);border:1px solid var(--linha);color:var(--creme);padding:14px 16px;font:inherit;border-radius:4px}
.busca input::placeholder{color:var(--areia)}

/* Cardápio */
main{max-width:900px;margin:0 auto;padding:12px 20px 40px}
.categoria{margin-top:48px}
.categoria header{border-bottom:2px solid var(--caramelo);padding-bottom:10px;margin-bottom:6px}
.categoria h2{font-family:var(--serif);font-size:2.2rem;font-weight:700;line-height:1.1}
.categoria header p{color:var(--areia);font-size:.92rem}
.item{display:flex;align-items:center;gap:16px;padding:16px 0;border-bottom:1px dotted var(--linha)}
.item .foto{width:72px;height:72px;object-fit:cover;border-radius:4px;flex-shrink:0;border:1px solid var(--linha)}
.info{flex:1;min-width:0}
.info h3{font-family:var(--serif);font-size:1.3rem;font-weight:700;line-height:1.2}
.info p{color:var(--areia);font-size:.9rem;margin-top:2px}
.compra{display:flex;flex-direction:column;align-items:flex-end;gap:8px;flex-shrink:0}
.valor{color:var(--caramelo);font-weight:700;font-size:1.2rem;line-height:1.2;white-space:nowrap}
.item.indisponivel .info h3,.item.indisponivel .valor{color:#7a6a58;text-decoration:line-through}
.item.indisponivel .info p{color:#6b5d4d}
.tag-indisponivel{font-size:.72rem;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:var(--areia);border:1px solid var(--linha);padding:2px 8px;border-radius:999px;margin-left:8px;vertical-align:middle;text-decoration:none;display:inline-block}
.btn{display:inline-block;text-align:center;background:var(--caramelo);color:var(--preto);border:0;padding:8px 18px;font:inherit;font-weight:700;font-size:.88rem;border-radius:2px;cursor:pointer}
.btn:hover{background:var(--creme)}
.btn:disabled{background:var(--linha);color:var(--areia);cursor:not-allowed;opacity:.7}
.btn.contorno{background:transparent;color:var(--caramelo);border:1px solid var(--caramelo)}
.btn.contorno:hover{background:var(--caramelo);color:var(--preto)}
.vazio{display:none;text-align:center;color:var(--areia);padding:60px 0}

/* Carrinho */
.carrinho{max-width:900px;margin:0 auto;padding:0 20px 40px}
.carrinho .box{background:var(--espresso);border:1px solid var(--linha);border-top:2px solid var(--caramelo);padding:24px}
.carrinho h2{font-family:var(--serif);font-size:2.2rem;line-height:1.1;margin-bottom:12px}
.linha-carrinho{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:14px 0;border-bottom:1px dotted var(--linha)}
.linha-carrinho .nome{flex:1;min-width:160px}
.linha-carrinho .nome small{display:block;color:var(--areia)}
.qtd-form{display:flex;gap:6px;align-items:center}
.qtd-form input{width:62px;background:var(--preto);border:1px solid var(--linha);color:var(--creme);padding:7px;font:inherit;border-radius:2px;text-align:center}
.sub{color:var(--caramelo);font-weight:700;min-width:90px;text-align:right}
.total{display:flex;justify-content:space-between;align-items:center;margin-top:18px;font-family:var(--serif);font-size:1.8rem;font-weight:700}
.total span:last-child{color:var(--caramelo)}
.finalizar{display:block;width:100%;margin-top:16px;padding:14px;text-align:center;font-size:1rem}
.carrinho-vazio{color:var(--areia)}

/* Barra fixa do carrinho */
.barra{position:fixed;left:0;right:0;bottom:0;z-index:20;background:var(--marrom);border-top:1px solid var(--caramelo);padding:12px 20px calc(12px + env(safe-area-inset-bottom,0px))}
.barra a{max-width:900px;margin:0 auto;display:flex;justify-content:space-between;align-items:center;font-weight:700}
.barra .n{background:var(--preto);color:var(--caramelo);border-radius:999px;padding:2px 10px;margin-right:8px;font-size:.9rem}

/* Rodapé */
.site-footer{background:var(--espresso);border-top:1px solid var(--caramelo);color:var(--areia);font-size:.92rem}
.footer-content{max-width:900px;margin:0 auto;display:flex;flex-wrap:wrap;gap:32px;padding:40px 20px 28px}
.footer-section{flex:1;min-width:260px}
.footer-section h3{font-family:var(--serif);font-size:1.5rem;color:var(--creme);border-bottom:1px solid var(--caramelo);padding-bottom:6px;margin-bottom:14px}
.address-text{margin-bottom:14px}
.map-container{border:1px solid var(--linha);border-radius:2px;overflow:hidden;background:var(--preto)}
.map-container iframe{display:block;filter:grayscale(.4) contrast(.95)}
.rotas{display:flex;flex-wrap:wrap;gap:10px;margin-top:12px}
.rotas .btn{padding:8px 14px}
.payments ul{display:flex;flex-wrap:wrap;gap:14px}
.payment-item{display:flex;flex-direction:column;align-items:center;gap:6px;font-size:.8rem;background:var(--preto);border:1px solid var(--linha);padding:12px 10px;border-radius:2px;width:84px}
.footer-bottom{border-top:1px solid var(--linha);text-align:center;padding:18px 20px 24px;font-size:.85rem}
.pace{display:inline-block;margin-top:12px;padding:9px 20px;border:1px solid var(--caramelo);border-radius:2px;color:var(--caramelo);font-weight:500;font-size:.88rem}
.pace:hover{background:var(--caramelo);color:var(--preto)}

@media(max-width:560px){
  .item{flex-wrap:wrap}
  .item .foto{width:64px;height:64px}
  .compra{flex-direction:row;align-items:center;justify-content:space-between;width:100%}
  .sub{text-align:left}
}
@media(prefers-reduced-motion:reduce){html{scroll-behavior:auto}.status-loja .bolinha{animation:none}}
</style>
</head>
<body>

<div class="capa">
  <span class="casa">Bar &amp; Restaurante</span>
  <h1>Lanchonete Minerva</h1>
  <div id="status-loja" class="status-loja <?= $status['aberto'] ? 'aberto' : 'fechado' ?>">
    <span class="bolinha"></span>
    <span id="status-texto"><?= e($status['mensagem']) ?></span>
  </div>
</div>

<nav class="nav" aria-label="Categorias">
  <div class="wrap">
<?php foreach ($cardapio as $i => $cat): ?>
    <a href="#cat-<?= $i ?>"><?= e($cat['nome']) ?></a>
<?php endforeach; ?>
  </div>
</nav>

<div class="busca">
  <input type="search" id="busca" placeholder="Buscar item do cardápio..." aria-label="Buscar item">
</div>

<main>
<?php foreach ($cardapio as $i => $cat): ?>
  <section class="categoria" id="cat-<?= $i ?>">
    <header><h2><?= e($cat['nome']) ?></h2><p><?= e($cat['descricao']) ?></p></header>
    <ul>
<?php foreach ($cat['itens'] as $id => $p): ?>
      <li class="item<?= $p['disponivel'] ? '' : ' indisponivel' ?>" id="item-<?= $id ?>">
<?php if (!empty($p['foto'])): ?>
        <img class="foto" src="<?= e($p['foto']) ?>" alt="<?= e($p['nome']) ?>" loading="lazy">
<?php endif; ?>
        <div class="info">
          <h3><?= e($p['nome']) ?><?php if (!$p['disponivel']): ?><span class="tag-indisponivel">Indisponível</span><?php endif; ?></h3>
          <p><?= e($p['descricao']) ?></p>
        </div>
        <div class="compra">
          <span class="valor"><?= reais($p['preco']) ?></span>
          <form action="index.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="acao" value="adicionar">
            <input type="hidden" name="produto_id" value="<?= $id ?>">
            <button type="submit" class="btn" <?= $p['disponivel'] ? '' : 'disabled' ?>><?= $p['disponivel'] ? 'Adicionar' : 'Indisponível' ?></button>
          </form>
        </div>
      </li>
<?php endforeach; ?>
    </ul>
  </section>
<?php endforeach; ?>
  <p class="vazio" id="vazio">Nenhum item encontrado.</p>
</main>

<section class="carrinho" id="carrinho">
  <div class="box">
    <h2>Seu Carrinho</h2>
<?php if ($carrinho->estaVazio()): ?>
    <p class="carrinho-vazio">O carrinho está vazio. Escolha seus itens no cardápio acima.</p>
<?php else: ?>
<?php foreach ($itensCarrinho as $item): ?>
    <div class="linha-carrinho">
      <div class="nome"><?= e($item['nome']) ?><small><?= reais($item['preco']) ?> cada</small></div>
      <form class="qtd-form" action="index.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
        <input type="hidden" name="acao" value="atualizar">
        <input type="hidden" name="produto_id" value="<?= $item['id'] ?>">
        <input type="number" name="quantidade" value="<?= $item['quantidade'] ?>" min="1" max="100" aria-label="Quantidade de <?= e($item['nome']) ?>">
        <button type="submit" class="btn contorno">Atualizar</button>
      </form>
      <span class="sub"><?= reais($item['preco'] * $item['quantidade']) ?></span>
      <form action="index.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
        <input type="hidden" name="acao" value="remover">
        <input type="hidden" name="produto_id" value="<?= $item['id'] ?>">
        <button type="submit" class="btn contorno">Remover</button>
      </form>
    </div>
<?php endforeach; ?>
    <div class="total"><span>Total</span><span>R$ <?= $totalFmt ?></span></div>
    <a href="form.php" class="btn finalizar">Finalizar Pedido</a>
<?php endif; ?>
  </div>
</section>

<?php $myFooter->render(); ?>

<?php if (!$carrinho->estaVazio()): ?>
<div class="barra"><a href="#carrinho"><span><b class="n"><?= $qtdTotal ?></b> <?= $qtdTotal === 1 ? 'item' : 'itens' ?> no carrinho</span><span>R$ <?= $totalFmt ?> · Ver carrinho</span></a></div>
<?php endif; ?>

<script>
(function(){
  /* status aberto/fechado em tempo real */
  async function atualizarStatusLoja(){
    try{
      var r=await fetch('status.php',{cache:'no-store'}); var s=await r.json();
      var c=document.getElementById('status-loja');
      c.classList.toggle('aberto',s.aberto); c.classList.toggle('fechado',!s.aberto);
      document.getElementById('status-texto').textContent=s.mensagem;
    }catch(e){}
  }
  setInterval(atualizarStatusLoja,30000);

  /* busca */
  var busca=document.getElementById('busca'), vazio=document.getElementById('vazio');
  var secoes=document.querySelectorAll('.categoria');
  busca.addEventListener('input',function(){
    var t=busca.value.trim().toLowerCase(), total=0;
    secoes.forEach(function(s){
      var n=0;
      s.querySelectorAll('.item').forEach(function(i){
        var ok=i.textContent.toLowerCase().indexOf(t)>-1;
        i.style.display=ok?'':'none'; if(ok)n++;
      });
      s.style.display=n?'':'none'; total+=n;
    });
    vazio.style.display=total?'none':'block';
  });

  /* categoria ativa na barra (rola só a barra, não a página) */
  var links=document.querySelectorAll('.nav a'), wrap=document.querySelector('.nav .wrap'), atual=null;
  function ativar(id){
    if(id===atual)return; atual=id;
    links.forEach(function(l){
      var on=l.getAttribute('href')==='#'+id;
      l.classList.toggle('ativo',on);
      if(on) wrap.scrollTo({left:l.offsetLeft-(wrap.clientWidth-l.offsetWidth)/2,behavior:'auto'});
    });
  }
  var obs=new IntersectionObserver(function(es){
    es.forEach(function(e){ if(e.isIntersecting)ativar(e.target.id); });
  },{rootMargin:'-30% 0px -60% 0px'});
  secoes.forEach(function(s){obs.observe(s)});
})();
</script>
</body>
</html>
