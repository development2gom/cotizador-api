<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ent_pagos_recibidos".
 *
 * @property int $id_pago_recibido
 * @property int $id_cliente
 * @property int $id_orden_compra
 * @property string $txt_transaccion_local
 * @property string $txt_ip
 * @property string $txt_notas Estado de la transaccion
 * @property string $txt_estatus Estado de la transaccion
 * @property string $txt_transaccion Numero de transaccion
 * @property string $txt_tipo_transaccion Tipo de transaccion
 * @property string $txt_cadena_comprador Texto de la cadena del comprador
 * @property string $txt_cadena_pago Texto de la cadena del pago
 * @property string $txt_cadena_producto Texto de la cadena del producto
 * @property string $txt_monto_pago
 * @property string $verify_sign
 * @property string $fch_pago Fecha del registro
 * @property int $b_facturado
 *
 * @property EntClientes $cliente
 * @property EntOrdenesCompras $ordenCompra
 */
class EntPagosRecibidos extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ent_pagos_recibidos';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id_cliente', 'txt_transaccion_local', 'txt_notas', 'txt_estatus', 'txt_transaccion'], 'required'],
            [['id_cliente', 'id_orden_compra', 'b_facturado'], 'integer'],
            [['txt_cadena_comprador'], 'string'],
            [['fch_pago'], 'safe'],
            [['txt_transaccion_local', 'txt_ip'], 'string', 'max' => 32],
            [['txt_notas', 'txt_estatus', 'txt_transaccion', 'txt_tipo_transaccion', 'verify_sign'], 'string', 'max' => 100],
            [['txt_cadena_pago'], 'string', 'max' => 2000],
            [['txt_cadena_producto'], 'string', 'max' => 1000],
            [['txt_monto_pago'], 'string', 'max' => 10],
            [['id_cliente'], 'exist', 'skipOnError' => true, 'targetClass' => EntClientes::className(), 'targetAttribute' => ['id_cliente' => 'id_cliente']],
            [['id_orden_compra'], 'exist', 'skipOnError' => true, 'targetClass' => EntOrdenesCompras::className(), 'targetAttribute' => ['id_orden_compra' => 'id_orden_compra']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id_pago_recibido' => 'Id Pago Recibido',
            'id_cliente' => 'Id Cliente',
            'id_orden_compra' => 'Id Orden Compra',
            'txt_transaccion_local' => 'Txt Transaccion Local',
            'txt_ip' => 'Txt Ip',
            'txt_notas' => 'Txt Notas',
            'txt_estatus' => 'Txt Estatus',
            'txt_transaccion' => 'Txt Transaccion',
            'txt_tipo_transaccion' => 'Txt Tipo Transaccion',
            'txt_cadena_comprador' => 'Txt Cadena Comprador',
            'txt_cadena_pago' => 'Txt Cadena Pago',
            'txt_cadena_producto' => 'Txt Cadena Producto',
            'txt_monto_pago' => 'Txt Monto Pago',
            'verify_sign' => 'Verify Sign',
            'fch_pago' => 'Fch Pago',
            'b_facturado' => 'B Facturado',
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
    public function getOrdenCompra()
    {
        return $this->hasOne(EntOrdenesCompras::className(), ['id_orden_compra' => 'id_orden_compra']);
    }
}
