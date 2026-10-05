class Entrada {
  constructor(){
    this.teclas = new Set();
    addEventListener("keydown", e => { this.teclas.add(e.code); if(["ArrowUp","ArrowDown","ArrowLeft","ArrowRight","Space"].includes(e.code)) e.preventDefault(); });
    addEventListener("keyup", e => this.teclas.delete(e.code));
  }
  pulsada(codigo){ return this.teclas.has(codigo); }
}
