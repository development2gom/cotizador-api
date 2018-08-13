<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "cat_proveedores".
 *
 * @property int $id_proveedor
 * @property string $txt_nombre_proveedor
 * @property string $txt_imagen
 * @property string $txt_descripcion_proveedor
 * @property int $b_habilitado
 *
 * @property WrkEnvios[] $wrkEnvios
 */
class CatProveedores extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cat_proveedores';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['txt_nombre_proveedor', 'txt_imagen'], 'required'],
            [['b_habilitado'], 'integer'],
            [['txt_nombre_proveedor', 'txt_descripcion_proveedor'], 'string', 'max' => 50],
            [['txt_imagen'], 'string', 'max' => 100],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id_proveedor' => 'Id Proveedor',
            'txt_nombre_proveedor' => 'Txt Nombre Proveedor',
            'txt_imagen' => 'Txt Imagen',
            'txt_descripcion_proveedor' => 'Txt Descripcion Proveedor',
            'b_habilitado' => 'B Habilitado',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWrkEnvios()
    {
        return $this->hasMany(WrkEnvios::className(), ['id_proveedor' => 'id_proveedor']);
    }
}
