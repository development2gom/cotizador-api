<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "cat_extras".
 *
 * @property int $id_extra
 * @property string $uddi
 * @property string $txt_nombre
 * @property double $num_precio
 * @property int $b_habilitado
 *
 * @property RelEnvioExtras[] $relEnvioExtras
 */
class CatExtras extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cat_extras';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['uddi'], 'required'],
            [['num_precio'], 'number'],
            [['b_habilitado'], 'integer'],
            [['uddi'], 'string', 'max' => 100],
            [['txt_nombre'], 'string', 'max' => 50],
            [['uddi'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id_extra' => 'Id Extra',
            'uddi' => 'Uddi',
            'txt_nombre' => 'Txt Nombre',
            'num_precio' => 'Num Precio',
            'b_habilitado' => 'B Habilitado',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRelEnvioExtras()
    {
        return $this->hasMany(RelEnvioExtras::className(), ['id_extra' => 'id_extra']);
    }
}
