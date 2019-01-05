<?php

namespace app\_360Utils;

use app\_360Utils\Services\UpsServices;
use app\_360Utils\Services\FedexServices;
use app\_360Utils\Services\EstafetaServices;
use app\_360Utils\Entity\CompraEnvio;


class CompraPaquete{


    function comprarPaquete(CompraEnvio $compra){
        switch(strtoupper( $compra->carrier)){
            case "FEDEX":
                $fedex = new FedexServices();
                $res = $fedex->comprarEnvioPaquete($compra);
                return $res;

            case "UPS":
                $ups = new UpsServices();
                $res = $ups->comprarEnvioPaquete($compra);
                return $res;
        }
    }
}

?>