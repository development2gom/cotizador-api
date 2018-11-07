<?php
namespace app\models;

use app\modules\ModUsuarios\models\Utils;
use yii\web\HttpException;


class OpenPay{

    public $idCliente;
    public $idEnvio;
    public $clienteNombre;
    public $clienteEmail;
    public $description;
    public $orderId;
    public $amount;
    public $subTotal;
    public $errorObject;
    public $ordenCompra;

    const TIEMPO_VIDA_TICKET_OPENPAY = '+4 hours';

    function __construct($idCliente,$clienteNombre, $clienteEmail, $description, $orderId, $amount, $subTotal, $idEnvio) {
         $this->idEnvio = $idEnvio;
         $this->idCliente = $idCliente;
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
    

    public function generarOrdenCompra($orderOpenPay, $barcodeUrl=null, $pagado=0, $referencia=null){

        $ordenCompra = new EntOrdenesCompras();
        $ordenCompra->id_cliente = $this->idCliente;
        $ordenCompra->id_envio = $this->idEnvio;
        $ordenCompra->txt_descripcion = $this->description;
        $ordenCompra->txt_order_open_pay = $orderOpenPay;
        $ordenCompra->txt_order_number = $this->orderId;
        $ordenCompra->b_pagado = $pagado;
        $ordenCompra->txt_referencia = $referencia;

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
            return $ordenCompra;
        }else{
            throw new HttpException(500, "No se pudo guardar la orden de compra".Utils::getErrors($this));
        }

    }

    /**
	 * Generar codigo para poder pagar en las tiendas
	 */
	public function generarTicket()
	{
        
		$openpay = \Openpay::getInstance(\Openpay::getId(), \Openpay::getApiKey());
        $cliente = $this->getCliente();
        
		$chargeData = [
			'method' => 'store',
			'amount' => $this->amount,
			'description' => $this->description,
			'customer' => $cliente,
            'order_id' => $this->orderId,
            'due_date' => date('c', strtotime(self::TIEMPO_VIDA_TICKET_OPENPAY))
        ];

        try{
            $charge = $openpay->charges->create($chargeData);

            if($ordenCompra = $this->generarOrdenCompra($charge->id, $charge->payment_method->barcode_url, 0, $charge->payment_method->reference)){
                
                return $ordenCompra;
            }else{
                throw new HttpException(500, "No se pudo generar el ticket");
            }
           
        }catch(\OpenpayApiError $error){
            throw new HttpException(500, "No se pudo generar el ticket".$error->getMessage());
        }


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

            if($ordenCompra = $this->generarOrdenCompra($charge->id, null, 1)){
               
                $envio->id_pago =  $this->ordenCompra->id_orden_compra;
                $envio->save();
                
            }else{
                throw new HttpException(500, "No se pudo generar la orden de compra");
            }
        }catch(\OpenpayApiError $error){
            throw new HttpException(500, "No se pudo hacer el cargo a la tarjeta".$error->getError());
        }
		return $respuesta;
	}
}