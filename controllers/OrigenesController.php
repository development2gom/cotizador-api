<?php
namespace app\controllers;

use Yii;
use yii\rest\Controller;
use app\models\WrkEnvios;
use yii\web\HttpException;
use app\models\WrkOrigen;
use app\models\EntClientes;
use app\models\WrkOrigenSearch;


class OrigenesController extends Controller{

    public $enableCsrfValidation = false;
    public $serializer = [
        'class' => 'app\components\SerializerExtends',
        'collectionEnvelope' => 'items',
    ];

    public function actionOrigenesCliente(){
        $request = Yii::$app->request;
        $params = $request->bodyParams;
        $uddiCliente = $request->getBodyParam("uddi_cliente");

        $cliente = EntClientes::getClienteByUddi($uddiCliente);

        $destinoBuscar = new WrkOrigenSearch();
        $destinoBuscar->id_cliente = $cliente->id_cliente;
        $data = $destinoBuscar->buscarDirecciones($params);
        return $data;
    }

    public function actionGetOrigen(){
        $request = Yii::$app->request;
        $params = $request->bodyParams;
        $uddi = $request->getBodyParam("uddi_origen");
        return WrkOrigen::getOrigen($uddi);
    }

}