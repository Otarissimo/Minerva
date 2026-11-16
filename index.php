<?php
// token csrf
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}
require_once __DIR__ . '/classes/Carrinho.php';
require_once __DIR__ . '/classes/Horario.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/loja.php';
require_once __DIR__ . '/includes/cardapio.php';

$carrinho = new Carrinho();
$horario = new Horario();
$status = lojaStatus($pdo);          // considera o chaveador do dono + horário
$lojaAberta = $status['aberto'];
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

$urlPace = 'https://pacetecnologia.simplifica.gru.br/';
$horariosSemana = $horario->getHorariosSemana();
require_once __DIR__ . '/includes/footerDinamico.php';

$cardapio = carregarCardapio($pdo);

$produtos = [];
foreach ($cardapio as $cat) {
  foreach ($cat['itens'] as $id => $p) $produtos[$id] = $p;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $token = $_POST['csrf_token'] ?? '';
  if (!is_string($token) || !isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
      http_response_code(403);
      echo json_encode(['error' => 'Token CSRF inválido']);
      exit;
    }
    http_response_code(403);
    exit('Token CSRF inválido.');
  }

  $id = filter_var($_POST['produto_id'] ?? null, FILTER_VALIDATE_INT);
  $acao = $_POST['acao'] ?? '';
  $qtd = filter_var($_POST['quantidade'] ?? 1, FILTER_VALIDATE_INT);

  if ($acao !== 'limpar' && (!$id || !isset($produtos[$id]) || !in_array($acao, ['adicionar', 'decrementar', 'remover'], true))) {
    http_response_code(400);
    exit('Dados inválidos');
  }

  $carrinho = new Carrinho();
  if ($acao === 'adicionar') {
    if (!$lojaAberta) {
      http_response_code(409);
      exit('Loja fechada');
    }
    if (!$produtos[$id]['disponivel']) {
      http_response_code(400);
      exit('Produto indisponível');
    }
    $carrinho->adicionar($id, $produtos[$id]['nome'], $produtos[$id]['preco'], max(1, (int)$qtd));
  } elseif ($acao === 'decrementar') {
    $carrinho->decrementar($id);
  } elseif ($acao === 'remover') {
    $carrinho->remover($id);
  } elseif ($acao === 'limpar') {
    $carrinho->limpar();
  }

  // Suporte a AJAX para atualizar a interface sem recarregar a página
  if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    $itensCarrinho = $carrinho->getItens();
    $qtdTotal = 0;
    foreach ($itensCarrinho as $it) $qtdTotal += $it['quantidade'];
    $totalFmt = number_format($carrinho->calcularTotal(), 2, ',', '.');

    ob_start();
    if ($carrinho->estaVazio()) {
      echo '<p class="carrinho-vazio">O carrinho está vazio. Escolha seus itens no cardápio acima.</p>';
    } else {
      foreach ($itensCarrinho as $item) {
?>
        <div class="linha-carrinho">
          <div class="nome"><?= htmlspecialchars($item['nome'], ENT_QUOTES, 'UTF-8') ?><small>R$ <?= number_format($item['preco'], 2, ',', '.') ?> cada</small></div>
          <div class="carrinho-qtd-control">
            <form class="ajax-form" action="index.php" method="POST">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
              <input type="hidden" name="acao" value="adicionar">
              <input type="hidden" name="produto_id" value="<?= $item['id'] ?>">
              <button type="submit" class="btn contorno btn-qtd" title="Aumentar" <?= $lojaAberta ? '' : 'disabled' ?>>+</button>
            </form>
            <span class="carrinho-qtd-numero"><?= $item['quantidade'] ?></span>
            <form class="ajax-form" action="index.php" method="POST">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
              <input type="hidden" name="acao" value="decrementar">
              <input type="hidden" name="produto_id" value="<?= $item['id'] ?>">
              <button type="submit" class="btn contorno btn-qtd" title="Diminuir">−</button>
            </form>
          </div>
          <span class="sub">R$ <?= number_format($item['preco'] * $item['quantidade'], 2, ',', '.') ?></span>
        </div>
      <?php
      }
      ?>
      <div class="total"><span>Total</span><span>R$ <?= $totalFmt ?></span></div>
      <div class="carrinho-acoes">
        <form class="ajax-form form-limpar" action="index.php" method="POST">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="acao" value="limpar">
          <button type="submit" class="btn contorno btn-limpar">Limpar Carrinho</button>
        </form>
        <?php if ($lojaAberta): ?>
          <a href="form.php" class="btn btn-finalizar-link">Finalizar Pedido</a>
        <?php else: ?>
          <button type="button" class="btn btn-finalizar-link" disabled>Loja fechada</button>
        <?php endif; ?>
      </div>
    <?php
    }
    $carrinhoHtml = ob_get_clean();

    ob_start();
    if (!$carrinho->estaVazio()) {
    ?>
      <a href="#carrinho"><span><b class="n"><?= $qtdTotal ?></b> <?= $qtdTotal === 1 ? 'item' : 'itens' ?> no carrinho</span><span>R$ <?= $totalFmt ?> · Ver carrinho</span></a>
<?php
    }
    $barraHtml = ob_get_clean();

    echo json_encode([
      'success' => true,
      'carrinhoHtml' => $carrinhoHtml,
      'barraHtml' => $barraHtml,
      'estaVazio' => $carrinho->estaVazio()
    ]);
    exit;
  }

  header('Location: index.php#carrinho', true, 303);
  exit;
}

$itensCarrinho = $carrinho->getItens();
$qtdTotal = 0;
foreach ($itensCarrinho as $it) $qtdTotal += $it['quantidade'];
$totalFmt = number_format($carrinho->calcularTotal(), 2, ',', '.');

function e(string $v): string
{
  return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}
function reais(float $v): string
{
  return 'R$ ' . number_format($v, 2, ',', '.');
}
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
  <link rel="stylesheet" href="assets/index.css">
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
        <header>
          <h2><?= e($cat['nome']) ?></h2>
          <?php if ($cat['descricao'] !== ''): ?><p><?= e($cat['descricao']) ?></p><?php endif; ?>
        </header>
        <ul>
          <?php foreach ($cat['itens'] as $id => $p):
            $podeComprar = $p['disponivel'] && $lojaAberta;
          ?>
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
                <form class="ajax-form" action="index.php" method="POST">
                  <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                  <input type="hidden" name="acao" value="adicionar">
                  <input type="hidden" name="produto_id" value="<?= $id ?>">
                  <button type="submit" class="btn" <?= $podeComprar ? '' : 'disabled' ?>><?= !$p['disponivel'] ? 'Indisponível' : ($lojaAberta ? 'Adicionar' : 'Loja fechada') ?></button>
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
    <div class="box" id="carrinho-box">
      <h2>Seu Carrinho</h2>
      <?php if ($carrinho->estaVazio()): ?>
        <p class="carrinho-vazio">O carrinho está vazio. Escolha seus itens no cardápio acima.</p>
      <?php else: ?>
        <?php foreach ($itensCarrinho as $item): ?>
          <div class="linha-carrinho">
            <div class="nome"><?= e($item['nome']) ?><small><?= reais($item['preco']) ?> cada</small></div>
            <div class="carrinho-qtd-control">
              <form class="ajax-form" action="index.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="acao" value="adicionar">
                <input type="hidden" name="produto_id" value="<?= $item['id'] ?>">
                <button type="submit" class="btn contorno btn-qtd" title="Aumentar" <?= $lojaAberta ? '' : 'disabled' ?>>+</button>
              </form>
              <span class="carrinho-qtd-numero"><?= $item['quantidade'] ?></span>
              <form class="ajax-form" action="index.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="acao" value="decrementar">
                <input type="hidden" name="produto_id" value="<?= $item['id'] ?>">
                <button type="submit" class="btn contorno btn-qtd" title="Diminuir">−</button>
              </form>
            </div>
            <span class="sub"><?= reais($item['preco'] * $item['quantidade']) ?></span>
          </div>
        <?php endforeach; ?>
        <div class="total"><span>Total</span><span>R$ <?= $totalFmt ?></span></div>
        <div class="carrinho-acoes">
          <form class="ajax-form form-limpar" action="index.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="acao" value="limpar">
            <button type="submit" class="btn contorno btn-limpar">Limpar Carrinho</button>
          </form>
          <?php if ($lojaAberta): ?>
            <a href="form.php" class="btn btn-finalizar-link">Finalizar Pedido</a>
          <?php else: ?>
            <button type="button" class="btn btn-finalizar-link" disabled>Loja fechada</button>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <?php $myFooter->render(); ?>

  <div id="barra-container">
    <?php if (!$carrinho->estaVazio()): ?>
      <div class="barra"><a href="#carrinho"><span><b class="n"><?= $qtdTotal ?></b> <?= $qtdTotal === 1 ? 'item' : 'itens' ?> no carrinho</span><span>R$ <?= $totalFmt ?> · Ver carrinho</span></a></div>
    <?php endif; ?>
  </div>

  <script>
    (function() {
      /* Interceptação AJAX para evitar recarregamento da página (elimina o efeito de subir e descer) */
      document.addEventListener('submit', async function(e) {
        var form = e.target;
        if (!form.classList.contains('ajax-form')) return;
        e.preventDefault();

        var formData = new FormData(form);
        try {
          var response = await fetch('index.php', {
            method: 'POST',
            headers: {
              'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
          });

          if (response.ok) {
            var data = await response.json();
            if (data.success) {
              document.getElementById('carrinho-box').innerHTML = '<h2>Seu Carrinho</h2>' + data.carrinhoHtml;
              document.getElementById('barra-container').innerHTML = data.barraHtml ? '<div class="barra">' + data.barraHtml + '</div>' : '';
            }
          } else if (response.status === 409) {
            /* loja fechou enquanto o cliente navegava: recarrega para refletir o estado */
            location.reload();
          }
        } catch (err) {
          form.submit();
        }
      });

      /* status aberto/fechado em tempo real */
      async function atualizarStatusLoja() {
        try {
          var r = await fetch('status.php', {
            cache: 'no-store'
          });
          var s = await r.json();
          var c = document.getElementById('status-loja');
          var mudou = c.classList.contains('aberto') !== !!s.aberto;
          c.classList.toggle('aberto', s.aberto);
          c.classList.toggle('fechado', !s.aberto);
          document.getElementById('status-texto').textContent = s.mensagem;
          /* quando abre/fecha, recarrega para liberar ou bloquear os botões */
          if (mudou) location.reload();
        } catch (e) {}
      }
      setInterval(atualizarStatusLoja, 30000);

      /* busca */
      var busca = document.getElementById('busca'),
        vazio = document.getElementById('vazio');
      var secoes = document.querySelectorAll('.categoria');
      busca.addEventListener('input', function() {
        var t = busca.value.trim().toLowerCase(),
          total = 0;
        secoes.forEach(function(s) {
          var n = 0;
          s.querySelectorAll('.item').forEach(function(i) {
            var ok = i.textContent.toLowerCase().indexOf(t) > -1;
            i.style.display = ok ? '' : 'none';
            if (ok) n++;
          });
          s.style.display = n ? '' : 'none';
          total += n;
        });
        vazio.style.display = total ? 'none' : 'block';
      });

      /* categoria ativa na barra */
      var links = document.querySelectorAll('.nav a'),
        wrap = document.querySelector('.nav .wrap'),
        atual = null;

      function ativar(id) {
        if (id === atual) return;
        atual = id;
        links.forEach(function(l) {
          var on = l.getAttribute('href') === '#' + id;
          l.classList.toggle('ativo', on);
          if (on) wrap.scrollTo({
            left: l.offsetLeft - (wrap.clientWidth - l.offsetWidth) / 2,
            behavior: 'auto'
          });
        });
      }
      var obs = new IntersectionObserver(function(es) {
        es.forEach(function(e) {
          if (e.isIntersecting) ativar(e.target.id);
        });
      }, {
        rootMargin: '-30% 0px -60% 0px'
      });
      secoes.forEach(function(s) {
        obs.observe(s)
      });
    })();
  </script>
</body>

</html>