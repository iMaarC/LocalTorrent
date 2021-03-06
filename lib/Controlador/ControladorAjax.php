<?php
require_once BASE_PATH.'/../includes/vendor/autoload.php';
use Transmission\Client;
use Transmission\Transmission;

/**
 * Clase BaseCtl que controla todas las peticiones ajax.
 * @author Jose Lorenzo, Marc Boule
 */
Class ControladorAjax extends BaseCtl {
	
	/**
	 * Gestor Ajax
	 */
	private $gestor = null;

	/**
	 * Constructor de la clase ControladorAjax, aqui controlamos todas las peticiones Ajax.
	 */
	public function __construct(){
		try{
			if (isset($_GET[PETICION_AJAX_KEY]) && !empty($_GET[PETICION_AJAX_KEY])) {
				$key = Utils::eliminarCaracteresEspeciales($_GET[PETICION_AJAX_KEY]);
				switch ($key) {
					case NUEVO_CONTENIDO:
						$this->isRutaDescargasConfigurado();

						$mejorTorrent = new MejorTorrent();
						$eliteTorrent = new EliteTorrent();

						$input = str_replace(" ", "+", $_GET["input"]);
						$pagina = $_GET["pagina"];

						if ($pagina == "mejorTorrent") {
							echo json_encode($mejorTorrent->obtenerTorrents($input));
						} else if($pagina == "eliteTorrent" && $_GET["pagEliteTorrent"] == "0") {
							echo $eliteTorrent->obtenerNpaginas($input);
						} else if($pagina == "eliteTorrent" && isset($_GET["pagEliteTorrent"])) {
							echo json_encode($eliteTorrent->obtenerResultados($_GET["pagEliteTorrent"], $input));
						} else {
							throw new Exception("Error en la petición!");
						}

						break;
					case DESCARGAR_TORRENT:
						$configuracionVO = $this->getGestor()->loadConfiguracionVO();
						$torrent = $this->parseTorrent();
						$rutaDescargas = $configuracionVO->getRutaDescargas() . "/" . $torrent->getCodigoTorrent();

						$this->isTorrentDuplicado($torrent);
						$this->isEspacioDisponible($_GET["url"], $configuracionVO->getRutaDescargas());
						$this->guardarTorrentTemp($_GET["url"]);

						$transmission = $this->getTransmissionObject($configuracionVO);

						$response = $transmission->getClient()->call('torrent-add', array(
						    'filename' => $this->getPathTorrentTemp(),
						    'download-dir' => $rutaDescargas
						));

						$this->eliminarTorrentTemp();

						if($response->result == "success") {
							$this->getGestor()->nuevoTorrent($torrent);

							$this->info[] = "Torrent añadido correctamente!";
							echo json_encode(array("info" => $this->info));
						} else {
							throw new Exception("No se ha podido añadir el torrent: " . $response->result);
						}

						break;
					case OBTENER_TORRENTS:
						echo json_encode($this->getTorrentsCliente());
						break;
					case ELIMINAR_TORRENT:
						if(isset($_GET["idTorrent"]) && $_GET["rutaBBDD"]) {
							$configuracionVO = $this->getGestor()->loadConfiguracionVO();
							$transmission = $this->getTransmissionObject($configuracionVO);

							$transmission->getClient()->call('torrent-remove', array(
							    'ids' => intval($_GET["idTorrent"]),
							    'delete-local-data' => true
							));

							$this->getGestor()->eliminarTorrent($_GET["rutaBBDD"]);

							$this->info[] = "Torrent eliminado correctamente!";
							echo json_encode(array("info" => $this->info));
						} else {
							throw new Exception("Error, no se puede eliminar el torrent!");
						}
						
						break;
					case PAUSA_PLAY_TORRENT:
						if(isset($_GET["idTorrent"])) {
							$configuracionVO = $this->getGestor()->loadConfiguracionVO();
							$transmission = $this->getTransmissionObject($configuracionVO);
							$isFound = false;

							foreach($transmission->all() as $torrent) {
								if($torrent->getId() == $_GET["idTorrent"]) {
									$isFound = true;

									if($torrent->getStatus() == 0) {
										$transmission->start($torrent);
										$this->info[] = "Torrent reanudado correctamente!";
									} else {
										$transmission->stop($torrent);
										$this->info[] = "Torrent pausado correctamente!";
									}

									break;
								}
							}

							if($isFound == true) {
								echo json_encode(array("info" => $this->info));	
							} else {
								throw new Exception("El torrent con ID (". $_GET["idTorrent"] .") no se ha encontrado");
							}
							
						} else {
							throw new Exception("No se puede reanudar/pausar la descarga.");
						}
						break;

					case OBTENER_RUTAS_CONTENIDO:
						if (isset($_GET["ruta"])) {
							$arrayRutasBuenas = array();
							$this->buscarEnDirectorio($_GET["ruta"], $arrayRutasBuenas);
							echo json_encode($arrayRutasBuenas);
						} else {
							throw new Exception("No se encuentra ruta indicada.");	
						}	
						break;
						
					default:
						throw new Exception("No se ha encontrado key");
						break;
				}
			}
		} catch (Exception $e) {
			$this->errors[] = $e->getMessage();
			echo json_encode(array("errors" => $this->errors));
		}
	}

	/**
	 * Obtenemos el GestorAjax
	 * @return BaseGestor
	 */
	private function getGestor() {
		if(is_null($this->gestor)) {
			$this->gestor = new GestorAjax();
		}

		return $this->gestor;
	}

	/**
	 * Obtener todos los torrents de un usuario.
	 * @return Array
	 */
	private function getTorrentsCliente() {
		$configuracionVO = $this->getGestor()->loadConfiguracionVO();
		$transmission = $this->getTransmissionObject($configuracionVO);
		$rutasUsuario = $this->getGestor()->getRutasTorrents();
		$torrents = array();

		foreach($transmission->all() as $torrent) {
			foreach($rutasUsuario as $ruta) {
				$rutaExplode = explode("/", $torrent->getDownloadDir());

				if($ruta == $rutaExplode[count($rutaExplode) - 1]) {
					array_push($torrents, array(
						"idTorrent" => $torrent->getId(),
						"nombre" => $torrent->getName(),
						"ratioDescarga" => $torrent->getDownloadRate() / 1000000,
						"tiempoEstimado" => $torrent->getEta(),
						"completado" => $torrent->getPercentDone(),
						"finalizado" => $torrent->isFinished(),
						"rutaBBDD" => $ruta,
						"isPausado" => ($torrent->getStatus() == 0 ? true : false)
					));
				}
			}
		}

		$this->updateTorrentsFinalizados($torrents, $transmission);

		return $torrents;
	}

	/**
	 * Actualizamos los torrents finalizados de la base de datos y los eliminamos del Transmission
	 * @param Array $torrents
	 * @param TransmissionObject $transmission
	 */
	private function updateTorrentsFinalizados($torrents, $transmission) {
		$this->getGestor()->updateTorrentsFinalizados($torrents, $transmission);
	}

	/**
	 * Obtenemos el objecto Transmission
	 * @return TransmissionObject $transmission
	 */
	private function getTransmissionObject($configuracionVO) {
		$transmission = new Transmission($configuracionVO->getTransmissionHost(), $configuracionVO->getTransmissionPuerto());
		$client = new Client();
		$client->authenticate($configuracionVO->getTransmissionUsuario(), $configuracionVO->getTransmissionPassword());
		$transmission->setClient($client);

		return $transmission;
	}

	/**
	 * Parseamos el torrent por $_GET y lo ponemos en el objecto TorrentVO
	 * @param TorrentVO $valueObject
	 */
	private function parseTorrent() {
		$valueObject = new TorrentVO();

		$valueObject->setIdUsuario($_SESSION["idUsuario"]);
		$valueObject->setCodigoTorrent(Utils::generarRandomString());
		$valueObject->setNombre($_GET["nombre"]);
		$valueObject->setSize($_GET["size"]);
		$valueObject->setCalidad($_GET["calidad"]);
		$valueObject->setIdioma($_GET["idioma"]);
		$valueObject->setImagen(Utils::cnvUrlSpaces20($_GET["img"]));

		return $valueObject;
	}

	/**
	 * Miramos si el torrent esta duplicado.
	 * @param TorrentVO $torrent
	 */
	private function isTorrentDuplicado($torrent) {
		if($this->getGestor()->isTorrentDuplicado($torrent->getNombre(), $torrent->getSize())) {
			throw new Exception("El torrent que intentas añadir ya se está descargando.");
		}
	}

	/**
	 * Miramos si hay espacio disponible en el disco.
	 * @param String $url
	 * @param String $rutaDescargas
	 */
	private function isEspacioDisponible($url, $rutaDescargas) {
		if(!is_dir($rutaDescargas)) {
			throw new Exception("La ruta descargas indicada no es correcta!");
		} else if(!is_writable($rutaDescargas) || !is_readable($rutaDescargas)) {
			throw new Exception("La ruta no tiene permisos de escritura y/o lectura.");
		}

		$infoT = new TorrentInfo($url);
		$configuracionVO = $this->getGestor()->loadConfiguracionVO();
		$totalSize = $this->getGestor()->obtenerSizeDescargasActivas() + $infoT->size();

		if ($totalSize > disk_free_space($configuracionVO->getRutaDescargas())) {
			throw new Exception("No hay espacio disponible en el disco!");
		}
	}

	/**
	 * Miramos si la rutaDescarga de ConfiguracionVO esta configurada
	 */
	private function isRutaDescargasConfigurado() {
		$configuracionVO = $this->getGestor()->loadConfiguracionVO();

		if(empty($configuracionVO->getRutaDescargas()) || is_null($configuracionVO->getRutaDescargas())) {
			throw new Exception("La ruta de descargas no puede estar vacía!");
		}
	}

	/**
	 * Guardamos temporalmente un Torrent en el disco local.
	 */
	private function guardarTorrentTemp($rutaTorrent) {
		file_put_contents(BASE_PATH . "tmpTorrent.torrent", fopen(Utils::cnvUrlSpaces20($rutaTorrent), 'r'));
		chmod(BASE_PATH . "tmpTorrent.torrent", 0777);
	}

	/**
	 * Eliminamos el Torrent guardado temporalmente.
	 */
	private function eliminarTorrentTemp() {
		unlink(BASE_PATH . "tmpTorrent.torrent");
	}

	/**
	 * Obtenemos la ruta del Torrent temporal.
	 * @return String
	 */
	private function getPathTorrentTemp() {
		return BASE_PATH . "tmpTorrent.torrent";
	}

	/**
	 * Obtenemos todos los archivos de forma recusiva de una ruta.
	 * @param String $dir
	 * @param Array $arrayRutasBuenas
	 */
	private function buscarEnDirectorio($dir, &$arrayRutasBuenas){
	    $ffs = scandir($dir);

	    unset($ffs[array_search('.', $ffs, true)]);
	    unset($ffs[array_search('..', $ffs, true)]);

	    if (count($ffs) < 1) {
	        return;
	    }

	    foreach($ffs as $ff){
	    	$ruta = $dir.'/'.$ff;

	        if(is_dir($ruta)) {
	        	$this->buscarEnDirectorio($ruta, $arrayRutasBuenas);
	        } else {
	        	$this->extensionVideo($ruta, $arrayRutasBuenas);
	        }
	    }
	}

	/**
	 * Miramos si la ruta del archivo tiene una extension valida.
	 * @param String $dir
	 * @param Array $arrayRutasBuenas
	 */
	private function extensionVideo($ruta, &$arrayRutasBuenas){
		$tipos = array("mp4", "mpeg", "mpg", "webm", "ogg", "mov");

		if(!is_dir($ruta)){
			$tipo = pathinfo($ruta);

			if (in_array($tipo["extension"], $tipos)) {
				array_push($arrayRutasBuenas, $ruta);
			}
		}
	}
}


?>