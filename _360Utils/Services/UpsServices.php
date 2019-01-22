<?php


namespace app\_360Utils\Services;

use Yii;

use app\_360Utils\Entity\Cotizacion;
use app\_360Utils\Entity\CompraEnvio;
use app\_360Utils\Entity\ResultadoEnvio;
use app\_360Utils\Entity\CotizacionRequest;
use app\_360Utils\Entity\Paquete;
use app\_360Utils\Entity\TrackingResult;
use app\_360Utils\Entity\Evento;

class UpsServices{

    const UPS_LICENCE_NUMBER    = 'DD53B5465E301D15';
    const UPS_PASSWORD          = 'Mexico01';
    const UPS_USER_NAME         = 'W1R182.apis';
    const UPS_CUSTOMER_CONTEXT  = 'Your Customer Context';
    const UPS_SHIPER_NUMBER     = 'W1R182';
    const UPS_RATE_CITY         = 'CITY';
    const UPS_TAX_ID_NUMBER     = '123456';

    const UPS_SOBRE_LARGO_IN    = 3;
    const UPS_SOBRE_ANCHO_IN    = 3;
    const UPS_SOBRE_ALTO_IN     = 3;


    // TIPOS DE PAQUETES ACEPTADOS

    const PT_UNKNOWN            = '00';
    const PT_UPSLETTER          = '01';
    const PT_PACKAGE            = '02';
    const PT_TUBE               = '03';
    const PT_PAK                = '04';
    const PT_UPS_EXPRESSBOX     = '21';
    const PT_UPS_25KGBOX        = '24';
    const PT_UPS_10KGBOX        = '25';
    const PT_PALLET             = '30';
    const PT_EXPRESSBOX_S       = '2a';
    const PT_EXPRESSBOX_M       = '2b';
    const PT_EXPRESSBOX_L       = '2c';
    const PT_FLATS              = '56';
    const PT_PARCELS            = '57';
    const PT_BPM                = '58';
    const PT_FIRST_CLASS        = '59';
    const PT_PRIORITY           = '60';
    const PT_MACHINABLES        = '61';
    const PT_IRREGULARS         = '62';
    const PT_PARCEL_POST        = '63';
    const PT_BPM_PARCEL         = '64';
    const PT_MEDIA_MAIL         = '65';
    const PT_BPM_FLAT           = '66';
    const PT_STANDARD_FLAT      = '67';


    //TIPS DE SERVICIOS -------------
     // Valid domestic values
     const S_AIR_1DAYEARLYAM    = '14';                    // ok package
     const S_AIR_1DAY           = '01';      //ok envelope // ok package
     const S_AIR_1DAYSAVER      = '13';
     const S_AIR_2DAYAM         = '59';
     const S_AIR_2DAY           = '02';     // ok envelope  // ok package
     const S_3DAYSELECT         = '12';                     // ok package
     const S_GROUND             = '03';     // ok envelope  // ok package
     const S_SURE_POST          = '93';

     // Valid international values
     const S_STANDARD           = '11'; 
     const S_WW_EXPRESS         = '07';
     const S_WW_EXPRESSPLUS     = '54';
     const S_WW_EXPEDITED       = '08';
     const S_SAVER              = '65'; // Require for Rating, ignored for Shopping
     const S_ACCESS_POINT       = '70'; // Access Point Economy
     // Valid Poland to Poland same day values
     const S_UPSTODAY_STANDARD  = '82';
     const S_UPSTODAY_DEDICATEDCOURIER   = '83';
     const S_UPSTODAY_INTERCITY = '84';
     const S_UPSTODAY_EXPRESS   = '85';
     const S_UPSTODAY_EXPRESSSAVER      = '86';
     const S_UPSWW_EXPRESSFREIGHT       = '96';

     // Valid Germany to Germany values
     const S_UPSEXPRESS_1200    = '74';

    // PackageWeight
    const UOM_LBS = 'LBS'; // Pounds (defalut)
    const UOM_KGS = 'KGS'; // Kilograms
    // Dimensions
    const UOM_IN = 'IN'; // Inches
    const UOM_CM = 'CM'; // Centimeters

    const URL_DEV       = 'https://wwwcie.ups.com/rest/';
    const URL_PROD      = 'https://onlinetools.ups.com/rest/';
    var $URL_SERVICE    = 'https://wwwcie.ups.com/rest/';
    var $URL_WEB_SERVICE    = 'https://wwwcie.ups.com/webservices/';


    //-------------- TRAKING -----------------


    

    function traking($trakingNumber){

        $path_to_wsdl = Yii::getAlias('@app') . '/_360Utils/shipmentCarriers/ups/wsdl/Track.wsdl';
        try
        {
            //Obtiene los datos del request
            $requestData = $this->getTrakingRequest($trakingNumber);

            // initialize soap client
            $client = new \SoapClient($path_to_wsdl, array('trace' => 1)); 
            $endpointurl = $this->URL_WEB_SERVICE . 'Track';
             
            
            $soapHeader = new \SoapHeader('http://www.ups.com/XMLSchema/XOLTWS/UPSS/v1.0','UPSSecurity',$requestData['header']);
            $client->__setSoapHeaders($soapHeader);
            $client->__setLocation($endpointurl);
            $resp = $client->__soapCall("ProcessTrack" , array($requestData['body']) );

            //------------ PROCESA LA RESPUESTA DEL SERVICIO ------------------

            $res = new TrackingResult();
            $res->data = json_encode($resp);


            //Verifica si hay error en la respuesta
            if($resp->Response->ResponseStatus->Code != 1){
                $res->isError = true;
                $res->message = $response->Notifications->Message; 
                return $res;
            }


            //Verifica si existe la entrada de Pakage
            if(isset($resp->Shipment->Package)){
                $activities = $resp->Shipment->Package->Activity;
            }else{
                $activities = $resp->Shipment->Activity;
            }

            
            
            //Si hay más de un elemento Activity
            if(is_array( $activities )){

                //Agrega los eventos a la respuesta
                foreach($activities as $item){
                    $ev = new Evento();
                    $ev->date = $item->Date . " " . $item->Time;
                    if(isset($item->Status)){
                        $ev->description = $item->Status->Description;
                    }else{
                        $ev->description = $item->Description;
                    }
                    $res->addEvento($ev);
                }

                //Toma la ultima actividad
                $activity = $this->getLastActivity($activities);
                
            }else{
                $activity = $activities;
            }
            
            
            $res->isError = false;
            $res->isTrakingFound  = true;
            if(isset($activity->Status)){
                $res->isDelivered = $activity->Status->Description == "DELIVERED";
                $res->message = $activity->Status->Description;
            }else{
                $res->isDelivered = $activity->Description == "DELIVERED";
                $res->message = $activity->Description;
            }
            
            $res->dateLastRecord = $activity->Date . " " . $activity->Time;
            

            //En caso que se cuente con un documento de firma
            if(isset($activity->Document)){
                $res->documentDesc = $activity->Document->Type->Description;
                $res->document = $activity->Document->Content;
                $res->documentFormat = $activity->Document->Format->Description;
            }
            

            return $res;
        }
        catch(\SoapFault $se){
            $xml = $client->__getLastRequest();
            $res = new TrackingResult();
            $res->isError = true;
            $res->message = $se->detail->Errors->ErrorDetail->PrimaryErrorCode->Description;
            return $res;
        }
        catch(\Exception $ex)
        {
            $xml = $client->__getLastRequest();
            $res = new TrackingResult();
            $res->isError = true;
            $res->message = "Error con el servicio " . $ex->getMessage();
            return $res;
        }
       
    }

    /*
    // ----------- TRAKING NUMBERS DE PRUEBA -----------
       TRACKING NUMBER, SERVICE, RESPONSE
        1Z12345E0205271688 (Signature Availability), 2nd Day Air, Delivered
        1Z12345E6605272234, World Wide Express, Delivered
        1Z12345E0305271640, (Second Package: 1Z12345E0393657226), Ground, Delivered
        1Z12345E1305277940, Next Day Air Saver, ORIGIN SCAN
        1Z12345E6205277936, Day Air Saver, 2nd Delivery attempt
        1Z12345E020527079, Invalid Tracking Number               
        1Z12345E1505270452, No Tracking Information Available
        990728071, UPS Freight LTL, In Transit
        3251026119, Delivered Origin CFS
        MI Tracking Number: 9102084383041101186729
        MI Reference Number: cgish000116630
        1Z648616E192760718, UPS Worldwide Express Freight, Order Process by UPS
        5548789114, UPS Express Freight, Response for UPS Air Freight
        ER751105042015062, UPS Ocean, Response for UPS Ocean Freight
        1ZWX0692YP40636269, UPS SUREPOST, Response for UPS SUREPOST
        */

        private function getTrakingRequest($trakingNumber){
       
            $header = [];
            $header['UsernameToken'] = [];
            $header['UsernameToken']['Username'] = self::UPS_USER_NAME;
            $header['UsernameToken']['Password'] = self::UPS_PASSWORD;
            $header['ServiceAccessToken'] = [];
            $header['ServiceAccessToken']['AccessLicenseNumber'] = self::UPS_LICENCE_NUMBER;
    
            $request = [];
            $request['Request'] = [];
            $request['Request']['RequestOption'] = "15";
            $request['Request']['TransactionReference'] = [];
            $request['Request']['TransactionReference']['CustomerContext'] = "Envios 360";
            
            
            $request['InquiryNumber'] = $trakingNumber;
            //$request['InquiryNumber'] = '1Z12345E0205271688';
            $request['TrackingOption'] = "02";
    
            $data = [];
            $data['header'] = $header;
            $data['body']   = $request;
    
            return $data;
    
        }


    /**
     * Permite encontrar el ultimo evento en una cadena de actividades
     */
    private function getLastActivity($list){

        $lastDate = -1;
        $lastItem = null;
        foreach($list as $item){
            $dt = (int)($item->Date . $item->Time);
            if($dt > $lastDate){
                $lastDate = $dt;
                $lastItem = $item;
            }
        }

        return $lastItem;
    }


    //------------- COTIZACION ---------------
    function cotizarEnvioDocumento($cp_origen,$stado_origen, $pais_origen, $cp_destino, $estado_destino, $pais_destino, $fecha, $paquetes){
        $servicios = [self::S_AIR_1DAY,self::S_AIR_2DAY,self::S_GROUND];
        $responses = [];

        $paquete = $paquetes[0];

        //Cambia el peso de kilos a libras
        $peso = $paquete['num_peso'];// * 2.20462;

        foreach($servicios as $item){
            $res = $this->cotizarEnvioDocumentoInterno($item,$cp_origen,$stado_origen, $pais_origen, $cp_destino, $estado_destino, $pais_destino, $fecha, $peso);
            if($res != null){
                array_push($responses,$res);
            }
        }

        return $responses;
    }


    function cotizarEnvioPaquete(CotizacionRequest $cotizacionRequest){
        $servicios = [self::S_AIR_1DAY,self::S_AIR_2DAY,self::S_GROUND, self::S_AIR_1DAYEARLYAM,self::S_3DAYSELECT];
        $responses = [];

        foreach($servicios as $item){
            $res = $this->cotizarEnvioPaqueteInterno($item, $cotizacionRequest);
            if($res != null){
                array_push($responses,$res);
            }
        }

        return $responses;
    }

    //---------------- COMPRA DE SERVICIOS --------------------------------

    function comprarEnvioDocumento(CompraEnvio $model){
        return $this->comprarEnvioInterno($model,'FEDEX_ENVELOPE');
    }

    function comprarEnvioPaquete(CompraEnvio $model){
        return $this->comprarEnvioInterno($model,'YOUR_PACKAGING');
    }

    private function comprarEnvioInterno(CompraEnvio $model, $servicePacking){


        $paquetes = $model->paquetes;


        $json["UPSSecurity"] = $this->getSecurity();
        
        $json["ShipmentRequest"] = [];
        $json["ShipmentRequest"]["Request"] = [];
        $json["ShipmentRequest"]["Request"]["RequestOption"] = "validate";
        $json["ShipmentRequest"]["Request"]["TransactionReference"] = [];
        $json["ShipmentRequest"]["Request"]["TransactionReference"]["CustomerContext"] = self::UPS_CUSTOMER_CONTEXT;
              
        $json["ShipmentRequest"]["Shipment"] = [];
        $json["ShipmentRequest"]["Shipment"]["Description"] = "Envios 360";
        
        $json["ShipmentRequest"]["Shipment"]["Shipper"]     = $this->getShipper($model->origen_estado,$model->origen_cp,$model->origen_pais, $model->origen_nombre_persona,  $model->origen_direccion);
        $json["ShipmentRequest"]["Shipment"]["ShipTo"]      = $this->getShipTo($model->destino_estado,$model->destino_cp,$model->destino_pais, $model->destino_nombre_persona, $model->destino_direccion);
        $json["ShipmentRequest"]["Shipment"]["ShipFrom"]    = $this->getShipper($model->origen_estado,$model->origen_cp,$model->origen_pais, $model->origen_nombre_persona, $model->origen_direccion);

        $json["ShipmentRequest"]["Shipment"]["PaymentInformation"] = [];
        $json["ShipmentRequest"]["Shipment"]["PaymentInformation"]["ShipmentCharge"] = [];
        $json["ShipmentRequest"]["Shipment"]["PaymentInformation"]["ShipmentCharge"]["Type"]= "01";
        $json["ShipmentRequest"]["Shipment"]["PaymentInformation"]["ShipmentCharge"]["BillShipper"] = [];
        $json["ShipmentRequest"]["Shipment"]["PaymentInformation"]["ShipmentCharge"]["BillShipper"]["AccountNumber"] = self::UPS_SHIPER_NUMBER;
           

        $json["ShipmentRequest"]["Shipment"]["Service"] = [];
        $json["ShipmentRequest"]["Shipment"]["Service"]["Code"] = $model->tipo_servicio ;//"01"; //Tipo de envío
        $json["ShipmentRequest"]["Shipment"]["Service"]["Description"] = "Service Code Description";
    

         //por cada paquete se agrega un elemento a la peticion
         $listaPaquetes = [];
         foreach($paquetes as $item){
             $res = $this->createPakageCompra($item);
             array_push($listaPaquetes, $res);
         }
         $json["ShipmentRequest"]["Shipment"]["Package"] = $listaPaquetes;
 

        $json["ShipmentRequest"]["LabelSpecification"] = [];
		$json["ShipmentRequest"]["LabelSpecification"]["LabelImageFormat"] = [];
		$json["ShipmentRequest"]["LabelSpecification"]["LabelImageFormat"]["Code"] = "GIF";
		$json["ShipmentRequest"]["LabelSpecification"]["LabelImageFormat"]["Description"] = "GIF";
		$json["ShipmentRequest"]["LabelSpecification"]["HTTPUserAgent"] = "Mozilla/4.5";
		

        $endpoint = $this->URL_SERVICE . 'Ship';

        $response = $this->jsonRequest($endpoint, $json);
        
        // Check for errors
        if($response === FALSE){
            //die(curl_error($ch));
            return null;
        }
    
        // Decode the response
        $responseData = json_decode($response, TRUE);

        //Respondio con error
        if(isset($responseData['Fault'])){
            $severityError = $responseData['Fault']['detail']['Errors']['ErrorDetail']['Severity'];
            $codeError = $responseData['Fault']['detail']['Errors']['ErrorDetail']['PrimaryErrorCode']['Code'];
            $descError = $responseData['Fault']['detail']['Errors']['ErrorDetail']['PrimaryErrorCode']['Description'];
            error_log("Error con el servicio de UPS: " . $severityError . " " . $codeError . " " . $descError);
            return null;
        }

        

        $resultado = [];
        

        if(isset($responseData['ShipmentResponse']['ShipmentResults']['PackageResults']['TrackingNumber'])){
            $res = new ResultadoEnvio();
                $res->data           = json_encode($responseData);
                $res->jobId          = $responseData['ShipmentResponse']['ShipmentResults']['PackageResults']['TrackingNumber'];
                $res->envioCode      = $responseData['ShipmentResponse']['ShipmentResults']['PackageResults']['TrackingNumber'];
                $res->envioCode2     = $responseData['ShipmentResponse']['ShipmentResults']['PackageResults']['TrackingNumber'];
                $res->tipoEmpaque    = $servicePacking;
                $res->tipoServicio   = $model->tipo_servicio;
                $res->etiqueta       = $responseData['ShipmentResponse']['ShipmentResults']['PackageResults']['ShippingLabel']['GraphicImage'];
                $res->etiquetaFormat = "GIF";
    
                array_push($resultado,$res);
        }else{
            //Multiples paquetes
            foreach($responseData['ShipmentResponse']['ShipmentResults']['PackageResults'] as $item){
                $res = new ResultadoEnvio();
                $res->data           = json_encode($responseData);
                $res->jobId          = $item['TrackingNumber'];
                $res->envioCode      = $item['TrackingNumber'];
                $res->envioCode2     = $item['TrackingNumber'];
                $res->tipoEmpaque    = $servicePacking;
                $res->tipoServicio   = $model->tipo_servicio;
                $res->etiqueta       = $item['ShippingLabel']['GraphicImage'];
                $res->etiquetaFormat = "GIF";
    
                array_push($resultado,$res);
            }
        }
        

        return $resultado;
    }

    
    /**
     * Envío de sobre
     */
    private function cotizarEnvioDocumentoInterno($tipo_servicio,$cp_origen,$estado_origen, $pais_origen, $cp_destino, $estado_destino, $pais_destino, $fecha, $peso_kilos){

        $json = [];

        
        $json["UPSSecurity"] = $this->getSecurity();
        
        $json["RateRequest"] = [];
        $json["RateRequest"]["Request"] = [];
        $json["RateRequest"]["Request"]["RequestOption"] = "Rate";
        $json["RateRequest"]["Request"]["TransactionReference"] = [];
        $json["RateRequest"]["Request"]["TransactionReference"]["CustomerContext"] = self::UPS_CUSTOMER_CONTEXT;
              
        $json["RateRequest"]["Shipment"] = [];
        $json["RateRequest"]["Shipment"]["Shipper"] = $this->getShipper($estado_origen,$cp_origen,$pais_origen);
        $json["RateRequest"]["Shipment"]["ShipTo"] = $this->getShipTo($cp_destino,$estado_destino,$pais_destino);
        $json["RateRequest"]["Shipment"]["ShipFrom"] = $this->getShipper($estado_origen,$cp_origen,$pais_origen);


        $json["RateRequest"]["Shipment"]["Service"] = [];
        $json["RateRequest"]["Shipment"]["Service"]["Code"] = $tipo_servicio ;//"01"; //Tipo de envío
        $json["RateRequest"]["Shipment"]["Service"]["Description"] = "Service Code Description";
                
        $json["RateRequest"]["Shipment"]["Package"] = [];
        $json["RateRequest"]["Shipment"]["Package"]["PackagingType"] = [];
        $json["RateRequest"]["Shipment"]["Package"]["PackagingType"]["Code"] = self::PT_UPSLETTER; //SOBRE
        $json["RateRequest"]["Shipment"]["Package"]["PackagingType"]["Description"] = "SOBRE";
                  
        $json["RateRequest"]["Shipment"]["Package"]["PackageWeight"] = [];
        $json["RateRequest"]["Shipment"]["Package"]["PackageWeight"]["UnitOfMeasurement"] = [];
        $json["RateRequest"]["Shipment"]["Package"]["PackageWeight"]["UnitOfMeasurement"]["Code"] = "kgs";
        $json["RateRequest"]["Shipment"]["Package"]["PackageWeight"]["UnitOfMeasurement"]["Description"] = "kilos";
                    
        $json["RateRequest"]["Shipment"]["Package"]["PackageWeight"]["Weight"]= "". $peso_kilos;
        
        $json["RateRequest"]["Shipment"]["ShipmentRatingOptions"] = [];
        $json["RateRequest"]["Shipment"]["NegotiatedRatesIndicator"] =  "";


        $endpoint = $this->URL_SERVICE . 'Rate';

        $response = $this->jsonRequest($endpoint, $json);
        
        // Check for errors
        if($response === FALSE){
            //die(curl_error($ch));
            return null;
        }
    
        // Decode the response
        $responseData = json_decode($response, TRUE);

        //Respondio con error
        if(isset($responseData['Fault'])){
            $severityError = $responseData['Fault']['detail']['Errors']['ErrorDetail']['Severity'];
            $codeError = $responseData['Fault']['detail']['Errors']['ErrorDetail']['PrimaryErrorCode']['Code'];
            $descError = $responseData['Fault']['detail']['Errors']['ErrorDetail']['PrimaryErrorCode']['Description'];
            error_log("Error con el servicio de UPS: " . $severityError . " " . $codeError . " " . $descError);
            return null;
        }


   
            $cotizacion = new Cotizacion();

            $cotizacion->provider               = "UPS";
            $cotizacion->price                  = $responseData["RateResponse"]["RatedShipment"]["TotalCharges"]["MonetaryValue"];
            $cotizacion->tax                    = 0;
            $cotizacion->serviceType            = $responseData["RateResponse"]["RatedShipment"]["Service"]["Code"] . " " . $responseData["RateResponse"]["RatedShipment"]["Service"]["Description"];
            $cotizacion->deliveryDate           = "";
            $cotizacion->currency               = $responseData["RateResponse"]["RatedShipment"]["TotalCharges"]["CurrencyCode"];
            $cotizacion->data                   = $responseData;
            $cotizacion->servicePacking         = "PT_UPSLETTER";


            


            //Tiempo de entrega UPS
            if(isset($responseData["RateResponse"]["RatedShipment"]["GuaranteedDelivery"])){
                if(isset($responseData["RateResponse"]["RatedShipment"]["GuaranteedDelivery"]["BusinessDaysInTransit"])){
                    $cotizacion->businessDaysInTransit  = $responseData["RateResponse"]["RatedShipment"]["GuaranteedDelivery"]["BusinessDaysInTransit"];

                    $cotizacion->deliveryDateStr = $cotizacion->businessDaysInTransit + " días";
                    
                }
                if(isset($responseData["RateResponse"]["RatedShipment"]["GuaranteedDelivery"]["DeliveryByTime"]) && 
                    $responseData["RateResponse"]["RatedShipment"]["GuaranteedDelivery"]["DeliveryByTime"] != null){
                    $cotizacion->deliveryByTime  = $responseData["RateResponse"]["RatedShipment"]["GuaranteedDelivery"]["DeliveryByTime"];
                }
            }
            

            //Alertas
            $alertas = $responseData["RateResponse"]["RatedShipment"]["RatedShipmentAlert"];
            if(is_array($alertas) && isset($alertas['Code']) ){
                $cotizacion->addAlert($alertas["Code"],$alertas["Description"]);
            }else{
                foreach($alertas as $alert){
                    $cotizacion->addAlert($alert["Code"],$alert["Description"]);
                }
            }

       
        
        return $cotizacion;
    }


    /**
     * Crea el objeto de paquetes de cotizacion
     */
    private function createPakageCotizacion(Paquete $paquete){

        
        $peso  = $paquete->peso;//['num_peso'];
        $largo = ceil($paquete->largo);
        $ancho = ceil($paquete->ancho);
        $alto  = ceil($paquete->alto);

        $json = [];
        
        $json["PackagingType"] = [];
        $json["PackagingType"]["Code"] = self::PT_PACKAGE; //PAQUETE
        $json["PackagingType"]["Description"] = "PAQUETE";
                  
        $json["Dimensions"] = [];
        $json["Dimensions"]["UnitOfMeasurement"] = [];
        $json["Dimensions"]["UnitOfMeasurement"]["Code"] = "CM";
        $json["Dimensions"]["UnitOfMeasurement"]["Description"] = "Centimetros";
                    
        $json["Dimensions"]["Length"] = "" . $largo;
        $json["Dimensions"]["Height"] = "" . $alto;
        $json["Dimensions"]["Width"]  = "" . $ancho;
                  
        $json["PackageWeight"] = [];
        $json["PackageWeight"]["UnitOfMeasurement"] = [];
        $json["PackageWeight"]["UnitOfMeasurement"]["Code"] = "KGS";
        $json["PackageWeight"]["UnitOfMeasurement"]["Description"] = "Kilos";
                    
        $json["PackageWeight"]["Weight"]= "". $peso;

        return $json;      
    }

    private function createPakageCompra($paquete){
        $peso  = $paquete->peso;
        $largo = ceil($paquete->largo);
        $ancho = ceil($paquete->ancho);
        $alto  = ceil($paquete->alto);

        $json = [];
        
        $json["Description"] = "Paquete 360";


        $json["Packaging"] = [];
        $json["Packaging"]["Code"] = self::PT_PACKAGE; //PAQUETE
        $json["Packaging"]["Description"] = "PAQUETE";
                  
        $json["Dimensions"] = [];
        $json["Dimensions"]["UnitOfMeasurement"] = [];
        $json["Dimensions"]["UnitOfMeasurement"]["Code"] = "CM";
        $json["Dimensions"]["UnitOfMeasurement"]["Description"] = "Centimetros";
                    
        $json["Dimensions"]["Length"] = "" . $largo;
        $json["Dimensions"]["Height"] = "" . $alto;
        $json["Dimensions"]["Width"]  = "" . $ancho;
                  
        $json["PackageWeight"] = [];
        $json["PackageWeight"]["UnitOfMeasurement"] = [];
        $json["PackageWeight"]["UnitOfMeasurement"]["Code"] = "KGS";
        $json["PackageWeight"]["UnitOfMeasurement"]["Description"] = "Kilos";
                    
        $json["PackageWeight"]["Weight"]= "". $peso;

        return $json; 
    }

   

    /**
     * Envío de sobre
     */
    private function cotizarEnvioPaqueteInterno($tipo_servicio,CotizacionRequest $cotizacionRequest){
        //$cp_origen,$estado_origen, $pais_origen, $cp_destino, $estado_destino, $pais_destino, $fecha, $paquetes
        $json = [];

        $json["UPSSecurity"] = $this->getSecurity();
        
        $json["RateRequest"] = [];
        $json["RateRequest"]["Request"] = [];
        $json["RateRequest"]["Request"]["RequestOption"] = "Rate";
        $json["RateRequest"]["Request"]["TransactionReference"] = [];
        $json["RateRequest"]["Request"]["TransactionReference"]["CustomerContext"] = self::UPS_CUSTOMER_CONTEXT;
              
        $json["RateRequest"]["Shipment"] = [];
        $json["RateRequest"]["Shipment"]["Shipper"] = $this->getShipper($cotizacionRequest->origenStateCode,$cotizacionRequest->origenCP,$cotizacionRequest->origenCountry);
        $json["RateRequest"]["Shipment"]["ShipTo"] = $this->getShipTo($cotizacionRequest->destinoCP, $cotizacionRequest->destinoStateCode,$cotizacionRequest->destinoCountry);
        $json["RateRequest"]["Shipment"]["ShipFrom"] = $this->getShipper($cotizacionRequest->origenStateCode,$cotizacionRequest->origenCP,$cotizacionRequest->origenCountry);

        $json["RateRequest"]["Shipment"]["Service"]["Code"] = $tipo_servicio ;//"01"; //Tipo de envío
        $json["RateRequest"]["Shipment"]["Service"]["Description"] = "Service Code Description ";

        

        //por cada paquete se agrega un elemento a la peticion
        $listaPaquetes = [];
        foreach($cotizacionRequest->paquetes as $item){
            $res = $this->createPakageCotizacion($item);
            array_push($listaPaquetes, $res);

            //$json["RateRequest"]["Shipment"]["Service"] = $res;
        }
        $json["RateRequest"]["Shipment"]["Package"] = $listaPaquetes;
        
        $json["RateRequest"]["Shipment"]["ShipmentRatingOptions"] = [];
        $json["RateRequest"]["Shipment"]["NegotiatedRatesIndicator"] =  "";


        $endpoint = $this->URL_SERVICE . 'Rate';

        $response = $this->jsonRequest($endpoint, $json);
        
        // Check for errors
        if($response === FALSE){
            //die(curl_error($ch));
            return null;
        }
    
        // Decode the response
        $responseData = json_decode($response, TRUE);

        //Respondio con error
        if(isset($responseData['Fault'])){
            $severityError = $responseData['Fault']['detail']['Errors']['ErrorDetail']['Severity'];
            $codeError = $responseData['Fault']['detail']['Errors']['ErrorDetail']['PrimaryErrorCode']['Code'];
            $descError = $responseData['Fault']['detail']['Errors']['ErrorDetail']['PrimaryErrorCode']['Description'];
            error_log("Error con el servicio de UPS: " . $severityError . " " . $codeError . " " . $descError);
            return null;
        }


   
            $cotizacion = new Cotizacion();

            $cotizacion->provider               = "UPS";
            $cotizacion->price                  = $responseData["RateResponse"]["RatedShipment"]["TotalCharges"]["MonetaryValue"];
            $cotizacion->tax                    = 0;
            $cotizacion->serviceType            = $responseData["RateResponse"]["RatedShipment"]["Service"]["Code"] . " " . $responseData["RateResponse"]["RatedShipment"]["Service"]["Description"];
            $cotizacion->deliveryDate           = "";
            $cotizacion->currency               = $responseData["RateResponse"]["RatedShipment"]["TotalCharges"]["CurrencyCode"];
            $cotizacion->data                   = $responseData;
            $cotizacion->servicePacking         = "PT_UPSLETTER";

            //Tiempo de entrega UPS
            if(isset($responseData["RateResponse"]["RatedShipment"]["GuaranteedDelivery"])){
                if(isset($responseData["RateResponse"]["RatedShipment"]["GuaranteedDelivery"]["BusinessDaysInTransit"])){
                    $cotizacion->businessDaysInTransit  = $responseData["RateResponse"]["RatedShipment"]["GuaranteedDelivery"]["BusinessDaysInTransit"];
                }
                if(isset($responseData["RateResponse"]["RatedShipment"]["GuaranteedDelivery"]["DeliveryByTime"]) && 
                    $responseData["RateResponse"]["RatedShipment"]["GuaranteedDelivery"]["DeliveryByTime"] != null){
                    $cotizacion->deliveryByTime  = $responseData["RateResponse"]["RatedShipment"]["GuaranteedDelivery"]["DeliveryByTime"];
                }
            }
            

            
             //Alertas
             $alertas = $responseData["RateResponse"]["RatedShipment"]["RatedShipmentAlert"];
             if(is_array($alertas) && isset($alertas['Code']) ){
                 $cotizacion->addAlert($alertas["Code"],$alertas["Description"]);
             }else{
                 foreach($alertas as $alert){
                     $cotizacion->addAlert($alert["Code"],$alert["Description"]);
                 }
             }

       
        
        return $cotizacion;
    }





    private function jsonRequest($url, $postData){
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt_array($ch, array(
            CURLOPT_POST => TRUE,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_POSTFIELDS => json_encode($postData),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            )
        ));

        // Send the request
        $response = curl_exec($ch);

        // Check for errors
        if($response === FALSE){
            die(curl_error($ch));
        }

        return $response;
    }



    //--------------- UTILIDADES ------------------------
    private function getSecurity(){
        $json = [];
        $json["UsernameToken"] = [];
        $json["UsernameToken"]["Username"] =  self::UPS_USER_NAME;
        $json["UsernameToken"]["Password"] =  self::UPS_PASSWORD;
        $json["ServiceAccessToken"] = [];
        $json["ServiceAccessToken"]["AccessLicenseNumber"] = self::UPS_LICENCE_NUMBER;

        return $json;
    }

    private function getShipper($stado_origen,$cp_origen,$pais_origen, $shiperName = null, $direccion = null){
        $json = [];
        if($shiperName == null){
            $json["Name"] = "Shipper Name";
        }else{
            $json["Name"] = $shiperName;
        }
        $json["ShipperNumber"] =  self::UPS_SHIPER_NUMBER;
        
        $json["Address"] = [];
        if($direccion != null){
            $json["Address"]["AddressLine"] = str_split($direccion, 30);
        }else{
            $json["Address"]["AddressLine"] = ["Address Line ", "Address Line ", "Address Line "];
        }
        
        $json["Address"]["City"] =  self::UPS_RATE_CITY;
        $json["Address"]["StateProvinceCode"] =  $stado_origen;
        $json["Address"]["PostalCode"] =  $cp_origen;
        $json["Address"]["CountryCode"] =  $pais_origen;

        return $json;
    }

    private function getShipTo( $estado_destino, $cp_destino,$pais_destino, $direccion = null){
        $json = [];
        $json["Name"] =  "Ship To Name";
        $json["Address"] = [];
        if($direccion != null){
            $json["Address"]["AddressLine"] = str_split($direccion, 30);
        }else{
            $json["Address"]["AddressLine"] = ["Address Line ", "Address Line ", "Address Line "];
        }
        $json["Address"]["City"] =  self::UPS_RATE_CITY;
        $json["Address"]["StateProvinceCode"] =  $estado_destino;
        $json["Address"]["PostalCode"] =  $cp_destino;
        $json["Address"]["CountryCode"] =  $pais_destino;

        return $json;
    }
}