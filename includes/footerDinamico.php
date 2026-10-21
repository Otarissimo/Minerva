<?php

/**
 * Footer dinâmico (localização com mapa + formas de pagamento).
 * Autor: Thiago. Integrado ao cardápio: use $myFooter->render() onde o rodapé deve aparecer.
 */
class DynamicFooter {
    private $addressName;
    private $mapEmbedUrl;
    private $paymentMethods;
    private $paceUrl;

    public function __construct(array $config) {
        $this->addressName = $config['addressName'] ?? '';
        $this->mapEmbedUrl = $config['mapEmbedUrl'] ?? '';
        $this->paymentMethods = $config['paymentMethods'] ?? [];
        $this->paceUrl = $config['paceUrl'] ?? '';
    }

    private static function e(string $v): string {
        return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
    }

    public function render(): void {
        $year = date('Y');
        $endereco = urlencode($this->addressName);
        $linkMaps = 'https://www.google.com/maps/search/?api=1&query=' . $endereco;
        $linkWaze = 'https://waze.com/ul?q=' . $endereco . '&navigate=yes';

        $paymentsHTML = '';
        foreach ($this->paymentMethods as $method) {
            $paymentsHTML .= sprintf(
                '<li class="payment-item"><img src="%s" alt="%s" title="%s" width="32" height="32" loading="lazy"><span>%s</span></li>',
                self::e($method['icon']),
                self::e($method['name']),
                self::e($method['name']),
                self::e($method['name'])
            );
        }

        $addr = self::e($this->addressName);
        $map = self::e($this->mapEmbedUrl);
        $pace = $this->paceUrl !== ''
            ? '<a class="pace" href="' . self::e($this->paceUrl) . '">Produzido por Pace</a>'
            : '';

        echo <<<HTML
<footer class="site-footer">
  <div class="footer-content">
    <div class="footer-section location">
      <h3>📍 Nossa Localização</h3>
      <p class="address-text">{$addr}</p>
      <div class="map-container">
        <iframe src="{$map}" title="Mapa da Lanchonete Minerva" width="100%" height="170" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
      </div>
      <div class="rotas">
        <a class="btn contorno" href="{$linkMaps}" target="_blank" rel="noopener">Abrir no Google Maps</a>
        <a class="btn contorno" href="{$linkWaze}" target="_blank" rel="noopener">Abrir no Waze</a>
      </div>
    </div>

    <div class="footer-section payments">
      <h3>💳 Formas de Pagamento</h3>
      <ul>{$paymentsHTML}</ul>
    </div>
  </div>
  <div class="footer-bottom">
    <p>&copy; {$year} - Todos os direitos reservados.</p>
    {$pace}
  </div>
</footer>
HTML;
    }
}

$footerData = [
    'addressName' => 'Rua Aracê, 449 - Vila Formosa, São Paulo - SP, 03362-050',
    'mapEmbedUrl' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3657.162517131451!2d-46.545271323914214!3d-23.562605961639427!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x94ce5ddb1e3d26a7%3A0xc6e8d7493fefdf81!2sRua%20Arac%C3%AA%2C%20449%20-%20Vila%20Formosa%2C%20S%C3%A3o%20Paulo%20-%20SP%2C%2003362-050!5e0!3m2!1spt-BR!2sbr!4v1792537519193!5m2!1spt-BR!2sbr',
    'paymentMethods' => [
        ['name' => 'Pix', 'icon' => 'https://img.icons8.com/color/48/pix.png'],
        ['name' => 'Visa', 'icon' => 'https://img.icons8.com/color/48/visa.png'],
        ['name' => 'Mastercard', 'icon' => 'https://img.icons8.com/color/48/mastercard.png']
    ],
    // Botão "Produzido por Pace" (vem da variável $urlPace do index.php)
    'paceUrl' => $urlPace ?? ''
];

$myFooter = new DynamicFooter($footerData);
