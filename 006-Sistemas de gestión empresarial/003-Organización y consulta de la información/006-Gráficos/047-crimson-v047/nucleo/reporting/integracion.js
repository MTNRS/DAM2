// Cargar tras el renderizado de la cabecera y del menú principal.
(()=>{const link=document.createElement('a');link.href='informes/demo.html';link.textContent='Informes';link.className='jr-informes-link';const menu=document.querySelector('#modulos')||document.querySelector('#menu');if(menu)menu.append(link);else console.warn('Reporting: menú no encontrado');})();
