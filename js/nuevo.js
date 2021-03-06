var timeoutSearch;
var ajaxRequests = [];
$("#minCaracteres").hide();

$.ajaxSetup({
    beforeSend: function(jqXHR) {
        ajaxRequests.push(jqXHR);
    }
});

$('#search').on('keyup', function(){
	clearTimeout(timeoutSearch);

	timeoutSearch = setTimeout(function(){
		var numPaginaEliteTorrent = 0;
		var valorBuscar = $('#search').val().trim();
		$("#torrents").html('');

		if(valorBuscar.length >= 3) {
			$("#minCaracteres").hide();
			$("#loadingImg").show();
			cancelarAjax();

			mejorTorrent(valorBuscar);
			eliteTorrent(valorBuscar, numPaginaEliteTorrent);
		} else {
			$("#minCaracteres").show();
			$("#loadingImg").hide();
			cancelarAjax();
		}
	}, 300);
});

function mejorTorrent(input){
	$.ajax({
		url : "ajax.php?peticion_ajax_key=nuevo_contenido&pagina=mejorTorrent&input=" + input,
		type: "GET",
		success: function(data) {
			data = JSON.parse(data);

			if(data["errors"] != undefined) {
				for(var i = 0; i < data["errors"].length; i++) {
					mostrarErrores(data["errors"][i]);
				}

				return;
			}
			
			mostrarTorrents(data["MejorTorrent"]);
			mostrarInfo(data["info"]);
		},
		error: function(data) {}
	});	
}

function eliteTorrent(input, numPagina) {
	$.ajax({
		url : "ajax.php?peticion_ajax_key=nuevo_contenido&pagina=eliteTorrent&input=" + input + "&pagEliteTorrent=" + numPagina,
		type: "GET",
		success: function(data) {
			data = JSON.parse(data);

			if(numPagina == 0) {
				numPaginaEliteTorrent = data;
			}

			if(data["errors"] != undefined) {
				for(var i = 0; i < data["errors"].length; i++) {
					mostrarErrores(data["errors"][i]);
				}

				return;
			}
			
			mostrarTorrents(data["EliteTorrent"] == undefined ? [] : data["EliteTorrent"]);
			mostrarInfo(data["info"]);

			if(numPagina < numPaginaEliteTorrent) {
				numPagina++;
				eliteTorrent(input, numPagina);
			} else {
				$("#loadingImg").hide();
			}
		},
		error: function(data) {}
	});
}

function mostrarTorrents(arrayTorrents) {
	for (var i = 0; i < arrayTorrents.length; i++) {
		$("#torrents").append(`<tr>
			<td>`+ arrayTorrents[i].nombre +`</td>
			<td>`+ arrayTorrents[i].idioma +`</td>
			<td>`+ arrayTorrents[i].calidad +`</td>
			<td>`+ (arrayTorrents[i].size / 1048576 ).toFixed(2) +` GB</td>
			<td>
				<i class="fa fa-arrow-circle-o-down" onclick="descargarTorrent('`+arrayTorrents[i].url+`', '`+arrayTorrents[i].nombre+`', '`+arrayTorrents[i].idioma+`', '`+arrayTorrents[i].calidad+`', '`+arrayTorrents[i].size+`', '`+arrayTorrents[i].img+`')"></i>
			</td>
		</tr>`);
		
	}
}

function descargarTorrent(url, nombre, idioma, calidad, size, img) {
	$("#loadingImg").show();
	cancelarAjax();
	
	$.ajax({
		url : "ajax.php?peticion_ajax_key=descargar_torrent&url=" + url + "&nombre=" + nombre + "&calidad=" + calidad + "&size=" + size + "&img=" + img + "&idioma=" + idioma,
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
			$("#loadingImg").hide();
		},
		error: function(data) {}
	});	
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

function cancelarAjax() {
	for(var i = 0; i < ajaxRequests.length; i++) {
		ajaxRequests[i].abort(); 
	}
}