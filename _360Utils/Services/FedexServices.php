<?php


namespace app\_360Utils\Services;

use Yii;
use app\models\WrkDatosCompras;
use app\_360Utils\Cotizacion;

class FedexServices{


    const FEDEX_KEY             = 'VY3a8M7siRPxvdOf';
    const FEDEX_PASSWORD        = 'W48oom2vQa4Sqt9tn1kuP7ihk'; 
    const FEDEX_PARENT_PASSWORD = 'XXX';
	const FEDEX_PARENT_KEY      = 'VY3a8M7siRPxvdOf'; 
	const FEDEX_SHIP_ACCOUNT    = '510088000';
	const FEDEX_BILL_ACCOUNT    = '510088000';
	const FEDEX_LOCATION_ID     = 'PLBA';
	const FEDEX_METER           = '119037066';



    function disponibilidadDocumento($origenCP,$origenCountry,$destinoCP,$destinoCountry,$fecha){
        //Corresponde a un documento
        return $this->disponibilidad($origenCP,$origenCountry,$destinoCP,$destinoCountry,$fecha , 'FEDEX_ENVELOPE');
    }


    function disponibilidadPaquete($origenCP,$origenCountry,$destinoCP,$destinoCountry,$fecha){
        //Corresponde a un paquete
        return $this->disponibilidad($origenCP,$origenCountry,$destinoCP,$destinoCountry,$fecha , 'YOUR_PACKAGING');
    }



    private function disponibilidad($origenCP,$origenCountry,$destinoCP,$destinoCountry,$fecha,$servicePacking){
        require_once(Yii::getAlias('@app') . '/_360Utils/shipment-carriers/fedex/fedex-common.php');
            $path_to_wsdl = Yii::getAlias('@app') . '/_360Utils/shipment-carriers/fedex/wsdl/ValidationAvailabilityAndCommitmentService_v8.wsdl';
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
                'PostalCode' => $origenCP, // Origin details
                'CountryCode' => $origenCountry
            );
            $request['Destination'] = array(
                'PostalCode' => $destinoCP, // Destination details
                'CountryCode' => $destinoCountry
            );
            $request['ShipDate'] = $fecha;
            $request['CarrierCode'] = 'FDXE'; // valid codes FDXE-Express, FDXG-Ground, FDXC-Cargo, FXCC-Custom Critical and FXFR-Freight
            //$request['Service'] = 'PRIORITY_OVERNIGHT'; // valid code STANDARD_OVERNIGHT, PRIORITY_OVERNIGHT, FEDEX_GROUND, ...
            $request['Packaging'] = $servicePacking;//$json->service_packing; // valid code FEDEX_BOX, FEDEX_PAK, FEDEX_TUBE, YOUR_PACKAGING, ...


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
                
            } catch (SoapFault $exception) {
            printFault($exception, $client);        
            }
    }


    function cotizarEnvioDocumento($serviceType, $origenCP,$origenCountry,$destinoCP,$destinoCountry,$fecha, $peso, $montoSeguro = false){
        //Cotiza un envio de documento
        return $this->cotizarEnvio($serviceType, $origenCP,$origenCountry,$destinoCP,$destinoCountry,$fecha, 'FEDEX_ENVELOPE', $peso, 0,0,0, $montoSeguro );
    }

    function cotizarEnvioPaquete($serviceType, $origenCP,$origenCountry,$destinoCP,$destinoCountry,$fecha, $peso, $largo, $ancho, $alto, $montoSeguro = false){
        //Cotiza un envio de documento
        return $this->cotizarEnvio($serviceType, $origenCP,$origenCountry,$destinoCP,$destinoCountry,$fecha, 'YOUR_PACKAGING', $peso, $largo, $ancho, $alto, $montoSeguro );
    }



    private function cotizarEnvio($serviceType, $origenCP,$origenCountry,$destinoCP,$destinoCountry,$fecha, $servicePacking, $peso, $largo = 0, $ancho = 0, $alto = 0, $montoSeguro = false){

        $preferedCurrency = 'MXN';
        $pickUp = 'REGULAR_PICKUP';
       
    

        require_once(Yii::getAlias('@app') . '/_360Utils/shipment-carriers/fedex/fedex-common.php');
        $path_to_wsdl = Yii::getAlias('@app') . '/_360Utils/shipment-carriers/fedex/wsdl/RateService_v22.wsdl';
        ini_set("soap.wsdl_cache_enabled", "0");

        $client = new \SoapClient($path_to_wsdl, array('trace' => 1));
        $request = $this->configClientRequest();

        $request['TransactionDetail'] = array('CustomerTransactionId' => ' *** Rate Request using PHP ***');
        $request['Version'] = array(
            'ServiceId' => 'crs', 
            'Major' => '22', 
            'Intermediate' => '0', 
            'Minor' => '0'
        );

        $request['ReturnTransitAndCommit']                  = true;
        $request['RequestedShipment']['DropoffType']        = $pickUp; // valid values REGULAR_PICKUP, REQUEST_COURIER, ...
        $request['RequestedShipment']['ShipTimestamp']      = $fecha;
        $request['RequestedShipment']['ServiceType']        = $serviceType; // valid values STANDARD_OVERNIGHT, PRIORITY_OVERNIGHT, FEDEX_GROUND, ...
        $request['RequestedShipment']['PackagingType']      = $servicePacking; // valid values FEDEX_BOX, FEDEX_PAK, FEDEX_TUBE, YOUR_PACKAGING, ...
        $request['RequestedShipment']['PreferredCurrency']  = $preferedCurrency;
        $request['RequestedShipment']['RateRequestTypes']   = 'PREFERRED';        

        
        if($montoSeguro){
            $request['RequestedShipment']['TotalInsuredValue']=array(
                'Ammount'=>$montoSeguro,
                'Currency'=>$preferedCurrency
            );
        }
        

        $request['RequestedShipment']['Shipper']    = $this->addShipper($origenCP, $origenCountry);
        $request['RequestedShipment']['Recipient']  = $this->addRecipient($destinoCP, $destinoCountry);
        //$request['RequestedShipment']['ShippingChargesPayment'] = $this->addShippingChargesPayment();
        $request['RequestedShipment']['PackageCount'] = '1';
        $request['RequestedShipment']['RequestedPackageLineItems'] = $this->addPackageLineItem($peso, $largo, $ancho, $alto);

        try {
            if(setEndpoint('changeEndpoint')){
                $newLocation = $client->__setLocation(setEndpoint('endpoint'));
            }
            
            $response = $client->getRates($request);
                
            if ($response -> HighestSeverity != 'FAILURE' && $response -> HighestSeverity != 'ERROR'){  
                
                $rateReply = $response->RateReplyDetails;

                //Precio y moneda
                if($rateReply->RatedShipmentDetails && is_array($rateReply->RatedShipmentDetails)){
                    $amount   = number_format($rateReply->RatedShipmentDetails[0]->ShipmentRateDetail->TotalNetCharge->Amount,2,".",",") ;
                    $tax      = number_format($rateReply->RatedShipmentDetails[0]->ShipmentRateDetail->TotalTaxes->Amount,2,".",",") ;
                    $currency = $rateReply->RatedShipmentDetails[0]->ShipmentRateDetail->TotalNetCharge->Currency;
                }elseif($rateReply->RatedShipmentDetails && ! is_array($rateReply->RatedShipmentDetails)){
                    $amount   = number_format($rateReply->RatedShipmentDetails->ShipmentRateDetail->TotalNetCharge->Amount,2,".",",") ;
                    $tax      = number_format($rateReply->RatedShipmentDetails->ShipmentRateDetail->TotalTaxes->Amount,2,".",",") ;
                    $currency = $rateReply->RatedShipmentDetails->ShipmentRateDetail->TotalNetCharge->Currency;
                }


               
                //Fecha de entrega
                if(array_key_exists('DeliveryTimestamp',$rateReply)){
                    $deliveryDate=  $rateReply->DeliveryTimestamp ;
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



                return $cotizacion;
            }else{
                return false;
            } 
            
        } catch (SoapFault $exception) {
           printFault($exception, $client); 
           return false;       
        }
    }



    function comprarEnvioDocumento(WrkDatosCompras $model){
        return $this->comprarEnvio($model,'FEDEX_ENVELOPE');
    }

    function comprarEnvioPaquete(WrkDatosCompras $model){
        return $this->comprarEnvio($model,'YOUR_PACKAGING');
    }

    private function comprarEnvio(WrkDatosCompras $model, $servicePacking){

        //$servicePacking = 'FEDEX_ENVELOPE';
        $preferedCurrency = 'MXN';
        $pickUp = 'REGULAR_PICKUP';
        $peso = $model->txt_peso;
        $largo = $model->txt_largo;
        $ancho = $model->txt_ancho;
        $alto = $model->txt_alto;


        require_once(Yii::getAlias('@app') . '/_360Utils/shipment-carriers/fedex/fedex-common.php');
        $path_to_wsdl = Yii::getAlias('@app') . '/_360Utils/shipment-carriers/fedex/wsdl/ShipService_v21.wsdl';
        ini_set("soap.wsdl_cache_enabled", "0");

        $client = new \SoapClient($path_to_wsdl, array('trace' => 1));
        $request = $this->configClientRequest();

        $request['TransactionDetail'] = array('CustomerTransactionId' => '*** Express International Shipping Request using PHP ***');
        $request['Version'] = array(
            'ServiceId' => 'ship', 
            'Major' => '21', 
            'Intermediate' => '0', 
            'Minor' => '0'
        );
        $request['RequestedShipment'] = array(
            'ShipTimestamp' => date('c'),
            'DropoffType' => $pickUp, // valid values REGULAR_PICKUP, REQUEST_COURIER, DROP_BOX, BUSINESS_SERVICE_CENTER and STATION
            'ServiceType' => $model->txt_tipo_servicio, // valid values STANDARD_OVERNIGHT, PRIORITY_OVERNIGHT, FEDEX_GROUND, ...
            'PackagingType' => $servicePacking, // valid values FEDEX_BOX, FEDEX_PAK, FEDEX_TUBE, YOUR_PACKAGING, ...
            
            
            'Recipient' => $this->addRecipient(
                $model->txt_destino_cp,
                $model->txt_destino_pais,
                $model->txt_destino_ciudad,
                $model->txt_destino_estado,
                $model->txt_destino_nombre_persona,
                $model->txt_destino_telefono,
                $model->txt_destino_direccion,
                $model->txt_destino_compania
            ),
            'Shipper' => $this->addRecipient(
                $model->txt_origen_cp,
                $model->txt_origen_pais,
                $model->txt_origen_ciudad,
                $model->txt_origen_estado,
                $model->txt_origen_nombre_persona,
                $model->txt_origen_telefono,
                $model->txt_origen_direccion,
                $model->txt_origen_compania
            ),
       

            'ShippingChargesPayment' => $this->addShippingChargesPayment(),
            //'CustomsClearanceDetail' => addCustomClearanceDetail(),                                                                                                       
            'LabelSpecification' => $this->addLabelSpecification(),
            'CustomerSpecifiedDetail' => array(
                'MaskedData'=> 'SHIPPER_ACCOUNT_NUMBER'
            ), 
            'PackageCount' => 1,
                'RequestedPackageLineItems' => array(

                '0' => $this->addPackageLineItem($peso, $largo,$ancho,$alto)
            ),
            'CustomerReferences' => array(
                '0' => array(
                    'CustomerReferenceType' => 'CUSTOMER_REFERENCE', 
                    'Value' => 'TC007_07_PT1_ST01_PK01_SNDUS_RCPCA_POS'
                )
            )
        );



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

                //return $data;
                return $response;         
            }else{
                printError($client, $response);
            }
        
            writeToLog($client);    // Write to log file
        } catch (SoapFault $exception) {
            printFault($exception, $client);
        }
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
                'PersonName' => 'Recipient Name',
                'CompanyName' => 'Company Name',
                'PhoneNumber' => '9012637906'
            ),
            'Address' => array(
                'StreetLines' => array('Address Line 1'),
                'City' => 'Mexico',
                'StateOrProvinceCode' => 'DF',
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
        $shippingChargesPayment = array(
            'PaymentType' => 'SENDER', // valid values RECIPIENT, SENDER and THIRD_PARTY
            'Payor' => array(
                'ResponsibleParty' => array(
                    'AccountNumber' => getProperty('billaccount'),
                    'CountryCode' => 'MX'
                )
            )
        );
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