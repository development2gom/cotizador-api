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
use app\_360Utils\Entity\CotizacionRequest;
use app\_360Utils\Tracking;
use app\models\MessageResponse;


class EnviosController extends Controller{

    const TIPO_ENVIO_PAQUETE    = 2;
    const TIPO_ENVIO_SOBRE      = 1;

    public $enableCsrfValidation = false;
    public $serializer = [
        'class' => 'app\components\SerializerExtends',
        'collectionEnvelope' => 'items',
    ];


    /**
     * Action para la cotizacion de un envio ya sea paquete o sea sobre
     */
    public function actionCotizarV2(){
        $request = Yii::$app->request;
        $params = $request->bodyParams;

        $tipoPaquete = $request->getBodyParam("tipo_paquete");

        if(!$tipoPaquete){
            throw new HttpException(500, "No se envio el tipo de paquete");
        }
        $cotizacion                   = new CotizacionRequest();
        $cotizacion->origenCP         = $request->getBodyParam("cp_from");
        $cotizacion->origenCountry    = $request->getBodyParam("country_code_from");
        $cotizacion->origenStateCode  = $request->getBodyParam("state_code_from");

        $cotizacion->destinoCP        = $request->getBodyParam("cp_to");
        $cotizacion->destinoCountry   = $request->getBodyParam("country_code_to");
        $cotizacion->destinoStateCode = $request->getBodyParam("state_code_to");

        //Fecha de solicitud de envio o recoleccion
        $cotizacion->solicitaPickup = $request->getBodyParam("b_requiere_recoleccion");
        if($request->getBodyParam("fch_recoleccion") != null){
            $cotizacion->fecha            = $request->getBodyParam("fch_recoleccion");
        }else{
            $cotizacion->fecha            = Calendario::getFechaActualMasDias(1); //Si no indica la fecha, le agrega 1 día para el envio
        }

        //Uso de seguro del envío
        if($request->getBodyParam("num_monto_seguro") != null){
            $cotizacion->montoSeguro           = $request->getBodyParam("num_monto_seguro");
            $cotizacion->hasSeguro             = true;
        }
       

        if(strtoupper($tipoPaquete)=="SOBRE"){
            $cotizacion->isPaquete = false;
            $dimenSobre = $request->getBodyParam("dimensiones_sobre");
            $cotizacion->addSobre($dimenSobre["num_peso"]/1000);
        }else{
            $cotizacion->isPaquete = true;
            $paquetesRequest = $request->getBodyParam("dimensiones_paquete");
           
            foreach($paquetesRequest as $paquete){
                $cotizacion->addPaqueteElementos($paquete["num_alto"],$paquete["num_ancho"],$paquete["num_largo"],$paquete["num_peso"]);
            }
        }


        //Llama al servicio de cotización pertinente
       if($cotizacion->isPaquete){
            $cotizador = new CotizadorPaquete();
            return $cotizador->realizaCotizacion($cotizacion);
       }else{
            $cotizador = new CotizadorSobre();
            return $cotizador->realizaCotizacion($cotizacion);
       }
    }




    // Crea un envio en la base de datos
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

            //Verifica el asunto del seguro del envio
            if(isset($envio->b_asegurado) && isset($envio->num_monto_seguro) && //Valida que los valores existan
             (int)$envio->num_monto_seguro == $envio->num_monto_seguro &&  //Valida que se a un numero entero
             (int)$envio->num_monto_seguro > 0){ //Valida que sea positivo
                $envio->b_asegurado = 1;
            }else{
                $envio->b_asegurado = 0;
                $envio->num_monto_seguro = null;
            }

            //Guarda la información del envio en la base de datos
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




    public function actionActualizarEnvioContenido(){
        $request = Yii::$app->request;
        $params = $request->bodyParams;

        $cliente = null;
       
        $uddiEnvio          = $request->getBodyParam("uddi_envio");
        $txtContenido       = $request->getBodyParam("txt_contenido");
        $numUnidades        = $request->getBodyParam("num_unidades");
        $numPrecioUnitario  = $request->getBodyParam("num_precio_unitario");
        
        $envio = WrkEnvios::getEnvio($uddiEnvio);

        $envio->txt_contenido       = $txtContenido;
        $envio->num_unidades        = $numUnidades;
        $envio->num_precio_unidad   = $numPrecioUnitario;
        
        $messageResponse = new MessageResponse();

        if($envio->save()){
            $messageResponse->responseCode = 1;
            $messageResponse->message = "Envío actualizado: contenido del paquete";
            $messageResponse->data = $envio;
        }else{
            $messageResponse->responseCode = -1;
            $messageResponse->message = "Error al actualizar el envío: " . json_encode($envio->errors());
            //$messageResponse->data = $envio;
        }

        
        return $messageResponse;
    }




    // Recupera todos los envios
    public function actionIndex(){
        $envios = new WrkEnviosSearch();
        $data = $envios->search(Yii::$app->getRequest()->get());

        return $data;
    }


    // Recupera todos los envios
    public function actionGetEnviosMostrador(){
        $envios = new WrkEnviosSearch();
        $data = $envios->searchMostrador([]);

        return $data;
    }


    /**
     * Realiza el tracking de un envio
     */
    public function actionTrackEnvio(){
        $request = Yii::$app->request;
        $params = $request->bodyParams;
        $uddi = $request->getBodyParam("uddi_envio_respuesta");

        $res = WrkResultadosEnvios::find()->where(['uddi'=>$uddi])->one();
        $carrier = $res->envio->proveedor->txt_nombre_proveedor;

        $track = new Tracking();
        $trackRes = $track->doTracking($carrier,$uddi);
        
        if($res){
            $res->fch_ultimo_estatus = Calendario::getFechaActual();
            $res->txt_ultimo_estatus = $trackRes->message;
            if($trackRes->isDelivered){
                $res->b_entregado = 1;
            }
            $res->update();
        }
        return $trackRes;
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
     * Metodo para registrar un envio con el carrier
     * y obtener sus etiquetas
     * ----------- COMPRA DEL ENVIO ---------
     */
    public function actionGenerarLabel2(){
        $request = Yii::$app->request;
        $uddiEnvio = $request->getBodyParam("uddi_envio");
        
        $envio = WrkEnvios::getEnvio($uddiEnvio);

        if($envio->txt_tracking_number){
            //throw new HttpException(500, "Ya existe una guia");
            $messageResponse = new MessageResponse();
            $messageResponse->message = "Ya existe una guía para el envío";
            return $messageResponse;
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
            $messageResponse = $compraPaquete->comprarPaquete($compra);
        }else{
            //sobre
            $compraSobre = new CompraSobre();
            $messageResponse = $compraSobre->comprarSobre($compra);
        }

        //Verifica la respuesta de res
        if($messageResponse->responseCode < 0){
            return $messageResponse;
        }

        $resEnvios = [];
        $res = $messageResponse->data;
        foreach($messageResponse->data as $item){
            if($item->isError){
                continue;
            }
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

        $messageResponse->data = ['envio'=>$envio,'comprarPaqueteRes'=>$res, 'resEnvios'=>$resEnvios]; 

        return $messageResponse;
    }


    /**
     * Crea el objto de la compra del envio
     */
    private function createCompraEnvio(WrkEnvios $envio,$origen , $destino){
        $compra = new CompraEnvio();
        $compra->servicio           = $envio->proveedor->uddi;
        $compra->tipo_servicio      = $envio->txt_tipo;
        $compra->carrier            = $envio->proveedor->txt_nombre_proveedor;
        $compra->txt_contenido      = $envio->txt_contenido;
        
        $compra->origen_cp              = $origen->num_codigo_postal;
        $compra->origen_pais            = $origen->txt_pais;
        $compra->origen_ciudad          = $origen->txt_estado;
        $compra->origen_estado          = $origen->txt_estado;
        $compra->origen_direccion       = $origen->direccionCompleta;
        $compra->origen_nombre_persona  = $origen->txt_nombre;
        $compra->origen_telefono        = $origen->num_telefono;
        $compra->origen_compania        = $origen->txt_empresa;

        $compra->destino_cp             = $destino->num_codigo_postal;
        $compra->destino_pais           = $destino->txt_pais;
        $compra->destino_ciudad         = $destino->txt_estado;
        $compra->destino_estado         = $destino->txt_estado;
        $compra->destino_direccion      = $destino->direccionCompleta;
        $compra->destino_nombre_persona = $destino->txt_nombre;
        $compra->destino_telefono       = $destino->num_telefono;
        $compra->destino_compania       = $destino->txt_empresa;

        $compra->fecha                  = $envio->fch_recoleccion;

        $compra->hasSeguro              = $envio->b_asegurado;

        if($envio->b_asegurado){
            $compra->valorSeguro = $envio->num_monto_seguro;
        }

        $compra->valorUnitario = $envio->num_precio_unidad;
        $compra->cantidadPiezasUnitarias = $envio->num_unidades;


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