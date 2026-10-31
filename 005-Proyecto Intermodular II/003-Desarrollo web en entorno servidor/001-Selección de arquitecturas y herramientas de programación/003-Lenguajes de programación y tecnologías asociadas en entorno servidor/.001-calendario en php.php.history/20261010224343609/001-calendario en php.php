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
    <?php
    	for($dia = 1;$dia < 31;$dia++){
      	echo '<div class="dia">'.$dia.'</div>';
      }
    ?>
    </div>
  </body>
</html>