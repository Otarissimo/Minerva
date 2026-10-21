<?php

class Horario
{
    private DateTimeZone $fuso;

    // 0 = domingo ... 6 = sábado. null = fechado o dia todo.
    // Se o fechamento for menor que a abertura, o expediente passa da meia-noite.
    private array $expediente = [
        0 => ['18:00', '23:30'],
        1 => null,
        2 => ['18:00', '23:00'],
        3 => ['18:00', '23:00'],
        4 => ['18:00', '23:00'],
        5 => ['18:00', '00:30'],
        6 => ['18:00', '00:30'],
    ];

    private array $nomesDias = ['domingo', 'segunda', 'terça', 'quarta', 'quinta', 'sexta', 'sábado'];

    public function __construct()
    {
        $this->fuso = new DateTimeZone('America/Sao_Paulo');
    }

    public function estaAberto(): bool
    {
        return $this->expedienteAtual() !== null;
    }

    public function getStatus(): array
    {
        $agora = $this->agora();
        $atual = $this->expedienteAtual();

        if ($atual !== null) {
            return [
                'aberto'   => true,
                'mensagem' => 'Aberto agora · fecha às ' . $atual[1]->format('H:i'),
            ];
        }

        $proxima = $this->proximaAbertura();

        return [
            'aberto'   => false,
            'mensagem' => $proxima === null
                ? 'Fechado'
                : 'Fechado · abre ' . $this->descreverDia($proxima, $agora) . ' às ' . $proxima->format('H:i'),
        ];
    }

    private function agora(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', $this->fuso);
    }

    private function intervaloDoDia(DateTimeImmutable $dia): ?array
    {
        $horario = $this->expediente[(int) $dia->format('w')] ?? null;
        if ($horario === null) {
            return null;
        }

        [$h1, $m1] = array_map('intval', explode(':', $horario[0]));
        [$h2, $m2] = array_map('intval', explode(':', $horario[1]));

        $inicio = $dia->setTime($h1, $m1);
        $fim = $dia->setTime($h2, $m2);

        if ($fim <= $inicio) {
            $fim = $fim->modify('+1 day');
        }

        return [$inicio, $fim];
    }

    private function expedienteAtual(): ?array
    {
        $agora = $this->agora();

        // Ontem entra na conta por causa do expediente que vira a meia-noite
        foreach ([-1, 0] as $offset) {
            $intervalo = $this->intervaloDoDia($agora->modify("$offset day"));
            if ($intervalo !== null && $agora >= $intervalo[0] && $agora < $intervalo[1]) {
                return $intervalo;
            }
        }

        return null;
    }

    private function proximaAbertura(): ?DateTimeImmutable
    {
        $agora = $this->agora();

        for ($offset = 0; $offset <= 7; $offset++) {
            $intervalo = $this->intervaloDoDia($agora->modify("+$offset day"));
            if ($intervalo !== null && $intervalo[0] > $agora) {
                return $intervalo[0];
            }
        }

        return null;
    }

    private function descreverDia(DateTimeImmutable $data, DateTimeImmutable $agora): string
    {
        $diferenca = (int) $agora->setTime(0, 0)->diff($data->setTime(0, 0))->days;

        return match ($diferenca) {
            0 => 'hoje',
            1 => 'amanhã',
            default => $this->nomesDias[(int) $data->format('w')],
        };
    }
}
