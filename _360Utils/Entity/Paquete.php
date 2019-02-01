<?php

namespace app\_360Utils\Entity;




class Paquete{
    var $alto = 0;
    var $ancho = 0;
    var $largo = 0;
    var $peso = 0;


    /**
     *  Recupera el peso que debe utilizar ya sea el indicado o el volumétrico
     */
    public function getPesoFinal(){
        if($this->peso > $this->getPesoVolumetrico()){
            return $this->peso;
        }else{
            return $this->getPesoVolumetrico();
        }
    }

    /**
     * Calcula el peso volumétrico del paquete
     */
    public function getPesoVolumetrico(){
        return ($this->alto * $this->ancho * $this->largo) / 5000;
    }
}


?>