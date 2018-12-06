<?php


namespace app\_360Utils\Services;

use Yii;
use app\models\WrkDatosCompras;
use app\_360Utils\Cotizacion;
use app\_360Utils\Entity\CPInfo;


class GeoNamesServices{

    const USER_NAME = '2gom360';


function getCPData($cp, $country){
   

        $endpoint = 'http://api.geonames.org/postalCodeSearchJSON?formatted=true&postalcode_startsWith=' . $cp . '&maxRows=10&country=' . $country . '&username=' . self::USER_NAME . '&style=MEDIUM';

        $response = $this->jsonRequest($endpoint, '');


        // Check for errors
        if($response === FALSE){
            //die(curl_error($ch));
            return null;
        }
    
        // Decode the response
        $responseData = json_decode($response, TRUE);

        $res = [];

        foreach($responseData['postalCodes'] as $item){
            $cp = new CPInfo();
            //$cp->neighborhood = $item['adminName3'];
            $cp->city = $item['adminName2'];
            $cp->country_code = $item['countryCode'];
            $cp->state_code = $item['ISO3166-2'];
            $cp->neighborhood = $item['placeName'];
            $cp->postal_code = $item['postalCode'];
            $cp->state = $item['adminName1'];
            $cp->value = $item['postalCode'] . " - " . $item['placeName'] . " - " . $item['adminName2'];
            array_push($res, $cp);
        }

        return $res;

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


}