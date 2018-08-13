<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "cat_tipo_empaque".
 *
 * @property int $id_tipo_empaque
 * @property string $text_tipo_empaque
 * @property string $b_habilitado
 *
 * @property WrkEmpaque[] $wrkEmpaques
 */
class CatTipoEmpaque extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cat_tipo_empaque';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id_tipo_empaque', 'text_tipo_empaque'], 'required'],
            [['id_tipo_empaque'], 'integer'],
            [['text_tipo_empaque', 'b_habilitado'], 'string', 'max' => 50],
            [['id_tipo_empaque'], 'unique'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id_tipo_empaque' => 'Id Tipo Empaque',
            'text_tipo_empaque' => 'Text Tipo Empaque',
            'b_habilitado' => 'B Habilitado',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWrkEmpaques()
    {
        return $this->hasMany(WrkEmpaque::className(), ['id_tipo_empaque' => 'id_tipo_empaque']);
    }
}
