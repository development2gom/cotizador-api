<?php

namespace app\_360Utils;

use app\_360Utils\Services\UpsServices;
use app\_360Utils\Services\FedexServices;
use app\_360Utils\Services\EstafetaServices;
use app\_360Utils\Services\DhlServices;
use app\_360Utils\Entity\TrackingResult;





class Tracking{

    public function doTracking($carrier, $trakingNumber){
        switch(strtoupper($carrier)){
            case "FEDEX":
                $service = new FedexServices();
                $res = $service->traking($trakingNumber);
                return $res;
            case "UPS":
                $service = new UpsServices();
                $res = $service->traking($trakingNumber);
                return $res;
            case "DHL":
                $service = new DhlServices();
                $res = $service->traking($trakingNumber);
                return $res;
            default:
                $res = new TrackingResult();
                $res->message = "Tipo de servicio no incluido";
                $res->isError = true;
                return $res;
        }
    }
}