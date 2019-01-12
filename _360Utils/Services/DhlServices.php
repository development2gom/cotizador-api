<?php

namespace app\_360Utils\Services;

use Yii;

use app\_360Utils\Entity\Cotizacion;
use app\_360Utils\Entity\CompraEnvio;
use app\_360Utils\Entity\ResultadoEnvio;
use app\_360Utils\shipmentCarriers\dhl\entity\Quote;
use app\_360Utils\shipmentCarriers\dhl\entity\PieceType;

class DhlServices{

    const DHL_PASSWORD  = "pg1sdVo1Ug";
    const DHL_SITE_ID   = "v62_iUniuQkBB5";
    const DHL_END_POINT = "https://xmlpitest-ea.dhl.com/XMLShippingServlet";

    


    function cotizarEnvioDocumento($origenCP,$origenCountry,$destinoCP,$destinoCountry,$fecha, $paquetes, $montoSeguro = false){
        return $this->cotizarEnvio($origenCP,$origenCountry,$destinoCP,$destinoCountry,$fecha, $paquetes, false);
    }

    function cotizarEnvioPaquete($origenCP,$origenCountry,$destinoCP,$destinoCountry,$fecha, $paquetes, $montoSeguro = false){
        return $this->cotizarEnvio($origenCP,$origenCountry,$destinoCP,$destinoCountry,$fecha, $paquetes, true);
    }

           
    private function cotizarEnvio($origenCP,$origenCountry,$destinoCP,$destinoCountry,$fecha, $paquetes, $isPaquete){
        $request = [];
        $request['GetQuote'] = [];

        $request['GetQuote']['Request'] = [];

        $request['GetQuote']['Request']['ServiceHeader'] = [];
        $request['GetQuote']['Request']['ServiceHeader']['MessageTime'] = '2019-01-10T11:28:56.000-08:00';
        $request['GetQuote']['Request']['ServiceHeader']['MessageReference'] = '1234567890123456789012345678901';
        $request['GetQuote']['Request']['ServiceHeader']['SiteID'] = self::DHL_SITE_ID;
        $request['GetQuote']['Request']['ServiceHeader']['Password'] = self::DHL_PASSWORD;

        $request['GetQuote']['From'] = $this->getAddr($destinoCountry,$origenCP);

        $request['GetQuote']['BkgDetails'] = [];

        $request['GetQuote']['BkgDetails']['PaymentCountryCode'] = "MX";
        $request['GetQuote']['BkgDetails']['Date'] = $fecha; //"2019-01-10";
        $request['GetQuote']['BkgDetails']['ReadyTime'] = "PT10H21M";
        $request['GetQuote']['BkgDetails']['ReadyTimeGMTOffset'] = "-06:00";
        $request['GetQuote']['BkgDetails']['DimensionUnit'] = "CM";
        $request['GetQuote']['BkgDetails']['WeightUnit'] = "KG";

        $request['GetQuote']['BkgDetails']['Pieces'] = [];

        $index = 1;
        foreach($paquetes as $item){
            $largo = $item['num_largo'];
            $ancho = $item['num_largo'];
            $alto = $item['num_largo'];
            $peso = $item['num_peso'];
            //$index,$alto,$ancho,$largo,$peso
            $request['GetQuote']['BkgDetails']['Pieces'] = $this->getPiece($index,$alto,$ancho,$largo,$peso);
            $index++;
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

        $request['GetQuote']['To'] = $this->getAddr($destinoCountry,$destinoCP);
       
        $request['GetQuote']['Dutiable'] = [];
        $request['GetQuote']['Dutiable']['DeclaredCurrency'] = "MXN";
        $request['GetQuote']['Dutiable']['DeclaredValue'] = 0.00;

        
        $res = $this->callWebService($request);
        $xml = simplexml_load_string($res);
        $json = json_encode($xml);
        $array = json_decode($json,TRUE);

        //echo($json);


        //Error en el request
        if(isset($array['Response']) && isset($array['Response']['Status'])){
            $errorMsg = $array['Response']['Status']['ActionStatus'];

            return null;
        }

        $listaServicios = $array['GetQuoteResponse']['BkgDetails']['QtdShp'];
        
        
        //Crea la lista de opciones de respuesta
        $res = [];
        foreach($listaServicios as $item){

            $cotizacion = new Cotizacion();
            $cotizacion->provider = "DHL";
            $cotizacion->price = $item['ShippingCharge'];;
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

    private function getAddr($cvePais, $cp){
        $res = [];
        $res['CountryCode'] = $cvePais;
        $res['Postalcode'] = $cp;

        return $res;
    }

    public function callWebService($request){
        if (!$ch = curl_init())
        {
            throw new \Exception('could not initialize curl');
        }

        $xml = $this->generateValidXmlFromArray($request,'p:DCTRequest');

        error_log($xml);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, self::DHL_END_POINT);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_PORT , 443);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $result = curl_exec($ch);
        
        if (curl_error($ch))
        {
            return false;
        }
        else
        {
            curl_close($ch);
        }

        return $result;
    }


    public static function generateValidXmlFromObj( $obj, $node_block='nodes', $node_name='node') {
        $arr = get_object_vars($obj);
        return self::generateValidXmlFromArray($arr, $node_block, $node_name);
    }

    public static function generateValidXmlFromArray($array, $node_block='nodes', $node_name='node') {
        $xml = '<?xml version="1.0" encoding="UTF-8" ?>';

        $xml .= '<' . $node_block . ' xmlns:p="http://www.dhl.com" xmlns:p1="http://www.dhl.com/datatypes" xmlns:p2="http://www.dhl.com/DCTRequestdatatypes" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.dhl.com DCT-req.xsd ">';
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