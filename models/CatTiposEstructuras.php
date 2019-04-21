<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "cat_tipos_estructuras".
 *
 * @property int $id_tipo_estructura
 * @property string $uuid
 * @property string $txt_nombre
 * @property string $txt_descripcion
 * @property int $b_habilitado
 *
 * @property EntClientes[] $entClientes
 */
class CatTiposEstructuras extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cat_tipos_estructuras';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['uuid', 'txt_nombre', 'txt_descripcion'], 'required'],
            [['b_habilitado'], 'integer'],
            [['uuid', 'txt_nombre'], 'string', 'max' => 45],
            [['txt_descripcion'], 'string', 'max' => 200],
            [['uuid'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id_tipo_estructura' => 'Id Tipo Estructura',
            'uuid' => 'Uuid',
            'txt_nombre' => 'Txt Nombre',
            'txt_descripcion' => 'Txt Descripcion',
            'b_habilitado' => 'B Habilitado',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEntClientes()
    {
        return $this->hasMany(EntClientes::className(), ['id_tipo_estructura' => 'id_tipo_estructura']);
    }
}
