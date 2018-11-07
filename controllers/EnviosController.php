<?php
namespace app\controllers;

use Yii;
use yii\rest\Controller;
use app\models\WrkEnvios;
use yii\web\HttpException;
use app\models\WrkOrigen;
use app\models\WrkDestino;
use app\models\EntClientes;
use app\models\CatProveedores;
use app\models\CatTipoEmpaque;
use app\models\WrkEnviosSearch;
use app\models\Fedex;

class EnviosController extends Controller{

    public $enableCsrfValidation = false;
    public $serializer = [
        'class' => 'app\components\SerializerExtends',
        'collectionEnvelope' => 'items',
    ];

    public function actionCreateEnvio(){
        $request = Yii::$app->request;
        $params = $request->bodyParams;
        $uddiCliente = $request->getBodyParam("uddi_cliente");
        $uddiProveedor = $request->getBodyParam("uddi_proveedor");
        $uddiTipoEmpaque = $request->getBodyParam("uddi_tipo_empaque");

        // Se busca al cliente para obtener el id del cliente
        $cliente = EntClientes::getClienteByUddi($uddiCliente);
        $proveedor = CatProveedores::getProveedorByUddi($uddiProveedor);
        $tipoEmpaque = CatTipoEmpaque::getTipoEmpaqueByUddi($uddiTipoEmpaque);

        $envio = new WrkEnvios();
        $origen = new WrkOrigen();
        $destino = new WrkDestino();

        if($envio->load($params, '') && $origen->load($params, "origen") && $destino->load($params, "destino")){
            $envio->generarEnvio($cliente, $origen, $destino, $proveedor, $tipoEmpaque);
            return $envio;
        }else{
            throw new HttpException(500, "No se enviaron todos los datos");
        }

    }

    public function actionIndex(){
        $envios = new WrkEnviosSearch();
        $data = $envios->search(Yii::$app->getRequest()->get());

        return $data;
    }

    

    public function actionUpdateEnvio(){

    }

    /**
     * Servicio para obtener un envio
     */
    public function actionGetEnvio(){
        $request = Yii::$app->request;
        $params = $request->bodyParams;
        $uddi = $request->getBodyParam("uddi_envio");
        return WrkEnvios::getEnvio($uddi);
    }

    /**
     * Servicio que agrega el Folio a la venta
     */
    public function actionAgregarFolio(){
        $request = Yii::$app->request;
        $params = $request->bodyParams;
        $uddi = $request->getBodyParam("uddi_envio");
        $envio =  WrkEnvios::getEnvio($uddi);

        if($envio->load($params, '')){
            $envio->save();
            return $envio;
        }else{
            throw new HttpException(500, "No se enviaron los datos");
        }
    }

    /**
     * Action para la cotizacion
     */
    public function actionCotizar(){
        $request = Yii::$app->request;
        $params = $request->bodyParams;

        $cpFrom = $request->getBodyParam("cp_from");
        $cpTo = $request->getBodyParam("cp_to");
        $countryCodeFrom = $request->getBodyParam("country_code_from");
        $countryCodeTo = $request->getBodyParam("country_code_to");
        $tipoPaquete = $request->getBodyParam("tipo_paquete");
        $paquetesRequest = $request->getBodyParam("dimensiones_paquete");
        $dimenSobre = $request->getBodyParam("dimensiones_sobre");
        $paquetes = [];

        if(!$tipoPaquete){
            throw new HttpException(500, "No se envio el tipo de paquete");
        }

        if(strtoupper($tipoPaquete)=="SOBRE"){
            $paquetes[]=[
                "num_peso"=>$dimenSobre["num_peso"],
                "num_alto"=>0,
                "num_ancho"=>0,
                "num_largo"=>0
            ];
        }else{
            foreach($paquetesRequest as $paquete){
                if($paquete["num_paquetes"]>1){
                    for($i=0; $i<$paquete["num_paquetes"]; $i++){
                        $paquetes[]=[
                            "num_peso"=>$paquete["num_peso"],
                            "num_alto"=>$paquete["num_alto"],
                            "num_ancho"=>$paquete["num_ancho"],
                            "num_largo"=>$paquete["num_largo"]
                        ];
                    }
                }else{
                    $paquetes[]=[
                        "num_peso"=>$paquete["num_peso"],
                        "num_alto"=>$paquete["num_alto"],
                        "num_ancho"=>$paquete["num_ancho"],
                        "num_largo"=>$paquete["num_largo"]
                    ];
                }
                
            }
        }


        $fedex = new Fedex($tipoPaquete);
        return $fedex->getFedex($cpFrom, $cpTo, $countryCodeFrom, $countryCodeTo, $paquetes,$tipoPaquete);

    }

    public function actionGenerarLabelFedex(){
        $request = Yii::$app->request;
        $params = $request->bodyParams;

        $uddiEnvio = $request->getBodyParam("uddi_envio");
        
        $envio = WrkEnvios::getEnvio($uddiEnvio);
        $origen = $envio->origen;
        $destino = $envio->destino;
        $paquetes = $envio->empaque;
        $fedex = new Fedex($envio->tipoEmpaque->uddi);
        $respuesta = $fedex->getLabel($envio->txt_tipo, $origen->num_codigo_postal,$origen->txt_pais, $destino->txt_pais, $destino->num_codigo_postal, $origen->txt_municipio, 
            $destino->txt_municipio, $origen->txt_nombre, $destino->txt_nombre, $origen->num_telefono_movil, $destino->num_telefono_movil, $origen->direccionShort, 
            $destino->direccionShort, $origen->txt_empresa, $destino->txt_empresa, $paquetes);
        $fedex->generarPDF($respuesta, $uddiEnvio);
        return $respuesta;    

    }

  

  

}