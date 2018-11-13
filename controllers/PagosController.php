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


        $cliente = EntClientes::getClienteByUddi($uddiCliente);
        $envio = WrkEnvios::getEnvio($uddiEnvio);

        $openPay = new OpenPay($cliente->id_cliente,$cliente->nombreCompleto, $cliente->txt_correo, "Ticket para envio", Utils::generateToken("tic_"), $envio->num_costo_envio, $envio->num_subtotal, $envio->id_envio);

        $respuesta = $openPay->generarTicket();

        return $respuesta;
    }

    public function actionPagarTarjeta(){
        $request = Yii::$app->request;
        $params = $request->bodyParams;
        $uddiCliente = $request->getBodyParam("uddi_cliente");
        $uddiEnvio = $request->getBodyParam("uddi_envio");
        $tokenid = $request->getBodyParam("token_id");
        $deviceIdHiddenFieldName = $request->getBodyParam("deviceIdHiddenFieldName");

        $cliente = EntClientes::getClienteByUddi($uddiCliente);
        $envio = WrkEnvios::getEnvio($uddiEnvio);
      
    
        $openPay = new OpenPay($cliente->id_cliente,$cliente->nombreCompleto, $cliente->txt_correo, "Pago con tarjeta para envio", Utils::generateToken("oc_"), $envio->num_costo_envio, $envio->num_subtotal, $envio->id_envio);
        $respuesta = $openPay->cargoTarjeta($tokenid, $deviceIdHiddenFieldName, $envio);
    
        return $respuesta;
           
        
    }

    public function actionWebHook(){

    }

}