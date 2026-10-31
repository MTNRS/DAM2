Navegador	Cuota mundial
Internet Explorer	46,94 %
Mozilla Firefox	30,76 %
Google Chrome	14,85 %
Safari	4,79 %
Opera	2,07 %

Operación	Internet Explorer antiguo	Firefox / Chrome / Safari
Seleccionar por ID	getElementById()	getElementById()
Seleccionar por etiqueta	getElementsByTagName()	Igual
Seleccionar por clase	Sin soporte nativo en IE 6–8	getElementsByClassName()
Selectores CSS	querySelector() en IE8+	querySelector()
Añadir eventos	attachEvent()	addEventListener()
Eliminar eventos	detachEvent()	removeEventListener()
Obtener evento	window.event	Parámetro event
Elemento que disparó evento	srcElement	target
Cancelar comportamiento	returnValue = false	preventDefault()
Detener propagación	cancelBubble = true	stopPropagation()
Texto de elemento	innerText	textContent
Peticiones AJAX	ActiveXObject (IE6 antiguo) o XMLHttpRequest	XMLHttpRequest