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
use app\models\WrkEnvios;
use app\models\ResponseServices;
use app\models\OpenPay;
use app\models\EntOrdenesCompras;
use app\models\Pagos;

/**
 * ConCategoiriesController implements the CRUD actions for ConCategoiries model.
 */
class ApiController extends Controller
{   
    const FEDEX = "FEDEX";
    const ESTAFETA = "Estafeta";

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
            'guardar-facturacion' => ['POST'],
            'pagos' => ['POST'],
            'confirmar-pago' => ['GET', 'HEAD'],
            'generar-factura' => ['POST'],
            'descargar-factura-pdf' => ['GET', 'HEAD'],
            'descargar-factura-xml' => ['GET', 'HEAD'],
            'get-buscar-origen' => ['POST'],
            'get-buscar-destino' => ['POST'],
            'get-buscar-facturacion' => ['POST']
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

        if(empty($request->getBodyParam('ObjectCotizar')['cpFrom'])){
            $error->message = 'Body de la petición faltante1';

            return $error;
        }
        if(empty($request->getBodyParam('ObjectCotizar')['countryCodeFrom'])){
            $error->message = 'Body de la petición faltante2';

            return $error;
        }
        if(empty($request->getBodyParam('ObjectCotizar')['countryCodeTo'])){
            $error->message = 'Body de la petición faltante3';

            return $error;
        }
        if(empty($request->getBodyParam('ObjectCotizar')['cpTo'])){
            $error->message = 'Body de la petición faltante4';

            return $error;
        }

        $from = $request->getBodyParam('ObjectCotizar')['cpFrom'];
        $to = $request->getBodyParam('ObjectCotizar')['cpTo'];
        $codeFrom = $request->getBodyParam('ObjectCotizar')['countryCodeFrom'];
        $codeTo = $request->getBodyParam('ObjectCotizar')['countryCodeTo'];
        $paquetes = $request->getBodyParam('ObjectCotizar')['paquetes'];

        // $serviciosMensajeria = new Fedex();
        // $fedex = $serviciosMensajeria->getFedex($from, $to, $codeFrom, $codeTo);
        // $data = array_merge($data, $fedex);

        $estafeta = Estafeta::datosEstafeta($from, $to, $paquetes);
        $data = array_merge($data, $estafeta);
        //EnviosObject::setSessionEnvios($data);

        return $data;
    }

    public function actionCrearCliente(){
        $request = Yii::$app->request;
        
        $error = new MessageResponse();
        $error->responseCode = -1;

        if(empty($request->getBodyParam('email'))){
            $error->message = 'Body de la petición faltante1';

            return $error;
        }
        if(empty($request->getBodyParam('nombre'))){
            $error->message = 'Body de la petición faltante2';

            return $error;
        }
        if(empty($request->getBodyParam('uddi'))){
            $error->message = 'Body de la petición faltante3';

            return $error;
        }

        $nombre = $request->getBodyParam('nombre');
        $email = $request->getBodyParam('email');
        $uddi = $request->getBodyParam('uddi');
        $apellido = null;

        if(!empty($request->getBodyParam('apellido'))){
            $apellido = $request->getBodyParam('apellido');
        }

        $cliente = EntClientes::find()->where(["uddi" => $uddi])->one();
        if ($cliente) {

            return $cliente;    
        }else{
            $cliente = new EntClientes();
            $cliente->txt_nombre = $nombre;
            $cliente->txt_correo = $email;
            $cliente->uddi = $uddi;
            $cliente->txt_apellido_paterno = $apellido;
            $cliente->password = "\(^.^)/";

            if($cliente->save()){
                
                return $cliente;
            }
        }
    }

    public function actionGuardarFacturacion(){
        $request = Yii::$app->request;

        $error = new MessageResponse();
        $error->responseCode = -1;

        if(empty($request->getBodyParam('idCliente'))){
            $error->message = "Faltan datos";

            return $error;
        }

        $uddi_cliente = $request->getBodyParam('idCliente');
        $cliente = EntClientes::find()->where(['uddi'=>$uddi_cliente])->one();

        if($cliente){
            $model = null;
            if(empty($request->getBodyParam('id'))){
                $model = new EntFacturacion();
            }else{
                $id_factura = $request->getBodyParam('id');
                $model = EntFacturacion::find()->where(['id_factura'=>$id_factura])->one();
            }
            
            if($model){
                parse_str( $request->getBodyParam('data'), $new_data);
                if($model->load($new_data)){
                    $model->id_cliente = $cliente->id_cliente;
                    if(!$model->save()){

                        return $model;
                    }

                    $response = new ResponseServices();
                    $response->status = "success";
                    $response->message = "Envio guardado";
                    $response->result = $model;

                    return $response;
                }else{
                    $error->message = 'No hay datos para guardar la factura';
                }
            }else{
                $error->message = "No existe el registro";
            }
        }else{
            $error->message = "No existe el cliente";
        }

        return $error;
    }

    public function actionPagos(){
        $request = Yii::$app->request;
        //print_r($request->bodyParams);exit;

        $error = new MessageResponse();
        $error->responseCode = -1;

        if(empty($request->getBodyParam('id'))){
            $error->message = 'Body de la petición faltante1';

            return $error;
        }
        if(empty($request->getBodyParam('idDestino'))){
            $error->message = 'Body de la petición faltante2';

            return $error;
        }
        if(empty($request->getBodyParam('idOrigen'))){
            $error->message = 'Body de la petición faltante3';

            return $error;
        }
        if(empty($request->getBodyParam('mensajeria'))){
            $error->message = 'Body de la petición faltante4';

            return $error;
        }
        if(empty($request->getBodyParam('cliente'))){
            $error->message = 'Body de la petición faltante5';

            return $error;
        }
        if(empty($request->getBodyParam('original'))){
            $error->message = 'Body de la petición faltante5';

            return $error;
        }
        if(empty($request->getBodyParam('cpOrigen'))){
            $error->message = 'Body de la petición faltante6';

            return $error;
        }
        if(empty($request->getBodyParam('cpDestino'))){
            $error->message = 'Body de la petición faltante7';

            return $error;
        }

        $cliente = EntClientes::find()->where(['uddi'=>$request->getBodyParam('id')])->one();
        $origen = new WrkOrigen();
        $destino = new WrkDestino();

        $params = $request->bodyParams;
        parse_str($params['idOrigen'],$new_data);
        parse_str($params['idDestino'],$new_data2);

        $transaction = Yii::$app->db->beginTransaction();
        try{
            if($origen->load($new_data) && $destino->load($new_data2)){
                $origen->id_cliente = $cliente->id_cliente;
                $destino->id_cliente = $cliente->id_cliente;

                if($origen->save() && $destino->save()){
                    $envio = new WrkEnvios();
                    $envio->id_cliente = $cliente->id_cliente;
                    $envio->id_destino = $destino->id_destino;
                    $envio->id_origen = $origen->id_origen;
                    $envio->id_proveedor = $this->getProveedor($request->getBodyParam('mensajeria'));
                    $envio->uddi = Utils::generateToken("env_");
                    $envio->num_cp_origen = $request->getBodyParam('cpOrigen');
                    $envio->num_cp_destino = $request->getBodyParam('cpDestino');
                    $envio->num_costo_envio = $request->getBodyParam('cliente');
                    $envio->num_subtotal = $request->getBodyParam('original');

                    if(!$envio->save()){
                        $transaction->rollBack();
                    }

                    $ordenCompra = new EntOrdenesCompras();
                    $ordenCompra->id_cliente = $cliente->id_cliente;
                    $ordenCompra->txt_descripcion = "Pago es sucursal";
                    $ordenCompra->txt_order_number = Utils::generateToken("oc_");
                    $ordenCompra->b_pagado = 1;

                    if($ordenCompra->b_pagado){
                        $ordenCompra->fch_pago = Calendario::getFechaActual();
                    }

                    $ordenCompra->fch_creacion = Calendario::getFechaActual();
                    $ordenCompra->num_total = $envio->num_costo_envio;
                    $ordenCompra->num_subtotal = $envio->num_subtotal;

                    if(!$ordenCompra->save()){
                        $transaction->rollBack();
                        $error->message = "No se guardo la orden de compra";

                        return $error;
                    }

                    $pagoRecibido = new EntPagosRecibidos();
                    $pagoRecibido->id_cliente = $cliente->id_cliente;
                    $pagoRecibido->id_orden_compra = $ordenCompra->id_orden_compra;
                    $pagoRecibido->txt_monto_pago = (string)$ordenCompra->num_total;
                    $pagoRecibido->fch_pago = $ordenCompra->fch_pago;

                    $pagoRecibido->txt_transaccion_local = "Transaccion local";
                    $pagoRecibido->txt_notas = "Pago recibido";
                    $pagoRecibido->txt_estatus = "Pago recibido";
                    $pagoRecibido->txt_transaccion = Utils::generateToken("tr_");

                    if(!$pagoRecibido->save()){
                        $transaction->rollBack();
                        $error->message = "No se guardo el recibo de pago";

                        return $error;
                    }

                    $transaction->commit();

                    $response = new ResponseServices();
                    $response->status = "success";
                    $response->message = "Envio guardado";
                    $response->result = $envio;

                    return $response;
                }else{
                    print_r($origen->errors);
                    print_r($destino->errors);
                    $transaction->rollBack();
                }
            }echo "fuera";exit;
        }catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }

        return $error;
    }

    public function getProveedor($proveedor)
    {
        if ($proveedor == self::FEDEX) {
            $proveedor = 1;
        } else if ($proveedor == self::ESTAFETA) {
            $proveedor = 2;
        }

        return $proveedor;
    }

    public function actionConfirmarPago($token = null)
    {
        $error = new MessageResponse();
        $error->responseCode = -1;

        $envio = WrkEnvios::find()->where(["uddi" => $token])->one();
        if (!$envio) {
            $error->message = "No se encontró la orden de envio";

            return $error;
        }
        $cliente = $envio->cliente;

        $ordenCompra = new EntOrdenesCompras();
        $ordenCompra->id_cliente = $cliente->id_cliente;
        $ordenCompra->txt_descripcion = "Pago es sucursal";
        $ordenCompra->txt_order_number = Utils::generateToken("oc_");
        $ordenCompra->b_pagado = 1;

        if($ordenCompra->b_pagado){
            $ordenCompra->fch_pago = Calendario::getFechaActual();
        }

        $ordenCompra->fch_creacion = Calendario::getFechaActual();
        $ordenCompra->num_total = $envio->num_costo_envio;
        $ordenCompra->num_subtotal = $envio->num_subtotal;

        if(!$ordenCompra->save()){
            $error->message = "No se guardo la orden de compra";

            return $error;
        }

        $pagoRecibido = new EntPagosRecibidos();
        $pagoRecibido->id_cliente = $cliente->id_cliente;
        $pagoRecibido->id_orden_compra = $ordenCompra->id_orden_compra;
        $pagoRecibido->txt_monto_pago = (string)$ordenCompra->num_total;
        $pagoRecibido->fch_pago = $ordenCompra->fch_pago;

        $pagoRecibido->txt_transaccion_local = "Transaccion local";
        $pagoRecibido->txt_notas = "Pago recibido";
        $pagoRecibido->txt_estatus = "Pago recibido";
        $pagoRecibido->txt_transaccion = Utils::generateToken("tr_");

        if(!$pagoRecibido->save()){
            $error->message = "No se guardo el recibo de pago";

            return $error;
        }

        $response = new ResponseServices();
        $response->status = "success";
        $response->message = "Orden de compra y pago generado correctamente";
        $response->data = $pagoRecibido;

        return $response;
    }

    public function actionGenerarFactura(){
        $request = Yii::$app->request;//print_r($request->getBodyParam('transacciones'));exit;

		$error = new MessageResponse();
        $error->responseCode = -1;
		$botones = "";

        if(empty($request->getBodyParam('transacciones'))){
            $error->message = 'Body de la petición faltante';

            return $error;
        }

        // Datos de facturación
        if(empty($request->getBodyParam('id_cliente'))){
            $error->message = 'Body de la petición faltante5';

            return $error;
        }

        $botonesArray = [];
        $i = 0;
        $transaccionesArray = explode(',', $request->getBodyParam('transacciones'));
        foreach($transaccionesArray as $transaccion){
            $i++;
            $ordenPagada = EntPagosRecibidos::find()->where(["txt_transaccion"=>$transaccion])->one();
            $botones = '<a class="btn donaciones-facturar-pdf js-descargar-pdf" target="_blank" href='.Url::base().'/api/descargar-factura-pdf?token='.$transaccion.'>PDF</a> 
            <a href='.Url::base().'/api/descargar-factura-xml?token='.$transaccion.' target="_blank" class="btn donaciones-facturar-xml js-descargar-xml">XML</a>';

            if(!$ordenPagada){
                $botonesArray[$i] = null;continue;
            }

            if($ordenPagada->b_facturado){
                $botonesArray[$i] = $botones;continue;
            }

            $id_cliente = $request->getBodyParam('id_cliente');
            $id_factura = 0;
            if($request->getBodyParam('id_factura')){
                $id_factura = $request->getBodyParam('id_factura');
            }
            $cliente = EntClientes::find()->where(['id_cliente'=>$id_cliente])->one();

            $facturacion = EntFacturacion::find()->where(["id_factura"=>$id_factura, "id_cliente"=>$cliente->id_cliente])->one();
            if(!$facturacion){
                $facturacion = new EntFacturacion();
                $facturacion->id_cliente = $id_cliente;

                if($facturacion->load($request->bodyParams)){
                    if(!$facturacion->save()){
        
                        return $facturacion;
                    }
                }
            }
                
            $factura = new Pagos();
            $facturaGenerar = $factura->generarFactura($facturacion, $ordenPagada);
            
            if(isset($facturaGenerar->pdf) && isset($facturaGenerar->xml)){
                
                $this->validarDirectorio("facturas/".$cliente->uddi);
                $this->validarDirectorio("facturas/".$cliente->uddi."/".$transaccion);

                $pdf = base64_decode($facturaGenerar->pdf);

                $xml = base64_decode($facturaGenerar->xml);

                file_put_contents("facturas/".$cliente->uddi."/".$transaccion."/factura.pdf", $pdf);
                file_put_contents("facturas/".$cliente->uddi."/".$transaccion."/factura.xml", $xml);

                $ordenPagada->b_facturado = 1;
                $ordenPagada->save();
                
                $botonesArray[$i] = $botones;continue;
            }

            if(isset($facturaGenerar->error) && $facturaGenerar->error){
                $botonesArray[$i] = null;continue;
            }
        }

        $response = new ResponseServices();
        $response->status = "success";
        $response->message = "Factura generada correctamente";
        $response->data = $botonesArray;

        return $response;
    }
    
    public function validarDirectorio($path){
		if(!file_exists($path)){
			mkdir($path, 0777);
		}
    }
    
    public function actionDescargarFacturaPdf($token=null){
        $ordenPagada = EntPagosRecibidos::find()->where(["txt_transaccion"=>$token])->one();
        if(!$ordenPagada){
            return;
        }
        $usuario = $ordenPagada->cliente;

		$file = "facturas/".$usuario->uddi."/".$ordenPagada->txt_transaccion."/factura.pdf";

		if (file_exists($file)) {
			
			return Yii::$app->response->sendFile($file);
		}
	}

	public function actionDescargarFacturaXml($token=null){
        $ordenPagada = EntPagosRecibidos::find()->where(["txt_transaccion"=>$token])->one();
        if(!$ordenPagada){
            return;
        }
        $usuario = $ordenPagada->cliente;

		$file = "facturas/".$usuario->uddi."/".$ordenPagada->txt_transaccion."/factura.xml";

		if (file_exists($file)) {
			
			return Yii::$app->response->sendFile($file);
		}
    }
    
    public function actionGetBuscarOrigen(){
        $request = Yii::$app->request;

        $error = new MessageResponse();
        $error->responseCode = -1;

        if(empty($request->getBodyParam('id_envio'))){
            $error->message = 'Body de la petición faltante1';

            return $error;
        }

        $id_envio = $request->getBodyParam('id_envio');

        $response = new ResponseServices();
        $origen = WrkOrigen::find()-> Where(['id_origen'=>$id_envio])->one();

        if($origen){
            $response->message = 'Se encontro el resgistro';
            $response->status = 'success';
            $response->result = $origen;
        }
        else{
            $response->message='No se encontro nada';
        }   
       
        return $response;
    }

    public function actionGetBuscarDestino(){
        $request = Yii::$app->request;

        $error = new MessageResponse();
        $error->responseCode = -1;
        
        if(empty($request->getBodyParam('id_envio'))){
            $error->message = 'Body de la petición faltante1';

            return $error;
        }

        $id_envio = $request->getBodyParam('id_envio');

        $response = new ResponseServices();
        $destino = WrkDestino::find()-> Where(['id_destino'=>$id_envio])->one();

        if($destino){
            $response->message = 'Se encontro el resgistro';
            $response->status = 'success';
            $response->result = $destino;
        }
        else{
            $response->message='No se encontro nada';
        }   
       
        return $response;
    }

    public function actionDatosFacturacion(){
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

        $datos = EntFacturacion::find()->where(['id_cliente'=>$cliente->id_cliente, 'b_habilitado'=>1])->all();  
         
        $selected = '';
        $out = [];
        foreach ($datos as $i => $dato) {
            $out[] = [
                'id' => $dato->id_factura, 
                'name' => $dato->txt_rfc];
            if ($i == 0) {
                $selected = null;
            }
        }
        // Shows how you can preselect a value
        return ['output' => $out, 'selected'=>$selected];
    }

    public function actionGetBuscarFacturacion(){
        $request = Yii::$app->request;

        $error = new MessageResponse();
        $error->responseCode = -1;
        
        if(empty($request->getBodyParam('id_factura'))){
            $error->message = 'Body de la petición faltante1';

            return $error;
        }

        $id_factura = $request->getBodyParam('id_factura');

        $response = new ResponseServices();
        $datos = EntFacturacion::find()-> Where(['id_factura'=>$id_factura])->one();

        if($datos){
            $response->message = 'Se encontro el resgistro';
            $response->status = 'success';
            $response->result = $datos;
        }
        else{
            $response->message='No se encontro nada';
        }   
       
        return $response;
    }
}