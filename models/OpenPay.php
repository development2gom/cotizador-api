<?php
namespace app\models;

use app\modules\ModUsuarios\models\Utils;


class OpenPay{

    public $clienteNombre;
    public $clienteEmail;
    public $description;
    public $orderId;
    public $amount;
    public $subTotal;
    public $errorObject;
    public $ordenCompra;

    function __construct($clienteNombre, $clienteEmail, $description, $orderId, $amount, $subTotal) {
         $this->clienteNombre = $clienteNombre;
         $this->clienteEmail = $clienteEmail;
         $this->description = $description;
         $this->orderId = $orderId;
         $this->amount = $amount;
         $this->subTotal = $subTotal;
    }

    public function getCliente(){

        $cliente = [
			"name" => $this->clienteNombre,
			"email" => $this->clienteEmail
        ];

        return $cliente;
    }
    

    public function generarOrdenCompra($orderOpenPay, $barcodeUrl=null, $pagado=0){

        $ordenCompra = new EntOrdenesCompras();
        $ordenCompra->id_cliente = EntClientes::getUsuarioLogueado()->id_cliente;
        $ordenCompra->txt_descripcion = $this->description;
        $ordenCompra->txt_order_open_pay = $orderOpenPay;
        $ordenCompra->txt_order_number = $this->orderId;
        $ordenCompra->b_pagado = $pagado;

        if($pagado){
            $ordenCompra->fch_pago = Calendario::getFechaActual();
        }

        if($barcodeUrl){
            $ordenCompra->txt_barcode_url = $barcodeUrl;
        }

        $ordenCompra->fch_creacion = Calendario::getFechaActual();
        $ordenCompra->num_total = $this->amount;
        $ordenCompra->num_subtotal = $this->subTotal;

        if($ordenCompra->save()){
            $this->ordenCompra = $ordenCompra;
            return true;
        }else{
            $this->errorObject = $ordenCompra->errors;
        }

        return false;
    }

    /**
	 * Generar codigo para poder pagar en las tiendas
	 */
	public function generarTicket()
	{
        //$orderId = Utils::generateToken("op_");
        $respuesta = new RespuestaDeApis();
		$openpay = \Openpay::getInstance(\Openpay::getId(), \Openpay::getApiKey());
        $cliente = $this->getCliente();
        
		$chargeData = [
			'method' => 'store',
			'amount' => $this->amount,
			'description' => $this->description,
			'customer' => $cliente,
			'order_id' => $this->orderId
        ];

        try{
            $charge = $openpay->charges->create($chargeData);

            if($this->generarOrdenCompra($charge->id, $charge->payment_method->barcode_url)){
                $respuesta->status= 1;
                $respuesta->message = "Todo bien";
                $respuesta->object = $charge;
            }else{
                $respuesta->message = "No se pudo generar la orden de compra";
                $respuesta->object = $this->errorObject;
            }
           
        }catch(\OpenpayApiError $error){
            $respuesta->message = $error->getMessage();
            $respuesta->object = $error;
        }


		return $respuesta;
    }
    
    /**
	 * Cargo
	 * 
	 * @param string $description        	
	 * @param string $orderId        	
	 * @param string $amount        	
	 * @return unknown
	 */
	public function cargoTarjeta($tokenId = null, $deviceId = null, $envio)
	{
        $respuesta = new RespuestaDeApis();
		$openpay = \Openpay::getInstance(\Openpay::getId(), \Openpay::getApiKey());
		
        $cliente = $this->getCliente();
        

		$chargeData = array(
			'method' => 'card',
			'customer' => $cliente,
			'source_id' => $tokenId,
			'amount' => ( float )$this->amount,
			'description' => $this->description,
			'order_id' => $this->orderId,
				// 'use_card_points' => $_POST["use_card_points"], // Opcional, si estamos usando puntos
			'device_session_id' => $deviceId
        );
        
        try{
            $charge = $openpay->charges->create($chargeData);

            if($this->generarOrdenCompra($charge->id, null, 1)){
                $respuesta->status= 1;
                $respuesta->message = "Todo bien";
                $respuesta->object = $charge;
                $envio->id_pago =  $this->ordenCompra->id_orden_compra;
                $envio->save();
                
            }else{
                $respuesta->message = "No se pudo generar la orden de compra";
            }
        }catch(\OpenpayApiError $error){
            $respuesta->message = $error->getMessage();
            $respuesta->object = $error;
        }
		return $respuesta;
	}
}