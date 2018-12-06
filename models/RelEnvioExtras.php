<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "rel_envio_extras".
 *
 * @property int $id_rel_envio_extra
 * @property int $id_extra
 * @property int $id_envio
 * @property int $num_unidades
 * @property int $num_precio
 * @property string $txt_nombre_extra
 *
 * @property WrkEnvios $envio
 * @property CatExtras $extra
 */
class RelEnvioExtras extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'rel_envio_extras';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id_extra', 'id_envio', 'num_unidades'], 'required'],
            [['id_extra', 'id_envio', 'num_unidades', 'num_precio'], 'integer'],
            [['txt_nombre_extra'], 'string', 'max' => 100],
            [['id_envio'], 'exist', 'skipOnError' => true, 'targetClass' => WrkEnvios::className(), 'targetAttribute' => ['id_envio' => 'id_envio']],
            [['id_extra'], 'exist', 'skipOnError' => true, 'targetClass' => CatExtras::className(), 'targetAttribute' => ['id_extra' => 'id_extra']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id_rel_envio_extra' => 'Id Rel Envio Extra',
            'id_extra' => 'Id Extra',
            'id_envio' => 'Id Envio',
            'num_unidades' => 'Num Unidades',
            'num_precio' => 'Num Precio',
            'txt_nombre_extra' => 'Txt Nombre Extra',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEnvio()
    {
        return $this->hasOne(WrkEnvios::className(), ['id_envio' => 'id_envio']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getExtra()
    {
        return $this->hasOne(CatExtras::className(), ['id_extra' => 'id_extra']);
    }
}
