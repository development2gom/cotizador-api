<?php
namespace app\models;

use app\config\ServicesApiConfig;
use app\models\Utils;
use yii\helpers\Url;
//https://github.com/linslin/Yii2-Curl
use linslin\yii2\curl\Curl;
use yii\base\Model;

class Fedex extends Model
{
    public $curl;
    public $tipoPaquete;
    const IMAGE_URL = "";
    const SOBRE = "FEDEX_ENVELOPE";
    const PAQUETE = "YOUR_PACKAGING";
    const TRADUCCIONES = [
        "STANDARD_OVERNIGHT"=>"Siguiente día",
        "FEDEX_EXPRESS_SAVER"=>"Express economico",
        "FEDEX_2_DAY_FREIGHT"=>"Dos días"
    ];
    
    public $message;

    function __construct($tipoPaquete) {
        
        if(strtoupper($tipoPaquete)=="SOBRE"){
            $this->tipoPaquete = self::SOBRE;
        }else{
            $this->tipoPaquete = self::PAQUETE;
        }

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
        ];

       $this->curl = new Curl();
       $this->curl->setOptions($options);
       $this->curl->setHeaders([
            'Cache-Control' => 'no-cache',
            'Content-Type'=> 'application/json'
        ]);
    }

    /**
     * Validacion de codigo postal
     */
    public function validarCP($cp){
        $parametros = [
            "country_code"=>"MX",
            "postal_code"=>$cp
        ];
        $parametros = json_encode($parametros);

        $respuesta = $this->curl
            ->setRawPostData($parametros)
            ->post(ServicesApiConfig::URL_API_VALIDATE_CP);
        $objetoRespuesta = json_decode($respuesta);

        return $objetoRespuesta; 

    }

    // Valida la disponibilidad del envio
    public function validarDisponibilidad($date, $from, $to, $countryCodeFrom, $countryCodeTo)
    {
        $parametros = [
            "ship_date"=>$date,
            "service_packing"=>$this->tipoPaquete,
            "shiper"=>[
                "postal_code"=>$from,
                "country_code"=>$countryCodeFrom
            ],
            "recipient"=>[
                "postal_code"=>$to,
                "country_code"=>$countryCodeTo
            ]
        ];

        $parametros = json_encode($parametros);

        $respuesta = $this->curl
            ->setRawPostData($parametros)
            ->post(ServicesApiConfig::URL_API_VALIDATE_SERVICE);
        $objetoRespuesta = json_decode($respuesta);

        

        return $objetoRespuesta; 
       
    }

    public function getCosto($serviceType, $from, $to, $countryCodeFrom, $countryCodeTo, $paquetes)
    {
        $params = [];
        $params["service_type"] = $serviceType;
        $params["service_packing"] = $this->tipoPaquete;// FEDEX_ENVELOPE
        $params["shiper"]["postal_code"] = $from;
        $params["shiper"]["country_code"] =$countryCodeFrom;
        $params["recipient"]["postal_code"] = $to;
        $params["recipient"]["country_code"] = $countryCodeTo;

        foreach($paquetes as $key => $paquete){
            $params["package"][$key]["peso_kg"] = $paquete['num_peso'];
            $params["package"][$key]["largo_cm"] = $paquete['num_largo'];
            $params["package"][$key]["ancho_cm"] = $paquete['num_ancho'];
            $params["package"][$key]["alto_cm"] = $paquete['num_alto'];
        }

        $curl = curl_init();

        curl_setopt_array($curl, array(

            CURLOPT_URL => ServicesApiConfig::URL_API_COSTO,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($params),
            CURLOPT_HTTPHEADER => array(
                "Cache-Control: no-cache",
                "Content-Type: application/json",

            ),
        ));

        $response = curl_exec($curl);//print_r($response);exit;
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
          //echo "cURL Error #:" . $err;
            return false;
        } else {
            return json_decode($response);
        }
    }

    public function getLabel($serviceType, $from,$paisOrigen, $paisDestino, $to, $ciudadOrigen, $ciudadDestino, $nombrePersonaOrigen, 
    $nombrePersonaDestino, $phoneOrigen, $phoneDestino, $addresLineOrigen, $addresLineDestino, $companyNameOrigen, $companyNameDestino, $paquetes)
    {   

        $paquetesEspecificaciones = [];
        foreach($paquetes as $key => $paquete){
            $paquetesEspecificaciones[$key]["peso_kg"] = $paquete->num_peso;
            $paquetesEspecificaciones[$key]["largo_cm"] = $paquete->num_largo;
            $paquetesEspecificaciones[$key]["ancho_cm"] = $paquete->num_ancho;
            $paquetesEspecificaciones[$key]["alto_cm"] = $paquete->num_alto;
        }

        $params = [
            "service_type"=>$serviceType,
            "service_packing"=>$this->tipoPaquete,
            "shiper"=>[
                "postal_code"=>$from,
                "country_code"=>$paisOrigen,
                "city"=>$ciudadOrigen,
                "state_code"=>"",
                "person_name"=>$nombrePersonaOrigen,
                "address_line"=>$addresLineOrigen,
                "phone_number"=>$phoneOrigen,
                "company_name"=>$companyNameOrigen
            ],
            "recipient"=>[
                "postal_code"=>$to,
                "country_code"=>$paisDestino,
                "city"=>$ciudadDestino,
                "state_code"=>"",
                "person_name"=>$nombrePersonaDestino,
                "address_line"=>$addresLineDestino,
                "phone_number"=>$phoneDestino,
                "company_name"=>$companyNameDestino
            ],
            "package"=>$paquetesEspecificaciones
            
        ];

        
        
        $params = json_encode($params);
        
        $respuesta = $this->curl
            ->setRawPostData($params)
            ->post(ServicesApiConfig::URL_API_LABEL);   

        $objetoRespuesta = json_decode($respuesta);

        return $objetoRespuesta; 
    }

    public function getFedex($from, $to, $countryCodeFrom, $countryCodeTo, $paquetes, $tipoPaquete){

        $data = [];
        $fecha = Utils::changeFormatDateInputShort(Calendario::getFechaActual());
        $opcionesEnvio = $this->validarDisponibilidad($fecha, $from, $to, $countryCodeFrom, $countryCodeTo);

        if (isset($opcionesEnvio->HighestSeverity) && $opcionesEnvio->HighestSeverity != "ERROR") {
            $opcionesArray = [];

            if(is_array($opcionesEnvio->Options)){
                $opcionesArray = $opcionesEnvio->Options;
            }else{
                $opcionesArray[] = $opcionesEnvio->Options;
            }

            foreach ($opcionesArray as $opciones) {
                $costo = $this->getCosto($opciones->Service, $from, $to, $countryCodeFrom, $countryCodeTo, $paquetes);
                $eo = new EnviosObject();
                
                
                if (isset($costo->HighestSeverity) && $costo->HighestSeverity != "ERROR") {
                    
                    $eo->cpOrigen = $from;
                    $eo->cpDestino = $to;
                    $eo->precioOriginal = $costo->RateReplyDetails->RatedShipmentDetails[1]->ShipmentRateDetail->TotalNetCharge->Amount;
                    $eo->precioCliente = $costo->RateReplyDetails->RatedShipmentDetails[1]->ShipmentRateDetail->TotalNetCharge->Amount;
                    $eo->mensajeria = "FEDEX";
                    if(isset($costo->RateReplyDetails->CommitDetails->CommitTimestamp)){
                        $eo->fechaEntrega = Calendario::getDateComplete($costo->RateReplyDetails->CommitDetails->CommitTimestamp);    
                    }
                    
                    $eo->tipoEnvio = $costo->RateReplyDetails->ServiceType;
                    $eo->urlImagen = Url::base()."/webAssets/images/fedex.png";
                    
                }else{
                    $eo->hasError = true;
                    $eo->mensaje = "Resultado vacío";
                    if(isset($costo->Notifications)){
                        $eo->mensaje = $this->obtenerErrores($costo->Notifications);
                    }
                    
                    //$eo->mensaje = $this->obtenerErrores($costo->Notifications);
                }
                $data[] = $eo;
    
            }
    
        }else{

            $errorMessage = $this->obtenerErrores($opcionesEnvio->Notifications);
            $eo = new EnviosObject();
            $eo->hasError = true;
            $eo->mensaje = $errorMessage;
            $data[]=$eo;
        }

        //print_r($opcionesEnvio);exit;
       
        return $data;
    }

    public function obtenerErrores($notifications){
        $errorMessage = '';
        if(is_array($notifications)){
            foreach($notifications as $notification){
                $errorMessage .= $notification->Message;
            }
        }else{
            $errorMessage .= $notifications->Message;
        }

        return $errorMessage;
    }

   

}