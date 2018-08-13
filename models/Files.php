<?php
namespace app\models;
class Files{

    public static function validarDirectorio($path){
        if (!file_exists($path)) {
			mkdir($path, 0777);
		}
    }

    public static function borrarArchivo($path){
        unlink($path);
    }

    /**
	 * Metodo para cambiar el tamaño de una imagen
	 *
	 * @param unknown $file        	
	 * @param unknown $ancho        	
	 * @param unknown $alto        	
	 * @param unknown $nuevo_ancho        	
	 * @param unknown $nuevo_alto        	
	 */
	public function rezisePicture($file, $ancho, $alto, $redimencionar, $nombreNuevo, $extension)
	{
		// Factor para el redimensionamiento
		$factor = $this->calcularFactor($ancho, $alto, $redimencionar);

		$nuevo_ancho = $ancho * $factor;
		$nuevo_alto = $alto * $factor;

		// Cargar
		$thumb = imagecreatetruecolor($nuevo_ancho, $nuevo_alto);

		
		if($extension==="jpg" || $extension==="jpeg"){
			$origen = imagecreatefromjpeg($file);
		
		// Cambiar el tamaño
		imagecopyresampled($thumb, $origen, 0, 0, 0, 0, $nuevo_ancho, $nuevo_alto, $ancho, $alto);
		imagejpeg($thumb, $nombreNuevo);
		}else{
			$origen = imagecreatefrompng($file);
		
			// Cambiar el tamaño
			imagecopyresampled($thumb, $origen, 0, 0, 0, 0, $nuevo_ancho, $nuevo_alto, $ancho, $alto);
			imagepng($thumb, $nombreNuevo);
		}
		
	}
    
    /**
	 * Calcula el factor
	 *
	 * @param unknown $ancho        	
	 * @param unknown $alto        	
	 * @param unknown $redimension        	
	 */
	private function calcularFactor($ancho, $alto, $redimension)
	{
		if ($ancho >= $alto) {
			$factor = $redimension / $ancho;
		} else if ($ancho <= $alto) {
			$factor = $redimension / $alto;
		}

		return $factor;
	}

}