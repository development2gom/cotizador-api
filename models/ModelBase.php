<?php
namespace app\models;

class ModelBase extends \yii\db\ActiveRecord{

    public static function findByUddi($uddi){
        $model = self::find()->where(["uddi"=>$uddi])->one();

        if(!$model){
            return null;
        }

        return $model;
    }

}