<?php

namespace app\_360Utils;

use app\_360Utils\Services\UpsServices;
use app\_360Utils\Services\FedexServices;
use app\_360Utils\Services\EstafetaServices;
use app\_360Utils\Entity\CompraEnvio;
use yii\web\HttpException;


class CompraSobre{


    function comprarSobre(CompraEnvio $compra){
        switch(strtoupper( $compra->carrier)){
            case "FEDEX":
                $fedex = new FedexServices();
                $res = $fedex->comprarEnvioDocumento($compra);
                return $res;
            case "UPS":
                $ups = new UpsServices();
                $res = $ups->comprarEnvioDocumento($compra);
                return $res;
            case "ESTAFETA":
                $estafeta = new EstafetaServices();
                $res = $estafeta->comprarEnvioDocumento($compra);
                return $res;
            default:
                throw new HttpException(500,"Carrier selecconado no implementado " . $compra->carrier );
        }
    }
}

?>