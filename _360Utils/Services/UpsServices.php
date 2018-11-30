<?php


namespace app\_360Utils\Services;

use Yii;
use app\models\WrkDatosCompras;
use app\_360Utils\Cotizacion;

class UpsServices{

    const UPS_LICENCE_NUMBER    = 'DD53B5465E301D15';
    const UPS_PASSWORD          = 'Mexico01';
    const UPS_USER_NAME         = 'W1R182.apis';
    const UPS_CUSTOMER_CONTEXT  = 'Your Customer Context';
    const UPS_SHIPER_NUMBER     = 'Shipper Number';
    const UPS_RATE_CITY         = 'CITY';

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

    const URL_DEV  = 'https://wwwcie.ups.com/rest/';
    const URL_PROD = 'https://onlinetools.ups.com/rest/';

    var $URL_SERVICE = 'https://wwwcie.ups.com/rest/';


    function cotizarEnvioDocumento($cp_origen,$stado_origen, $pais_origen, $cp_destino, $estado_destino, $pais_destino, $fecha, $paquetes){
        $servicios = [self::S_AIR_1DAY,self::S_AIR_2DAY,self::S_GROUND];
        $responses = [];

        $paquete = $paquetes[0];

        //Cambia el peso de kilos a libras
        $peso = $paquete['num_peso'] * 2.20462;

        foreach($servicios as $item){
            $res = $this->cotizarEnvioDocumentoInterno($item,$cp_origen,$stado_origen, $pais_origen, $cp_destino, $estado_destino, $pais_destino, $fecha, $peso);
            if($res != null){
                array_push($responses,$res);
            }
        }

        return $responses;
    }


    function cotizarEnvioPaquete($cp_origen,$stado_origen, $pais_origen, $cp_destino, $estado_destino, $pais_destino, $fecha, $paquetes){
        $servicios = [self::S_AIR_1DAY,self::S_AIR_2DAY,self::S_GROUND, self::S_AIR_1DAYEARLYAM,self::S_3DAYSELECT];
        $responses = [];

        //TODO contemplar varios paquetes
        $paquete = $paquetes[0];

        //Cambia el peso de kilos a libras
        $peso = $paquete['num_peso'] * 2.20462;

        //Cambia el tamaño de cm a pulgadas
        $largo = $paquete['num_largo'] * 0.393701;
        $ancho = $paquete['num_ancho'] * 0.393701;
        $alto  =  $paquete['num_alto'] * 0.393701;


        foreach($servicios as $item){
            $res = $this->cotizarEnvioPaqueteInterno($item,$cp_origen,$stado_origen, $pais_origen, $cp_destino, $estado_destino, $pais_destino, $fecha, $peso, $largo,$ancho,$alto);
            if($res != null){
                array_push($responses,$res);
            }
        }

        return $responses;
    }

    
    /**
     * Envío de sobre
     */
    private function cotizarEnvioDocumentoInterno($tipo_servicio,$cp_origen,$estado_origen, $pais_origen, $cp_destino, $estado_destino, $pais_destino, $fecha, $peso_libras){

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
                  
        //$json["RateRequest"]["Shipment"]["Package"]["Dimensions"] = [];
        //$json["RateRequest"]["Shipment"]["Package"]["Dimensions"]["UnitOfMeasurement"] = [];
        //$json["RateRequest"]["Shipment"]["Package"]["Dimensions"]["UnitOfMeasurement"]["Code"] = "IN";
        //$json["RateRequest"]["Shipment"]["Package"]["Dimensions"]["UnitOfMeasurement"]["Description"] = "inches";
                    
        //$json["RateRequest"]["Shipment"]["Package"]["Dimensions"]["Length"] = "5";
        //$json["RateRequest"]["Shipment"]["Package"]["Dimensions"]["Width"] = "4";
        //$json["RateRequest"]["Shipment"]["Package"]["Dimensions"]["Height"] = "3";
                  
        $json["RateRequest"]["Shipment"]["Package"]["PackageWeight"] = [];
        $json["RateRequest"]["Shipment"]["Package"]["PackageWeight"]["UnitOfMeasurement"] = [];
        $json["RateRequest"]["Shipment"]["Package"]["PackageWeight"]["UnitOfMeasurement"]["Code"] = "Lbs";
        $json["RateRequest"]["Shipment"]["Package"]["PackageWeight"]["UnitOfMeasurement"]["Description"] = "pounds";
                    
        $json["RateRequest"]["Shipment"]["Package"]["PackageWeight"]["Weight"]= "". $peso_libras;
        
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
            foreach($responseData["RateResponse"]["RatedShipment"]["RatedShipmentAlert"] as $alert){
                $cotizacion->addAlert($alert["Code"],$alert["Description"]);
            }

       
        
        return $cotizacion;
    }


    /**
     * Envío de sobre
     */
    private function cotizarEnvioPaqueteInterno($tipo_servicio,$cp_origen,$estado_origen, $pais_origen, $cp_destino, $estado_destino, $pais_destino, $fecha, $peso_libras, $largo,$ancho,$alto){

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
        $json["RateRequest"]["Shipment"]["Service"]["Description"] = "Service Code Description ";
                
        $json["RateRequest"]["Shipment"]["Package"] = [];
        $json["RateRequest"]["Shipment"]["Package"]["PackagingType"] = [];
        $json["RateRequest"]["Shipment"]["Package"]["PackagingType"]["Code"] = self::PT_PACKAGE; //PAQUETE
        $json["RateRequest"]["Shipment"]["Package"]["PackagingType"]["Description"] = "PAQUETE";
                  
        $json["RateRequest"]["Shipment"]["Package"]["Dimensions"] = [];
        $json["RateRequest"]["Shipment"]["Package"]["Dimensions"]["UnitOfMeasurement"] = [];
        $json["RateRequest"]["Shipment"]["Package"]["Dimensions"]["UnitOfMeasurement"]["Code"] = "IN";
        $json["RateRequest"]["Shipment"]["Package"]["Dimensions"]["UnitOfMeasurement"]["Description"] = "inches";
                    
        $json["RateRequest"]["Shipment"]["Package"]["Dimensions"]["Length"] = "" . ceil($largo);
        $json["RateRequest"]["Shipment"]["Package"]["Dimensions"]["Width"] = "" . ceil($ancho);
        $json["RateRequest"]["Shipment"]["Package"]["Dimensions"]["Height"] = "" . ceil($alto);
                  
        $json["RateRequest"]["Shipment"]["Package"]["PackageWeight"] = [];
        $json["RateRequest"]["Shipment"]["Package"]["PackageWeight"]["UnitOfMeasurement"] = [];
        $json["RateRequest"]["Shipment"]["Package"]["PackageWeight"]["UnitOfMeasurement"]["Code"] = "Lbs";
        $json["RateRequest"]["Shipment"]["Package"]["PackageWeight"]["UnitOfMeasurement"]["Description"] = "pounds";
                    
        $json["RateRequest"]["Shipment"]["Package"]["PackageWeight"]["Weight"]= "". $peso_libras;
        
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
            foreach($responseData["RateResponse"]["RatedShipment"]["RatedShipmentAlert"] as $alert){
                $cotizacion->addAlert($alert["Code"],$alert["Description"]);
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

    private function getShipper($stado_origen,$cp_origen,$pais_origen){
        $json = [];
        $json["Name"] = "Shipper Name";
        $json["ShipperNumber"] =  self::UPS_SHIPER_NUMBER;
        
        $json["Address"] = [];
        $json["Address"]["AddressLine"] = ["Address Line ", "Address Line ", "Address Line "];
        $json["Address"]["City"] =  self::UPS_RATE_CITY;
        $json["Address"]["StateProvinceCode"] =  $stado_origen;
        $json["Address"]["PostalCode"] =  $cp_origen;
        $json["Address"]["CountryCode"] =  $pais_origen;

        return $json;
    }

    private function getShipTo($cp_destino, $estado_destino, $pais_destino){
        $json = [];
        $json["Name"] =  "Ship To Name";
        $json["Address"] = [];
        $json["Address"]["AddressLine"] = ["Address Line ", "Address Line ", "Address Line "];
        $json["Address"]["City"] =  self::UPS_RATE_CITY;
        $json["Address"]["StateProvinceCode"] =  $estado_destino;
        $json["Address"]["PostalCode"] =  $cp_destino;
        $json["Address"]["CountryCode"] =  $pais_destino;

        return $json;
    }
}