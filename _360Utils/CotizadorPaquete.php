<?php

namespace app\_360Utils;

use app\_360Utils\Services\UpsServices;
use app\_360Utils\Services\FedexServices;
use app\_360Utils\Services\EstafetaServices;
use app\_360Utils\Services\DhlServices;
use app\_360Utils\Entity\CotizacionRequest;


class CotizadorPaquete{
    

    //Servicios habilitaos
    const USE_FEDEX       = true; // Habilita FEDEX
    const USE_UPS         = true; //Habilita UPS
    const USE_ESTAFETA    = true; // Habilita ESTAFETA
    const USE_DHL         = true;


    

    /**
     * Realiza la cotización de los paquetes recibidos
     */
    function realizaCotizacion(CotizacionRequest $cotizacionRequest){
    
        //Resultado de la busqueda
        $data = [];

       
       // UTILIZA FEDEX ---------------------------------
       if(self::USE_FEDEX){
            $res = $this->cotizaPaqueteFedex($cotizacionRequest);
                if($res != null){
                $data = array_merge($data,$res);
            }   
        }

        // UTILIZA USE_DHL ---------------------------------
        if(self::USE_DHL){
            $res = $this->cotizaPaqueteDHL($cotizacionRequest);
            if($res != null){
                $data = array_merge($data, $res);
            }
        }

        // UTILIZA UPS ---------------------------------
        if(self::USE_UPS){
            $res = $this->cotizaPaqueteUps($cotizacionRequest);
            if($res != null){
                $data = array_merge($data, $res);
            }
        }

        // UTILIZA ESTAFETA ---------------------------------
        if(self::USE_ESTAFETA){
            $res = $this->cotizaPaqueteEstafeta($cotizacionRequest);
            if($res != null){
                $data = array_merge($data, $res);
            }
        }

    return $data;
    }



    // ----------------- FEDEX ---------------------

    private function cotizaPaqueteFedex(CotizacionRequest $cotizacion){
        $fedex = new FedexServices();
        //fecha actual
        $fecha = date('Y-m-d');
        $cotizacion->fecha = $fecha;
        $disponiblidad = $fedex->disponibilidadPaquete($cotizacion);

        if(!$disponiblidad){
            return [];
        }
        
        
        //Por cada opcion de disponibilidad verifica el precio
        $data = [];
        $data['notifications']  = $disponiblidad->Notifications;
        $data['options']        = $disponiblidad->Options;

        

        $cotizaciones = [];
        $count = 0;
        foreach($data['options'] as $item){
            $service = $item->Service;

            $_cotizacion = $fedex->cotizarEnvioPaquete($service, $cotizacion);
            if($_cotizacion){
                array_push($cotizaciones, $_cotizacion);
            }
        }

        return $cotizaciones;
    }

     // ----------------------------- COTIZACION ESTAFETA ----------------------------------------
     private function cotizaPaqueteEstafeta(CotizacionRequest $cotizacionRequest){
        //Estafeta solo tiene entregas de MX a MX, en caso contrario, no se pide la cotizacón
        if($cotizacionRequest->origenCountry != "MX" || $cotizacionRequest->destinoCountry != "MX"){
            return null;
        }


        $estafeta = new EstafetaServices();
        $fecha = "";
        $cotizaciones = $estafeta->cotizarEnvioPaquete($cotizacionRequest);
        return $cotizaciones;
    }


    //--------------- UPS -----------------------


    private function cotizaPaqueteUps(CotizacionRequest $cotizacionRequest){
        $ups = new UpsServices();
        $fecha = "";
        $cotizaciones = $ups->cotizarEnvioPaquete($cotizacionRequest);

        return $cotizaciones;
    }


    //--------------- DHL -----------------------


    private function cotizaPaqueteDhl(CotizacionRequest $cotizacion){
        $dhl = new DhlServices();
        $fecha = date('c');
        $cotizaciones = $dhl->cotizarEnvioPaquete($cotizacion);

        return $cotizaciones;
    }
}

?>