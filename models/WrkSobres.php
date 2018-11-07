<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "wrk_sobres".
 *
 * @property int $id_sobre
 * @property int $id_envio
 * @property int $num_peso
 */
class WrkSobres extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'wrk_sobres';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id_envio', 'num_peso'], 'required'],
            [['id_envio', 'num_peso'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id_sobre' => 'Id Sobre',
            'id_envio' => 'Id Envio',
            'num_peso' => 'Num Peso',
        ];
    }
}
