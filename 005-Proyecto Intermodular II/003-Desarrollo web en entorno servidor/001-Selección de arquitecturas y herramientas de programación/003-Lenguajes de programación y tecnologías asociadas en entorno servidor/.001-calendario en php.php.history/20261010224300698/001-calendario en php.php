<!doctype html>
<html>
  <head>
    <style>
      #calendario{display:grid;grid-template-columns:repeat(7,1fr);}
      .dia{border:1px solid grey;}
    </style>
  </head>
  <body>
    <div id="calendario">
    </div>
    <script>
      let calendario = document.querySelector("#calendario")
      for(let dia = 1;dia<31;dia++){
      	calendario.innerHTML += '<div class="dia">'+dia+'</div>'
      }
    </script>
  </body>
</html>