<?php
namespace app\controllers;

use Yii;
use yii\rest\Controller;
use app\_360Utils\APIResponses\DataBarResponse;
use app\_360Utils\APIResponses\DataBar;
use app\models\WrkEnvios;


class ReportServiceController extends ServicesBaseController{



    public $enableCsrfValidation = false;
    public $layout = null;


  

    function actionIndex(){
        return "Hello";
    }

    function actionGetEnviosByCarrier($fchInicio = null, $fchFin = null ){

        if($fchInicio == null){
            $fchInicio = date("Y-m-d");
        }

        if($fchFin == null){
            $fchFin = date("Y-m-d");
        }


        $model = WrkEnvios::find()
        ->select(['COUNT(*) AS total','P.txt_nombre_proveedor as value'])
        ->joinWith('proveedor AS P')
        ->where(['between', 'fch_creacion', $fchInicio . ' 00:00:00', $fchFin . ' 23:59:59'])
        ->groupBy(['P.id_proveedor'])
        ->createCommand()
        ->queryAll();

        
        $colNames = [];
        $colData = [];
        foreach($model as $item){
            $colData[] = $item['total'];
            $colNames[] = $item['value'];
        }


        // --------- CREA LA RESPUESTA DEL REPORTE ------------
        $response            = new DataBarResponse();
        $response->title     = "Reporte de envios por carrier";
        $response->subtitle  = "envios";
        $response->unit      = "envíos";

        $response->xAxis     = "Carrier";
        $response->colNames  = $colNames;

        $response->startDate = $fchInicio;
        $response->endDate   = $fchFin;
        $response->operation = "Get Envíos by Carrier";
        $response->message   = "Data ok";

        $dataBar = new DataBar();
        $dataBar->name ="Carrier 1";
        $dataBar->values = $colData;
        
        $response->addDataBar($dataBar);

        return $response;
    }


    function actionGetMontosByCarrier($fchInicio = null, $fchFin = null ){

        if($fchInicio == null){
            $fchInicio = date("Y-m-d");
        }

        if($fchFin == null){
            $fchFin = date("Y-m-d");
        }


        $model = WrkEnvios::find()
        ->select([' SUM(num_costo_envio) AS total','P.txt_nombre_proveedor as value'])
        ->joinWith('proveedor AS P')
        ->where(['between', 'fch_creacion', $fchInicio . ' 00:00:00', $fchFin . ' 23:59:59'])
        ->groupBy(['P.id_proveedor'])
        ->createCommand()
        ->queryAll();

        
        $colNames = [];
        $colData = [];
        foreach($model as $item){
            $colData[] = $item['total'];
            $colNames[] = $item['value'];
        }


        // --------- CREA LA RESPUESTA DEL REPORTE ------------
        $response            = new DataBarResponse();
        $response->title     = "Reporte de montos por carrier";
        $response->subtitle  = "Montos";
        $response->unit      = "$";

        $response->xAxis     = "Carrier";
        $response->colNames  = $colNames;

        $response->startDate = $fchInicio;
        $response->endDate   = $fchFin;
        $response->operation = "Get montos by Carrier";
        $response->message   = "Data ok";

        $dataBar = new DataBar();
        $dataBar->name ="Carrier 1";
        $dataBar->values = $colData;
        
        $response->addDataBar($dataBar);

        return $response;
    }


    /// ---------------------------------------- SEGURIDAD DEL API -------------------------------------------------
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::className(),
            'cors' => [
                'Origin' => ['*'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => null,
                'Access-Control-Max-Age' => 86400,
            ],
        ];
        return $behaviors;
    }

    public function init()
    {
        parent::init();        
        date_default_timezone_set('America/Mexico_City');
      }

    
    public function beforeAction($action){
        // your custom code here, if you want the code to run before action filters,
        // which are triggered on the [[EVENT_BEFORE_ACTION]] event, e.g. PageCache or AccessControl

        if (!parent::beforeAction($action)) {
            return false;
        }

        $headers = Yii::$app->request->headers;

        $key = $headers->get('api-key');
        $secret = $headers->get('api-secret');

        if($key != $this::API_KEY || $secret != $this::API_SECRET){
            echo(\json_encode( $this->getErrorResponse($this::ERROR_API,'Invalid API or Secret') ));
            return false;
        }


        //Pone el header de cerrar la conexión
        //$headers = Yii::$app->response->headers;
        //$headers->set('Connection', 'close');

        //Si la accion solicitada se encientra en el arreglo, no pide el token de autenticacion
        $enabledActions = array("login","version-android","version-ios");
        if(in_array($action->id,$enabledActions)){
            return true;
        }

        
        
        //Valida el token de autenticacion
        
            
        // returns the Accept header value
        $auth = $headers->get('Authentication-Token');

        /*
        $wrkSesion = TmpSesionesOficilaes::find()->where(['txt_token'=>$auth])->one();
        

        //1 Si no existe la sesion lo manda a volar
        if(is_null($wrkSesion)){
            echo(\json_encode( $this->getErrorResponse($this::ERROR_SESION_USUARIO_INVALIDA,'Sesion del usuario invalida') ));
            return false;
        }
        
        
        
        //2 verifica el tiempo de la sesion, si han pasado más de X minutos
        if(\strtotime('now') - \strtotime($wrkSesion->fch_last_update) > $this::SESION_DURACION_MINUTOS ){
            echo(\json_encode( $this->getErrorResponse($this::ERROR_SESION_DURACION_MINUTOS,'Sesion del usuario caducada') ));
            return false;
        }

        $wrkSesion->fch_last_update = date('Y-m-d H:i:s', time());
        $wrkSesion->save();

        */
        return true; // or false to not run the action
    }

}
?>