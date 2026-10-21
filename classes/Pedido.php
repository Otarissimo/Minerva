<?php
ini_set('default_charset', 'UTF-8');

require_once 'Carrinho.php';

class Pedido
{
    private Carrinho $carrinho;
    private string $codigoPedido;
    private string $telefoneLanchonete = "---";


    public function __construct(Carrinho $carrinho)
    {
        $this->carrinho = $carrinho;
        $this->codigoPedido = $this->gerarCodigo();
    }

    /**
     * Gera um código identificador aleatório de 4 dígitos para o pedido
     */
    private function gerarCodigo(): string
    {
        return '#' . rand(1000, 9999);
    }

    /**
     * Retorna o código gerado para o pedido
     */
    public function getCodigoPedido(): string
    {
        return $this->codigoPedido;
    }


    /**
     * Gera o link do WhatsApp
     */
    public function gerarMensagemWhatsApp(array $dadosCliente, string $codigoPedido): string
    {
        $emojiHambuerguer  = "\u{1F354}";
        $emojiCliente      = "\u{1F464}";
        $emojiCarrinho     = "\u{1F6D2}";
        $emojiSacoDinheiro = "\u{1F4B0}";
        $emojiRelogio      = "\u{1F552}";
        $bullet            = "\u{2022}";

        $dataHora = (new DateTime('now', new DateTimeZone('America/Sao_Paulo')))->format('d/m/Y \à\s H:i');

        $texto = "{$emojiHambuerguer} *PEDIDO {$codigoPedido}* {$emojiHambuerguer}\n";
        $texto .= "{$emojiRelogio} {$dataHora}\n";
        $texto .= "━━━━━━━━━━━━━━━━━━━\n\n";

        // Dados do Cliente
        $texto .= "{$emojiCliente} *DADOS DO CLIENTE*\n";
        $texto .= "{$bullet} *Nome:* " . trim($dadosCliente['nome']) . "\n";

        if ($dadosCliente['tipo_entrega'] === 'entrega') {
            $texto .= "{$bullet} *Tipo:* Entrega em Casa\n";

            $endereco = trim($dadosCliente['rua']) . ", Nº " . trim($dadosCliente['numero']);
            if (!empty($dadosCliente['complemento'])) {
                $endereco .= " (" . trim($dadosCliente['complemento']) . ")";
            }
            $endereco .= " - Bairro: " . trim($dadosCliente['bairro']);
            $endereco .= " - " . trim($dadosCliente['cidade']);

            $texto .= "{$bullet} *Endereço:* " . $endereco . "\n";
        } else {
            $texto .= "{$bullet} *Tipo:* Retirada no Local\n";
        }

        $texto .= "{$bullet} *Pagamento:* " . $dadosCliente['pagamento'] . "\n\n";

        // Itens do Pedido
        $texto .= "{$emojiCarrinho} *RESUMO DOS ITENS*\n";
        foreach ($this->carrinho->getItens() as $item) {
            $precoUnitario = number_format($item['preco'], 2, ',', '.');
            $subtotalItem = number_format($item['preco'] * $item['quantidade'], 2, ',', '.');
            $texto .= "{$bullet} {$item['quantidade']}x {$item['nome']} (R$ {$precoUnitario} un.) — *R$ {$subtotalItem}*\n";
        }

        // Valor Total
        $total = number_format($this->carrinho->calcularTotal(), 2, ',', '.');
        $texto .= "\n━━━━━━━━━━━━━━━━━━━\n";
        $texto .= "{$emojiSacoDinheiro} *TOTAL A PAGAR:* R$ " . $total;

        return $texto;
    }

    public function gerarLinkWhatsApp(array $dadosCliente, ?string $codigoPedido = null): string
    {
        $mensagem = $this->gerarMensagemWhatsApp($dadosCliente, $codigoPedido ?? $this->codigoPedido);
        $telefone = preg_replace('/\D/', '', $this->telefoneLanchonete);

        return "https://wa.me/{$telefone}?text=" . rawurlencode($mensagem);
    }
}
