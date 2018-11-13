<?php
namespace app\models;

use app\models\Utils;
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

            if($pagado==1){
                return $this->guardarPagoRecibido($ordenCompra->id_orden_compra, $orderOpenPay);
            }
            return $ordenCompra;
        }else{
            throw new HttpException(500, "No se pudo guardar la orden de compra".Utils::getErrors($ordenCompra));
        }

    }

    public function guardarPagoRecibido($idOrdenCompra, $transaccion){
        $pagoRecibido = new EntPagosRecibidos();
        $pagoRecibido->id_cliente = $this->idCliente;
        $pagoRecibido->id_orden_compra = $idOrdenCompra;
        $pagoRecibido->txt_transaccion = $transaccion;
        $pagoRecibido->txt_tipo_transaccion = "Tarjeta";
        $pagoRecibido->txt_monto_pago = $this->amount;
        $pagoRecibido->fch_pago = Calendario::getFechaActual();
        $pagoRecibido->b_facturado = 0;
        $pagoRecibido->txt_transaccion_local = uniqid();
        $pagoRecibido->txt_notas = "Pago con tarjeta de credito";
        $pagoRecibido->txt_estatus = "PAGADO";

        if($pagoRecibido->save()){
            
            return $pagoRecibido;
        }

        throw new HttpException(500, "No se pudo guardar el pago ".Utils::getErrors($pagoRecibido));
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

            if($pagoRecibido = $this->generarOrdenCompra($charge->id, null, 1)){
               
                $envio->id_pago =  $pagoRecibido->id_pago_recibido;
                $envio->save();

                return $pagoRecibido;
                
            }else{
                throw new HttpException(500, "No se pudo generar la orden de compra");
            }
        }catch(\Exception $error){
            $message = "";
            if($error->getMessage()=="The card was declined"){
                $message = "La tarjeta fue declinada";
            }

            if($error->getMessage() =="The card has expired"){
                $message = "La tarjeta ha expirado";
            }

            if($error->getMessage()=="The card doesn't have sufficient funds"){
                $message = "La tarjeta no tiene los suficientes fondos";
            }

            if($error->getMessage()=="The card was reported as stolen"){
                $message = "La tarjeta ha sido bloqueada";
            }

            if($error->getMessage()=="The card was declined (k)"){
                $message = "La tarjeta tiene marca de fraude";
            }
            throw new HttpException(500, "No se pudo hacer el cargo a la tarjeta: ".$message);
        }
		return $charge;
	}
}