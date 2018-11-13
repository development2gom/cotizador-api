<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "cat_ciudades".
 *
 * @property int $id_ciudad
 * @property int $id_pais
 * @property string $txt_codigo_postal
 * @property string $txt_localidad
 * @property string $txt_ciudad
 * @property string $txt_codigo
 * @property string $txt_estado
 * @property int $b_habilitado
 * @property double $num_latitud
 * @property double $num_longitud
 *
 * @property CatPaises $pais
 */
class CatCiudades extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cat_ciudades';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id_pais'], 'required'],
            [['id_pais', 'b_habilitado'], 'integer'],
            [['num_latitud', 'num_longitud'], 'number'],
            [['txt_codigo_postal', 'txt_codigo'], 'string', 'max' => 30],
            [['txt_localidad', 'txt_ciudad', 'txt_estado'], 'string', 'max' => 100],
            [['id_pais'], 'exist', 'skipOnError' => true, 'targetClass' => CatPaises::className(), 'targetAttribute' => ['id_pais' => 'id_pais']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id_ciudad' => 'Id Ciudad',
            'id_pais' => 'Id Pais',
            'txt_codigo_postal' => 'Txt Codigo Postal',
            'txt_localidad' => 'Txt Localidad',
            'txt_ciudad' => 'Txt Ciudad',
            'txt_codigo' => 'Txt Codigo',
            'txt_estado' => 'Txt Estado',
            'b_habilitado' => 'B Habilitado',
            'num_latitud' => 'Num Latitud',
            'num_longitud' => 'Num Longitud',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPais()
    {
        return $this->hasOne(CatPaises::className(), ['id_pais' => 'id_pais']);
    }
}
