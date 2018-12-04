<?php

namespace app\_360Utils\Entity;



/**
 * Clase que representa la solicitud de compra de un envío
 */
class CompraEnvio{

    
    var $carrier;
    var $tipo_servicio;
    var $tipo_empaque;
    var $origen_cp;
    var $origen_pais;
    var $origen_ciudad;
    var $origen_estado;
    var $origen_direccion;
    var $origen_nombre_persona;
    var $origen_telefono;
    var $origen_compania;
    var $destino_cp;
    var $destino_pais;
    var $destino_ciudad;
    var $destino_estado;
    var $destino_direccion;
    var $destino_nombre_persona;
    var $destino_telefono;
    var $destino_compania;
    var $paquetes = [];

    function addPaquete(Paquete $paquete){
        array_push($this->paquetes,$paquete);
    }
  
 }