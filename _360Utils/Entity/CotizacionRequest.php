<?php
namespace app\_360Utils\Entity;



/**
 * Objeto que represta la cotizacion solicitada por un usuario para hacer un envío
 */
class CotizacionRequest{
    public $origenCP;
    public $origenCountry;
    public $origenStateCode;

    public $destinoCP;
    public $destinoCountry;
    public $destinoStateCode;

    public $fecha;

    public $paquetes = [];
    public $isPaquete;
    public $valorDeclarado = 0.00;

    public $hasSeguro = false;
    public $montoSeguro = 0.00;

    public $packingType;


    function addPaquete(Paquete $pkg){
        array_push($this->paquetes, $pkg);
    }

    function addSobre($peso){
        $pkg = new Paquete();
        $pkg->alto = 0;
        $pkg->ancho = 0;
        $pkg->largo = 0;
        $pkg->peso = $peso;
        $this->addPaquete($pkg);
    }

    function addPaqueteElementos($alto,$ancho,$largo,$peso){
        $pkg = new Paquete();
        $pkg->alto = $alto;
        $pkg->ancho = $ancho;
        $pkg->largo = $largo;
        $pkg->peso = $peso;
        $this->addPaquete($pkg);
    }



    function paquetesCount(){
        return count($this->paquetes);
    }
}
?>