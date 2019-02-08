<?php

namespace app\_360Utils\Entity;



/**
 * Clase que representa la solicitud de compra de un envío
 */
class CompraEnvio{

    
    var $carrier;
    var $tipo_servicio;
    var $tipo_empaque;
    var $txt_contenido = "";

    var $origen_cp;
    var $origen_pais;
    var $origen_ciudad;
    var $origen_estado;
    var $origen_direccion;
    var $origen_nombre_persona;
    var $origen_telefono;
    var $origen_compania;
    var $origen_correo;

    var $destino_cp;
    var $destino_pais;
    var $destino_ciudad;
    var $destino_estado;
    var $destino_direccion;
    var $destino_nombre_persona;
    var $destino_telefono;
    var $destino_compania;
    var $destino_correo;

    // para envios internacionales
    var $valorUnitario = 0.0;
    var $cantidadPiezasUnitarias = 0;
    

    //Seguro del envío
    var $hasSeguro = false;
    var $valorSeguro = 0;

    var  $fecha = "2019-01-15";

    var $paquetes = [];

    function isEnvioInternacional(){
        return strtoupper($this->origen_pais) == "MX" && strtoupper($this->destino_pais) != "MX";
    }

    function getValorTotal(){
        return $this->valorUnitario * $this->cantidadPiezasUnitarias;
    }

    function addPaquete(Paquete $paquete){
        array_push($this->paquetes,$paquete);
    }

    function getTotalWeight(){
        $res = 0.0;
        foreach($this->paquetes as $item){
            $res += $item->getPesoFinal();
        }

        return $res;
    }
  
 }