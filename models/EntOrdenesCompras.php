<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ent_ordenes_compras".
 *
 * @property int $id_orden_compra
 * @property string $txt_order_number
 * @property string $txt_order_open_pay
 * @property string $txt_descripcion
 * @property string $txt_barcode_url
 * @property int $id_cliente
 * @property string $fch_creacion
 * @property int $b_pagado
 * @property double $num_total
 * @property int $b_habilitado
 * @property int $b_subscripcion
 * @property double $num_subtotal
 * @property string $fch_pago
 *
 * @property EntClientes $cliente
 * @property EntPagosRecibidos[] $entPagosRecibidos
 * @property WrkEnvios[] $wrkEnvios
 */
class EntOrdenesCompras extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ent_ordenes_compras';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id_cliente', 'num_total'], 'required'],
            [['id_cliente', 'b_pagado', 'b_habilitado', 'b_subscripcion'], 'integer'],
            [['fch_creacion', 'fch_pago'], 'safe'],
            [['num_total', 'num_subtotal'], 'number'],
            [['txt_order_number', 'txt_order_open_pay'], 'string', 'max' => 100],
            [['txt_descripcion', 'txt_barcode_url'], 'string', 'max' => 500],
            [['txt_order_number'], 'unique'],
            [['id_cliente'], 'exist', 'skipOnError' => true, 'targetClass' => EntClientes::className(), 'targetAttribute' => ['id_cliente' => 'id_cliente']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id_orden_compra' => 'Id Orden Compra',
            'txt_order_number' => 'Txt Order Number',
            'txt_order_open_pay' => 'Txt Order Open Pay',
            'txt_descripcion' => 'Txt Descripcion',
            'txt_barcode_url' => 'Txt Barcode Url',
            'id_cliente' => 'Id Cliente',
            'fch_creacion' => 'Fch Creacion',
            'b_pagado' => 'B Pagado',
            'num_total' => 'Num Total',
            'b_habilitado' => 'B Habilitado',
            'b_subscripcion' => 'B Subscripcion',
            'num_subtotal' => 'Num Subtotal',
            'fch_pago' => 'Fch Pago',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCliente()
    {
        return $this->hasOne(EntClientes::className(), ['id_cliente' => 'id_cliente']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEntPagosRecibidos()
    {
        return $this->hasMany(EntPagosRecibidos::className(), ['id_orden_compra' => 'id_orden_compra']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWrkEnvios()
    {
        return $this->hasMany(WrkEnvios::className(), ['id_pago' => 'id_orden_compra']);
    }
}
