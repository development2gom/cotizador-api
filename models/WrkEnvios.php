<?php

namespace app\models;

use Yii;
use yii\web\HttpException;
use yii\db\Exception;

/**
 * This is the model class for table "wrk_envios".
 *
 * @property int $id_envio
 * @property int $id_origen
 * @property int $id_destino
 * @property int $id_proveedor
 * @property int $id_pago
 * @property int $id_cliente
 * @property int $id_tipo_empaque
 * @property double $num_costo_envio
 * @property double $num_impuesto
 * @property double $num_subtotal
 * @property string $txt_folio
 * @property string $txt_tipo
 * @property int $b_habilitado
 * @property string $uddi
 *
 * @property EntClientes $cliente
 * @property WrkDestino $destino
 * @property WrkOrigen $origen
 * @property EntOrdenesCompras $pago
 * @property CatProveedores $proveedor
 * @property CatTipoEmpaque $tipoEmpaque
 */
class WrkEnvios extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'wrk_envios';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id_origen', 'id_destino', 'id_proveedor', 'id_pago', 'id_cliente', 'id_tipo_empaque', 'b_habilitado'], 'integer'],
            [['id_cliente', 'id_tipo_empaque', 'id_destino', 'id_origen', 'id_proveedor'], 'required'],
            [['num_costo_envio', 'num_impuesto', 'num_subtotal'], 'number'],
            [['txt_folio', 'txt_tipo'], 'string', 'max' => 50],
            [['uddi'], 'string', 'max' => 100],
            [['uddi'], 'unique'],
            [['id_cliente'], 'exist', 'skipOnError' => true, 'targetClass' => EntClientes::className(), 'targetAttribute' => ['id_cliente' => 'id_cliente']],
            [['id_destino'], 'exist', 'skipOnError' => true, 'targetClass' => WrkDestino::className(), 'targetAttribute' => ['id_destino' => 'id_destino']],
            [['id_origen'], 'exist', 'skipOnError' => true, 'targetClass' => WrkOrigen::className(), 'targetAttribute' => ['id_origen' => 'id_origen']],
            [['id_pago'], 'exist', 'skipOnError' => true, 'targetClass' => EntOrdenesCompras::className(), 'targetAttribute' => ['id_pago' => 'id_orden_compra']],
            [['id_proveedor'], 'exist', 'skipOnError' => true, 'targetClass' => CatProveedores::className(), 'targetAttribute' => ['id_proveedor' => 'id_proveedor']],
            [['id_tipo_empaque'], 'exist', 'skipOnError' => true, 'targetClass' => CatTipoEmpaque::className(), 'targetAttribute' => ['id_tipo_empaque' => 'id_tipo_empaque']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id_envio' => 'Id Envio',
            'id_origen' => 'Id Origen',
            'id_destino' => 'Id Destino',
            'id_proveedor' => 'Id Proveedor',
            'id_pago' => 'Id Pago',
            'id_cliente' => 'Id Cliente',
            'id_tipo_empaque' => 'Id Tipo Empaque',
            'num_costo_envio' => 'Num Costo Envio',
            'num_impuesto' => 'Num Impuesto',
            'num_subtotal' => 'Num Subtotal',
            'txt_folio' => 'Txt Folio',
            'txt_tipo' => 'Txt Tipo',
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
    public function getEmpaque()
    {
        return $this->hasMany(WrkEmpaque::className(), ['id_envio' => 'id_envio']);
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

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTipoEmpaque()
    {
        return $this->hasOne(CatTipoEmpaque::className(), ['id_tipo_empaque' => 'id_tipo_empaque']);
    }

    public static function getEnvio($uddi){
        $model = self::find()->where(["uddi"=>$uddi])->one();
        if(!$model){
            throw new HttpException(404, "No se encuentra el envio");
        }

        return $model;
    }

    /**
     * Guarda el envio
     */
    public function generarEnvio($cliente, $origen, $destino, $proveedor, $tipoEmpaque){

        $transaction = $this->getDb()->beginTransaction();
        
        // Guardar datos del destino
        $destino->guardar($cliente->id_cliente);

        // Guardar datos del origen
        $origen->guardar($cliente->id_cliente);

        $this->id_origen = $origen->id_origen;
        $this->id_destino = $destino->id_destino;
        $this->id_cliente = $cliente->id_cliente;
        $this->id_proveedor = $proveedor->id_proveedor;
        $this->id_tipo_empaque = $tipoEmpaque->id_tipo_empaque;
        $this->uddi = Utils::generateToken("env_");

        // Guardar los datos del envio
        if(!$this->save()){
            $transaction->rollBack();
            throw new HttpException(500, "No se pudo guardar en la base de datos\n".Utils::getErrors($this));
        }else{
            $transaction->commit();
        }
      
        
        //$transaction = $envio->getDb()->beginTransaction();

    }
}
