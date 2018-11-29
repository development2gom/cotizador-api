<?php

namespace app\_360Utils;

use app\_360Utils\Services\UpsServices;
use app\_360Utils\Services\FedexServices;


class CotizadorPaquete{
    

    //Servicios habilitaos
    const USE_FEDEX = false; // Habilita FEDEX
    const USE_DGOM  = false; //HABILITA DGOM
    const USE_UPS   = true; //Habilita UPS


    function realizaCotizacion($json){
    
        //Resultado de la busqueda
        $data = [];

       
       // UTILIZA FEDEX ---------------------------------
       if(self::USE_FEDEX){
            $res = $this->cotizaPaqueteFedex($json);
            $data = array_merge($data, $res);    
        }

        // UTILIZA 2GOM ---------------------------------
        if(self::USE_DGOM){
            $res = $this->cotizaDocumentoDGOM($json);
            $data = array_merge($data, $res);
        }

        // UTILIZA UPS ---------------------------------
        if(self::USE_UPS){
            $res = $this->cotizaPaqueteUps($json);
            $data = array_merge($data, $res);
        }

    return $data;
    }



    // ----------------- FEDEX ---------------------

    private function cotizaPaqueteFedex($json){
        $fedex = new FedexServices();
        //FIXME: fecha actual
        $fecha = "2018-10-06";
        $disponiblidad = $fedex->disponibilidadPaquete($json->cp_origen, $json->pais_origen, $json->cp_destino, $json->pais_destino, $fecha);

        if(!$disponiblidad){
            return [];
        }
        
        
        //Por cada opcion de disponibilidad verifica el precio
        $data = [];
        $data['notifications']  = $disponiblidad->Notifications;
        $data['options']        = $disponiblidad->Options;

        // FIXME 
        $fecha = date('c');

        $cotizaciones = [];
        $count = 0;
        foreach($data['options'] as $item){
            $service = $item->Service;

            $cotizacion = $fedex->cotizarEnvioPaquete($service, $json->cp_origen, $json->pais_origen, $json->cp_destino, $json->pais_destino, $fecha, $json->peso_kilogramos, $json->alto_cm,$json->ancho_cm,$json->largo_cm);
            if($cotizacion){
                array_push($cotizaciones, $cotizacion);
            }

            $count++;
            if($count >1){
                break;
            }
        }

        return $cotizaciones;
    }


    //--------------- UPS -----------------------


    private function cotizaPaqueteUps($json){
        $ups = new UpsServices();
        $fecha = "";
        $cotizaciones = $ups->cotizarEnvioPaquete($json->cp_origen, $json->estado_origen, $json->pais_origen, $json->cp_destino, $json->estado_destino , $json->pais_destino, $fecha, $json->peso_kilogramos, $json->largo_cm, $json->ancho_cm, $json->alto_cm);

        return $cotizaciones;
    }
}

?>