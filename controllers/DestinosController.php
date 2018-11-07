<?php
namespace app\controllers;

use Yii;
use yii\rest\Controller;
use app\models\WrkEnvios;
use yii\web\HttpException;

use app\models\WrkDestino;
use app\models\EntClientes;
use app\models\WrkDestinoSearch;


class DestinosController extends Controller{

    public $enableCsrfValidation = false;
    public $serializer = [
        'class' => 'app\components\SerializerExtends',
        'collectionEnvelope' => 'items',
    ];

    public function actionDestinosCliente(){
        $request = Yii::$app->request;
        $params = $request->bodyParams;
        $uddiCliente = $request->getBodyParam("uddi_cliente");

        $cliente = EntClientes::getClienteByUddi($uddiCliente);

        $destinoBuscar = new WrkDestinoSearch();
        $destinoBuscar->id_cliente = $cliente->id_cliente;
        $data = $destinoBuscar->buscarDirecciones($params);
        return $data;
    }

    public function actionGetDestino(){
        $request = Yii::$app->request;
        $params = $request->bodyParams;
        $uddi = $request->getBodyParam("uddi_destino");
        return WrkDestino::getDestino($uddi);
    }

   

}