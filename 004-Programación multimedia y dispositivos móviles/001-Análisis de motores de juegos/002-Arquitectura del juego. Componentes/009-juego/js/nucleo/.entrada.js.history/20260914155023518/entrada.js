// Entrada
document.addEventListener("keydown", function(event) {
  switch (event.key) {
    case "ArrowUp":
      jugador.mover(0, -avance);
      break;

    case "ArrowDown":
      jugador.mover(0, avance);
      break;

    case "ArrowLeft":
      jugador.mover(-avance, 0);
      break;

    case "ArrowRight":
      jugador.mover(avance, 0);
      break;

    case " ":
      console.log("SPACE DOWN");
      jugador.disparar();
      break;
  }
});

document.addEventListener("keyup", function(event) {
  switch (event.key) {
    case "ArrowUp":
      console.log("UP");
      break;

    case "ArrowDown":
      console.log("DOWN");
      break;

    case "ArrowLeft":
      console.log("LEFT");
      break;

    case "ArrowRight":
      console.log("RIGHT");
      break;

    case " ":
      console.log("SPACE UP");
      break;
  }
});