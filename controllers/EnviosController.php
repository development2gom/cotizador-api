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
use app\_360Utils\CotizadorPaquete;
use app\_360Utils\CotizadorSobre;
use app\models\Utils;
use app\models\WrkEmpaque;
use app\_360Utils\Entity\CompraEnvio;
use app\_360Utils\Entity\Paquete;
use app\_360Utils\Services\FedexServices;
use app\_360Utils\Services\GeoNamesServices;
use app\_360Utils\CompraPaquete;
use app\models\WrkResultadosEnvios;
use app\_360Utils\CompraSobre;


class EnviosController extends Controller{

    const TIPO_ENVIO_PAQUETE    = 2;
    const TIPO_ENVIO_SOBRE      = 1;

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
       
        $uddiEnvio = $request->getBodyParam("uddi_envio");

        if($request->getBodyParam("uddi_cliente")){
            $uddiCliente = $request->getBodyParam("uddi_cliente");
            $cliente = EntClientes::getClienteByUddi($uddiCliente);
        }

        $envio = WrkEnvios::getEnvio($uddiEnvio);
        $origen = $envio->origen;
        $destino = $envio->destino;
        

        if($envio->load($params, '') && $origen->load($params, "origen") && $destino->load($params, "destino")){
            $envio->actualizarEnvio($cliente, $origen, $destino);
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

    /**
     * Action para la cotizacion
     */
    public function actionCotizarV2(){
        $request = Yii::$app->request;
        $params = $request->bodyParams;

        $cpFrom = $request->getBodyParam("cp_from");
        $cpTo = $request->getBodyParam("cp_to");
        $stateCodeFrom = $request->getBodyParam("state_code_from");
        $stateCodeTo= $request->getBodyParam("state_code_to");

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
                "num_peso"=>$dimenSobre["num_peso"]/1000,
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

       

        if(strtoupper($tipoPaquete)=="SOBRE"){
            $json = (object)[
                "cp_origen"=>$cpFrom,
                "pais_origen"=>$countryCodeFrom,
                "estado_origen"=>$stateCodeTo,
                "cp_destino"=>$cpTo,
                "pais_destino"=>$countryCodeTo,
                "estado_destino"=>$stateCodeTo,
            ];
            $cotizador = new CotizadorSobre();
            return $cotizador->realizaCotizacion($json,  $paquetes); // Agregar arreglo con paquetes
        }else{

            $json = (object)[
                "cp_origen"=>$cpFrom,
                "pais_origen"=>$countryCodeFrom,
                "estado_origen"=>$stateCodeTo,
                "cp_destino"=>$cpTo,
                "pais_destino"=>$countryCodeTo,
                "estado_destino"=>$stateCodeTo,
            ];
            $cotizador = new CotizadorPaquete();
             return $cotizador->realizaCotizacion($json, $paquetes);
        }
    }


    /**
     * MEtodo para registrar un envio con el carrier
     * y obtener sus etiquetas
     */
    public function actionGenerarLabel2(){
        $request = Yii::$app->request;
        $uddiEnvio = $request->getBodyParam("uddi_envio");
        
        $envio = WrkEnvios::getEnvio($uddiEnvio);

        if($envio->txt_tracking_number){
            throw new HttpException(500, "Ya existe una guia");
        }

        $origen  = $envio->origen;
        $destino = $envio->destino;

        
        //Crea el objeto de compra
        $compra = $this->createCompraEnvio($envio,$origen,$destino);
        
        

        $pkgs = $envio->empaque;
        if($pkgs == null){
            $pkgs = $envio->sobres;
        }
        //Asigna los paquetes a la compra
        foreach($pkgs as $key=>$sobre){
            $p = new Paquete();
            $p->peso = $sobre->num_peso;
            if($envio->id_tipo_empaque == self::TIPO_ENVIO_PAQUETE){
                $p->alto = $sobre->num_alto;
                $p->largo = $sobre->num_largo;
                $p->ancho = $sobre->num_ancho;
            }
            $compra->addPaquete($p);
        }   
            
        
        if($envio->id_tipo_empaque == self::TIPO_ENVIO_PAQUETE ){
            $compraPaquete = new CompraPaquete();
            $res = $compraPaquete->comprarPaquete($compra);
        }else{
            // implementar sobre
            $compraSobre = new CompraSobre();
            $res = $compraSobre->comprarSobre($compra);
        }

        $resEnvios = [];
        foreach($res as $item){
            //almacena la respuesta en la base de datos
            $wrkResultadoEnvio = new WrkResultadosEnvios();
            $wrkResultadoEnvio->uddi = Utils::generateToken('res_env_');
            $wrkResultadoEnvio->id_envio = $envio->id_envio;
            $wrkResultadoEnvio->txt_traking_number = $item->jobId;
            $wrkResultadoEnvio->txt_envio_code = $item->envioCode;
            $wrkResultadoEnvio->txt_envio_code_2 = $item->envioCode2;
            $wrkResultadoEnvio->txt_tipo_empaque = $item->tipoEmpaque;
            $wrkResultadoEnvio->txt_tipo_servicio = $item->tipoServicio;
            $wrkResultadoEnvio->txt_etiqueta_formato = $item->etiquetaFormat;
            $wrkResultadoEnvio->txt_data = $item->data;

            //Garda el resultado del envio
            $wrkResultadoEnvio->save();

            //Genera la etiqueta
            $wrkResultadoEnvio->generarPDF($item->etiqueta);

            $resEnvios[] = $wrkResultadoEnvio;
        }

        //Actualiza el traking number de la guia
        $envio->txt_tracking_number = $res[0]->jobId;
        $envio->save();

        return ['envio'=>$envio,'comprarPaqueteRes'=>$res, 'resEnvios'=>$resEnvios];        
    }


    private function createCompraEnvio(WrkEnvios $envio,$origen , $destino){
        $compra = new CompraEnvio();
        $compra->servicio = $envio->proveedor->uddi;
        $compra->tipo_servicio = $envio->txt_tipo;
        $compra->carrier = $envio->proveedor->txt_nombre_proveedor;
        
        $compra->origen_cp = $origen->num_codigo_postal;
        $compra->origen_pais = $origen->txt_pais;
        $compra->origen_ciudad = $origen->txt_estado;
        $compra->origen_estado = $origen->txt_estado;
        $compra->origen_direccion = $origen->direccionCompleta;
        $compra->origen_nombre_persona = $origen->txt_nombre;
        $compra->origen_telefono = $origen->num_telefono;
        $compra->origen_compania = $origen->txt_empresa;

        $compra->destino_cp = $destino->num_codigo_postal;
        $compra->destino_pais = $destino->txt_pais;
        $compra->destino_ciudad = $destino->txt_estado;
        $compra->destino_estado = $destino->txt_estado;
        $compra->destino_direccion = $destino->direccionCompleta;
        $compra->destino_nombre_persona = $destino->txt_nombre;
        $compra->destino_telefono = $destino->num_telefono;
        $compra->destino_compania = $destino->txt_empresa;

        return $compra;
    }

    public function actionGetCode(){

        $request = Yii::$app->request;
        $params = $request->bodyParams;

        $cp = $request->getBodyParam("cp");
        $country = $request->getBodyParam("country");

        $geoNames = new GeoNamesServices();
        return $geoNames->getCPData($cp, $country);
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
        if($envio->id_tipo_empaque==2){
            $paquetes = $envio->empaque;
        }else{
            $paquetes = [];
            foreach($envio->sobres as $key=>$sobre){
                $pa = new WrkEmpaque();
                $pa->num_peso = $sobre->num_peso;
                $pa->num_alto = 0;
                $pa->num_ancho = 0;
                $pa->num_largo = 0;
                $paquetes[]= $pa;
            }   
            
        }
        

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

    public function actionDescargarEtiqueta($uddi, $uddilabel){
        
        
        //$envio = WrkEnvios::getEnvio($uddi);
        
        $basePath = "trackings/".$uddi.'/' . $uddilabel . '-tracking.pdf';
        $basePathGif = "trackings/".$uddi.'/' . $uddilabel . '-tracking.gif';

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
        }else if(file_exists($basePathGif)){
            header('Content-Description: File Transfer');
                //header('Content-Type: application/gif');
                header('Content-Disposition: attachment; filename="' . basename($basePathGif) . '"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                //header('Content-Length: ' . filesize($file2));
                readfile($basePathGif);
                exit;
        }
        else{
            throw new HttpException(404, "No existe el archivo para descargar");
        }

    }

  

  

}