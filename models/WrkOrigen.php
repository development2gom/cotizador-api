<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "wrk_origen".
 *
 * @property int $id_origen
 * @property string $txt_nombre
 * @property string $txt_pais
 * @property string $txt_calle
 * @property string $txt_estado
 * @property string $txt_municipio
 * @property int $num_codigo_postal
 * @property int $num_telefono
 * @property int $num_telefono_movil
 * @property string $txt_colonia
 * @property string $num_exterior
 * @property string $num_interior
 * @property int $id_cliente
 * @property string $txt_referencia
 * @property string $txt_nombre_ubicacion
 * @property string $txt_empresa
 * @property string $txt_puesto
 * @property int $b_habilitado
 *
 * @property WrkEnvios[] $wrkEnvios
 * @property EntClientes $cliente
 */
class WrkOrigen extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'wrk_origen';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['txt_nombre', 'txt_pais', 'txt_calle', 'num_codigo_postal', 'txt_colonia', 'num_exterior', 'id_cliente'], 'required'],
            [['num_codigo_postal', 'num_telefono', 'num_telefono_movil', 'id_cliente', 'b_habilitado'], 'integer'],
            [['txt_referencia'], 'string'],
            [['txt_nombre', 'txt_nombre_ubicacion', 'txt_empresa', 'txt_puesto'], 'string', 'max' => 100],
            [['txt_pais', 'txt_calle', 'txt_estado', 'txt_municipio', 'txt_colonia'], 'string', 'max' => 50],
            [['num_exterior', 'num_interior'], 'string', 'max' => 5],
            [['id_cliente'], 'exist', 'skipOnError' => true, 'targetClass' => EntClientes::className(), 'targetAttribute' => ['id_cliente' => 'id_cliente']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id_origen' => 'Id Origen',
            'txt_nombre' => 'Txt Nombre',
            'txt_pais' => 'Txt Pais',
            'txt_calle' => 'Txt Calle',
            'txt_estado' => 'Txt Estado',
            'txt_municipio' => 'Txt Municipio',
            'num_codigo_postal' => 'Num Codigo Postal',
            'num_telefono' => 'Num Telefono',
            'num_telefono_movil' => 'Num Telefono Movil',
            'txt_colonia' => 'Txt Colonia',
            'num_exterior' => 'Num Exterior',
            'num_interior' => 'Num Interior',
            'id_cliente' => 'Id Cliente',
            'txt_referencia' => 'Txt Referencia',
            'txt_nombre_ubicacion' => 'Txt Nombre Ubicacion',
            'txt_empresa' => 'Txt Empresa',
            'txt_puesto' => 'Txt Puesto',
            'b_habilitado' => 'B Habilitado',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWrkEnvios()
    {
        return $this->hasMany(WrkEnvios::className(), ['id_origen' => 'id_origen']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCliente()
    {
        return $this->hasOne(EntClientes::className(), ['id_cliente' => 'id_cliente']);
    }
}
