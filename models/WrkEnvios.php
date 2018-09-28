<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "wrk_envios".
 *
 * @property int $id_envio
 * @property int $id_origen
 * @property int $id_destino
 * @property int $id_proveedor
 * @property int $num_cp_origen
 * @property int $num_cp_destino
 * @property int $id_pago
 * @property int $id_cliente
 * @property double $num_costo_envio
 * @property double $num_impuesto
 * @property double $num_subtotal
 * @property int $b_habilitado
 * @property string $uddi
 *
 * @property EntClientes $cliente
 * @property WrkDestino $destino
 * @property WrkOrigen $origen
 * @property EntOrdenesCompras $pago
 * @property CatProveedores $proveedor
 */
class WrkEnvios extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'wrk_envios';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id_origen', 'id_destino', 'id_proveedor', 'num_cp_origen', 'num_cp_destino', 'id_pago', 'id_cliente', 'b_habilitado'], 'integer'],
            [['id_cliente'], 'required'],
            [['num_costo_envio', 'num_impuesto', 'num_subtotal'], 'number'],
            [['uddi'], 'string', 'max' => 100],
            [['txt_folio', 'txt_tipo'], 'string', 'max' => 50],
            [['uddi'], 'unique'],
            [['id_cliente'], 'exist', 'skipOnError' => true, 'targetClass' => EntClientes::className(), 'targetAttribute' => ['id_cliente' => 'id_cliente']],
            [['id_destino'], 'exist', 'skipOnError' => true, 'targetClass' => WrkDestino::className(), 'targetAttribute' => ['id_destino' => 'id_destino']],
            [['id_origen'], 'exist', 'skipOnError' => true, 'targetClass' => WrkOrigen::className(), 'targetAttribute' => ['id_origen' => 'id_origen']],
            [['id_pago'], 'exist', 'skipOnError' => true, 'targetClass' => EntOrdenesCompras::className(), 'targetAttribute' => ['id_pago' => 'id_orden_compra']],
            [['id_proveedor'], 'exist', 'skipOnError' => true, 'targetClass' => CatProveedores::className(), 'targetAttribute' => ['id_proveedor' => 'id_proveedor']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id_envio' => 'Id Envio',
            'id_origen' => 'Id Origen',
            'id_destino' => 'Id Destino',
            'id_proveedor' => 'Id Proveedor',
            'num_cp_origen' => 'Num Cp Origen',
            'num_cp_destino' => 'Num Cp Destino',
            'id_pago' => 'Id Pago',
            'id_cliente' => 'Id Cliente',
            'num_costo_envio' => 'Num Costo Envio',
            'num_impuesto' => 'Num Impuesto',
            'num_subtotal' => 'Num Subtotal',
            'b_habilitado' => 'B Habilitado',
            'uddi' => 'Uddi',
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
    public function getDestino()
    {
        return $this->hasOne(WrkDestino::className(), ['id_destino' => 'id_destino']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrigen()
    {
        return $this->hasOne(WrkOrigen::className(), ['id_origen' => 'id_origen']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPago()
    {
        return $this->hasOne(EntOrdenesCompras::className(), ['id_orden_compra' => 'id_pago']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProveedor()
    {
        return $this->hasOne(CatProveedores::className(), ['id_proveedor' => 'id_proveedor']);
    }
}
