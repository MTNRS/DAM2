<?php
class TextoCondicional extends TextoAvanzado {
    public function render(): string {
        $r=$this->config['regla'] ?? [];
        $actual=$this->resolver((string)($r['campo'] ?? ''));
        $esperado=$r['valor'] ?? null;
        $op=$r['operador'] ?? 'igual';
        $ok=match($op) {
            'igual'=>$actual === $esperado, 'distinto'=>$actual !== $esperado,
            'mayor'=>is_numeric($actual)&&is_numeric($esperado)&&(float)$actual>(float)$esperado,
            'menor'=>is_numeric($actual)&&is_numeric($esperado)&&(float)$actual<(float)$esperado,
            'mayor_igual'=>is_numeric($actual)&&is_numeric($esperado)&&(float)$actual>=(float)$esperado,
            'menor_igual'=>is_numeric($actual)&&is_numeric($esperado)&&(float)$actual<=(float)$esperado,
            'existe'=>$actual !== null,
            default=>false
        };
        return $ok?'<p class="texto-condicional">'.$this->contenido().'</p>':'';
    }
}
