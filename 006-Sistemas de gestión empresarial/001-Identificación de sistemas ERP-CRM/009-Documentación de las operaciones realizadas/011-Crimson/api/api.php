<?php
	switch($_GET['bloque']){
  	case "menu":
    	echo '["clientes","pedidos","facturas"]';
      break;
    case "tabla":
    	echo '
      	{
          "productos": [
            {
              "id": 1,
              "nombre": "Portátil ProBook 15",
              "categoria": "Informática",
              "precio": 749.99,
              "stock": 12
            },
            {
              "id": 2,
              "nombre": "Monitor UltraView 27",
              "categoria": "Informática",
              "precio": 229.90,
              "stock": 25
            },
            {
              "id": 3,
              "nombre": "Teclado Mecánico K500",
              "categoria": "Periféricos",
              "precio": 79.95,
              "stock": 40
            },
            {
              "id": 4,
              "nombre": "Ratón Inalámbrico M200",
              "categoria": "Periféricos",
              "precio": 34.50,
              "stock": 65
            },
            {
              "id": 5,
              "nombre": "Disco SSD 1TB",
              "categoria": "Almacenamiento",
              "precio": 89.99,
              "stock": 30
            },
            {
              "id": 6,
              "nombre": "Memoria USB 128GB",
              "categoria": "Almacenamiento",
              "precio": 19.90,
              "stock": 120
            },
            {
              "id": 7,
              "nombre": "Webcam Full HD",
              "categoria": "Periféricos",
              "precio": 54.99,
              "stock": 18
            },
            {
              "id": 8,
              "nombre": "Auriculares Studio X",
              "categoria": "Audio",
              "precio": 69.50,
              "stock": 35
            },
            {
              "id": 9,
              "nombre": "Altavoces SoundBox",
              "categoria": "Audio",
              "precio": 49.95,
              "stock": 22
            },
            {
              "id": 10,
              "nombre": "Hub USB-C 8 en 1",
              "categoria": "Accesorios",
              "precio": 44.90,
              "stock": 50
            }
          ]
        }
      ';
      break;
  }
?>