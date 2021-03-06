<?php

namespace app\models;

use Yii;
use yii\web\HttpException;

/**
 * This is the model class for table "ent_facturacion".
 *
 * @property string $id_factura
 * @property string $uddi
 * @property string $txt_rfc
 * @property string $txt_razon_social
 * @property string $txt_nombre
 * @property string $txt_apellido_paterno
 * @property string $txt_apellido_materno
 * @property string $txt_calle
 * @property string $num_exterior
 * @property string $num_interior
 * @property string $txt_colonia
 * @property string $txt_estado
 * @property string $txt_pais
 * @property string $id_cliente
 * @property string $b_habilitado
 *
 * @property EntClientes $cliente
 */
class EntFacturacion extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ent_facturacion';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['uddi', 'txt_rfc', 'txt_razon_social', 'txt_calle', 'num_exterior', 'txt_colonia', 'txt_pais', 'id_cliente'], 'required'],
            [['num_exterior', 'num_interior', 'id_cliente', 'b_habilitado'], 'integer'],
            [['uddi', 'txt_razon_social'], 'string', 'max' => 100],
            [['txt_rfc'], 'string', 'max' => 13],
            [['txt_nombre', 'txt_apellido_paterno', 'txt_apellido_materno', 'txt_calle', 'txt_colonia', 'txt_estado', 'txt_pais'], 'string', 'max' => 50],
            [['uddi'], 'unique'],
            [['id_cliente'], 'exist', 'skipOnError' => true, 'targetClass' => EntClientes::className(), 'targetAttribute' => ['id_cliente' => 'id_cliente']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id_factura' => 'Id Factura',
            'uddi' => 'Uddi',
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

    public static function getFacturaByIdCliente($id_factura, $id_cliente){
        $facturacion = EntFacturacion::find()->where(["id_factura"=>$id_factura, "id_cliente"=>$id_cliente])->one();
        if(!$facturacion){
            throw new HttpException(404, 'No se encontraron datos de facturacion');
        }

        return $facturacion;
    }
    
    public static function getFacturacionUddi($uddi)
    {
        $factura = self::find()->where(["uddi"=>$uddi])->one();

        if(!$factura)
        {
            throw new HttpException(404,'No existe la factura');
        }
        return $factura;
    }
}
