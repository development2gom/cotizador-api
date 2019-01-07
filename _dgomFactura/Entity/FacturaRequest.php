<?php
namespace app\_dgomFactura\Entity;

class FacturaRequest{

    public $useSandBox = true;
    public $transaccion;
    public $formaPago;
    public $condicionesPago;
    public $subTotal;
    public $total;
    public $rfcReceptor;
    public $nombreReceptor;
    public $claveProdServicio;
    public $cantidad;
	public $claveUnidad;
	public $unidad;
	public $descripcion;
	public $valorUnitario;
    public $importe;
    public $usoCFDIReceptor;

    public function isValid(){
        return true;
    }
    
    public function getParams(){
        $parametros = [
			"sandbox"           =>$this->useSandBox,
			"transaccion"       =>$this->transaccion,
			"formaPago"         =>$this->formaPago,
			"condicionesDePago" =>$this->condicionesPago,
			"subTotal"          =>$this->subTotal,
			"total"             =>$this->total,
			"rfcReceptor"       =>strtoupper( $this->rfcReceptor ),
			"nombreReceptor"    =>$this->nombreReceptor,
			"usoCFDIReceptor"   =>$this->usoCFDIReceptor,
			"claveProdServ"     =>$this->claveProdServicio,
			"cantidad"          =>$this->cantidad,
			"claveUnidad"       =>$this->claveUnidad,
			"unidad"            =>$this->unidad,
			"descripcion"       =>$this->descripcion,
			"valorUnitario"     =>$this->valorUnitario,
            "importe"           =>$this->importe
            
        ];
        
        return $parametros;
    }

}

?>