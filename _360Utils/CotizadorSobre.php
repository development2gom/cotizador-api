<?php

namespace app\_360Utils;

use app\_360Utils\Services\UpsServices;
use app\_360Utils\Services\FedexServices;
use app\_360Utils\Services\EstafetaServices;
use app\_360Utils\Entity\CotizacionRequest;
use app\_360Utils\APIResponses\ListResponse;
use app\_360Utils\Services\DhlServices;





class CotizadorSobre{

    //Servicios habilitaos
    const USE_FEDEX       = TRUE; // Habilita FEDEX
    const USE_UPS         = true; //Habilita UPS
    const USE_ESTAFETA    = true; // Habilita ESTAFETA
    const USE_DHL         = true; //HAbilta DHL



    function realizaCotizacion(CotizacionRequest $cotizacionRequest){
       
        //Resultado de la busqueda
        $data = [];
        $errors = [];
        
       
       // UTILIZA FEDEX ---------------------------------
        if(self::USE_FEDEX){
            try{
            $res = $this->cotizaDocumentoFedex($cotizacionRequest);
            $data = array_merge($data, $res);
            }catch(\Exception $e){
                error_log("Excepcion cotizar FEDEX: " . $e->getMessage());
                $errors[] = "Error al recuperar los datos de FEDEX";
        } 
            
        }

        if(self::USE_UPS){
            try{
                if(!$cotizacionRequest->hasSeguro){
                    $res = $this->cotizaDocumentoUPS($cotizacionRequest);
                    if($res != null){
                        $data = array_merge($data, $res);
                    }
                }
            } catch(\Exception $e){
                error_log("Excepcion cotizar UPS: " . $e->getMessage());
                $errors[] = "Error al recuperar los datos de UPS";
            } 
        }

        if(self::USE_ESTAFETA){
            try{
                if(!$cotizacionRequest->hasSeguro){

                    $res = $this->cotizaDocumentoEstafeta($cotizacionRequest);
                    if($res != null){
                        $data = array_merge($data, $res);
                    }
                }
            } catch(\Exception $e){
                error_log("Excepcion cotizar ESTAFETA: " . $e->getMessage());
                $errors[] = "Error al recuperar los datos de ESTAFETA";
            } 
        }

        if(self::USE_DHL){
            try{
                if(!$cotizacionRequest->hasSeguro){

                    $res = $this->cotizaDocumentoDhl($cotizacionRequest);
                    if($res != null){
                        $data = array_merge($data, $res);
                    }
                }
            } catch(\Exception $e){
                error_log("Excepcion cotizar DHL: " . $e->getMessage());
                $errors[] = "Error al recuperar los datos de DHL";
            } 
        }

        //return $data;

        $response = new ListResponse();
        $response->message = "realizaCotización";
        $response->responseCode = 1;
        $response->operation = "Cotización de sobre";
        $response->results = $data;
        $response->errors = $errors;
        $response->count = count($data);

        return $response;
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
        
        foreach($data['options'] as $item){
            if(!isset($item->Service)){
                continue;
            }
            $service = $item->Service;

            $cot = $fedex->cotizarEnvioDocumento($service, $cotizacion,$fecha);
            if($cot){
                array_push($cotizaciones, $cot);
            }

           
        }

        return $cotizaciones;
    }


    //--------------- DHL -----------------------


    private function cotizaDocumentoDhl(CotizacionRequest $cotizacion){
        $dhl = new DhlServices();
        //$fecha = date('c');
        $fecha = date('c',strtotime($cotizacion->fecha));
        $cotizaciones = $dhl->cotizarEnvioDocumento($cotizacion);

        return $cotizaciones;
    }
}

?>