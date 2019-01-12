<?php

namespace app\_360Utils;

use app\_360Utils\Services\UpsServices;
use app\_360Utils\Services\FedexServices;
use app\_360Utils\Services\EstafetaServices;
use app\_360Utils\Services\DhlServices;


class CotizadorPaquete{
    

    //Servicios habilitaos
    const USE_FEDEX       = false; // Habilita FEDEX
    const USE_UPS         = false; //Habilita UPS
    const USE_ESTAFETA    = false; // Habilita ESTAFETA
    const USE_DHL         = TRUE;


    

    /**
     * Realiza la cotización de los paquetes recibidos
     */
    function realizaCotizacion($json, $paquetes){
    
        //Resultado de la busqueda
        $data = [];

       
       // UTILIZA FEDEX ---------------------------------
       if(self::USE_FEDEX){
            $res = $this->cotizaPaqueteFedex($json, $paquetes);
            if($res != null){
                $data = array_merge($data, $res);
            }   
        }

        // UTILIZA USE_DHL ---------------------------------
        if(self::USE_DHL){
            $res = $this->cotizaPaqueteDHL($json, $paquetes);
            if($res != null){
                $data = array_merge($data, $res);
            }
        }

        // UTILIZA UPS ---------------------------------
        if(self::USE_UPS){
            $res = $this->cotizaPaqueteUps($json, $paquetes);
            if($res != null){
                $data = array_merge($data, $res);
            }
        }

        // UTILIZA ESTAFETA ---------------------------------
        if(self::USE_ESTAFETA){
            $res = $this->cotizaPaqueteEstafeta($json, $paquetes);
            if($res != null){
                $data = array_merge($data, $res);
            }
        }

    return $data;
    }



    // ----------------- FEDEX ---------------------

    private function cotizaPaqueteFedex($json, $paquetes){
        $fedex = new FedexServices();
        //fecha actual
        $fecha = date('Y-m-d');
        $disponiblidad = $fedex->disponibilidadPaquete($json->cp_origen, $json->pais_origen, $json->cp_destino, $json->pais_destino, $fecha);

        if(!$disponiblidad){
            return [];
        }
        
        
        //Por cada opcion de disponibilidad verifica el precio
        $data = [];
        $data['notifications']  = $disponiblidad->Notifications;
        $data['options']        = $disponiblidad->Options;

        // Fecha actual 
        $fecha = date('c');

        $cotizaciones = [];
        $count = 0;
        foreach($data['options'] as $item){
            $service = $item->Service;

            $cotizacion = $fedex->cotizarEnvioPaquete($service, $json->cp_origen, $json->pais_origen, $json->cp_destino, $json->pais_destino, $fecha, $paquetes);
            if($cotizacion){
                array_push($cotizaciones, $cotizacion);
            }

            //FIXME: Limita el resultado de FEDEX
            $count++;
            if($count >1){
                break;
            }
        }

        return $cotizaciones;
    }

     // ----------------------------- COTIZACION ESTAFETA ----------------------------------------
     private function cotizaPaqueteEstafeta($json, $paquetes){
        //Estafeta solo tiene entregas de MX a MX, en caso contrario, no se pide la cotizacón
        if($json->pais_origen != "MX" || $json->pais_destino != "MX"){
            return null;
        }


        $estafeta = new EstafetaServices();
        $fecha = "";
        $cotizaciones = $estafeta->cotizarEnvioPaquete($json->cp_origen,  $json->cp_destino, $fecha, $paquetes);
        return $cotizaciones;
    }


    //--------------- UPS -----------------------


    private function cotizaPaqueteUps($json, $paquetes){
        $ups = new UpsServices();
        $fecha = "";
        $cotizaciones = $ups->cotizarEnvioPaquete($json->cp_origen, $json->estado_origen, $json->pais_origen, $json->cp_destino, $json->estado_destino , $json->pais_destino, $fecha,  $paquetes);

        return $cotizaciones;
    }


    //--------------- DHL -----------------------


    private function cotizaPaqueteDhl($json, $paquetes){
        $dhl = new DhlServices();
        $fecha = date('c');
        $cotizaciones = $dhl->cotizarEnvioPaquete($json->cp_origen, $json->pais_origen, $json->cp_destino,  $json->pais_destino, $fecha,  $paquetes);

        return $cotizaciones;
    }
}

?>