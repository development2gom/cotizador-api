<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "cat_paises".
 *
 * @property int $id_pais
 * @property string $uddi
 * @property string $txt_nombre
 * @property string $txt_codigo
 * @property int $b_habilitado
 * @property string $txt_regex_codigo_postal
 * @property string $txt_url_bandera
 *
 * @property CatCiudades[] $catCiudades
 */
class CatPaises extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cat_paises';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['uddi'], 'required'],
            [['b_habilitado'], 'integer'],
            [['uddi'], 'string', 'max' => 100],
            [['txt_nombre', 'txt_codigo', 'txt_regex_codigo_postal'], 'string', 'max' => 50],
            [['txt_url_bandera'], 'string', 'max' => 200],
            [['uddi'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id_pais' => 'Id Pais',
            'uddi' => 'Uddi',
            'txt_nombre' => 'Txt Nombre',
            'txt_codigo' => 'Txt Codigo',
            'b_habilitado' => 'B Habilitado',
            'txt_regex_codigo_postal' => 'Txt Regex Codigo Postal',
            'txt_url_bandera' => 'Txt Url Bandera',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCatCiudades()
    {
        return $this->hasMany(CatCiudades::className(), ['id_pais' => 'id_pais']);
    }
}
