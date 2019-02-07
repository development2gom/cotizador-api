<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\components\AccessControlExtend;
use yii\helpers\Json;
use app\models\MessageResponse;
use app\models\ListResponse;



class ServicesBaseController extends \yii\rest\Controller{

    public $enableCsrfValidation = false;
    public $layout = null;


    // -------------- CONSTANTES -----------------------------------------

    const APPI_VERSION = "1.0.0";
    
    const LIST_PAGE_SIZE = 100;
    const LIST_PAGE_NUMBER = 1;

    const GOOGLE_ANALITYCS_ID = 'UA-117925414-1';


    const RESPONSE_SUCCESS              = 1; //Respuesta del api correcta
    const ERROR_ITEM_NOT_FOUND          = -2; //Objeto no encontrado en la base de datos
    const RESPONSE_NOT_FOUND            = -3;

    const REQUIRED_DATA_ERROR           = -3;
    const SAVE_MODEL_ERRROR             = -101;
    
    const ERROR_LOGIN                   = -100001;
    const ERROR_API                     = -100002;
    const ERROR_API_PARAMETRO_FALTANTE  = -100003;
    const ERROR_SESION_USUARIO_INVALIDA = -100004;
    const ERROR_SESION_DURACION_MINUTOS = -100005;
    const ERROR_BUSINESS                = -500;
    const ERROR_DATABASE_SAVE           = -501;
    

    const API_KEY = 'key';
    const API_SECRET = 'secret';

    const SESION_DURACION_MINUTOS = 30 * 60 * 60 * 1000;


    //------------------------ UTILIDADES DE LA APLICACION ------------------------------


    /**
     * Valida que se reciban los parámetros necesarios en el post
     */
    protected function validateData($postData, $requiredParams){
        $error = new MessageResponse();
        if(!$this->validateRequiredParam($error,isset($GLOBALS["HTTP_RAW_POST_DATA"]), "Raw Data" )){
            return $error;
        }
        $data = json_decode($GLOBALS["HTTP_RAW_POST_DATA"], true);
        return $this->validateDataJson($data,$requiredParams);
    }

    /**
     * Funcion recursiva que permite validar campos anidados
     */
    private function validateDataJson($json, $requiredParams){ 
        $error = new MessageResponse();
        foreach($requiredParams as $key=>$value){
            if(is_array($value)){
                $res = $this->validateDataJson($json[$key],$value);
                if(!$res == null ){
                    return $res;
                }
            }else{
                if(!$this->validateRequiredParam($error,isset($json[$key]), $value )){
                    return $error;
                }   
            } 
        }
        return null;
    }

    /**
     * Valida que un valor se encuentre 
     */
    protected function validateRequiredParam($response, $isSet, $atributoName){
        if(!$isSet){
            $response->responseCode = self::ERROR_API;
            $response->message = $atributoName . ' faltante';
            return false;
        }
        return true;
    }

    protected function getErrorResponse($code, $message){
        $response = new MessageResponse();
        $response->responseCode = $code;
        $response->message = $message;
        return $response;
    }

    protected function getMessageResponse($code, $message,$data){
        $response = new MessageResponse();
        $response->responseCode = $code;
        $response->message = $message;
        $response->data = $data;
        return $response;
    }

    protected function getMessageResponseNoData($code, $message){
        $response = new MessageResponse();
        $response->responseCode = $code;
        $response->message = $message;
        return $response;
    }


    protected function getListResponse($responseCode, $operation,$message, $results, $page =self::LIST_PAGE_NUMBER ,$maxPage = 1,$pageSize = self::LIST_PAGE_SIZE){
        $response = new ListResponse();
        $response->responseCode = $responseCode;
        $response->operation = $operation;
        $response->message = $message;
        $response->results = $results;
        $response->count = count($results);
        $response->page = $page;
        $response->maxPage = $maxPage;
        $response->pageSize = $pageSize;

        return $response;
    }

    

    protected function parseSaveErrors($errors){
        $message = "";
        foreach ($errors as $key => $value){
			foreach($value as $v){
				$message .= $v . ", ";
			}
		}
		return $message;
    }

    protected function getErrorResponseSaveErrors($errors){
        $message = $this->parseSaveErrors($errors);
        return $this->getErrorResponse(self::SAVE_MODEL_ERRROR,$message);
    }



    /**
     * Creador de LOG de archivos
     */
    public function crearLog($dirName, $nombreArchivo,$message){
        
        $basePath = Yii::getAlias('@app'); 
        $fichero = $basePath.'/' . $dirName . '/'.$nombreArchivo.'.log';

        $logData =  Utils::getFechaActual()."\n".$message."\n\n";
        
        $fp = fopen($fichero,"a");
        fwrite($fp,$logData);
        fclose($fp);
    }


    
    

    /**
     * Agrega la informacion a los analiticos de la aplicacion
     */
    private function createAnalyticsEvent($uddUser, $app, $accion,$tipoEvento){
        $url = "https://www.google-analytics.com/collect?v=1&tid=" . $this::GOOGLE_ANALITYCS_ID . "&ev=2&an=galstore-api&t=event" .
        "&av=" . self::APPI_VERSION . 
        "&cid=" . $uddUser .
        "&aid=" . $app . 
        "&ec=". $accion . 
        "&ea=" . $tipoEvento;

        $ch = curl_init(); 

        // set url 
        curl_setopt($ch, CURLOPT_URL, $url); 

        //return the transfer as a string 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 

        // $output contains the output string 
        curl_exec($ch); 

        // close curl resource to free up system resources 
        curl_close($ch);      
    }

    protected function parsePageNumber($page){
        $page = abs($page);
        if($page == 0){
            $page = 1;
        }

        return $page;
    }

    protected function parsePageSize($pageSize){
        $pageSize = abs($pageSize);
        if($pageSize == 0){
            $pageSize = 1;
        }

        return $pageSize;
    }

    protected function parsePageNumberJson($json){
        $page = self::LIST_PAGE_NUMBER;
        if(isset($json->page)){
            $page = $json->page;
        }
        $page = abs($page);
        if($page == 0){
            $page = 1;
        }

        return $page;
    }

    protected function parsePageSizeJson($json){
        $pageSize = self::LIST_PAGE_SIZE;
        if(isset($json->page_size)){
            $pageSize = $json->page_size;
        }
        $pageSize = abs($pageSize);
        if($pageSize == 0){
            $pageSize = 1;
        }

        return $pageSize;
    }
    
}
