<?php

namespace app\controllers;

use Yii;
use yii\rest\Controller;
use yii\data\ActiveDataFilter;
use yii\data\ActiveDataProvider;
use app\models\EntClientesSearch;
use app\models\MessageResponse;
use app\models\EntClientes;
use app\models\WrkOrigen;
use app\models\ListResponse;
use app\models\WrkDestino;
use app\models\EnviosObject;
use yii\helpers\Url;
use app\models\Calendario;
use app\models\Utils;
use app\models\EntPagosRecibidos;

/**
 * ConCategoiriesController implements the CRUD actions for ConCategoiries model.
 */
class ApiController extends Controller
{   
    public $serializer = [
        'class' => 'app\components\SerializerExtends',
        'collectionEnvelope' => 'items',
    ];

    /**
     * {@inheritdoc}
     */
    protected function verbs()
    {
        return [
            'buscar-cliente' => ['GET', 'HEAD'],
            'direcciones-origen' => ['POST'],
            'direcciones-destino' => ['POST'],
            'direccion-origen' => ['POST'],
            'direccion-destino' => ['POST'],
            'datos-estafeta' => ['POST'],
            'datos-fedex' => ['POST'],
            'guardar-origen' => ['POST'],
            'guardar-destino' => ['POST'],
            'pagos-recibidos' => ['POST'],

            'update' => ['PUT', 'PATCH'],
            'delete' => ['DELETE'],
        ];
    }

    public function actionBuscarCliente($q=null, $page=0){
        //\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $criterios['txt_correo'] = $q;
        $searchModel = new EntClientesSearch();

        if($page > 1){
            $page--;
        }

        $dataProvider = $searchModel->searchClientes($criterios, $page);
        $response['results'] = null;
        $response['total_count'] = $dataProvider->getTotalCount();

        $resultados = $dataProvider->getModels();
        if (count($resultados) == 0) {
            $response['results'][0] = ['id' => '', "txt_nombre" => ''];
        }

        foreach ($resultados as $model) {
            $response['results'][] = [
                'id' => $model->uddi, 
                "txt_nombre" => $model->txt_correo
            ];     
        }        

        return $response;
    }

    /**
     * Servicio para buscar direcciones origen de cliente
     */
    public function actionDireccionesOrigen(){
        $request = Yii::$app->request;
        // $request->getBodyParam('uddi_cliente');

        $error = new MessageResponse();
        $error->responseCode = -1;

        if(empty($request->getBodyParam('uddi_cliente'))){
            $error->message = 'Body de la petición faltante';

            return $error;
        }

        //verifica que los parámetros solicitados se encuentren
        $uddi_cliente = $request->getBodyParam('uddi_cliente');

        $cliente = EntClientes::find()->where(['uddi'=>$uddi_cliente])->one();
        
        if(!$cliente){
            $error->message = 'El cliente no se encontro';
            
            return $error;
        }

        $origenes = WrkOrigen::find()->where(['id_cliente'=>$cliente->id_cliente])->andWhere(['!=', 'txt_nombre_ubicacion', ''])->all();
        
        $response = new ListResponse();
        $response->results = $origenes;
        $response->count = count($origenes);
        $response->operation = "Lista de direcciones origen de un cliente";

        return $response;
    }

    /**
     * Servicio para buscar direcciones destino de cliente
     */
    public function actionDireccionesDestino(){
        $request = Yii::$app->request;
        // $request->getBodyParam('uddi_cliente');

        $error = new MessageResponse();
        $error->responseCode = -1;

        if(empty($request->getBodyParam('uddi_cliente'))){
            $error->message = 'Body de la petición faltante';

            return $error;
        }

        //verifica que los parámetros solicitados se encuentren
        $uddi_cliente = $request->getBodyParam('uddi_cliente');

        $cliente = EntClientes::find()->where(['uddi'=>$uddi_cliente])->one();

        if(!$cliente){
            $error->message = 'El cliente no se encontro';
            
            return $error;
        }

        $destinos = WrkDestino::find()->where(['id_cliente'=>$cliente->id_cliente])->andWhere(['!=', 'txt_nombre_ubicacion', ''])->all();
        
        $response = new ListResponse();
        $response->results = $destinos;
        $response->count = count($destinos);
        $response->operation = "Lista de direcciones destino de un cliente";

        return $response;
    }

    /**
     * Servicio para buscar una direccion origen del cliente
     */
    public function actionDireccionOrigen(){
        $request = Yii::$app->request;

        $error = new MessageResponse();
        $error->responseCode = -1;

        if(empty($request->getBodyParam('id_origen'))){
            $error->message = 'Body de la petición faltante';

            return $error;
        }

        //verifica que los parámetros solicitados se encuentren
        $id_origen = $request->getBodyParam('id_origen');

        $direccion = WrkOrigen::find()->where(['id_origen'=>$id_origen])->one();
        if(!$direccion){
            $error->message = 'La dirección no se encontro';
            
            return $error;
        }

        $success = new MessageResponse();
        $success->message = "Success";
        $success->responseCode = 1;
        $success->data = $direccion;

        return $success;
    }

    /**
     * Servicio para buscar una direccion destino del cliente
     */
    public function actionDireccionDestino(){
        $request = Yii::$app->request;

        $error = new MessageResponse();
        $error->responseCode = -1;

        if(empty($request->getBodyParam('id_destino'))){
            $error->message = 'Body de la petición faltante';

            return $error;
        }

        //verifica que los parámetros solicitados se encuentren
        $id_destino = $request->getBodyParam('id_destino');

        $direccion = WrkDestino::find()->where(['id_destino'=>$id_destino])->one();
        if(!$direccion){
            $error->message = 'La dirección no se encontro';
            
            return $error;
        }

        $success = new MessageResponse();
        $success->message = "Success";
        $success->responseCode = 1;
        $success->data = $direccion;

        return $success;
    }

    /**
     * 
     */
    public function actionDatosEstafeta($package = null){
        $request = Yii::$app->request;//print_r($request->getBodyParam('from'));exit;

        $error = new MessageResponse();
        $error->responseCode = -1;

        if(empty($request->getBodyParam('from'))){
            $error->message = 'Body de la petición faltante';

            return $error;
        }

        if(empty($request->getBodyParam('to'))){
            $error->message = 'Body de la petición faltante1';

            return $error;
        }

        //verifica que los parámetros solicitados se encuentren
        $from = $request->getBodyParam('from');
        $to = $request->getBodyParam('to');

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

        if($err){
            echo "cURL Error #:" . $err;
        }else{
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

    public function actionFedex(){
        $request = Yii::$app->request;

        $error = new MessageResponse();
        $error->responseCode = -1;

        if(empty($request->getBodyParam('from'))){
            $error->message = 'Body de la petición faltante1';

            return $error;
        }
        if(empty($request->getBodyParam('countryCodeFrom'))){
            $error->message = 'Body de la petición faltante2';

            return $error;
        }
        if(empty($request->getBodyParam('countryCodeTo'))){
            $error->message = 'Body de la petición faltante3';

            return $error;
        }
        if(empty($request->getBodyParam('to'))){
            $error->message = 'Body de la petición faltante4';

            return $error;
        }

        //verifica que los parámetros solicitados se encuentren
        $from = $request->getBodyParam('from');
        $countryCodeFrom = $request->getBodyParam('countryCodeFrom');
        $countryCodeTo = $request->getBodyParam('countryCodeTo');
        $to = $request->getBodyParam('to');

        $data = [];
        $fecha = Utils::changeFormatDateInputShort(Calendario::getFechaActual());
        $opcionesEnvio = $this->validarDisponibilidad($fecha, $from, $to, $countryCodeFrom, $countryCodeTo);
        print_r($opcionesEnvio);exit;

        foreach ($opcionesEnvio->data->options as $opciones) {
            $costo = $this->getCosto($opciones->Service, $from, $to, $countryCodeFrom, $countryCodeTo);

            if (isset($costo->HighestSeverity) && $costo->HighestSeverity != "ERROR") {
                $eo = new EnviosObject();
                $eo->cpOrigen = $from;
                $eo->cpDestino = $to;
                $eo->precioOriginal = $costo->RateReplyDetails->RatedShipmentDetails[1]->ShipmentRateDetail->TotalNetCharge->Amount;
                $eo->mensajeria = "FEDEX";
                $eo->fechaEntrega = Calendario::getDateComplete($costo->RateReplyDetails->CommitDetails->CommitTimestamp);
                $eo->tipoEnvio = $costo->RateReplyDetails->ServiceType;
                $eo->urlImagen = Url::base()."/webAssets/images/fedex.png";
                $data[] = $eo;
            }
        }

        return $data;
    }

    public function validarDisponibilidad($date, $from, $to, $countryCodeFrom, $countryCodeTo){
        $curl = curl_init();
        
        $params["ship_date"] = $date;
        $params["shiper"]["postal_code"] = $from;
        $params["shiper"]["country_code"] = $countryCodeFrom;
        $params["recipient"]["country_code"] = $countryCodeTo;
        $params["recipient"]["postal_code"] = $to;

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://dev.2geeksonemonkey.com/cotizador-envios/web/services/validate-service",
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

    public function actionGuardarOrigen(){
        $request = Yii::$app->request;

        $error = new MessageResponse();
        $error->responseCode = -1;

        $datosOrigen = new WrkOrigen();
        if($datosOrigen->load($request->bodyParams, "")){
            if(!$datosOrigen->save()){
                $error->message = 'No se guardo la direccion';
                $error->data = $datosOrigen->errors;

                return $error;
            }

            return $datosOrigen;
        }else{
            $error->message = 'No hay datos para guardar la direccion';

            return $error;
        }
    }

    public function actionGuardarDestino(){
        $request = Yii::$app->request;

        $error = new MessageResponse();
        $error->responseCode = -1;

        $datosDestino = new WrkDestino();
        if($datosDestino->load($request->bodyParams, "")){
            if(!$datosDestino->save()){
                $error->message = 'No se guardo la direccion';
                $error->data = $datosDestino->errors;

                return $error;
            }

            return $datosDestino;
        }else{
            $error->message = 'No hay datos para guardar la direccion';

            return $error;
        }
    }

    public function actionPagosRecibidos(){
        $request = Yii::$app->request;
        
        $error = new MessageResponse();
        $error->responseCode = -1;

        if(empty($request->getBodyParam('id_cliente'))){
            $error->message = 'Body de la petición faltante';

            return $error;
        }

        $id_cliente = $request->getBodyParam('id_cliente');

        $pagos = EntPagosRecibidos::find()->where(['id_cliente'=>$id_cliente])->all();
        if($pagos){

            return $pagos;
        }
        $error->message = 'No hay pagos realizados';

        return $error;
    }
}