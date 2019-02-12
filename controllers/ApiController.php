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
use app\models\EntOrdeness;
use app\models\Pagos;
use app\models\RelEnvioExtras;
use app\config\ServicesApiConfig;
use yii\web\HttpException;
use app\_dgomFactura\Entity\FacturaRequest;

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
            'get-buscar-facturacion' => ['POST'],
            'get-pagos-usuarios' => ['GET', 'HEAD'],
            'download-pdf' => ['GET', 'HEAD'],
            'buscar-ultima-factura' => ['POST'],
            'guardar-factura' => ['POST'],

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

        $serviciosMensajeria = new Fedex();
        $fedex = $serviciosMensajeria->getFedex($from, $to, $codeFrom, $codeTo, $paquetes);
        $data = array_merge($data, $fedex);

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
        if(empty($request->getBodyParam('tipo'))){
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
        if(empty($request->getBodyParam('codPaisOr'))){
            $error->message = 'Body de la petición faltante8';

            return $error;
        }
        if(empty($request->getBodyParam('codPaisDes'))){
            $error->message = 'Body de la petición faltante9';

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
                $pais1 = CatPaises::find()->where(['txt_codigo'=>$request->getBodyParam('codPaisOr')])->one();
                $pais2 = CatPaises::find()->where(['txt_codigo'=>$request->getBodyParam('codPaisDes')])->one();

                $origen->txt_pais = $pais1->uddi;
                $origen->id_cliente = $cliente->id_cliente;
                $destino->id_cliente = $cliente->id_cliente;
                $destino->txt_pais = $pais1->uddi;

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
                    $envio->txt_tipo = $request->getBodyParam('tipo');

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
        $response->data = $envio;

        return $response;
    }


    /**
     * Funcion 
     */
    public function actionGenerarFactura2(){
        $request = Yii::$app->request;
        $uddiEnvio = $request->getBodyParam('uddi_envio');

        $envio = WrkEnvios::getEnvio($uddiEnvio);

        if($envio->b_facturado == 1){
            return true;
        }

        //Si no tiene folio de pago ni txt_folio, es que no se ha pagado
        //por lo cual no se puede facturar
        if(!$envio->pago && !$envio->txt_folio){
            throw new HttpException(500,'El envío no tiene asociado un pago');
        }

        // Monto del envio ---------------
        $montoTotal = $envio->num_costo_envio;
        //Busca los extras
        $extras = RelEnvioExtras::find()->where(['id_envio'=>$envio->id_envio])->sum('num_precio');
        $montoTotal += $extras;
        
        //Pone los datos de la factura
        $facturaRequest = new FacturaRequest();

        $facturaRequest->useSandBox        = true;
        $facturaRequest->transaccion       = $envio->uddi;
        $facturaRequest->formaPago         = "04";
        $facturaRequest->condicionesPago   = 'Contado';
        $facturaRequest->subTotal          = $montoTotal;
        $facturaRequest->total             = $montoTotal;
        $facturaRequest->rfcReceptor       = $envio->cliente->txt_rfc;
        $facturaRequest->nombreReceptor    = $envio->cliente->nombreCompleto;
        $facturaRequest->claveProdServicio = '84101600';
        $facturaRequest->cantidad          = '1';
        $facturaRequest->claveUnidad       = 'C62'; 
        $facturaRequest->unidad            = 'Uno';
        $facturaRequest->descripcion       = 'Envio de paqueteria';
        $facturaRequest->valorUnitario     = $montoTotal;
        $facturaRequest->importe           = $montoTotal;
        $facturaRequest->usoCFDIReceptor   = 'G03';

        $factura = new Pagos();
        $facturaGenerar = $factura->generarFactura2($facturaRequest);
        
        if(isset($facturaGenerar->pdf) && isset($facturaGenerar->xml)){
            
            $this->validarDirectorio("facturas/".$envio->cliente->uddi);
            $this->validarDirectorio("facturas/".$envio->cliente->uddi."/".$envio->uddi);

            $pdf = base64_decode($facturaGenerar->pdf);

            $xml = base64_decode($facturaGenerar->xml);

            file_put_contents("facturas/".$envio->cliente->uddi."/".$envio->uddi."/factura.pdf", $pdf);
            file_put_contents("facturas/".$envio->cliente->uddi."/".$envio->uddi."/factura.xml", $xml);

            $envio->b_facturado = 1;
            $envio->save();
        }        

        return true; 
    }


    /**
     * Funcion 
     */
    public function actionGenerarFacturaConsolidado(){
        $messageResponse = new MessageResponse();

        $request = Yii::$app->request;
        
        $fch = $request->getBodyParam('fch');
        $tipo = $request->getBodyParam('tipo');

        if($fch == null){
            $messageResponse->responseCode = -3;
            $messageResponse->message = "La fecha es nula";
            return $messageResponse;
        }

        if($tipo == null){
            $messageResponse->responseCode = -4;
            $messageResponse->message = "El tipo es nulo";
            return $messageResponse;
        }

        $nombreServicio = 'Envio de paqueteria ';
        switch($tipo){
            case "NAL":
            $nombreServicio .= "Nacional";
            break;
            case "INT_EXT":
            $nombreServicio .= "Internacional exportación";
            break;
            case "INT_IMP":
            $nombreServicio .= "Internacional importación";
            break;
            case "INT_INT":
            $nombreServicio .= "Internacional internacional";
            break;
            default:
            $messageResponse->responseCode = -1;
            $messageResponse->message = "El tipo de servicio es inválido";
            return $messageResponse;
        }

       

        $envio =  WrkEnvios::find()
            ->joinWith('proveedor')
            ->where(['IS NOT', 'txt_tracking_number', null])
            ->andWhere(['IS', 'txt_identificador_proveedor' , null])
            ->andWhere(['b_facturado'=>0,'date(fch_creacion)'=>$fch])
            ->all();



        $enviosList = [];
        $montoEnvios = 0;
        $montoIva = 0;
        $montoExtras = 0;
        //Selecciona unicamente los envios del tipo
        //Si no tiene folio de pago ni txt_folio, es que no se ha pagado
        foreach($envio as $item){
            if($item->tipoEnvio == $tipo && $item->b_facturado == 0){
                $enviosList[] = $item;
                $montoEnvios  += $item->num_costo_envio;
                $montoIva     += $item->num_impuesto;

                //Busca los extras
                $montoExtras = RelEnvioExtras::find()->where(['id_envio'=>$item->id_envio])->sum('num_precio');
            }
        }

        if(count($enviosList) == 0){
            $messageResponse->responseCode = -5;
            $messageResponse->message = "No hay envíos para facturar";
            return $messageResponse;
        }
    

        // ---------------- Monto del envio ---------------------
        $montoTotal = $montoEnvios + $montoIva + $montoExtras;

        $RFC_360     = "dgo130923fy0";
        $NOMBRE_360  = "Envíos 360 de México, SA de CV";
        $uuidFactura = uniqid("CONS_");
   
        
        //Pone los datos de la factura
        $facturaRequest = new FacturaRequest();

        $facturaRequest->useSandBox        = true;
        $facturaRequest->transaccion       = $uuidFactura;
        $facturaRequest->formaPago         = "04";
        $facturaRequest->condicionesPago   = 'Contado';
        $facturaRequest->subTotal          = $montoTotal;
        $facturaRequest->total             = $montoTotal;
        $facturaRequest->rfcReceptor       = $RFC_360;
        $facturaRequest->nombreReceptor    = $NOMBRE_360;
        $facturaRequest->claveProdServicio = '84101600';
        $facturaRequest->cantidad          = count($enviosList);
        $facturaRequest->claveUnidad       = 'C62'; 
        $facturaRequest->unidad            = 'Uno';
        $facturaRequest->descripcion       = $nombreServicio;
        $facturaRequest->valorUnitario     = $montoTotal;
        $facturaRequest->importe           = $montoTotal;
        $facturaRequest->usoCFDIReceptor   = 'G03';

        $factura = new Pagos();
        $facturaGenerar = $factura->generarFactura2($facturaRequest);

        if($facturaGenerar->error){
            $messageResponse->responseCode = -6;
            $messageResponse->message = $facturaGenerar->message;
            return $messageResponse; 
        }
        
        if(isset($facturaGenerar->pdf) && isset($facturaGenerar->xml)){
        
            foreach($enviosList as $item){
                $this->validarDirectorio("facturas/envios_360");
                $this->validarDirectorio("facturas/envios_360" . "/" . $item->uddi);

                $pdf = base64_decode($facturaGenerar->pdf);
                $xml = base64_decode($facturaGenerar->xml);

                file_put_contents("facturas/envios_360/" . $item->uddi . "/factura.pdf", $pdf);
                file_put_contents("facturas/envios_360/" . $item->uddi . "/factura.xml", $xml);

                $item->b_facturado   = 1;
                $item->b_factura_360 = $uuidFactura;
                $item->save();
            }

            $messageResponse->responseCode = 1;
            $messageResponse->message = "Facturado correctamente, factura: " . $uuidFactura;
            return $messageResponse; 
        }  else{
            $messageResponse->responseCode = -2;
            $messageResponse->message = "Error al genrar las facturas";
            return $messageResponse; 
        }      

        
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

    public function actionGetPagosUsuarios($uddi = null){
        $error = new MessageResponse();
        $error->responseCode = -1;

        if($uddi){
            $cliente = EntClientes::find()->where(['uddi'=>$uddi])->one();
            if($cliente){
                $pagos = EntPagosRecibidos::find()->where(['id_cliente'=>$cliente->id_cliente, 'b_facturado'=>0])->all();

                if($pagos){
                    $response = new ResponseServices();
                    $response->status = "success";
                    $response->message = "Se han realizado pagos";
                    $response->result = $pagos;

                    return $response;
                }else{
                    $response = new ResponseServices();
                    $response->status = "sinDatos";
                    $response->message = "No se han realizado pagos";

                    return $response;
                }
            }
        }

        return $uddi;
    }

    public function actionDownloadPdf($uddi_envio){
        $envio = WrkEnvios::find()->where(['uddi'=>$uddi_envio])->one();
        $origen = $envio->origen;
        $destino = $envio->destino;
        $cliente = $envio->cliente;

        $paisOrigen = CatPaises::find()->where(['uddi'=>$origen->txt_pais])->one();
        $paisDestino = CatPaises::find()->where(['uddi'=>$destino->txt_pais])->one();
        //print_r($envio);exit;

        $curl = curl_init();

        $params["service_type"]= $envio->txt_tipo;
        $params["service_packing"] = "YOUR_PACKAGING";

        $params["shiper"]["postal_code"] = $origen->num_codigo_postal;
        $params["shiper"]["country_code"] = $paisOrigen->txt_codigo;
        $params["shiper"]["city"] = $origen->txt_estado;
        $params["shiper"]["state_code"] = "EM";
        $params["shiper"]["person_name"] = $origen->txt_nombre;
        $params["shiper"]["address_line"] = $origen->txt_calle . " " . $origen->txt_municipio . " " . $origen->txt_estado;
        $params["shiper"]["phone_number"] = $origen->num_telefono_movil;
        $params["shiper"]["company_name"] = "Envios360";

        $params["recipient"]["postal_code"] = $destino->num_codigo_postal;;
        $params["recipient"]["country_code"] = $paisDestino->txt_codigo;
        $params["recipient"]["city"] = $destino->txt_estado;;
        $params["recipient"]["state_code"] = "EM";
        $params["recipient"]["person_name"] = $destino->txt_nombre;
        $params["recipient"]["address_line"] = $destino->txt_calle . " " . $destino->txt_municipio . " " . $destino->txt_estado;;
        $params["recipient"]["phone_number"] = $destino->num_telefono_movil;
        $params["recipient"]["company_name"] = "Envios360";

        $params["package"]["peso_kg"] = 2;
        $params["package"]["largo_cm"] = 20;
        $params["package"]["ancho_cm"] = 20;
        $params["package"]["alto_cm"] = 10;
        

        curl_setopt_array($curl, array(

            CURLOPT_URL => ServicesApiConfig::URL_API_LABEL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($params),
            CURLOPT_HTTPHEADER => array(
                "Cache-Control: no-cache",
                "Content-Type: application/json"
            ),
        ));

        $response = curl_exec($curl);//print_r($response);exit;
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            //echo "cURL Error #:" . $err;
            return false;
        } else {
            $response = json_decode($response);

            $file = base64_encode($response->CompletedShipmentDetail->CompletedPackageDetails->Label->Parts->Image);

            $decoded = base64_decode($file);//echo $decoded;exit;
            $file2 = 'label.pdf';
            $fp = fopen($file2, "w+");
            file_put_contents($file2, $decoded);

            if (file_exists($file2)) {
                header('Content-Description: File Transfer');
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="' . basename($file2) . '"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                //header('Content-Length: ' . filesize($file2));
                readfile($file2);
                exit;
            }else{
                echo "No existe el archivo";
            }
        }
    }

    public function actionBuscarUltimaFactura(){
        $request = Yii::$app->request;

        $error = new MessageResponse();
        $error->responseCode = -1;

        if(empty($request->getBodyParam('uddi_cliente'))){
            $error->message = 'Body de la petición faltante';

            return $error;
        }

        $uddiCliente = $request->getBodyParam('uddi_cliente');
        // $uddiCliente = 'tDgV69e9ORcJwOUDYJ1zWv0PiGX2';//$request->getBodyParam('uddi_cliente');
        $cliente = EntClientes::getClienteByUddi($uddiCliente);
        $facturas = EntFacturacion::find()->where(['id_cliente'=>$cliente->id_cliente])->orderBy('id_factura DESC')->all();

        foreach($facturas as $factura){
            $response = new ResponseServices(); 

            $response->status = 'success';
            $response->message = 'Ultima factura';
            $response->result = $factura->uddi;
            
            return $response;
        }
    }

    public function actionGuardarFactura(){
        $request = Yii::$app->request;
        $uddiCliente = $request->getBodyParam('uddi_cliente');
        $rfc = $request->getBodyParam('rfc');
        $cliente = EntClientes::getClienteByUddi($uddiCliente);

        $cliente->txt_rfc = $rfc;
        $cliente->save(false);
       
        return $cliente;
    }
}