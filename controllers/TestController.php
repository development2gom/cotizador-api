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
use app\config\ServicesApiConfig;
use yii\web\HttpException;
use app\models\CatProveedores;
use app\models\CatTipoEmpaque;
use app\models\WrkEmpaque;

/**
 * ConCategoiriesController implements the CRUD actions for ConCategoiries model.
 */
class TestController extends Controller
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
            'crear-envio' => ['POST'],
            'actualizar-datos-envio' => ['POST']
        ];
    }

    public function actionCrearEnvio(){
        $request = Yii::$app->request;
        $params = $request->bodyParams;

        $cliente = null;
        $paquetes = null;
        $sobre = null;

        if($request->getBodyParam("uddi_cliente")){
            $uddiCliente = $request->getBodyParam("uddi_cliente");
            $cliente = EntClientes::getClienteByUddi($uddiCliente);
        }
        if($request->getBodyParam("dimensiones_paquete")){
            $paquetes = $request->getBodyParam("dimensiones_paquete");
        }
        if($request->getBodyParam("dimensiones_sobre")){
            $sobre = $request->getBodyParam("dimensiones_sobre");
        }

        $uddiProveedor = $request->getBodyParam("uddi_proveedor");
        $uddiTipoEmpaque = $request->getBodyParam("uddi_tipo_empaque");

        $proveedor = CatProveedores::getProveedorByUddi($uddiProveedor);
        $tipoEmpaque = CatTipoEmpaque::getTipoEmpaqueByUddi($uddiTipoEmpaque);

        $envio = new WrkEnvios();
        $origen = new WrkOrigen();
        $destino = new WrkDestino();

        if($envio->load($params, '') && $origen->load($params, "origen") && $destino->load($params, "destino")){
            $envio->generarNuevoEnvio($cliente, $origen, $destino, $proveedor, $tipoEmpaque, $paquetes, $sobre);
            return $envio;
        }else{
            throw new HttpException(500, "No se enviaron todos los datos");
        }
    }

    public function actionActualizarDatosEnvio(){
        $request = Yii::$app->request;
        $params = $request->bodyParams;

        $cliente = null;
        $paquetes = null;
        $sobre = null;
        $uddiEnvio = $request->getBodyParam("uddi_envio");

        if($request->getBodyParam("uddi_cliente")){
            $uddiCliente = $request->getBodyParam("uddi_cliente");
            $cliente = EntClientes::getClienteByUddi($uddiCliente);
        }

        $envio = WrkEnvios::getEnvio($uddiEnvio);
        $origen = $envio->origen;
        $destino = $envio->destino;
        $proveedor = $envio->proveedor;
        $tipoEmpaque = $envio->tipoEmpaque;

        if($envio->empaque){
            $paquetes = $envio->empaque;
        }
        if($envio->sobres){
            $sobre = $envio->sobres;
        }

        if($envio->load($params, '') && $origen->load($params, "origen") && $destino->load($params, "destino")){
            $envio->generarNuevoEnvio($cliente, $origen, $destino, $proveedor, $tipoEmpaque, $paquetes, $sobre);
            return $envio;
        }else{
            throw new HttpException(500, "No se enviaron todos los datos");
        }
    }
}