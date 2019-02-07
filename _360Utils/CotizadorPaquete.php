<?php

namespace app\_360Utils;

use app\_360Utils\Services\UpsServices;
use app\_360Utils\Services\FedexServices;
use app\_360Utils\Services\EstafetaServices;
use app\_360Utils\Services\DhlServices;
use app\_360Utils\Entity\CotizacionRequest;
use app\models\ListResponse;


class CotizadorPaquete{
    

    //Servicios habilitaos
    const USE_FEDEX       = true; // Habilita FEDEX
    const USE_UPS         = true; //Habilita UPS
    const USE_ESTAFETA    = true; // Habilita ESTAFETA
    const USE_DHL         = true; //HAbilta DHL


    

    /**
     * Realiza la cotización de los paquetes recibidos
     */
    function realizaCotizacion(CotizacionRequest $cotizacionRequest){
    
        //Resultado de la busqueda
        $data = [];
        $errors = [];
        

       
       // UTILIZA FEDEX ---------------------------------
       if(self::USE_FEDEX){
           try{
                $res = $this->cotizaPaqueteFedex($cotizacionRequest);
                    if($res != null){
                    $data = array_merge($data,$res);
                } 
            } catch(\Exception $e){
                error_log("Excepcion cotizar FEDEX: " . $e->getMessage());
                $errors[] = "Error al recuperar los datos de FEDEX";
            } 
        }

        // UTILIZA USE_DHL ---------------------------------
        if(self::USE_DHL){
            try{
                //DHL si maneja seguro en el envio
                //if(!$cotizacionRequest->hasSeguro){
                    $res = $this->cotizaPaqueteDHL($cotizacionRequest);
                    if($res != null){
                        $data = array_merge($data, $res);
                    }
                //}
            } catch(\Exception $e){
                error_log("Excepcion cotizar DHL: " . $e->getMessage());
                $errors[] = "Error al recuperar los datos de DHL";
            } 
        }

        // UTILIZA UPS ---------------------------------
        if(self::USE_UPS){
            try{
                //UPS no maneja seguro en el envio
                if(!$cotizacionRequest->hasSeguro){
                    $res = $this->cotizaPaqueteUps($cotizacionRequest);
                    if($res != null){
                        $data = array_merge($data, $res);
                    }
                }
            } catch(\Exception $e){
                error_log("Excepcion cotizar UPS: " . $e->getMessage());
                $errors[] = "Error al recuperar los datos de UPS";
            } 
        }

        // UTILIZA ESTAFETA ---------------------------------
        if(self::USE_ESTAFETA){
            try{
                //UPS no maneja seguro en el envio
                if(!$cotizacionRequest->hasSeguro){
                    $res = $this->cotizaPaqueteEstafeta($cotizacionRequest);
                    if($res != null){
                        $data = array_merge($data, $res);
                    }
                }
            } catch(\Exception $e){
                error_log("Excepcion cotizar ESTAFETA: " . $e->getMessage());
                $errors[] = "Error al recuperar los datos de ESTAFETA";
            } 
        }

        $response = new ListResponse();
        $response->message = "realizaCotizacion";
        $response->responseCode = 1;
        $response->operation = "Cotización de paquete";
        $response->results = $data;
        $response->errors = $errors;
        $response->count = count($data);

        return $response;
 
    }



    // ----------------- FEDEX ---------------------

    private function cotizaPaqueteFedex(CotizacionRequest $cotizacion){
        $fedex = new FedexServices();
        //fecha actual
        //fecha del envio
        $date = new \DateTime($cotizacion->fecha);    
        $fechaEnvio = $date->format('Y-m-d');

        
        $disponiblidad = $fedex->disponibilidadPaquete($cotizacion,$fechaEnvio);

        //Actualiza la fecha de evio
        $fechaEnvio = date('c',strtotime($cotizacion->fecha));

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

            $_cotizacion = $fedex->cotizarEnvioPaquete($service, $cotizacion,$fechaEnvio);
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
        //$fecha = date('c');
        $fecha = date('c',strtotime($cotizacion->fecha));
        $cotizaciones = $dhl->cotizarEnvioPaquete($cotizacion);

        return $cotizaciones;
    }
}

?>