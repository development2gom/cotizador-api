<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "wrk_empaque".
 *
 * @property int $id_empaque
 * @property int $id_tipo_empaque
 * @property int $num_paquetes
 * @property int $num_peso
 * @property int $num_alto
 * @property int $num_ancho
 * @property int $num_largo
 * @property int $b_habilitado
 *
 * @property CatTipoEmpaque $tipoEmpaque
 */
class WrkEmpaque extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'wrk_empaque';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['num_peso', 'num_alto', 'num_ancho', 'num_largo'], 'required'],
            [['num_paquetes', 'num_peso', 'num_alto', 'num_ancho', 'num_largo', 'b_habilitado'], 'number'],
            
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id_empaque' => 'Id Empaque',
            'id_tipo_empaque' => 'Id Tipo Empaque',
            'num_paquetes' => 'Num Paquetes',
            'num_peso' => 'Num Peso',
            'num_alto' => 'Num Alto',
            'num_ancho' => 'Num Ancho',
            'num_largo' => 'Num Largo',
            'b_habilitado' => 'B Habilitado',
        ];
    }

   
}
