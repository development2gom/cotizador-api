<?php

namespace app\_360Utils;

use app\_360Utils\Services\UpsServices;
use app\_360Utils\Services\FedexServices;
use app\_360Utils\Services\EstafetaServices;
use app\_360Utils\Entity\CotizacionRequest;





class CotizadorSobre{

      //Servicios habilitaos
      const USE_FEDEX       = TRUE; // Habilita FEDEX
      const USE_UPS         = TRUE; //Habilita UPS
      const USE_ESTAFETA    = TRUE; // Habilita ESTAFETA


      const USE_DGOM        = false; //HABILITA DGOM


    function realizaCotizacion(CotizacionRequest $cotizacionRequest){
       
        //Resultado de la busqueda
        $data = [];
        
       
       // UTILIZA FEDEX ---------------------------------
        if(self::USE_FEDEX){
            try{
            $res = $this->cotizaDocumentoFedex($cotizacionRequest);
            $data = array_merge($data, $res);
            }catch(\Exception $e){
                $mess = $e->getMessage();
                error_log("USE FEDEX Cotizacion error: " + $mess);
            }
            
        }

        if(self::USE_UPS){
            if(!$cotizacionRequest->hasSeguro){
                $res = $this->cotizaDocumentoUPS($cotizacionRequest);
                if($res != null){
                    $data = array_merge($data, $res);
                }
            }
        }

        if(self::USE_ESTAFETA){
            if(!$cotizacionRequest->hasSeguro){

                $res = $this->cotizaDocumentoEstafeta($cotizacionRequest);
                if($res != null){
                    $data = array_merge($data, $res);
                }
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


    // ----------------------------- COTIZACION ESTAFETA ----------------------------------------
    private function cotizaDocumentoEstafeta(CotizacionRequest $cotizacionRequest){
        //Estafeta solo tiene entregas de MX a MX, en caso contrario, no se pide la cotizacón
        if($cotizacionRequest->origenCountry != "MX" || $cotizacionRequest->destinoCountry != "MX"){
            return null;
        }


        $estafeta = new EstafetaServices();
        $fecha = "";
        $cotizaciones = $estafeta->cotizarEnvioDocumento($cotizacionRequest);
        return $cotizaciones;
    }

    // ----------------------------- COTIZACION UPS ----------------------------------------
    private function cotizaDocumentoUPS(CotizacionRequest $cotizacion){
        //UPS no maneja seguro en el envio
        if(!$cotizacion->hasSeguro){
            $ups = new UpsServices();
            $fecha = "";
            $cotizaciones = $ups->cotizarEnvioDocumento($cotizacion);
            return $cotizaciones;
        }
    }
    

//---------------------------------- COTIZACION DE FEDEX -----------------------------------

    private function cotizaDocumentoFedex(CotizacionRequest $cotizacion){
        // Metodos de envio disponibles

        $fedex = new FedexServices();
        //fecha del envio
        $date = new \DateTime($cotizacion->fecha);
        $fechaEnvio = $date->format('Y-m-d');

        //Consulta las opciones disponibles de fedex para envios de paquetes
        $disponiblidad = $fedex->disponibilidadDocumento($cotizacion, $fechaEnvio);

        if(!$disponiblidad){
            return [];
        }
        
        
        //Por cada opcion de disponibilidad verifica el precio
        $data = [];
        $data['notifications']  = $disponiblidad->Notifications;
        $data['options']        = $disponiblidad->Options;

        // FIXME 
        //$fecha = date('c');
        $fecha = date('c',strtotime($cotizacion->fecha));

        $cotizaciones = [];
        $count = 0;
        foreach($data['options'] as $item){
            if(!isset($item->Service)){
                continue;
            }
            $service = $item->Service;

            $cot = $fedex->cotizarEnvioDocumento($service, $cotizacion,$fecha);
            if($cot){
                array_push($cotizaciones, $cot);
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