<?php
namespace app\models;
class EnviosObject{

    public $cpOrigen;
    public $cpDestino;
    public $paquete = [];
    public $precioOriginal;
    public $precioCliente;
    public $mensajeria;
    public $tipoEnvio;
    public $fechaEntrega;
    public $urlImagen;


    public static function getSessionEnvios(){
        $session = \Yii::$app->session;
        $envioSeleccionado = $session->get('envios');
        return $envioSeleccionado;
    }

    public static function setSessionEnvios($opciones){
        $session = \Yii::$app->session;
        $session->set('envios', $opciones);
    }

    public static function setEnvioSeleccionado($opcionSeleccionada){
        $session = \Yii::$app->session;
        $session->set('envioSeleccionado', $opcionSeleccionada);
    }

    public static function getEnvioSeleccionado(){
        $session = \Yii::$app->session;
        $envioSeleccionado = $session->get('envioSeleccionado');

        $envios = self::getSessionEnvios();



        return $envios[$envioSeleccionado];
    }

    public static function getIndex(){
        $session = \Yii::$app->session;
        $envioSeleccionado = $session->get('envioSeleccionado');

        return $envioSeleccionado;
    }

    public static function validarSesionEnvio(){
        $existeSession = self::getSession();
        if($existeSession){
            return $existeSession;
        }else{
            return false;
        }
    }

    public function calcularPrecioCliente(){
        $precioPorcentaje = ($this->precioOriginal * (.20));
        $this->precioCliente = ($this->precioOriginal + $precioPorcentaje);

        return number_format($this->precioCliente, 2, ".", ",");
    }
}