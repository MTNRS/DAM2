<?php
abstract class TextoAvanzado extends Componente {
    public function __construct(protected array $config, protected array $datos = []) { parent::__construct(); }
    protected function e(mixed $v): string { return escapar(is_scalar($v) || $v === null ? (string)$v : json_encode($v, JSON_UNESCAPED_UNICODE)); }
    protected function resolver(string $ruta): mixed {
        $v = $this->datos;
        foreach (explode('.', $ruta) as $parte) { if (!is_array($v) || !array_key_exists($parte,$v)) return null; $v=$v[$parte]; }
        return $v;
    }
    protected function interpolar(string $s): string {
        return preg_replace_callback('/\{\{\s*([a-zA-Z0-9_.]+)\s*\}\}/', fn($m) => $this->e($this->resolver($m[1]) ?? ''), $s);
    }
    protected function contenido(): string { return $this->interpolar((string)($this->config['texto'] ?? '')); }
}
