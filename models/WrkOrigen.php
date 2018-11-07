<?php

namespace app\models;

use Yii;
use yii\web\HttpException;

/**
 * This is the model class for table "wrk_origen".
 *
 * @property int $id_origen
 * @property string $txt_nombre
 * @property string $txt_pais
 * @property string $txt_calle
 * @property string $txt_estado
 * @property string $txt_municipio
 * @property string $num_codigo_postal
 * @property string $num_telefono
 * @property string $num_telefono_movil
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
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'wrk_origen';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['txt_nombre', 'txt_pais', 'txt_calle', 'txt_colonia', 'num_exterior', 'id_cliente'], 'required'],
            [['id_cliente', 'b_habilitado'], 'integer'],
            [['txt_referencia'], 'string'],
            [['txt_nombre', 'txt_nombre_ubicacion', 'txt_empresa', 'txt_puesto'], 'string', 'max' => 100],
            [['txt_pais', 'txt_calle', 'txt_estado', 'txt_municipio', 'txt_colonia'], 'string', 'max' => 50],
            [['num_codigo_postal'], 'string', 'max' => 11],
            [['num_telefono', 'num_telefono_movil'], 'string', 'max' => 20],
            [['num_exterior', 'num_interior'], 'string', 'max' => 5],
            [['id_cliente'], 'exist', 'skipOnError' => true, 'targetClass' => EntClientes::className(), 'targetAttribute' => ['id_cliente' => 'id_cliente']],
        ];
    }

    /**
     * {@inheritdoc}
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

    public function guardar($idCliente){
        $this->uddi = Utils::generateToken("ori_");
        $this->id_cliente = $idCliente;
        if(!$this->save()){
            throw new HttpException(500, "No se pudo guardar en la base de datos\n".Utils::getErrors($this));
        }
    }

    public static function getOrigen($uddi){
        $model = self::find()->where(["uddi"=>$uddi])->one();
        if(!$model){
            throw new HttpException(404, "No se encuentra el origen");
        }

        return $model;
    }

    public function getDireccionCompleta(){
        return $this->txt_calle." ".$this->num_exterior." ".$this->num_interior." ".$this->txt_colonia." ".$this->txt_municipio." ".$this->txt_estado." ".$this->txt_pais." ".$this->num_codigo_postal;
    }

    public function getDireccionShort(){
        return $this->txt_calle . " " . $this->txt_municipio . " " . $this->txt_estado;
    }
}
