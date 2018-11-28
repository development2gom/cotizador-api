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
use app\models\Calendario;
use app\models\EnviosObject;
use yii\helpers\Url;


class EnviosController extends Controller{

    public $enableCsrfValidation = false;
    public $serializer = [
        'class' => 'app\components\SerializerExtends',
        'collectionEnvelope' => 'items',
    ];

    // Crea un envio
    public function actionCreateEnvio(){
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

    public function actionActualizarEnvio(){
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
        

        if($envio->load($params, '') && $origen->load($params, "origen") && $destino->load($params, "destino")){
            $envio->generarNuevoEnvio($cliente, $origen, $destino, $proveedor, $tipoEmpaque, $paquetes, $sobre);
            return $envio;
        }else{
            throw new HttpException(500, "No se enviaron todos los datos");
        }
    }

    // Recupera todos los envios
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

        $respuesta = [];
        for($i = 0; $i<3; $i++){
            $eo = new EnviosObject();
            $eo->cpOrigen = "54710";
            $eo->cpDestino = "57349";
            $eo->precioOriginal = 258+$i;
            $eo->precioCliente = 258+($i+1);
            $eo->mensajeria = "FEDEX";
            $eo->fechaEntrega = "2018-11-26";    
            $eo->tipoEnvio = "Express";
            $eo->urlImagen = Yii::$app->urlManager->createAbsoluteUrl([''])."images/fedex.jpg";

            $respuesta[] = $eo;
        }

        //return $respuesta;

        $fedex = new Fedex($tipoPaquete);
        
        return $fedex->getFedex($cpFrom, $cpTo, $countryCodeFrom, $countryCodeTo, $paquetes,$tipoPaquete);

    }

    public function actionGenerarLabel(){
        $request = Yii::$app->request;
        $params = $request->bodyParams;

        $uddiEnvio = $request->getBodyParam("uddi_envio");
        
        $envio = WrkEnvios::getEnvio($uddiEnvio);

        if($envio->txt_tracking_number){
            throw new HttpException(500, "Ya existe una guia");
        }

        $origen = $envio->origen;
        $destino = $envio->destino;
        $paquetes = $envio->empaque;

        // @TODO Generar el label de acuerdo al courier
        $fedex = new Fedex($envio->tipoEmpaque->uddi);
        $respuesta = $fedex->getLabel($envio->txt_tipo, $origen->num_codigo_postal,$origen->txt_pais, $destino->txt_pais, $destino->num_codigo_postal, $origen->txt_municipio, 
            $destino->txt_municipio, $origen->txt_nombre, $destino->txt_nombre, $origen->num_telefono_movil, $destino->num_telefono_movil, $origen->direccionShort, 
            $destino->direccionShort, $origen->txt_empresa, $destino->txt_empresa, $paquetes);

        if (isset($respuesta->HighestSeverity) && $respuesta->HighestSeverity != "ERROR") {
            $envio->guardarNumeroRastreo($respuesta->CompletedShipmentDetail->MasterTrackingId->TrackingNumber, $respuesta->JobId);    
        }
        
        $envio->generarPDF($respuesta);
        return $respuesta;    

    }

    public function actionDescargarEtiqueta($uddi){
        
        
        $envio = WrkEnvios::getEnvio($uddi);
        
        $basePath = "trackings/".$uddi.'/tracking.pdf';

        if (file_exists($basePath)) {
                header('Content-Description: File Transfer');
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="' . basename($basePath) . '"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                //header('Content-Length: ' . filesize($file2));
                readfile($basePath);
                exit;
        }else{
            throw new HttpException(404, "No existe el archivo para descargar");
        }

    }

  

  

}