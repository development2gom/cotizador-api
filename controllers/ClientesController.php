<?php
namespace app\controllers;

use Yii;
use yii\rest\Controller;
use app\models\WrkEnvios;
use yii\web\HttpException;
use app\models\EntClientes;
use app\models\EntClientesSearch;

class ClientesController extends Controller{

    public $enableCsrfValidation = false;
    public $serializer = [
        'class' => 'app\components\SerializerExtends',
        'collectionEnvelope' => 'items',
    ];

    public function actionIndex(){
        $request = Yii::$app->request;
        $params = $request->bodyParams;
        
        $clientesBuscar = new EntClientesSearch();
        $data = $clientesBuscar->buscarClientes($params);
        return $data;
    }

   

}