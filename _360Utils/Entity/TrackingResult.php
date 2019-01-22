<?php

namespace app\_360Utils\Entity;



/**
 * Clase que representa la solicitud de compra de un envío
 */
class TrackingResult{

    public $isError = false;
    public $isDelivered = false;
    public $message;
    public $document;
    public $documentDesc;
    public $documentFormat;
    public $isTrakingFound = false;
    public $intentosEntrega = 0;
    public $numeroPaquetes = 0;
    public $dateLastRecord;
    public $data;

    public $eventos = [];


    public function addEvento(Evento $ev){
        array_push($this->eventos, $ev);
    }

}

class Evento{
    public $description;
    public $date;
}

?>