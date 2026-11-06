<?php
/** Imagen de informe: URL https o imagen embebida data:image (PNG/JPEG/GIF/WebP). */
final class Imagen extends Componente {
    private string $src;
    private string $alt;
    private string $pie;
    private int $ancho;
    public function __construct(array $datos) {
        parent::__construct('informe-imagen');
        $src=(string)($datos['src']??'');
        $http=(bool)preg_match('~^https?://[^\s<>"\']+$~i',$src);
        $data=(bool)preg_match('~^data:image/(?:png|jpeg|gif|webp);base64,[A-Za-z0-9+/=]+$~D',$src);
        if(!$http&&!$data)throw new InvalidArgumentException('Imagen: src debe ser URL HTTP(S) o data:image base64 válido.');
        if(strlen($src)>1500000)throw new InvalidArgumentException('Imagen demasiado grande.');
        $this->src=$src;
        $this->alt=(string)($datos['alt']??'');
        $this->pie=(string)($datos['pie']??'');
        $this->ancho=max(80,min(1200,(int)($datos['ancho']??640)));
    }
    public function render(): string {
        $esc=static fn(string $s):string=>htmlspecialchars($s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
        return '<figure class="informe-imagen"><img src="'.$esc($this->src).'" alt="'.$esc($this->alt).'" loading="lazy" style="max-width:100%;width:'.$this->ancho.'px;height:auto">'
            .($this->pie!==''?'<figcaption>'.$esc($this->pie).'</figcaption>':'').'</figure>';
    }
}
