<?php

declare(strict_types=1);

require_once __DIR__ . '/Carrinho.php';

class Pedido
{
    private Carrinho $carrinho;
    private string $codigoPedido;
    private string $telefoneLanchonete = "551127854260";


    public function __construct(Carrinho $carrinho)
    {
        $this->carrinho = $carrinho;
        $this->codigoPedido = '#' . random_int(1000, 9999);
    }

    /**
     * Retorna o código gerado para o pedido
     */
    public function getCodigoPedido(): string
    {
        return $this->codigoPedido;
    }

    /**
     * Gera a mensagem formatada para o WhatsApp
     */
    public function gerarMensagemWhatsApp(array $dadosCliente, string $codigoPedido): string
    {
        $agora    = new DateTime('now', new DateTimeZone('America/Sao_Paulo'));
        $dataHora = $agora->format('d/m/Y') . ' às ' . $agora->format('H:i');

        $eBurger   = "\u{1F354}"; // 🍔
        $eRelogio  = "\u{1F552}"; // 🕒
        $eCliente  = "\u{1F464}"; // 👤
        $eCarrinho = "\u{1F6D2}"; // 🛒
        $eDinheiro = "\u{1F4B0}"; // 💰
        $bolinha   = "\u{2022}";  // •
        $travessao = "\u{2014}";  // —
        $linha     = str_repeat("\u{2501}", 19);

        $t  = "{$eBurger} *PEDIDO {$codigoPedido}* {$eBurger}\n";
        $t .= "{$eRelogio} {$dataHora}\n";
        $t .= "{$linha}\n\n";

         // Dados do cliente
        $t .= "{$eCliente} *DADOS DO CLIENTE*\n";
        $t .= "{$bolinha} *Nome:* " . trim($dadosCliente['nome']) . "\n";

        if ($dadosCliente['tipo_entrega'] === 'entrega') {
            $t .= "{$bolinha} *Tipo:* Entrega em Casa\n";

            $endereco = trim($dadosCliente['rua']) . ', Nº ' . trim($dadosCliente['numero']);
            if (!empty($dadosCliente['complemento'])) {
                $endereco .= ' (' . trim($dadosCliente['complemento']) . ')';
            }
            $endereco .= ' - Bairro: ' . trim($dadosCliente['bairro']);
            $endereco .= ' - ' . trim($dadosCliente['cidade']);

            $t .= "{$bolinha} *Endereço:* {$endereco}\n";
        } else {
            $t .= "{$bolinha} *Tipo:* Retirada no Local\n";
        }

        $t .= "{$bolinha} *Pagamento:* " . $dadosCliente['pagamento'] . "\n";

         // Troco (apenas dinheiro)
        if (strcasecmp($dadosCliente['pagamento'], 'Dinheiro') === 0) {
            $troco = trim($dadosCliente['troco'] ?? '');
            $t .= $troco === ''
                ? "{$bolinha} *Troco:* Não precisa de troco\n"
                : "{$bolinha} *Troco:* Para {$troco}\n";
        }

        // Itens
        $t .= "\n{$eCarrinho} *RESUMO DOS ITENS*\n";
        foreach ($this->carrinho->getItens() as $item) {
            $unit = number_format((float)$item['preco'], 2, ',', '.');
            $subt = number_format((float)$item['preco'] * (int)$item['quantidade'], 2, ',', '.');
            $t .= "{$bolinha} {$item['quantidade']}x {$item['nome']} (R$ {$unit} un.) {$travessao} *R$ {$subt}*\n";
        }

        // Observação (opcional)
        $obs = trim($dadosCliente['observacao'] ?? '');
        if ($obs !== '') {
            $t .= "{$bolinha} *Observações:* {$obs}\n";
        }

        // Total
        $total = number_format((float)$this->carrinho->calcularTotal(), 2, ',', '.');
        $t .= "\n{$linha}\n";
        $t .= "{$eDinheiro} *TOTAL A PAGAR:* R$ {$total}";

        return $t;
    }

    public function gerarLinkWhatsApp(array $dadosCliente, ?string $codigoPedido = null): string
    {
        $mensagem = $this->gerarMensagemWhatsApp($dadosCliente, $codigoPedido ?? $this->codigoPedido);
        $telefone = preg_replace('/\D/', '', $this->telefoneLanchonete);

        return "https://api.whatsapp.com/send?phone={$telefone}&text=" . rawurlencode($mensagem);
    }
}
