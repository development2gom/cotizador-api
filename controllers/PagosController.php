<?php
namespace app\controllers;

use Yii;
use yii\rest\Controller;
use app\models\OpenPay;
use app\models\EntClientes;
use app\models\Utils;
use app\models\WrkEnvios;


class PagosController extends Controller{

    public $enableCsrfValidation = false;
    public $serializer = [
        'class' => 'app\components\SerializerExtends',
        'collectionEnvelope' => 'items',
    ];

    public function actionGenerarTicketOpenPay(){
        $request = Yii::$app->request;
        $params = $request->bodyParams;
        $uddiCliente = $request->getBodyParam("uddi_cliente");
        $uddiEnvio = $request->getBodyParam("uddi_envio");
        $total = $request->getBodyParam("total");
        $subTotal = $request->getBodyParam("sub_total");


        $cliente = EntClientes::getClienteByUddi($uddiCliente);
        $envio = WrkEnvios::getEnvio($uddiEnvio);

        $openPay = new OpenPay($cliente->id_cliente,$cliente->nombreCompleto, $cliente->txt_correo, "Ticket para envio", Utils::generateToken("tic_"), $total, $subTotal, $envio->id_envio);

        $respuesta = $openPay->generarTicket();

        return $respuesta;
    }

    public function actionPagarTarjeta(){

    }

    public function actionWebHook(){

    }

}