<?php
namespace app\controllers;

use Yii;
use yii\rest\Controller;
use app\models\WrkEnvios;
use yii\web\HttpException;
use app\models\EntClientes;
use app\models\EntClientesSearch;
use app\models\WrkEnviosSearch;

class ClientesController extends Controller{

    public $enableCsrfValidation = false;
    public $serializer = [
        'class' => 'app\components\SerializerExtends',
        'collectionEnvelope' => 'items',
    ];

    public function actionIndex(){
        
        $params =  Yii::$app->request->queryParams;

        $clientesBuscar = new EntClientesSearch();
        $data = $clientesBuscar->buscarClientes($params);
        return $data;
    }

    public function actionGetCliente(){
        $request = Yii::$app->request;
        $params = $request->bodyParams;
        $uddi = $request->getBodyParam("uddi_cliente");

        return EntClientes::getClienteByUddi($uddi);
    }

    public function actionGetEnviosCliente(){
        $request = Yii::$app->request;
        $params = $request->bodyParams;
        $uddi = $request->getBodyParam("uddi_cliente");
        $tracking = $request->getBodyParam("txt_tracking_number");
        $cliente = EntClientes::getClienteByUddi($uddi);

        $enviosSearch = new WrkEnviosSearch();
        $enviosSearch->id_cliente = $cliente->id_cliente;
        //$enviosSearch->txt_tracking_number = $tracking;
        $envios = $enviosSearch->search([]);

        return $envios;
    }

    /**
     * Servicio para obtener un envio
     */
    public function actionGetUltimoEnvio(){
        $request = Yii::$app->request;
        $params = $request->bodyParams;
        $uddi = $request->getBodyParam("uddi_cliente");
        $cliente = EntClientes::getClienteByUddi($uddi);
        
        return $cliente->ultimoEnvio;
    }
   

}