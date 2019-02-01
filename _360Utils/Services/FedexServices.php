<?php


namespace app\_360Utils\Services;

use Yii;

use app\_360Utils\Entity\Cotizacion;
use app\_360Utils\Entity\CompraEnvio;
use app\_360Utils\Entity\ResultadoEnvio;
use app\_360Utils\Entity\CotizacionRequest;
use app\_360Utils\Entity\TrackingResult;
use yii\base\ExitException;
use function GuzzleHttp\json_encode;


class FedexServices{


    const FEDEX_KEY             = 'VY3a8M7siRPxvdOf';
    const FEDEX_PASSWORD        = 'W48oom2vQa4Sqt9tn1kuP7ihk'; 
    const FEDEX_PARENT_PASSWORD = 'XXX';
	const FEDEX_PARENT_KEY      = 'VY3a8M7siRPxvdOf'; 
	const FEDEX_SHIP_ACCOUNT    = '510088000';
	const FEDEX_BILL_ACCOUNT    = '510088000';
	const FEDEX_LOCATION_ID     = 'PLBA';
	const FEDEX_METER           = '119037066';

    const FEDEX_CUSTOMER_REF    = '794653027330';


    //------------- TRAKING -------------------------

    function traking($trakingNumber){
        require_once(Yii::getAlias('@app') . '/_360Utils/shipmentCarriers/fedex/fedex-common.php');
        $path_to_wsdl = Yii::getAlias('@app') . '/_360Utils/shipmentCarriers/fedex/wsdl/TrackService_v16.wsdl';
        ini_set("soap.wsdl_cache_enabled", "0");


        $client = new \SoapClient($path_to_wsdl, array('trace' => 1)); // Refer to http://us3.php.net/manual/en/ref.soap.php for more information

        
        $request = $this->configClientRequest();

        $request['TransactionDetail'] = array('CustomerTransactionId' => '*** Track Request using PHP ***');
        $request['Version'] = array(
	        'ServiceId' => 'trck', 
	        'Major' => '16', 
	        'Intermediate' => '0', 
	        'Minor' => '0'
        );

        $request['SelectionDetails'] = array(
	        'PackageIdentifier' => array(
		    'Type' => 'CUSTOMER_REFERENCE',
		    'Value' => $trakingNumber // Replace with a valid customer reference
	        ),
            //'ShipDateRangeBegin' => getProperty('begindate'),
            //'ShipDateRangeEnd' => getProperty('enddate'),
            'ShipmentAccountNumber' => self::FEDEX_SHIP_ACCOUNT // Replace with account used for shipment
        );

        try {
            if(setEndpoint('changeEndpoint')){
                $newLocation = $client->__setLocation(setEndpoint('endpoint'));
            }
            
            $response = $client ->track($request);
            $res = new TrackingResult();
            $res->data = $response;
                
            //No hay error
            if ($response -> HighestSeverity != 'FAILURE' && $response -> HighestSeverity != 'ERROR'){ 
                $res->isError = false;
                $res->message = $response-> CompletedTrackDetails-> TrackDetails-> Notification->Message;  
                
                //Se encontro el traking
                if($response-> CompletedTrackDetails-> TrackDetails-> Notification-> Severity != "ERROR"){
                    $res->isTrakingFound  = true;
                    $res->numeroPaquetes  =  $response->CompletedTrackDetails->TrackDetails->PackageCount;
                    $res->intentosEntrega =  $response->CompletedTrackDetails->TrackDetails->DeliveryAttempts;
                }


            }else{
                $res->isError = true;
                $res->message = $response->Notifications->Message; 
            } 


            return $res;
            
        } catch (SoapFault $exception) {
            //printFault($exception, $client);        
            $res = new TrackingResult();
            $res->isError = true;
            $res->message = "Error con el servicio";
            return $res;
        }
        catch (\Exception $exception) {
            //printFault($exception, $client);        
            $res = new TrackingResult();
            $res->isError = true;
            $res->message = "Error con el servicio, " . $exception->getMessage();
            return $res;
        }

        
    }

    //------------- ENVIOS --------------------------
    function disponibilidadDocumento(CotizacionRequest $cotizacion, $fecha){
        //Corresponde a un documento
        $cotizacion->packingType = 'FEDEX_ENVELOPE';
        return $this->disponibilidad($cotizacion,$fecha );
    }


    function disponibilidadPaquete(CotizacionRequest $cotizacion,$fecha){
        //Corresponde a un paquete
        $cotizacion->packingType = 'YOUR_PACKAGING';
        return $this->disponibilidad($cotizacion ,$fecha);
    }



    /**
     * Verifica los diferentes metodos de envio disponibles
     */
    private function disponibilidad(CotizacionRequest $cotizacion, $fecha){
        require_once(Yii::getAlias('@app') . '/_360Utils/shipmentCarriers/fedex/fedex-common.php');
            $path_to_wsdl = Yii::getAlias('@app') . '/_360Utils/shipmentCarriers/fedex/wsdl/ValidationAvailabilityAndCommitmentService_v8.wsdl';
            ini_set("soap.wsdl_cache_enabled", "0");

            $client = new \SoapClient($path_to_wsdl, array('trace' => 1));
            $request = $this->configClientRequest();

            $request['TransactionDetail'] = array('CustomerTransactionId' => ' *** Service Availability Request v5.1 using PHP ***');
            $request['Version'] = array(
                'ServiceId' => 'vacs', 
                'Major' => '8',
                'Intermediate' => '0', 
                'Minor' => '0'
            );
            $request['Origin'] = array(
                'PostalCode' => $cotizacion->origenCP, // Origin details
                'CountryCode' => $cotizacion->origenCountry
            );
            $request['Destination'] = array(
                'PostalCode' => $cotizacion->destinoCP, // Destination details
                'CountryCode' => $cotizacion->destinoCountry
            );
            //$request['ShipDate'] = $cotizacion->fecha;
            $request['ShipDate'] = $fecha;
            $request['CarrierCode'] = 'FDXE'; // valid codes FDXE-Express, FDXG-Ground, FDXC-Cargo, FXCC-Custom Critical and FXFR-Freight
            //$request['Service'] = 'PRIORITY_OVERNIGHT'; // valid code STANDARD_OVERNIGHT, PRIORITY_OVERNIGHT, FEDEX_GROUND, ...
            $request['Packaging'] = $cotizacion->packingType;//$json->service_packing; // valid code FEDEX_BOX, FEDEX_PAK, FEDEX_TUBE, YOUR_PACKAGING, ...


            try {
                if(setEndpoint('changeEndpoint')){
                    $newLocation = $client->__setLocation(setEndpoint('endpoint'));
                }
                
                $response = $client->serviceAvailability($request);
                    
                if ($response -> HighestSeverity != 'FAILURE' && $response -> HighestSeverity != 'ERROR'){ 
                   
                    return $response;
                
                }else{
                    return false;
                } 
                
            } catch (\SoapFault $exception) {
                printFault($exception, $client); 
                error_log($client->__getLastRequest());       
                error_log($client->__getLastResponse());  
            }
    }


    function cotizarEnvioDocumento($serviceType, CotizacionRequest $cotizacion,$fecha){
        //Cotiza un envio de documento
        $cotizacion->packingType = 'FEDEX_ENVELOPE';
        return $this->_cotizarEnvio($serviceType, $cotizacion,$fecha );
    }

    function cotizarEnvioPaquete($serviceType, CotizacionRequest $cotizacion,$fecha){
        //Cotiza un envio de documento
        $cotizacion->packingType = 'YOUR_PACKAGING';
        return $this->_cotizarEnvio($serviceType, $cotizacion, $fecha );
    }



    private function _cotizarEnvio($serviceType, CotizacionRequest $cotizacion, $fecha){
        //$serviceType, $origenCP,$origenCountry,$destinoCP,$destinoCountry,$fecha, $servicePacking, $paquetes, $montoSeguro = false

        $preferedCurrency = 'MXN';
        $pickUp = 'REGULAR_PICKUP';

        //manejar varios paquetes
        $numeroPaquetes = $cotizacion->paquetesCount();
        

        require_once(Yii::getAlias('@app') . '/_360Utils/shipmentCarriers/fedex/fedex-common.php');
        $path_to_wsdl = Yii::getAlias('@app') . '/_360Utils/shipmentCarriers/fedex/wsdl/RateService_v22.wsdl';
        ini_set("soap.wsdl_cache_enabled", "0");

        $client = new \SoapClient($path_to_wsdl, array('trace' => 1));
        $request = $this->configClientRequest();

        $request['TransactionDetail'] = array('CustomerTransactionId' => ' *** Rate Request ENVIOS 360 ***');
        $request['Version'] = array(
            'ServiceId' => 'crs', 
            'Major' => '22', 
            'Intermediate' => '0', 
            'Minor' => '0'
        );

        $request['ReturnTransitAndCommit']                  = true;
        $request['RequestedShipment']['DropoffType']        = $pickUp; // valid values REGULAR_PICKUP, REQUEST_COURIER, ...
        $request['RequestedShipment']['ShipTimestamp']      = $fecha;//date('c');//$cotizacion->fecha;
        $request['RequestedShipment']['ServiceType']        = $serviceType; // valid values STANDARD_OVERNIGHT, PRIORITY_OVERNIGHT, FEDEX_GROUND, ...
        $request['RequestedShipment']['PackagingType']      = $cotizacion->packingType; // valid values FEDEX_BOX, FEDEX_PAK, FEDEX_TUBE, YOUR_PACKAGING, ...

        
        

        $request['RequestedShipment']['PreferredCurrency']  = $preferedCurrency;
        $request['RequestedShipment']['RateRequestTypes']   = 'PREFERRED';        

        //Seguro de envío
        if($cotizacion->hasSeguro){
            $request['RequestedShipment']['TotalInsuredValue']=array(
                'Amount'=>$cotizacion->montoSeguro,
                'Currency'=>$preferedCurrency
            );
        }
        

        $request['RequestedShipment']['Shipper']    = $this->addShipper($cotizacion->origenCP, $cotizacion->origenCountry);
        $request['RequestedShipment']['Recipient']  = $this->addRecipient($cotizacion->destinoCP, $cotizacion->destinoCountry);
        //$request['RequestedShipment']['ShippingChargesPayment'] = $this->addShippingChargesPayment();
        $request['RequestedShipment']['PackageCount'] = $numeroPaquetes;
        $request['RequestedShipment']['RequestedPackageLineItems'] = [];
        
        //Agrega los paquetes
        foreach( $cotizacion->paquetes as $item){
            $largo = $item->largo;
            $ancho = $item->ancho;
            $alto = $item->alto;

            $peso = $item->getPesoFinal();

            $pkg = $this->addPackageLineItem($peso, $largo, $ancho, $alto);
            array_push($request['RequestedShipment']['RequestedPackageLineItems'], $pkg);
        }
        

        try {
            if(setEndpoint('changeEndpoint')){
                $newLocation = $client->__setLocation(setEndpoint('endpoint'));
            }
            
            $response = $client->getRates($request);
                
            //hay error 

            if ($response->HighestSeverity == 'FAILURE' || $response -> HighestSeverity == 'ERROR'){  

                error_log("Error al cotizar FEDEX:");
                if(is_array($response->Notifications)){
                    foreach($response->Notifications as $not){
                        error_log("Notification: " . $not->Severity . " Message: " . $not->Message . " Code: " . $not->Code);
                        if($not->Code == 200){
                            error_log("Rating is temporarily unavailable, please try again later.");
                        }
                    }
                }else{
                    error_log("Notification: " . $response->Notifications->Severity . " Message: " . $response->Notifications->Message . " Code: " . $response->Notifications->Code);
                    if($not->Code == 200){
                        error_log("Rating is temporarily unavailable, please try again later.");
                    }
                }

                    //error_log("JSON REQUEST: " . json_encode($request));
                    error_log($client->__getLastRequest());
                    error_log($client->__getLastResponse());

                return null;
            }

             
                
                $rateReply = $response->RateReplyDetails;

                //Precio y moneda
                //Fedes puede regresar distintos tipos de cotizacióm, buscaremos PREFERRED_ACCOUNT_SHIPMENT

                //Valor por defecto la primer opcion
                $rateShipmentDetails = $rateReply->RatedShipmentDetails[0];
                foreach($rateReply->RatedShipmentDetails as $item){
                    if($item->ShipmentRateDetail->RateType == 'PREFERRED_ACCOUNT_SHIPMENT'){
                        $rateShipmentDetails = $item;
                    }
                }


                if($rateReply->RatedShipmentDetails && is_array($rateReply->RatedShipmentDetails)){
                    $amount   = number_format($rateShipmentDetails->ShipmentRateDetail->TotalNetCharge->Amount,2,".",",") ;
                    $tax      = number_format($rateShipmentDetails->ShipmentRateDetail->TotalTaxes->Amount,2,".",",") ;
                    $currency = $rateShipmentDetails->ShipmentRateDetail->TotalNetCharge->Currency;
                }elseif($rateReply->RatedShipmentDetails && ! is_array($rateReply->RatedShipmentDetails)){
                    $amount   = number_format($rateReply->RatedShipmentDetails->ShipmentRateDetail->TotalNetCharge->Amount,2,".",",") ;
                    $tax      = number_format($rateReply->RatedShipmentDetails->ShipmentRateDetail->TotalTaxes->Amount,2,".",",") ;
                    $currency = $rateReply->RatedShipmentDetails->ShipmentRateDetail->TotalNetCharge->Currency;
                }


               
                //Fecha de entrega
                if(array_key_exists('DeliveryTimestamp',$rateReply)){
                    $deliveryDate =  $rateReply->DeliveryTimestamp ;
                }else if(array_key_exists('TransitTime',$rateReply)){
                    $deliveryDate=  $rateReply->TransitTime ;
                }else {
                    $deliveryDate='N/A';
                }

                
                //Tipo de servicio
                $serviceType = $rateReply->ServiceType;
                //Tipo de empaquetamient
                $servicePacking = $rateReply->PackagingType;
                
                $cotizacion = new Cotizacion();

                $cotizacion->provider     = "FEDEX";
                $cotizacion->price        = $amount;
                $cotizacion->tax          = $tax;
                $cotizacion->serviceType  = $serviceType; // FIRST_OVERNIGHT, PRIORITY_OVERNIGHT
                $cotizacion->deliveryDate = $deliveryDate;
                $cotizacion->currency     = $currency;
                $cotizacion->data         = $response;
                $cotizacion->servicePacking  = $servicePacking;

                if($deliveryDate != null &&   $deliveryDate != "N/A"){
                    $cotizacion->deliveryDateStr = $deliveryDate;
                }

                $cotizacion->serviceTypeStr  = str_replace('_', ' ',$serviceType); // FIRST_OVERNIGHT, PRIORITY_OVERNIGHT

                return $cotizacion;
            
            
        } catch (\Exception $exception) {
           printFault($exception, $client); 
           error_log($client->__getLastRequest());
           error_log($client->__getLastResponse());
           return false;       
        }
    }



    



    //---------------- COMPRA DE SERVICIOS --------------------------------

    /**
     * https://stackoverflow.com/questions/14040137/fedex-api-shipping-label-multiple-package-shipments
     * 
     * There is a difference between the FedEx Rate API and the FedEx Shipping API. You can rate multiple packages using one SOAP request; 
     * however, to ship an Multiple Pieces Shipment (MPS), you have to perform a shipping request for each one of the packages.
     * 
     * The first package (the package in the first request), will be your Master containing the master tracking number. 
     * Once you have this master tracking number, you have to attach it to the shipping request of the remaining packages. 
     * Please, refer to the latest FedEx Developer Guide for more information about MPS shipments and download the example of performing an Express domestic MPS shipment from the FedEx developer portal.
     * 
     * Something to watch out is that the shipping process does not occur as a transaction, so if you are trying to ship 3 packages, 
     * and package 1 and 2 are submitted successfully, 
     * but package 3 fails for so unknown reason, you are responsible for canceling package 1 and 2 or resubmitting package 3. 
     * I would recommend anyone to validate the shipment (using the same shipping API) before creating the actual shipment.
     */

    function comprarEnvioDocumento(CompraEnvio $model){
        return $this->comprarEnvio($model,'FEDEX_ENVELOPE');
    }

    function comprarEnvioPaquete(CompraEnvio $model){
        return $this->comprarEnvio($model,'YOUR_PACKAGING');
    }




    /**
     * Metodo para comprar envios
     */
    private function comprarEnvio(CompraEnvio $model, $servicePacking){
        require_once(Yii::getAlias('@app') . '/_360Utils/shipmentCarriers/fedex/fedex-common.php');

        //$preferedCurrency = 'MXN';

        //TODO, en está llamada se una NMP y no MXN
        //https://www.fedex.com/us/developer/WebHelp/ws/2014/dvg/WS_DVG_WebHelp/Appendix_F_Currency_Codes.htm
        $preferedCurrency = 'NMP';
        $pickUp = 'REGULAR_PICKUP';
        $MasterTrackingId = null;


        $numeroPaquetes = count($model->paquetes); 

        $resultados = [];


        

        foreach($model->paquetes as $item){
            $peso = $item->getPesoFinal();
            $largo = $item->largo;
            $ancho = $item->ancho;
            $alto = $item->alto;

            //Create request
            $request = $this->createRequest($model,$peso, $largo,$ancho,$alto, $preferedCurrency, $pickUp, $servicePacking,$model->fecha, $model->valorSeguro ,$MasterTrackingId);

            //Realiza el envio
            $resultadoEnvio = $this->realizaEnvioCompra($model,$request,$servicePacking);
            //Toma el $MasterTrackingId del primer envio para enviarlo en los subsecuentes
            if($MasterTrackingId == null && $resultadoEnvio != null && $resultadoEnvio->isError == false){
                $MasterTrackingId = $resultadoEnvio->envioCode;
            }

            //Agrega el envio al arreglo de resultados
            $resultados[] = $resultadoEnvio;
        }

        return $resultados;
    }



    /**
     * Crea el objeto del request para la compra de un paquete
     * En caso de ser un envío multiple se debe enviar:
     *  MasterTrackingId - Este se obtiene despues de enviar el primer paquete
     */
    private function createRequest($model,$peso, $largo,$ancho,$alto, $preferedCurrency, $pickUp, $servicePacking, $fecha, $montoSeguro,  $MasterTrackingId = null){
        $request = $this->configClientRequest();

        $request['TransactionDetail'] = array('CustomerTransactionId' => '*** Express International Shipping Request using PHP ***');
        $request['Version'] = array(
            'ServiceId' => 'ship', 
            'Major' => '21', 
            'Intermediate' => '0', 
            'Minor' => '0'
        );
        $request['RequestedShipment'] = [
            'ShipTimestamp' => date('c',strtotime($fecha)),//date('c'),
            'DropoffType' => $pickUp, // valid values REGULAR_PICKUP, REQUEST_COURIER, DROP_BOX, BUSINESS_SERVICE_CENTER and STATION
            'ServiceType' => $model->tipo_servicio, // valid values STANDARD_OVERNIGHT, PRIORITY_OVERNIGHT, FEDEX_GROUND, ...
            'PackagingType' => $servicePacking, // valid values FEDEX_BOX, FEDEX_PAK, FEDEX_TUBE, YOUR_PACKAGING, ...
            //'TotalInsuredValue' => $montoSeguro, //Valor del seguro
            //Seguro de envío
        ];


        //TODO: ns:ShipmentManifestDetail
        // <xs:element name="ManifestDetail" type="ns:ShipmentManifestDetail" minOccurs="0">
        //     <xs:annotation>
        //       <xs:documentation>This specifies information related to the manifest associated with the shipment.</xs:documentation>
        //     </xs:annotation>
        //   </xs:element>

        //Manejo del seguro
        if($montoSeguro != null && $montoSeguro > 0 ){
            $request['RequestedShipment']['TotalInsuredValue']=array(
                'Currency'=>"NMP", //$preferedCurrency,
                'Amount'=>$montoSeguro
            );
        }
            
        $request['RequestedShipment']['Recipient'] = $this->addRecipient(
                $model->destino_cp,
                $model->destino_pais,
                $model->destino_ciudad,
                $model->destino_estado,
                $model->destino_nombre_persona,
                $model->destino_telefono,
                $model->destino_direccion,
                $model->destino_compania
        );
        
        $request['RequestedShipment']['Shipper'] = $this->addRecipient(
                $model->origen_cp,
                $model->origen_pais,
                $model->origen_ciudad,
                $model->origen_estado,
                $model->origen_nombre_persona,
                $model->origen_telefono,
                $model->origen_direccion,
                $model->origen_compania
        );
       

        $request['RequestedShipment']['ShippingChargesPayment'] = $this->addShippingChargesPayment();

            //'CustomsClearanceDetail' => addCustomClearanceDetail(),                                                                                                       
        $request['RequestedShipment']['LabelSpecification'] = $this->addLabelSpecification();
        $request['RequestedShipment']['CustomerSpecifiedDetail'] = ['MaskedData'=> 'SHIPPER_ACCOUNT_NUMBER'];
       

        //Master traking id para envíos multiples  
        if($MasterTrackingId != null){  
            $request['RequestedShipment']['MasterTrackingId'] = 1;
        }


        //PackageCount Siempre debe ser 1
        $request['RequestedShipment']['PackageCount'] = 1;
        $request['RequestedShipment']['RequestedPackageLineItems'] = array(
                '0' => $this->addPackageLineItem($peso, $largo,$ancho,$alto)
        );
        
        $request['RequestedShipment']['CustomerReferences'] = array(
            '0' => array(
                'CustomerReferenceType' => 'CUSTOMER_REFERENCE', 
                'Value' => 'TC007_07_PT1_ST01_PK01_SNDUS_RCPCA_POS'
            )
        );
        


        return $request;
    }


    /**
     * Metodo que realiza el envío de un paquete (Ya la compra)
     */
    private function realizaEnvioCompra($model,$request,$servicePacking){

        
        $path_to_wsdl = Yii::getAlias('@app') . '/_360Utils/shipmentCarriers/fedex/wsdl/ShipService_v21.wsdl';
        ini_set("soap.wsdl_cache_enabled", "0");

        $client = new \SoapClient($path_to_wsdl, array('trace' => 1));

        try{
            if(setEndpoint('changeEndpoint')){
                $newLocation = $client->__setLocation(setEndpoint('endpoint'));
            }
            
            $response = $client->processShipment($request); // FedEx web service invocation
        
            if ($response->HighestSeverity != 'FAILURE' && $response->HighestSeverity != 'ERROR'){
                
                $data = [];
                $data['notifications'] = $response->Notifications;
                $data['job_id']= $response->JobId;
                $data['master_tracking_id'] = $response->CompletedShipmentDetail->MasterTrackingId;
                $data['label_pdf'] = base64_encode($response->CompletedShipmentDetail->CompletedPackageDetails->Label->Parts->Image);


                $res = new ResultadoEnvio();

                $res->data          = json_encode($response);
                $res->jobId         = $data['job_id'];
                $res->envioCode     = $data['master_tracking_id']->TrackingNumber;
                $res->trakingNumber = $data['master_tracking_id']->TrackingNumber;
                $res->envioCode2    = $data['master_tracking_id']->FormId;
                $res->tipoEmpaque   = $servicePacking;
                $res->tipoServicio  = $model->tipo_servicio;
                $res->etiqueta      = $data['label_pdf'];

                return $res;


            }else{
                printError($client, $response);
                $res = new ResultadoEnvio();
                $res->isError       = true;
                if(is_array( $response->Notifications)){
                    $res->errorMessage = "";
                    foreach($response->Notifications as $not){
                        $res->errorMessage  .= " - " . $not->Message;
                    }
                }else{
                    $res->errorMessage  = $response->Notifications->Message;
                }
                $res->data          = json_encode($response);
                return $res;
            }
        
            writeToLog($client);    // Write to log file
        } catch (\SoapFault $exception) {
            printFault($exception, $client);
            error_log($client->__getLastRequest());       
            error_log($client->__getLastResponse());  
        }
    }


    


    /**
     * Compra un documento de FEDEX
     */
    private function comprarFedexDocumento(CompraEnvio $model){
        $fedex = new FedexServices();
        $response = $fedex->comprarEnvioDocumento($model);

        $model->data = json_encode($response);
        $data = [];
        $data['notifications'] = $response->Notifications;
        $data['job_id']= $response->JobId;
        $data['master_tracking_id'] = $response->CompletedShipmentDetail->MasterTrackingId;
        $data['label_pdf'] = base64_encode($response->CompletedShipmentDetail->CompletedPackageDetails->Label->Parts->Image);

        $model->envio_code = $data['master_tracking_id']->TrackingNumber;
        $model->envio_code_2 = $data['master_tracking_id']->FormId;
        $model->envio_label = $data['label_pdf'];

        return $model;
    }



    private function comprarFedexPaquete(CompraEnvio $model){
        $fedex = new FedexServices();
        $response = $fedex->comprarEnvioPaquete($model);

        $model->data = json_encode($response);
        $data = [];
        $data['notifications'] = $response->Notifications;
        $data['job_id']= $response->JobId;
        $data['master_tracking_id'] = $response->CompletedShipmentDetail->MasterTrackingId;
        $data['label_pdf'] = base64_encode($response->CompletedShipmentDetail->CompletedPackageDetails->Label->Parts->Image);

        $model->envio_code = $data['master_tracking_id']->TrackingNumber;
        $model->envio_code_2 = $data['master_tracking_id']->FormId;
        $model->envio_label = $data['label_pdf'];

        return $model;
    }




//---------------- FUNCIONES DE NEGOCIO DE FEDEX -------------------------
    /**
     * Configura los datos del proveedor
     */
    private function configClientRequest(){
        $request['WebAuthenticationDetail'] = array(
            'ParentCredential' => array(
                'Key' => $this::FEDEX_PARENT_KEY, 
                'Password' => $this::FEDEX_PARENT_PASSWORD
            ),
            'UserCredential' => array(
                'Key' => $this::FEDEX_KEY, 
                'Password' => $this::FEDEX_PASSWORD
            )
        );
        
        $request['ClientDetail'] = array(
            'AccountNumber' => $this::FEDEX_SHIP_ACCOUNT, 
            'MeterNumber' => $this::FEDEX_METER
        );

        return $request;
    }

    private function addShipper($cp, $countryCode , $city=null, $stateProvinceCode=null){

        $shipper = array(
            'Contact' => array(
                'PersonName' => 'Sender Name',
                'CompanyName' => 'Sender Company Name',
                'PhoneNumber' => '9012638716'
            ),
            'Address' => array(
                'StreetLines' => array('Address Line 1'),
                //'City' => 'Mexico',
                'StateOrProvinceCode' => 'EM',
                'PostalCode' => $cp,
                'CountryCode' => $countryCode
            )
        );

        if($city && $stateProvinceCode){
            $shipper['Address'] = array(
                'StreetLines' => array('Address Line 1'),
                'City' => $city,
                'StateOrProvinceCode' => $stateProvinceCode,
                'PostalCode' => $cp,
                'CountryCode' => $countryCode
            );
        }

        return $shipper;
    }

    private function addRecipient($cp, $countryCode,$city=null, $stateProvinceCode=null,$personName=null,$phoneNumber=null, $addressLine = null,$companyName=null){
        $recipient = array(
            'Contact' => array(
                'PersonName' => ($personName)?'Recipient Name':$personName,
                'CompanyName' => ($companyName)?'Company Name':$companyName,
                'PhoneNumber' => ($phoneNumber)?'9012637906':$phoneNumber,
            ),
            'Address' => array(
                'StreetLines' => array(($addressLine)?'Address Line 1':$addressLine),
                'City' => ($city)?'Mexico':$city,
                'StateOrProvinceCode' => ($stateProvinceCode)?'DF':$stateProvinceCode,
                'PostalCode' => $cp,
                'CountryCode' => $countryCode,
                'Residential' => false
            )
        );

        if($personName != null){
            $recipient['Contact'] = array(
                'PersonName' => $personName,
                'CompanyName' => $companyName,
                'PhoneNumber' => $phoneNumber
            );
        }

        if($city && $stateProvinceCode){
            $recipient['Address'] = array(
                'StreetLines' => array($addressLine),
                'City' => $city,
                'StateOrProvinceCode' => $stateProvinceCode,
                'PostalCode' => $cp,
                'CountryCode' => $countryCode
            );
        }
        return $recipient;	                                    
    }


    
    private function addPackageLineItem($pesoKg, $largoCm,$anchoCm,$altoCm){
        $packageLineItem = array(
            'SequenceNumber'=>1,
            'GroupPackageCount'=>1,
            'Weight' => array(
                'Value' => $pesoKg,
                'Units' => 'KG'
            ),
            'Dimensions' => array(
                'Length' => $largoCm,
                'Width' => $anchoCm,
                'Height' => $altoCm,
                'Units' => 'CM'
            )
        );
        return $packageLineItem;
    }

    private function addShippingChargesPayment(){
        $shippingChargesPayment = [
            'PaymentType' => 'SENDER', // valid values RECIPIENT, SENDER and THIRD_PARTY
            'Payor' => [
                'ResponsibleParty' => [
                    'AccountNumber' => getProperty('billaccount'),
                    'CountryCode' => 'MX'
                ]
            ]
        ];
        return $shippingChargesPayment;
    }

    private function addLabelSpecification(){
        $labelSpecification = array(
            'LabelFormatType' => 'COMMON2D', // valid values COMMON2D, LABEL_DATA_ONLY
            'ImageType' => 'PDF',  // valid values DPL, EPL2, PDF, ZPLII and PNG
            'LabelStockType' => 'PAPER_7X4.75'
        );
        return $labelSpecification;
    }

}

?>