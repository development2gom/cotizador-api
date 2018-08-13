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
}