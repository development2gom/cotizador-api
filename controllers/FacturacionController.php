<?php

namespace app\controllers;

use Yii;
use app\models\EntFacturacionSearch;
use yii\web\HttpException;
use app\models\EntFacturacion;

class FacturacionController extends \yii\rest\Controller
{
    public $enableCsrfValidation = false;
    public $serializer = [
        'class' => 'app\components\SerializerExtends',
        'collectionEnvelope' => 'items',
    ];

    public function actionIndex()
    {
        $params =  Yii::$app->request->queryParams;

        $buscarFactura = new EntFacturacionSearch();
        $data = $buscarFactura->buscarFactura($params);
        return $data;
        //return $this->render('index');
    }

    public function actionGetFactura()
    {
        $request = Yii::$app->request;
        $params = $request->bodyParams;
        $uddi = $request->getBodyParam("uddi_factura");
        // echo $uddi;
        // exit;
       return EntFacturacion::getFacturacionUddi($uddi);
    }

}
