<?php
namespace app\models;

use app\config\ServicesApiConfig;
use app\models\Utils;
use yii\helpers\Url;


class Fedex
{
    public function validarCP($cp)
    {

        $curl = curl_init();

        curl_setopt_array($curl, array(

            CURLOPT_URL => ServicesApiConfig::URL_API_VALIDATE_CP,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "{\n  \"country_code\":\"MX\",\n  \"postal_code\":\"" . $cp . "\"\n}",
            CURLOPT_HTTPHEADER => array(
                "Cache-Control: no-cache",
                "Content-Type: application/json",
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
           // "cURL Error #:" . $err;
            return false;
        } else {
            $response = json_decode($response);
            if (isset($response->data)) {

                return $response;
            }
            return false;

        }
    }

    public function validarDisponibilidad($date, $from, $to, $countryCodeFrom, $countryCodeTo)
    {
        $curl = curl_init();
       // $params = [];
        $params["ship_date"] = $date;
        $params["service_packing"] = "YOUR_PACKAGING";
        $params["shiper"]["postal_code"] = $from;
        $params["shiper"]["country_code"] = $countryCodeFrom;
        $params["recipient"]["country_code"] = $countryCodeTo;
        $params["recipient"]["postal_code"] = $to;


        curl_setopt_array($curl, array(

            CURLOPT_URL => ServicesApiConfig::URL_API_VALIDATE_SERVICE,
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
//print_r(json_encode($params));
//exit;
        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            "cURL Error #:" . $err;
            return false;
        } else {
            return json_decode($response);
        }
    }

    public function getCosto($serviceType, $from, $to, $countryCodeFrom, $countryCodeTo)
    {
        $params = [];
        $params["service_type"] = $serviceType;
        $params["service_packing"] = "YOUR_PACKAGING";
        $params["shiper"]["postal_code"] = $from;
        $params["shiper"]["country_code"] =$countryCodeFrom;
        $params["recipient"]["postal_code"] = $to;
        $params["recipient"]["country_code"] = $countryCodeTo;
        $params["package"]["peso_kg"] = 2;
        $params["package"]["largo_cm"] = 200;
        $params["package"]["ancho_cm"] = 20;
        $params["package"]["alto_cm"] = 10;

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

    public function getLabel($serviceType=null, $from=null, $to=null, $ciudadOrigen=null, $ciudadDestino=null, $nombrePersonaOrigen=null, 
    $nombrePersonaDestino=null, $phoneOrigen=null, $phoneDestino=null, $addresLineOrigen=null, $addresLineDestino=null, $companyNameOrigen=null, $companyNameDestino=null)
    {
        $curl = curl_init();

        $params["service_type"]= $serviceType;
        $params["service_packing"] = "YOUR_PACKAGING";
        $params["shiper"]["postal_code"] = $from;
        $params["shiper"]["country_code"] = "MX";
        $params["shiper"]["city"] = $ciudadOrigen;
        $params["shiper"]["state_code"] = "EM";
        $params["shiper"]["person_name"] = $nombrePersonaOrigen;
        $params["shiper"]["address_line"] = $addresLineOrigen;
        $params["shiper"]["phone_number"] = $phoneOrigen;
        $params["shiper"]["company_name"] = $companyNameOrigen;

        $params["recipient"]["postal_code"] = $to;
        $params["recipient"]["country_code"] = "MX";
        $params["recipient"]["city"] = $ciudadDestino;
        $params["recipient"]["state_code"] = "EM";
        $params["recipient"]["person_name"] = $nombrePersonaDestino;
        $params["recipient"]["address_line"] = $addresLineDestino;
        $params["recipient"]["phone_number"] = $phoneDestino;
        $params["recipient"]["company_name"] = $companyNameDestino;

        $params["package"]["peso_kg"] = 2;
        $params["package"]["largo_cm"] = 20;
        $params["package"]["ancho_cm"] = 20;
        $params["package"]["alto_cm"] = 10;
        

        curl_setopt_array($curl, array(

            CURLOPT_URL => ServicesApiConfig::URL_API_LABEL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "{\n  \"service_type\": \"FEDEX_EXPRESS_SAVER\",\n    \"service_packing\": \"YOUR_PACKAGING\",\n  \"shiper\":{\n    \"postal_code\":\"53240\",\n    \"country_code\":\"MX\",\n    \"city\":\"mexico\",\n    \"state_code\":\"EM\",\n    \"person_name\":\"Alberto Farías\",\n    \"phone_number\":\"12345678\",\n    \"address_line\":\"Circunvalacion pte 16 CD brisa naucalpan\",\n    \"company_name\":\"2gom\"\n  },\n  \"recipient\":{\n    \"postal_code\":\"08500\",\n    \"country_code\":\"MX\",\n    \"city\":\"mexico\",\n    \"state_code\":\"DF\",\n    \"person_name\":\"Alfredo Elizondo\",\n    \"phone_number\":\"123456789\",\n    \"address_line\":\"Juan Pestalozi 1234 casa 4 benito juarez\",\n    \"company_name\":\"2 geeks one monkey\"\n  },\n  \"package\":{\n    \"peso_kg\":2, \n    \"largo_cm\":20,\n    \"ancho_cm\":20,\n    \"alto_cm\":10\n  }\n}",
            CURLOPT_HTTPHEADER => array(
                "Cache-Control: no-cache",
                "Content-Type: application/json"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            //echo "cURL Error #:" . $err;
            return false;
        } else {
            return json_decode($response);
        }
    }

    public function getFedex($from, $to, $countryCodeFrom, $countryCodeTo){
        $data = [];
        $fecha = Utils::changeFormatDateInputShort(Calendario::getFechaActual());
        $opcionesEnvio = $this->validarDisponibilidad($fecha, $from, $to, $countryCodeFrom, $countryCodeTo);

        //print_r($opcionesEnvio);exit;
        foreach ($opcionesEnvio->data->options as $opciones) {
            $costo = $this->getCosto($opciones->Service, $from, $to, $countryCodeFrom, $countryCodeTo);

            if (isset($costo->HighestSeverity) && $costo->HighestSeverity != "ERROR") {
                $eo = new EnviosObject();
                $eo->cpOrigen = $from;
                $eo->cpDestino = $to;
                $eo->precioOriginal = $costo->RateReplyDetails->RatedShipmentDetails[1]->ShipmentRateDetail->TotalNetCharge->Amount;
                $eo->precioCliente = $costo->RateReplyDetails->RatedShipmentDetails[1]->ShipmentRateDetail->TotalNetCharge->Amount;
                $eo->mensajeria = "FEDEX";
                $eo->fechaEntrega = Calendario::getDateComplete($costo->RateReplyDetails->CommitDetails->CommitTimestamp);
                $eo->tipoEnvio = $costo->RateReplyDetails->ServiceType;
                $eo->urlImagen = Url::base()."/webAssets/images/fedex.png";
                $data[] = $eo;
            }else{
                print_r($costo);
            }

        }

        return $data;
    }

}