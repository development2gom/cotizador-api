<?php

namespace app\_360Utils\Services;

use Yii;

use app\_360Utils\Entity\Cotizacion;
use app\_360Utils\Entity\CompraEnvio;
use app\_360Utils\Entity\ResultadoEnvio;
use app\_360Utils\shipmentCarriers\dhl\entity\Quote;
use app\_360Utils\shipmentCarriers\dhl\entity\PieceType;
use app\_360Utils\Entity\CotizacionRequest;

class DhlServices{

    const DHL_PASSWORD  = "pg1sdVo1Ug";
    const DHL_SITE_ID   = "v62_iUniuQkBB5";
    const DHL_END_POINT = "https://xmlpitest-ea.dhl.com/XMLShippingServlet";

    const DHL_SHIPPER_ACCOUNT_NUMBER = "753871175";
    const DHL_SHIPPING_PAYMENT_TYPE = "S";
    const DHL_BILLING_ACCOUNT_NUMBER = "753871175";
    const DHL_DUTY_PAYMENT_TYPE = "S";
    const DHL_DUTY_ACCOUNT_NUMBER = "753871175";
    const DHL_SHIPPER_ID = "751008818";
    const DHL_REGISTER_ACCOUNT = "751008818";

    const DHL_WS_DCT_REQUEST = ' xmlns:p="http://www.dhl.com" xmlns:p1="http://www.dhl.com/datatypes" xmlns:p2="http://www.dhl.com/DCTRequestdatatypes" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.dhl.com DCT-req.xsd">';
    const DHL_WS_SHIPMENT_REQUEST = 'xmlns:req="http://www.dhl.com" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" schemaVersion="5.0" xsi:schemaLocation="http://www.dhl.com ship-val-global-req.xsd">';


    


    function cotizarEnvioDocumento(CotizacionRequest $cotiacion){
        return $this->_cotizarEnvioInterno($cotizacion);
    }

    /**
     * Cotiza el envio de uno o más paquetes
     */
    function cotizarEnvioPaquete(CotizacionRequest $cotizacion){
        return $this->_cotizarEnvioInterno($cotizacion);
    }



    
    function comprarEnvioPaquete(CompraEnvio $model){
        return $this->_comprarEnvioInterno($model, true);
    }

    function comprarEnvioSobre(CompraEnvio $model){
        return $this->_comprarEnvioInterno($model, false);
    }

      
    //---------------- COMPRA DEL ENVIO ---------------------
    
    private function _comprarEnvioInterno(CompraEnvio $model, $isPaquete){
        
        $request = $this->createShipmentRequest($model,$isPaquete);
        $res = $this->callWebService($request, false);
        $xml = simplexml_load_string($res);
        $json = json_encode($xml);
        $array = json_decode($json,TRUE);

        //Error en el request
        if(isset($array['Response']) && isset($array['Response']['Status'])){
            $errorMsg = $array['Response']['Status']['ActionStatus'];

            return null;
        }
        

        $resultado = [];

        $res = new ResultadoEnvio();
        $res->data           = json_encode($json);
        $res->jobId          = $array['AirwayBillNumber']; //<Pieces><Piece><LicensePlate>
        $res->envioCode      = $array['DHLRoutingCode'];
        $res->envioCode2     = $array['DHLRoutingDataId'];
        $res->tipoEmpaque    = "servicePacking";
        $res->tipoServicio   = $array['ProductShortName'];
        $res->etiqueta       = $array['LabelImage']['OutputImage'];//<Pieces><Piece><LicensePlateBarCode>
        $res->etiquetaFormat = "PDF";

        array_push($resultado,$res);

        return $resultado;

    }

    /**
     * Crea el objeto para la compra del servicio
     */
    private function createShipmentRequest(CompraEnvio $model, $isPaquete){
        $request = [];
        $request['Request']['ServiceHeader'] = [];
        $request['Request']['ServiceHeader']['MessageTime'] = '2019-01-10T11:28:56.000-08:00';
        $request['Request']['ServiceHeader']['MessageReference'] = '1234567890123456789012345678901';
        $request['Request']['ServiceHeader']['SiteID'] = self::DHL_SITE_ID;
        $request['Request']['ServiceHeader']['Password'] = self::DHL_PASSWORD;

        $request['RegionCode'] = "AM";
        $request['RequestedPickupTime'] = "N";
        $request['NewShipper'] = "Y";
        $request['LanguageCode'] = "es";
        $request['PiecesEnabled'] = "Y" ;

        $request['Billing'] = [];
        $request['Billing']['ShipperAccountNumber'] = self::DHL_SHIPPER_ACCOUNT_NUMBER;
        $request['Billing']['ShippingPaymentType'] = self::DHL_SHIPPING_PAYMENT_TYPE;
        $request['Billing']['BillingAccountNumber'] = self::DHL_BILLING_ACCOUNT_NUMBER;
        $request['Billing']['DutyPaymentType'] = self::DHL_DUTY_PAYMENT_TYPE;
        $request['Billing']['DutyAccountNumber'] = self::DHL_DUTY_ACCOUNT_NUMBER;

        //Identifies the consignee(receiver) of the shipment
        $request['Consignee'] = [];
        $request['Consignee']['CompanyName'] = $model->destino_compania;
        
        //$request['Consignee']['AddressLine'] = [];
        $addr = str_split($model->destino_direccion, 30);
        foreach($addr as $item){
            $addr = [];
            $addr['AddressLine'] = $item;
            array_push($request['Consignee'], $addr);
        }
        
        
        $request['Consignee']['City'] = $model->destino_ciudad;
        $request['Consignee']['PostalCode'] = $model->destino_cp;
        $request['Consignee']['CountryCode'] = $model->destino_pais;
        $request['Consignee']['CountryName'] = $model->destino_pais;

        $request['Consignee']['Contact'] = [];
        $request['Consignee']['Contact']['PersonName'] = $model->destino_nombre_persona;
        $request['Consignee']['Contact']['PhoneNumber'] = $model->destino_telefono;
        $request['Consignee']['Contact']['PhoneExtension'] = "";
        $request['Consignee']['Contact']['FaxNumber'] = $model->destino_telefono;
        $request['Consignee']['Contact']['Telex'] = $model->destino_telefono;
        $request['Consignee']['Contact']['Email'] = $model->destino_correo;

        $request['Commodity'] = [];
        $request['Commodity']['CommodityCode'] = "cc";
        $request['Commodity']['CommodityName'] = "cn";

        $request['Dutiable'] = [];
        $request['Dutiable']['DeclaredValue'] = 200;
        $request['Dutiable']['DeclaredCurrency'] = "MXN";
        $request['Dutiable']['ScheduleB'] = "3002905110";
        $request['Dutiable']['ExportLicense'] = "D123456";
        $request['Dutiable']['ShipperEIN'] = "112233445566";
        $request['Dutiable']['ShipperIDType'] = "S";
        $request['Dutiable']['ImportLicense'] = "ImportLic";
        $request['Dutiable']['ConsigneeEIN'] = "ConEIN2123";
        $request['Dutiable']['TermsOfTrade'] = "DAP";

        $request['Reference'] = [];
        $request['Reference']['ReferenceID'] = "AM international shipment";
        $request['Reference']['ReferenceType'] = "St";

        $request['ShipmentDetails'] = [];
        $request['ShipmentDetails']['NumberOfPieces'] = count($model->paquetes);
        $request['ShipmentDetails']['Pieces'] = [];

        $index = 1;
        foreach($model->paquetes as $item){
            $largo = $item->largo;
            $ancho = $item->ancho;
            $alto = $item->alto;
            $peso = $item->peso;
            array_push($request['ShipmentDetails']['Pieces'], $this->getPieceRequest($index++,$alto,$ancho,$largo,$peso));
        }

        $request['ShipmentDetails']['Weight'] = $model->getTotalWeight();
        $request['ShipmentDetails']['WeightUnit'] = "K";
        $request['ShipmentDetails']['GlobalProductCode'] = "P";
        $request['ShipmentDetails']['LocalProductCode'] = "P";
        $request['ShipmentDetails']['Date'] = $model->fecha;
        $request['ShipmentDetails']['Contents'] = "AM international shipment contents";
        $request['ShipmentDetails']['DoorTo'] = "DD"; //DD (Door to Door, DA (Door to Airport), AA (Airport to Airport), DC (Door to Door non-Compliant)
        $request['ShipmentDetails']['DimensionUnit'] = "C";
        $request['ShipmentDetails']['InsuredAmount'] = $model->valorSeguro;
        
        if($isPaquete){
            $request['ShipmentDetails']['PackageType'] = "YP"; // EE - DHL Express Envelope,YP - Your packaging
        }else{
            $request['ShipmentDetails']['PackageType'] = "EE"; // EE - DHL Express Envelope,YP - Your packaging
        }
        
        $request['ShipmentDetails']['IsDutiable'] = "Y";
        $request['ShipmentDetails']['CurrencyCode'] = "MXN";

        $request['Shipper'] = [];
        $request['Shipper']['ShipperID'] = self::DHL_SHIPPER_ID ;
        $request['Shipper']['CompanyName'] = $model->origen_compania ;
        $request['Shipper']['RegisteredAccount'] = self::DHL_REGISTER_ACCOUNT ;


        $addr = str_split($model->origen_direccion, 30);
        foreach($addr as $item){
            $addr = [];
            $addr['AddressLine'] = $item;
            array_push($request['Shipper'], $addr);
        }

        
        $request['Shipper']['City'] = $model->origen_ciudad ;
        //$request['Shipper']['Division'] = "" ;
        //$request['Shipper']['DivisionCode'] = "";
        $request['Shipper']['PostalCode'] = $model->origen_cp ;
        $request['Shipper']['CountryCode'] = $model->origen_pais ;
        $request['Shipper']['CountryName'] = $model->origen_pais ;

        $request['Shipper']['Contact'] = [];
        $request['Shipper']['Contact']['PersonName'] = $model->origen_nombre_persona ;
        $request['Shipper']['Contact']['PhoneNumber'] = $model->origen_telefono ;
        $request['Shipper']['Contact']['PhoneExtension'] = "" ;
        $request['Shipper']['Contact']['FaxNumber'] = $model->origen_telefono ;
        $request['Shipper']['Contact']['Telex'] = "" ;
        $request['Shipper']['Contact']['Email'] = $model->origen_correo ;

        $request['LabelImageFormat'] = "PDF";

        return $request;
    }

    //---------------- COTIZACION DEL ENVIO ---------------------
    private function _cotizarEnvioInterno(CotizacionRequest $cotizacion){
        
        $request = $this->getCotizarRequest($cotizacion);
        
        $res = $this->callWebService($request, true);
        $xml = simplexml_load_string($res);
        $json = json_encode($xml);
        $array = json_decode($json,TRUE);

       


        //Error en el request
        if(isset($array['Response']) && isset($array['Response']['Status'])){
            $errorMsg = $array['Response']['Status']['ActionStatus'];

            return null;
        }

        $listaServicios = $array['GetQuoteResponse']['BkgDetails']['QtdShp'];
        
        
        //Crea la lista de opciones de respuesta
        if(isset($listaServicios['ShippingCharge'])){
            $res = [];
            $item = $listaServicios;
            $cotizacion = new Cotizacion();
            $cotizacion->provider = "DHL";
            $cotizacion->price = $item['ShippingCharge'];
            $cotizacion->tax = $item['TotalTaxAmount'];;
            $cotizacion->serviceType = $item['LocalProductName'];
            $cotizacion->deliveryDate = $item['DeliveryDate'];
            $cotizacion->deliveryByTime = $item['DeliveryTime'];
            $cotizacion->deliveryDateStr = $item['DeliveryDate'] . ' ' . $item['DeliveryTime'];
            $cotizacion->currency = "MXN";
            $cotizacion->data = $json;
            $cotizacion->servicePacking = "";
            $cotizacion->serviceTypeStr = $item['ProductShortName'];

            array_push($res,$cotizacion);
        }else{
            $res = $this->parseQuoteResponseMultiplePakages($listaServicios, $json);
        }

        return $res;
    }

    /**
     * Genera el arreglo de la cotizacion
     */
    private function getCotizarRequest(CotizacionRequest $cotizacion){
        $request = [];
        $request['GetQuote'] = [];

        $request['GetQuote']['Request'] = [];

        $request['GetQuote']['Request']['ServiceHeader'] = [];
        $request['GetQuote']['Request']['ServiceHeader']['MessageTime'] = '2019-01-10T11:28:56.000-08:00';
        $request['GetQuote']['Request']['ServiceHeader']['MessageReference'] = '1234567890123456789012345678901';
        $request['GetQuote']['Request']['ServiceHeader']['SiteID'] = self::DHL_SITE_ID;
        $request['GetQuote']['Request']['ServiceHeader']['Password'] = self::DHL_PASSWORD;

        $request['GetQuote']['From'] = $this->getAddr($cotizacion->destinoCountry,$cotizacion->origenCP);

        $request['GetQuote']['BkgDetails'] = [];

        $request['GetQuote']['BkgDetails']['PaymentCountryCode'] = "MX";
        $request['GetQuote']['BkgDetails']['Date'] = date("Y-m-d") ; //$cotizacion->fecha; //"2019-01-10";
        $request['GetQuote']['BkgDetails']['ReadyTime'] = "PT10H21M";
        $request['GetQuote']['BkgDetails']['ReadyTimeGMTOffset'] = "-06:00";
        $request['GetQuote']['BkgDetails']['DimensionUnit'] = "CM";
        $request['GetQuote']['BkgDetails']['WeightUnit'] = "KG";

        $request['GetQuote']['BkgDetails']['Pieces'] = [];

        $index = 1;
        foreach($cotizacion->paquetes as $item){
            $largo = $item->largo;
            $ancho = $item->ancho;
            $alto = $item->alto;
            $peso = $item->peso;
            array_push($request['GetQuote']['BkgDetails']['Pieces'], $this->getPiece($index++,$alto,$ancho,$largo,$peso));
        }

        $request['GetQuote']['BkgDetails']['PaymentAccountNumber'] = 'CASHSIN';
        $request['GetQuote']['BkgDetails']['IsDutiable'] = 'N';
        $request['GetQuote']['BkgDetails']['NetworkTypeCode'] = 'AL';

        /*
        $request['GetQuote']['BkgDetails']['QtdShp'] = [];
        $request['GetQuote']['BkgDetails']['QtdShp']['GlobalProductCode'] = 'D';
        $request['GetQuote']['BkgDetails']['QtdShp']['LocalProductCode'] = 'D';
        */
        /*
        $request['GetQuote']['BkgDetails']['QtdShp']['QtdShpExChrg'] = [];
        $request['GetQuote']['BkgDetails']['QtdShp']['QtdShpExChrg']['SpecialServiceType'] = [];
        $request['GetQuote']['BkgDetails']['QtdShp']['QtdShpExChrg']['SpecialServiceType'] = 'AA';
        */

        $request['GetQuote']['To'] = $this->getAddr($cotizacion->destinoCountry,$cotizacion->destinoCP);
       
        $request['GetQuote']['Dutiable'] = [];
        $request['GetQuote']['Dutiable']['DeclaredCurrency'] = "MXN";
        $request['GetQuote']['Dutiable']['DeclaredValue'] = $cotizacion->valorDeclarado;

        return $request;
    }

    private function parseQuoteResponseMultiplePakages($listaServicios, $json){
        $res = [];
        foreach($listaServicios as $item){

            $cotizacion = new Cotizacion();
            $cotizacion->provider = "DHL";
            $cotizacion->price = $item['ShippingCharge'];
            $cotizacion->tax = $item['TotalTaxAmount'];;
            $cotizacion->serviceType = $item['LocalProductName'];
            $cotizacion->deliveryDate = $item['DeliveryDate'];
            $cotizacion->deliveryByTime = $item['DeliveryTime'];
            $cotizacion->deliveryDateStr = $item['DeliveryDate'] . ' ' . $item['DeliveryTime'];
            $cotizacion->currency = "MXN";
            $cotizacion->data = $json;
            $cotizacion->servicePacking = "";
            $cotizacion->serviceTypeStr = $item['ProductShortName'];

            array_push($res,$cotizacion);
            
        }
        return $res;
    }

    //----------- UTILUDADES -------------------------

    private function getPiece($index,$alto,$ancho,$largo,$peso){
        $res = [];
        $res['Piece']=[];
        $res['Piece']['PieceID'] = $index;
        $res['Piece']['Height'] = $alto;
        $res['Piece']['Depth'] = $ancho;
        $res['Piece']['Width'] = $largo;
        $res['Piece']['Weight'] = $peso;

        return $res;
    }

    private function getPieceRequest($index,$alto,$ancho,$largo,$peso){
        $res = [];
        $res['Piece']=[];
        $res['Piece']['PieceID'] = $index;
        $res['Piece']['Weight'] = $peso;
        $res['Piece']['Width'] = $largo;
        $res['Piece']['Height'] = $alto;
        $res['Piece']['Depth'] = $ancho;
        
        return $res;
    }
    

    private function getAddr($cvePais, $cp){
        $res = [];
        $res['CountryCode'] = $cvePais;
        $res['Postalcode'] = $cp;

        return $res;
    }

    public function callWebService($request, $isCotizacion){
        if (!$ch = curl_init())
        {
            throw new \Exception('could not initialize curl');
        }

        if($isCotizacion){
            $xml = $this->generateValidXmlFromArray($request,'p:DCTRequest');
        }else{
            $xml = $this->generateValidXmlFromArray($request,'req:ShipmentRequest');
        }

        $xml = str_replace("<node>" ,"",$xml);
        $xml = str_replace("</node>" ,"",$xml);
        

        error_log($xml);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, self::DHL_END_POINT);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_PORT , 443);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $result = curl_exec($ch);
        error_log($result);
        
        if (curl_error($ch)){
            return false;
        }
        else {
            curl_close($ch);
        }

        return $result;
    }


    public static function generateValidXmlFromObj( $obj, $node_block='nodes', $node_name='node') {
        $arr = get_object_vars($obj);
        return self::generateValidXmlFromArray($arr, $node_block, $node_name);
    }


    

    /**
     * Genera el XML a partir del arreglo
     */
    public static function generateValidXmlFromArray($array, $node_block='nodes', $node_name='node') {
        $xml = '<?xml version="1.0" encoding="UTF-8" ?>';

        $xml .= '<' . $node_block . ' ';
        if($node_block == "p:DCTRequest"){
            $xml .= self::DHL_WS_DCT_REQUEST;
        }else{
            $xml .= self::DHL_WS_SHIPMENT_REQUEST;
        }
        $xml .= self::generateXmlFromArray($array, $node_name);
        $xml .= '</' . $node_block . '>';

        return $xml;
    }

    private static function generateXmlFromArray($array, $node_name) {
        $xml = '';

        if (is_array($array) || is_object($array)) {
            foreach ($array as $key=>$value) {
                if (is_numeric($key)) {
                    $key = $node_name;
                }

                $xml .= '<' . $key . '>' . self::generateXmlFromArray($value, $node_name) . '</' . $key . '>';
            }
        } else {
            $xml = htmlspecialchars($array, ENT_QUOTES);
        }

        return $xml;
    }

    public function toXML(\XMLWriter $xmlWriter = null)
    {
        $this->validateParameters();

        $xmlWriter = new \XMLWriter();
        $xmlWriter->openMemory();
        $xmlWriter->setIndent(true);
        $xmlWriter->startDocument('1.0', 'UTF-8');
            
        $xmlWriter->startElement('req:' . $this->_serviceName);
        $xmlWriter->writeAttribute('xmlns:req', self::DHL_REQ);
        $xmlWriter->writeAttribute('xmlns:xsi', self::DHL_XSI);
        $xmlWriter->writeAttribute('xsi:schemaLocation', self::DHL_REQ . ' ' .$this->_serviceXSD);
    
        if ($this->_displaySchemaVersion) 
        {
            $xmlWriter->writeAttribute('schemaVersion', $this->_schemaVersion);
        }

        if (null !== $this->_xmlNodeName) 
        {
            $xmlWriter->startElement($this->_xmlNodeName);
        }

        $xmlWriter->startElement('Request');
        $xmlWriter->startElement('ServiceHeader');
        foreach ($this->_headerParams as $name => $infos) 
        {
            $xmlWriter->writeElement($name, $this->$name);
        }
        $xmlWriter->endElement(); // End of Request
        $xmlWriter->endElement(); // End of ServiceHeader

        foreach ($this->_bodyParams as $name => $infos) 
        {
            if ($this->$name)
            {
                if (is_object($this->$name)) 
                {
                    $this->$name->toXML($xmlWriter);
                }
                elseif (is_array($this->$name)) 
                {
                    if ('string' == $this->_params[$name]['type'])
                    {
                        foreach ($this->$name as $subelement)
                        {
                            $xmlWriter->writeElement($name, $subelement);
                        }
                    }
                    else
                    {
                        if (!isset($this->_params[$name]['disableParentNode']) || false == $this->_params[$name]['disableParentNode']) 
                        {              
                            $xmlWriter->startElement($name);
                        }

                        foreach ($this->$name as $subelement) 
                        {
                            $subelement->toXML($xmlWriter);
                        }

                        if (!isset($this->_params[$name]['disableParentNode']) || false == $this->_params[$name]['disableParentNode']) 
                        {              
                            $xmlWriter->endElement();
                        }
                    }
                }
                else
                {
                    $xmlWriter->writeElement($name, $this->$name);
                }
            }
        }

        $xmlWriter->endElement(); // End of parent node

        // End of class name tag
        if (null !== $this->_xmlNodeName) 
        {
            $xmlWriter->endElement();
        }

        $xmlWriter->endDocument();
    
        return $xmlWriter->outputMemory(true);
    }
}

?>