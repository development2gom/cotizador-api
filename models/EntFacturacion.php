<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ent_facturacion".
 *
 * @property int $id_factura
 * @property string $txt_rfc
 * @property string $txt_razon_social
 * @property string $txt_nombre
 * @property string $txt_apellido_paterno
 * @property string $txt_apellido_materno
 * @property string $txt_calle
 * @property int $num_exterior
 * @property int $num_interior
 * @property string $txt_colonia
 * @property string $txt_estado
 * @property string $txt_pais
 * @property int $id_cliente
 * @property int $b_habilitado
 *
 * @property EntClientes $cliente
 */
class EntFacturacion extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ent_facturacion';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['txt_rfc', 'txt_razon_social', 'txt_calle', 'num_exterior', 'txt_colonia', 'txt_pais', 'id_cliente'], 'required'],
            [['num_exterior', 'num_interior', 'id_cliente', 'b_habilitado'], 'integer'],
            [['txt_rfc'], 'string', 'max' => 13],
            [['txt_razon_social'], 'string', 'max' => 100],
            [['txt_nombre', 'txt_apellido_paterno', 'txt_apellido_materno', 'txt_calle', 'txt_colonia', 'txt_estado', 'txt_pais'], 'string', 'max' => 50],
            [['id_cliente'], 'exist', 'skipOnError' => true, 'targetClass' => EntClientes::className(), 'targetAttribute' => ['id_cliente' => 'id_cliente']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id_factura' => 'Id Factura',
            'txt_rfc' => 'Txt Rfc',
            'txt_razon_social' => 'Txt Razon Social',
            'txt_nombre' => 'Txt Nombre',
            'txt_apellido_paterno' => 'Txt Apellido Paterno',
            'txt_apellido_materno' => 'Txt Apellido Materno',
            'txt_calle' => 'Txt Calle',
            'num_exterior' => 'Num Exterior',
            'num_interior' => 'Num Interior',
            'txt_colonia' => 'Txt Colonia',
            'txt_estado' => 'Txt Estado',
            'txt_pais' => 'Txt Pais',
            'id_cliente' => 'Id Cliente',
            'b_habilitado' => 'B Habilitado',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCliente()
    {
        return $this->hasOne(EntClientes::className(), ['id_cliente' => 'id_cliente']);
    }

    public function fields(){
        $fields = parent::fields();
        
        /**
         * Se calculan y se regresan en el json de respuesta
         */
        $fields[] = 'cliente';

        return $fields;
    }
}
