<?php require_once 'lib/lib.php'; ?>
<!DOCTYPE html>
<html>
<head>
	<title>Nuevo Contenido - LocalTorrent</title>
	<?php require_once 'includes/header.php'; ?>
	<link rel="stylesheet" type="text/css" href="<?php echo Utils::addArchivoNoCache("css/nuevo.css"); ?>">
</head>
<body>
	<?php require_once 'includes/sideMenu.php'; ?>

	<div class="container">
		<h1>Nuevo Contenido</h1>

		<input type="text" id="search" placeholder="Buscar">

		<table>
			<thead>
				<tr>
					<th>Titulo</th>
					<th>Idioma</th>
					<th>Calidad</th>
					<th>Tamaño</th>
					<th>#</th>
				</tr>
			</thead>
			<tbody id="torrents"></tbody>
		</table>
		
	</div>
	<script src="<?php echo Utils::addArchivoNoCache("js/nuevo.js"); ?>"></script>
</body>
</html>