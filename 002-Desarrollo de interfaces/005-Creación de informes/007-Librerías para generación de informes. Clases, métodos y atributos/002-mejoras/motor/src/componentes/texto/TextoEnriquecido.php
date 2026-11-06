<?php
class TextoEnriquecido extends TextoAvanzado {
    public function render(): string {
        $html='<div class="texto-enriquecido">';
        foreach ($this->config['fragmentos'] ?? [] as $f) {
            $txt=$this->interpolar((string)($f['texto'] ?? ''));
            $estilos=[];
            if (!empty($f['negrita'])) $estilos[]='font-weight:bold';
            if (!empty($f['cursiva'])) $estilos[]='font-style:italic';
            if (!empty($f['subrayado'])) $estilos[]='text-decoration:underline';
            if (isset($f['color']) && preg_match('/^#[0-9a-fA-F]{6}$/D',$f['color'])) $estilos[]='color:'.$f['color'];
            $txt='<span style="'.implode(';',$estilos).'">'.$txt.'</span>';
            if (isset($f['enlace']) && preg_match('~^https?://[^\s"<>]+$~i',$f['enlace'])) $txt='<a rel="noopener noreferrer" href="'.$this->e($f['enlace']).'">'.$txt.'</a>';
            $html.=$txt;
        }
        return $html.'</div>';
    }
}
