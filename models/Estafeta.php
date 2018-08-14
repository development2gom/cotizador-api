<?php
namespace app\models;

use yii\helpers\Url;

class Estafeta{

    public static function datosEstafeta($from, $to, $package = null){

        $params['shiper'] = [
            "postal_code" => $from,
        ];
        $params['recipient'] = [
            'postal_code' => $to
        ];
        $params['package'] = [
            "peso_kg" => 2, 
            "largo_cm" => 200,
            "ancho_cm" => 20,
            "alto_cm" => 10
        ];


        $curl = \curl_init();
        //$curl = new GuzzleHttp();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://dev.2geeksonemonkey.com/cotizador-envios/web/estafeta-services/frecuencia-cotizador",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($params),
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            //echo $response;
            $datos = self::objetosEstafeta(json_decode($response));
            return $datos;
        }
    }


    public static function objetosEstafeta($response){
       
        $arrayEnvios = [];
        $i = 0;
        
        foreach($response->FrecuenciaCotizadorResult->Respuesta->TipoServicio->TipoServicio as $opcion){
            if($opcion->CostoTotal > 0){
                //echo $opcion->CostoTotal . "<br/>";
                $objetoEnvio = new EnviosObject();
                $objetoEnvio->cpOrigen = $response->FrecuenciaCotizadorResult->Respuesta->Origen->CodigoPosOri;
                $objetoEnvio->cpDestino = $response->FrecuenciaCotizadorResult->Respuesta->Destino->CpDestino;
                $objetoEnvio->mensajeria = "Estafeta";
                $objetoEnvio->precioOriginal = $opcion->CostoTotal;
                //$objetoEnvio->precioCliente = $objetoEnvio->calcularPrecioCliente($opcion->CostoTotal);
                $objetoEnvio->precioCliente = $objetoEnvio->calcularPrecioCliente();
                $objetoEnvio->tipoEnvio = $opcion->DescripcionServicio;
                $objetoEnvio->urlImagen = Url::base()."/webAssets/images/estafeta.png";

                $arrayEnvios[$i] = $objetoEnvio;
                $i++;
            }
        }
        //print_r($arrayEnvios);exit;
        return $arrayEnvios;
    }
}