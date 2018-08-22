<?php
namespace app\models;

class RespuestaDeApis{
    public $status = 0;
    public $codeStatus;
    public $message = "Ocurrio un problema";
    public $objet; // Objeto con respuesta del api
}