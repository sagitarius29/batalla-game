<!doctype html>
<html>
  <head>
    <title>Socket.IO chat</title>
    <style>
      table td {
      	width: 25px;
      	height: 25px;
      	text-align: center;
      }
      .left {
      	float: left;
      }
      .us {
      	width: 200px;
      }
      td {
      	cursor: pointer;
      }

      td:hover {
      	background-color: #ccc;
      }

      .select {
      	background: #235097;
      }
      .select_shot {
      	background: #3d6db5;
      }
      .select_dead {
      	background: #1a2a42;
      }
      .shot {
      	background: #9e2a2a;
      }
      .shot_reciv {
      	background: #ea7070;
      }
      .dead {
      	background: black;
      }
      .dead_reciv {
      	background: #4c4c4c;
      }
      .dead_reciv_dead {
      	background: #1c0808;
      }
      .clear {
      	clear: both;
      	margin: 0px;
      	padding: 0px;
      }
      #esperando {
      	display: block;
		position: absolute;
		top: 40%;
		background: black;
		width: 88%;
		color: white;
		padding: 5%;
		font-size: 25px;
		font-family: sans-serif;
		opacity: .8;
  		text-align: center;
  		display: none;
      }
    </style>
    <script src="./socket.io-1.2.0.js"></script>
    <script src="./jquery-1.11.1.js"></script>
  </head>
  <body>
  	<div id="esperando">Esperando el turno de tu oponente...</div>
  	<div><center><h2 id="batalla"></h2></center></div>
	<div class="clear"></div>
    <div class="left us">
    	<div class="jugando" style="display:none;">
    		
    		<div>
	    		Campo de Batalla:<br>
	    		<ul id="campo"></ul>
	    	</div>
    	</div>
    	Jugadores<br>
    	<ul id="users"></ul>
    </div>
    <div class="left" id="matrix" style="display:none;">
    	<table border="1">
    		<tr>
    			<td>...</td>
    			<?php 
    			//$letras = "ABCDEFGHIJKMNLOPQRSTUVWXYZ";
    			$letras = "ABCDEFG";
    			for ($i=0; $i < 7; $i++) { //26
    				echo '<th>'.$letras[$i].'</th>';
    			}
    			?>
    		</tr>
    		<?php 
    		for ($i=1; $i < 7; $i++) { 
    			echo '<tr>';
    			echo '<th>'.$i.'</th>';
    			for ($a=0; $a < 7; $a++) { 
    				echo '<td id="'.$letras[$a].$i.'"></td>';
    			}
    			echo '</tr>';
    		}
    		?>
    	<?php 

    	?>
    	</table>
    </div>
  </body>
  <script>
	  var socket = io("192.168.0.100:3000");
	  var nombre = prompt("Ingrese un nombre");
	  var oponente;
	  var jid;

	  //Juego
	  var idgame;

	  //Tipo de accion
	  var action = 1; // 1 nuevo soldado, 2 disparar
	  
	  socket.emit('nombres', {name : nombre});
	  
	  $('form').submit(function(){
	    socket.emit('chat message', name+ ": " + $('#m').val());
	    $('#m').val('');
	    return false;
	  });

	  socket.on('online', function(data) {
	  	console.log(data);
	  	$('#users').html("");
	  	/*data.forEach(function(valor, index) {
	  		//$('#users').append($('<li>').text(valor.nombre));
	  		console.log(index);
	  	});*/
	  	jid = socket.io.engine.id;
	  	$.each(data, function(index, value) {

	  		

	  		if(jid == index)
	  		{
	  			$('#users').append($('<li>').html(value.nombre));
	  		}
	  		else
	  		{
	  			if(value.game != 0) {
	  				$('#users').append($('<li>').html(value.nombre + ' (Jugando)'));
	  			} else {
	  				$('#users').append($('<li>').html(value.nombre + ' <button onClick="retar(this);" jid="' + index + '">Retar</button>'));
	  			}
	  		}
	  	});
	  });

	  socket.on('posicion', function(data) {
	  	console.log(data);
	  	pintar(data);
	  	/*data.forEach(function(valor, index) {
	  		//$('#users').append($('<li>').text(valor.nombre));
	  		console.log(index);
	  	});*/
	  });
	  socket.on('connected', function(data) {
	  	console.log("conectado");
	  });

	  socket.on('retador', function(data) {
	  	console.log(data);
	  	if(confirm(data.msj))
	  	{
	  		socket.emit('aceptar', {retador: data.jid});
	  	}
	  	//pintar(data);
	  	/*data.forEach(function(valor, index) {
	  		//$('#users').append($('<li>').text(valor.nombre));
	  		console.log(index);
	  	});*/
	  });

	  //##### Nuevo Juego ######

	  socket.on('nuevo_juego', function(data) {
	  	idgame = data.gid;

	  	$('#campo').html("");
	  	console.log(data);
	  	//Generando lista de batalla
	  	$.each(data.users, function(index, value) {
	  		if(jid != index)
	  		{
	  			oponente = index;
	  		}

	  		var estado = "Agregando Soldados";
	  		if (value.listo == 1) {
	  			estado = "Listo";
	  		}

	  		$('#campo').append($('<li>').html(value.name + ' ('+estado+')'));
	  	});

	  	$("#batalla").text(data.batalla);
	  	$(".jugando").show('slow');
	  	$("#matrix").show('slow');
	  });

	  socket.on('status', function(data) {
	  	$('#campo').html("");
	  	console.log(data);
	  	//Generando lista de batalla
	  	$.each(data.users, function(index, value) {
	  		var estado = "Agregando Soldados";
	  		if (value.listo == 1) {
	  			estado = value.soldados+" Soldados";
	  		}

	  		$('#campo').append($('<li>').html(value.name + ' ('+estado+')'));
	  	});
	  });

	  socket.on('add_soldier', function(data) {
	  	if(data.status == 1) {
	  		pintar(data.cord);
	  	} else {
	  		action = 2;
	  	}
	  });
	  socket.on('shoting', function(data) {
	  	console.log(data);
	  	if (data.status == 1) {
	  		//$("#"+data.cord).attr('class',data.css);
	  		shot_reciv("#"+data.cord, data.css);
	  	} else {
	  		alert(data.msj);
	  	}
	  });

	  socket.on('turn', function(data) {
	  	if (data.status == 1) {
	  		$("#esperando").hide('slow');
	  	} else {
	  		$("#esperando").show('slow');
	  	}
	  });


		$("td").click(function() {
			//console.log($(this).attr('id'));
			if (action == 1) {
				socket.emit('add_soldier', {game: idgame, oponente: oponente, cord: $(this).attr('id')});
			}
			else
			{
				socket.emit('shoting', {game: idgame, oponente: oponente, cord: $(this).attr('id')});
			}
			//pintar($(this).attr('id'));
		});

	function pintar(id) {
		var css;
		if (action == 1) {
			css = "select";
		}
		else if(action == 2) {
			css = "shot";
		}

		if($("#"+id).hasClass(css)) {
			$("#"+id).removeClass(css);
		}
		else
		{
			$("#"+id).addClass(css);
		}
	}

	function shot_reciv(id, css) {
		if($(id).hasClass("shot_reciv")) {
			if(css == "dead_reciv") {
				$(id).attr('class',"dead_reciv_dead");
			} else {
				$(id).attr('class',css);
			}
		} else if($(id).hasClass("dead_reciv")) {
			
		} else if($(id).hasClass("dead")) {

		} else if($(id).hasClass("shot")) {

		} else if($(id).hasClass("select")) {
			if(css == 'shot')
			{
				$(id).attr('class',"select_shot");
			} else {
				$(id).attr('class',"select_dead");
			}
		} else {
			$(id).attr('class',css);
		}
	}

	function retar(element) {
		var id_a_retar = $(element).attr('jid');
		var mi_id = socket.io.engine.id;

		if(mi_id != id_a_retar) {
			socket.emit('reto', {reto: id_a_retar, name: nombre});
		}
		return false;
	}

	// Acciones de Botones del juego
	$("#add_soldier").click(function() {
		console.log("Anadiendo soldado");
		action = 1;
		return false;
	});

	$("#shot").click(function() {
		console.log("Modo Disparo");
		action = 2;
		return false;
	});


	</script>
</html>