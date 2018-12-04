<?php

namespace app\_360Utils\Entity;




class Cotizacion{
    var $provider;
    var $price;
    var $tax;
    var $serviceType; // FIRST_OVERNIGHT, PRIORITY_OVERNIGHT
    var $deliveryDate;
    var $businessDaysInTransit; // UPS
    var $deliveryByTime;        // UPS
    var $currency;
    var $data;
    var $servicePacking;
    var $alerts = [];
    var $deliveryDateStr = "No disponible"; //Texto que indica la fecha estimada para la entrega

    function addAlert($code, $desc){
        $alert = new Alert();
        $alert->code = $code;
        $alert->description = $desc;

        array_push($this->alerts, $alert);
    }
}

class Alert{
    var $code;
    var $description;

}

