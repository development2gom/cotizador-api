<?php


namespace app\_360Utils\Services;

use Yii;

use app\_360Utils\Entity\Cotizacion;
use app\_360Utils\Entity\CompraEnvio;
use app\_360Utils\Entity\ResultadoEnvio;

class EstafetaServices{

    const ID_USUARIO = 1;
    const USUARIO = 'AdminUser';
    const PASSWORD = ',1,B(vVi';

    const SOBRE_ALTO    = 30;
    const SOBRE_ANCHO   = 17;
    const SOBRE_LARGO   = 1;

    const TIEMPO_ENTREGA = [
        'Dia Sig.' => 'Dia siguente habil, de 8 a 18 horas',
        'Terrestre' => 'De 2 a 5 días habiles, de 8 a 18 horas',
        '2 Dias' => 'Segundo día habil, de 8 a 18 horas'
    ];


    function cotizarEnvioDocumento($origenCP,$destinoCP,$fecha, $paquetes, $montoSeguro = false){
        return $this->cotizarEnvio($origenCP,$destinoCP,$fecha, $paquetes, false);
    }

    function cotizarEnvioPaquete($origenCP,$destinoCP,$fecha, $paquetes, $montoSeguro = false){
        return $this->cotizarEnvio($origenCP,$destinoCP,$fecha, $paquetes, true);
    }

           
    private function cotizarEnvio($origenCP,$destinoCP,$fecha, $paquetes, $isPaquete){

        //TODO manejar varios paquete
        if($isPaquete){
            $largo = $paquetes[0]['num_largo'];
            $ancho = $paquetes[0]['num_largo'];
            $alto = $paquetes[0]['num_largo'];
            $peso = $paquetes[0]['num_peso'];
        }else{
            $largo  = '' . self::SOBRE_LARGO;
            $ancho  = '' . self::SOBRE_ANCHO;
            $alto   = '' . self::SOBRE_ALTO;
            $peso   = '' . $paquetes[0]['num_peso'];
        }

        
        $path_to_wsdl = Yii::getAlias('@app') . '/_360Utils/shipment-carriers/estafeta/wsdl/Frecuenciacotizador.wsdl';
        ini_set("soap.wsdl_cache_enabled", "0");

        $client = new \SoapClient($path_to_wsdl, array('trace' => 1));

        $request = [];
        $request['idusuario'] = $this::ID_USUARIO;
        $request['usuario'] = $this::USUARIO;
        $request['contra'] = $this::PASSWORD;
        $request['esFrecuencia'] = false;
        $request['esLista'] = true;
        
        
        $request['tipoEnvio']['EsPaquete'] = $isPaquete; //Define si es un paquete o no
        $request['tipoEnvio']['Largo']     = $largo;
        $request['tipoEnvio']['Peso']      = $peso;
        $request['tipoEnvio']['Alto']      = $alto;
        $request['tipoEnvio']['Ancho']     = $ancho;

        $request['datosOrigen'] = [];
        $request['datosOrigen']['string'] = $origenCP;
        
        $request['datosDestino'] = [];
        $request['datosDestino']['string'] = $destinoCP;
        


        $response = $client->FrecuenciaCotizador($request);


        if(!isset($response->FrecuenciaCotizadorResult) || !isset($response->FrecuenciaCotizadorResult->Respuesta)){
            return null;
        }

        $respuesta = $response->FrecuenciaCotizadorResult->Respuesta;
        if($respuesta->Error != "000"){
            error_log("Se presento un error con Estafeta " . $respuesta->Error . " " . $respuesta->MensajeError);
            return null;
        }

        $tipoServicioList = $response->FrecuenciaCotizadorResult->Respuesta->TipoServicio->TipoServicio;

        $res = [];
        foreach($tipoServicioList as $item){

            //Si la opcion es LTL (pallets) no aplica
            if($item->DescripcionServicio == "LTL"){
                continue;
            }
    
            $cotizacion = new Cotizacion();
            $cotizacion->provider = "Estafeta";
            $cotizacion->price = $item->CostoTotal;
            $cotizacion->tax = 0;
            $cotizacion->serviceType = $item->DescripcionServicio;
            $cotizacion->deliveryDate = "";
            $cotizacion->currency = "MXN";
            $cotizacion->data = $response;
            $cotizacion->servicePacking = "";
            
            if(self::TIEMPO_ENTREGA[$cotizacion->serviceType] != null){
                $cotizacion->deliveryDateStr = self::TIEMPO_ENTREGA[$cotizacion->serviceType];
            }
            $cotizacion->serviceTypeStr = $item->DescripcionServicio;

            array_push($res,$cotizacion);
            
        }
        return $res;
    }
}
?>