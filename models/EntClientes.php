<?php

namespace app\models;

use Yii;
use yii\web\HttpException;

/**
 * This is the model class for table "ent_clientes".
 *
* @property int $id_cliente
* @property string $uddi
* @property int $id_tipo_estructura 
* @property string $txt_nombre
* @property string $txt_razon_social 
* @property string $txt_apellido_paterno
* @property string $txt_apellido_materno
* @property string $txt_rfc 
* @property string $txt_tipo_persona 
* @property string $num_telefono
* @property string $txt_correo
* @property int $b_habilitado
* @property int $b_desea_registro 
*
* @property EntAreasClientes[] $entAreasClientes 
* @property EntFacturacion[] $entFacturacions
* @property EntOrdenesCompras[] $entOrdenesCompras
* @property EntPagosRecibidos[] $entPagosRecibidos
 */
class EntClientes extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ent_clientes';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id_tipo_estructura', 'b_habilitado', 'id_cliente', 'b_desea_registro'], 'integer'],
            [['uddi'], 'string', 'max' => 100],
            [['txt_nombre', 'txt_apellido_paterno', 'txt_apellido_materno', 'txt_correo'], 'string', 'max' => 50],
            [['num_telefono'], 'string', 'max' => 11],
            [['uddi'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id_cliente' => 'Id Cliente',
            'uddi' => 'Uddi',
            'txt_nombre' => 'Txt Nombre',
            'txt_apellido_paterno' => 'Txt Apellido Paterno',
            'txt_apellido_materno' => 'Txt Apellido Materno',
            'num_telefono' => 'Num Telefono',
            'txt_correo' => 'Txt Correo',
            'b_habilitado' => 'B Habilitado',
        ];
    }

    /** 
    * @return \yii\db\ActiveQuery 
    */ 
   public function getEntAreasClientes() 
   { 
       return $this->hasMany(EntAreasClientes::className(), ['id_cliente' => 'id_cliente']); 
   }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEntFacturacions()
    {
        return $this->hasMany(EntFacturacion::className(), ['id_cliente' => 'id_cliente']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEntOrdenesCompras()
    {
        return $this->hasMany(EntOrdenesCompras::className(), ['id_cliente' => 'id_cliente']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEntPagosRecibidos()
    {
        return $this->hasMany(EntPagosRecibidos::className(), ['id_cliente' => 'id_cliente']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWrkDestinos()
    {
        return $this->hasMany(WrkDestino::className(), ['id_cliente' => 'id_cliente']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWrkEnvios()
    {
        return $this->hasMany(WrkEnvios::className(), ['id_cliente' => 'id_cliente']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWrkOrigens()
    {
        return $this->hasMany(WrkOrigen::className(), ['id_cliente' => 'id_cliente']);
    }

    public function getNombreCompleto(){
        return $this->txt_nombre." ".$this->txt_apellido_paterno." ".$this->txt_apellido_materno;
    }


    public function getTipoCliente(){
        return $this->idTipoCliente->txt_nombre;
    }

    public static function getClienteByUddi($uddi){
        $cliente = self::find()->where(["uddi"=>$uddi])->one();

        if(!$cliente){
            throw new HttpException(404, "No existe el cliente");
        }

        return $cliente;
    }

    public function getUltimoEnvio(){
        $envio = WrkEnvios::find()->where(["id_cliente"=>$this->id_cliente])->orderBy("id_envio DESC")->one();

        return $envio;
    }

    public function fields(){
        $fields = parent::fields();
        unset($fields["id_cliente"]);
        $fields[]   = "nombreCompleto";
        //$fields[]   = "tipoCliente";
        return $fields;
    }
}
