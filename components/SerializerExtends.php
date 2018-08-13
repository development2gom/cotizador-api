<?php
namespace app\components;

use yii\rest\Serializer;
use yii\web\Link;

class SerializerExtends extends Serializer{

      /**
     * Serializes the validation errors in a model.
     * @param Model $model
     * @return array the array representation of the errors
     */
    protected function serializeModelErrors($model)
    {
        $this->response->setStatusCode(422, 'Data Validation Failed.');
        $result = [];
        foreach ($model->getFirstErrors() as $name => $message) {
            
            $result["fieldErrors"][] = [
                'field' => $name,
                'message' => $message,
            ];
        }

        return $result;
    }

}