<?php

class Carrinho
{
    private array $itens;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->itens = $_SESSION['carrinho'] ?? [];
    }

    /**
     * Adiciona um novo item ou incrementa a quantidade se já existir.
     */
    public function adicionar(int $id, string $nome, float $preco, int $quantidade = 1): void
    {
        if (isset($this->itens[$id])) {
            $this->itens[$id]['quantidade'] += $quantidade;
        } else {
            $this->itens[$id] = [
                'id'         => $id,
                'nome'       => $nome,
                'preco'      => $preco,
                'quantidade' => $quantidade
            ];
        }
        $this->salvar();
    }

    /**
     * Decrementa a quantidade em 1. Se for 1, remove o item.
     */
    public function decrementar(int $id): void
    {
        if (isset($this->itens[$id])) {
            if ($this->itens[$id]['quantidade'] > 1) {
                $this->itens[$id]['quantidade']--;
            } else {
                $this->remover($id);
                return;
            }
            $this->salvar();
        }
    }

    /**
     * Remove um item do carrinho.
     */
    public function remover(int $id): void
    {
        if (isset($this->itens[$id])) {
            unset($this->itens[$id]);
            $this->salvar();
        }
    }

    /**
     * Esvazia o carrinho.
     */
    public function limpar(): void
    {
        $this->itens = [];
        $this->salvar();
    }

    /**
     * Calcula o valor total do carrinho.
     */
    public function calcularTotal(): float
    {
        $total = 0.0;
        foreach ($this->itens as $item) {
            $total += $item['preco'] * $item['quantidade'];
        }
        return $total;
    }

    /**
     * Retorna a lista de itens.
     */
    public function getItens(): array
    {
        return $this->itens;
    }

    /**
     * Verifica se o carrinho está vazio.
     */
    public function estaVazio(): bool
    {
        return empty($this->itens);
    }

    /**
     * Persiste o estado atual da propriedade $itens na sessão.
     */
    private function salvar(): void
    {
        $_SESSION['carrinho'] = $this->itens;
    }
}
