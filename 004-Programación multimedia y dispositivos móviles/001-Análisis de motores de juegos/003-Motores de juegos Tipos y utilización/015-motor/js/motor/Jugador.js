/** Jugador sigue siendo genérico: sólo añade entrada y un mapa configurable de acciones. */
class Jugador extends Actor {
  constructor(config={}){
    super(config); this.controles=config.controles??{}; this.acciones=config.acciones??{};
  }
  actualizar(dt, escena){
    for(const [accion, teclas] of Object.entries(this.controles)){
      const lista=Array.isArray(teclas)?teclas:[teclas];
      if(lista.some(t=>escena.motor.entrada.pulsada(t))) this.acciones[accion]?.(this,dt,escena);
    }
    super.actualizar(dt,escena);
  }
}
