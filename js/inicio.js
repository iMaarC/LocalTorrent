setInterval(obtener_torrents, 2000);

obtener_torrents();

function obtener_torrents() {
	$.ajax({
		url : "ajax.php?peticion_ajax_key=obtener_torrents",
		type: "GET",
		success: function(data) {
			data = JSON.parse(data);

			if(data["errors"] != undefined) {
				for(var i = 0; i < data["errors"].length; i++) {
					mostrarErrores(data["errors"][i]);
				}

				return;
			}

			mostrarInfo(data["info"]);
			actualizarTorrents(data);
		},
		error: function(data) {}
	});	
}

function eliminarTorrent(id, rutaBBDD) {
	$.ajax({
		url : "ajax.php?peticion_ajax_key=eliminar_torrent&idTorrent=" + id + "&rutaBBDD=" + rutaBBDD,
		type: "GET",
		success: function(data) {
			data = JSON.parse(data);

			if(data["errors"] != undefined) {
				for(var i = 0; i < data["errors"].length; i++) {
					mostrarErrores(data["errors"][i]);
				}

				return;
			}
			
			obtener_torrents();
			mostrarInfo(data["info"]);
		},
		error: function(data) {}
	});	
}

function pausaPlay(id) {
	$.ajax({
		url : "ajax.php?peticion_ajax_key=pausa_play_torrent&idTorrent=" + id,
		type: "GET",
		success: function(data) {
			data = JSON.parse(data);

			if(data["errors"] != undefined) {
				for(var i = 0; i < data["errors"].length; i++) {
					mostrarErrores(data["errors"][i]);
				}

				return;
			}
			
			obtener_torrents();
			mostrarInfo(data["info"]);
		},
		error: function(data) {}
	});	
}

function actualizarTorrents(arrayTorrents) {
	$("#torrents").html("");

	for (var i = 0; i < arrayTorrents.length; i++) {
		var tiempoEstimado = 0
		var tempTiempo = arrayTorrents[i].tiempoEstimado;

		if(tempTiempo.toString().indexOf("-") == -1){
			if(tempTiempo >= 3600) {
				tiempoEstimado = (tempTiempo / 3600).toFixed(0) + " H";
			} else if(tempTiempo >= 60) {
				tiempoEstimado = (tempTiempo / 60).toFixed(0) + " Min";
			} else {
				tiempoEstimado = tempTiempo + " Seg";
			}
		} 

		$("#torrents").append(`<tr>
			<td>`+ arrayTorrents[i].nombre +`</td>
			<td>
				<div class="progressBarContainer `+ (arrayTorrents[i].finalizado == false ? "activo" : "acabado") +`" style="width: `+ arrayTorrents[i].completado +`%">
					<span>`+ arrayTorrents[i].completado.toFixed(2) +`%</span>
				</div>
			</td>
			<td>`+ arrayTorrents[i].ratioDescarga.toFixed(2) +` MB/s</td>
			<td>`+ tiempoEstimado +`</td>
			<td><i class="fa fa-`+ (arrayTorrents[i].isPausado == true ? "play" : "pause") +`" onclick="pausaPlay(`+ arrayTorrents[i].idTorrent +`)"></i></td>
			<td><i class="fa fa-trash" onclick="eliminarTorrent(`+ arrayTorrents[i].idTorrent +`, '`+ arrayTorrents[i].rutaBBDD +`')"></i></td>
		</tr>`);
	}
}

function mostrarErrores(error){
	$("div.error").remove();
	$("div.info").remove();
	$("div.container").prepend("<div class='error'>"+ error +"</div>");
}

function mostrarInfo(info){
	if(info != undefined) {
		for(var i = 0; i < info.length; i++) {
			$("div.info").remove();
			$("div.error").remove();
			$("div.container").prepend("<div class='info'>"+ info[i] +"</div>");
		}
	}
}