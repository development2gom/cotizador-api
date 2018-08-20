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
use app\models\Fedex;
use app\models\Estafeta;
use app\models\CatPaises;
use yii\filters\auth\HttpBearerAuth;
use app\models\EntFacturacion;

/**
 * ConCategoiriesController implements the CRUD actions for ConCategoiries model.
 */
class ApiController extends Controller
{   
    public $enableCsrfValidation = false;
    public $serializer = [
        'class' => 'app\components\SerializerExtends',
        'collectionEnvelope' => 'items',
    ];

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::className(),
            'cors' => [
                // restrict access to
                'Origin' => ['*'],
                'Access-Control-Request-Method' => ['POST', 'GET','PUT', 'OPTIONS'],
                // Allow only POST and PUT methods
                'Access-Control-Request-Headers' => ['*'],
                // Allow only headers 'X-Wsse'
                // 'Access-Control-Allow-Credentials' => true,
                // Allow OPTIONS caching
                'Access-Control-Max-Age' => 3600,
                // Allow the X-Pagination-Current-Page header to be exposed to the browser.
                'Access-Control-Expose-Headers' => ['X-Pagination-Current-Page'],
            ],
        ];
    
        $auth = $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::className(),
            'only' => ['can-access','profile'],  //access controller
        ];
    
       $behaviors['authenticator']['except'] = ['options'];
        return $behaviors;
    }

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
            'get-cotizacion' => ['POST'],

            'crear-cliente' => ['POST'],

            'update' => ['PUT', 'PATCH'],
            'delete' => ['DELETE'],
            'datos-facturacion' => ['POST'],
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

        if(empty($request->getBodyParam('depdrop_all_params')['search-cliente'])){
            $error->message = 'Body de la petición faltante';

            return $error;
        }

        //verifica que los parámetros solicitados se encuentren
        $uddi_cliente = $request->getBodyParam('depdrop_all_params')['search-cliente'];

        $cliente = EntClientes::find()->where(['uddi'=>$uddi_cliente])->one();
        
        if(!$cliente){
            $error->message = 'El cliente no se encontro';
            
            return $error;
        }

        $origenes = WrkOrigen::find()->where(['id_cliente'=>$cliente->id_cliente])->andWhere(['!=', 'txt_nombre_ubicacion', ''])->all();  
         
        $selected = '';
        $out = [];
        foreach ($origenes as $i => $origen) {
            $out[] = [
                'id' => $origen->id_origen, 
                'name' => $origen->txt_nombre_ubicacion];
            if ($i == 0) {
                $selected = null;
            }
        }
        // Shows how you can preselect a value
        return ['output' => $out, 'selected'=>$selected];
    }

    /**
     * Servicio para buscar direcciones destino de cliente
     */
    public function actionDireccionesDestino(){
        $request = Yii::$app->request;
        // $request->getBodyParam('uddi_cliente');

        $error = new MessageResponse();
        $error->responseCode = -1;

        if(empty($request->getBodyParam('depdrop_all_params')['search-cliente'])){
            $error->message = 'Body de la petición faltante';

            return $error;
        }

        //verifica que los parámetros solicitados se encuentren
        $uddi_cliente = $request->getBodyParam('depdrop_all_params')['search-cliente'];

        $cliente = EntClientes::find()->where(['uddi'=>$uddi_cliente])->one();

        if(!$cliente){
            $error->message = 'El cliente no se encontro';
            
            return $error;
        }

        $destinos = WrkDestino::find()->where(['id_cliente'=>$cliente->id_cliente])->andWhere(['!=', 'txt_nombre_ubicacion', ''])->all();
        
        $selected = '';
        $out = [];
        foreach ($destinos as $i => $destino) {
            $out[] = [
                'id' => $destino->id_destino, 
                'name' => $destino->txt_nombre_ubicacion];
            if ($i == 0) {
                $selected = null;
            }
        }
        // Shows how you can preselect a value
        return ['output' => $out, 'selected'=>$selected];
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

    public function actionGuardarOrigen(){
        $request = Yii::$app->request;

        $error = new MessageResponse();
        $error->responseCode = -1;

        if(empty($request->getBodyParam('uddi_cliente'))){
            $error->message = 'Falta seleccionar un cliente';

            return $error;
        }

        //verifica que los parámetros solicitados se encuentren
        $uddi_cliente = $request->getBodyParam('uddi_cliente');

        $datosOrigen = new WrkOrigen();
        if($datosOrigen->load($request->bodyParams)){
            $cliente = EntClientes::find()->where(['uddi'=>$uddi_cliente])->one();
            if(!$cliente){
                $error->message = 'El cliente no se encuentra registrado';

                return $error;
            }

            $datosOrigen->id_cliente = $cliente->id_cliente;
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

        if(empty($request->getBodyParam('uddi_cliente'))){
            $error->message = 'Falta seleccionar un cliente';

            return $error;
        }

        //verifica que los parámetros solicitados se encuentren
        $uddi_cliente = $request->getBodyParam('uddi_cliente');

        $datosDestino = new WrkDestino();
        if($datosDestino->load($request->bodyParams)){
            $cliente = EntClientes::find()->where(['uddi'=>$uddi_cliente])->one();
            if(!$cliente){
                $error->message = 'El cliente no se encuentra registrado';

                return $error;
            }

            $datosDestino->id_cliente = $cliente->id_cliente;
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

    public function actionGetCotizacion(){
        $request = Yii::$app->request;
        $data = [];

        $error = new MessageResponse();
        $error->responseCode = -1;

        if(empty($request->getBodyParam('from'))){
            $error->message = 'Body de la petición faltante1';

            return $error;
        }
        if(empty($request->getBodyParam('pais_origen'))){
            $error->message = 'Body de la petición faltante2';

            return $error;
        }
        if(empty($request->getBodyParam('pais_destino'))){
            $error->message = 'Body de la petición faltante3';

            return $error;
        }
        if(empty($request->getBodyParam('to'))){
            $error->message = 'Body de la petición faltante4';

            return $error;
        }

        $paisOrigen = CatPaises::find()->where(["uddi"=>$request->getBodyParam('pais_origen')])->one();
        $paisDestino = CatPaises::find()->where(["uddi"=>$request->getBodyParam('pais_destino')])->one();

        $serviciosMensajeria = new Fedex();
        $from = $request->getBodyParam('from');
        $to = $request->getBodyParam('to');
        
        $fedex = $serviciosMensajeria->getFedex($from, $to, $paisOrigen->txt_codigo, $paisDestino->txt_codigo);
        $data = array_merge($data, $fedex);

        $estafeta = Estafeta::datosEstafeta($from, $to);
        
        $data = array_merge($data, $estafeta);
        EnviosObject::setSessionEnvios($data);

        return $data;
    }

    public function actionCrearCliente(){
        $request = Yii::$app->request;
        
        $error = new MessageResponse();
        $error->responseCode = -1;

        $model = new EntClientes();
        $model->scenario = "registerInput";

        if($model->load($request->bodyParams)){
            $model->uddi = Utils::generateToken();

            if(!$model->save()){
                
                return $model;
            }

            return $model;
        }else{
            $error->message = 'No hay datos para guardar al cliente';

            return $error;
        }
    }

    public function actionDatosFacturacion(){
        $request = Yii::$app->request;//print_r($request->bodyParams);exit;

        $error = new MessageResponse();
        $error->responseCode = -1;

        $model = new EntFacturacion();

        if($model->load($request->bodyParams)){
            if(!$model->save()){

                return $model;
            }

            return $model;
        }else{
            $error->message = 'No hay datos para guardar la factura';

            return $error;
        }
    }
}