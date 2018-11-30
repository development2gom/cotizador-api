<?php

namespace app\_360Utils;

use app\_360Utils\Services\UpsServices;
use app\_360Utils\Services\FedexServices;





class CotizadorSobre{

      //Servicios habilitaos
      const USE_FEDEX = true; // Habilita FEDEX
      const USE_DGOM  = false; //HABILITA DGOM
      const USE_UPS   = true; //Habilita UPS


    function realizaCotizacion($json,$paquetes){
       
        //Resultado de la busqueda
        $data = [];
        
       
       // UTILIZA FEDEX ---------------------------------
        if(self::USE_FEDEX){
            $res = $this->cotizaDocumentoFedex($json,$paquetes);
            $data = array_merge($data, $res);
            
        }

        // UTILIZA 2GOM ---------------------------------
        if(self::USE_DGOM){
            $res = $this->cotizaDocumentoDGOM($json,$paquetes);
            $data = array_merge($data, $res);
        }

        if(self::USE_UPS){
            $res = $this->cotizaDocumentoUPS($json,$paquetes);
            if($res != null){
                $data = array_merge($data, $res);
            }
        }

        return $data;
    }


    //---------------------------------- COTIZACION DE DGOM -----------------------------------

    private function cotizaDocumentoDGOM($json){
        $data = [];

        $cotizacion = new Cotizacion();

        $cotizacion->provider     = "DGOM";
        $cotizacion->price        = 100;
        $cotizacion->tax          = 16;
        $cotizacion->serviceType  = "FIRST_OVERNIGHT";//, PRIORITY_OVERNIGHT
        $cotizacion->deliveryDate = "2018-11-10";
        $cotizacion->currency     = "MXP";
        
        array_push($data, $cotizacion);

        $cotizacion = new Cotizacion();

        $cotizacion->provider     = "DGOM-2";
        $cotizacion->price        = 150;
        $cotizacion->tax          = 16;
        $cotizacion->serviceType  = "PRIORITY_OVERNIGHT";//, 
        $cotizacion->deliveryDate = "2018-11-10";
        $cotizacion->currency     = "MXP";
        
        array_push($data, $cotizacion);

        return $data;
    }



    // ----------------------------- COTIZACION UPS ----------------------------------------
    private function cotizaDocumentoUPS($json, $paquetes){
        $ups = new UpsServices();
        $fecha = "";
        $cotizaciones = $ups->cotizarEnvioDocumento($json->cp_origen, $json->estado_origen, $json->pais_origen, $json->cp_destino, $json->estado_destino , $json->pais_destino, $fecha, $paquetes);

        return $cotizaciones;
    }
    

//---------------------------------- COTIZACION DE FEDEX -----------------------------------

    private function cotizaDocumentoFedex($json,$paquetes){
        // Metodos de envio disponibles

        $fedex = new FedexServices();
        //FIXME: fecha actual
        $fecha = "2018-10-06";
        $disponiblidad = $fedex->disponibilidadDocumento($json->cp_origen, $json->pais_origen, $json->cp_destino, $json->pais_destino, $fecha);

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

            $cotizacion = $fedex->cotizarEnvioDocumento($service, $json->cp_origen, $json->pais_origen, $json->cp_destino, $json->pais_destino, $fecha, $paquetes);
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
}

?>