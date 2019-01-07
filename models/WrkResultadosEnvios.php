<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "wrk_resultados_envios".
 *
 * @property int $id_resultado_envio
 * @property string $uddi
 * @property int $id_envio
 * @property string $txt_traking_number
 * @property string $txt_envio_code
 * @property string $txt_envio_code_2
 * @property string $txt_tipo_empaque
 * @property string $txt_tipo_servicio
 * @property string $txt_etiqueta_formato
 * @property string $txt_data
 *
 * @property WrkEnvios $envio
 */
class WrkResultadosEnvios extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'wrk_resultados_envios';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['uddi', 'id_envio', 'txt_traking_number', 'txt_tipo_servicio', 'txt_etiqueta_formato', 'txt_data'], 'required'],
            [['id_envio'], 'integer'],
            [['txt_data'], 'string'], 
            [['uddi'], 'string', 'max' => 65],
            [['txt_traking_number', 'txt_envio_code', 'txt_envio_code_2', 'txt_tipo_empaque', 'txt_tipo_servicio', 'txt_etiqueta_formato'], 'string', 'max' => 45],
            [['uddi'], 'unique'],
            [['id_envio'], 'exist', 'skipOnError' => true, 'targetClass' => WrkEnvios::className(), 'targetAttribute' => ['id_envio' => 'id_envio']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id_resultado_envio' => 'Id Resultado Envio',
            'uddi' => 'Uddi',
            'id_envio' => 'Id Envio',
            'txt_traking_number' => 'Txt Traking Number',
            'txt_envio_code' => 'Txt Envio Code',
            'txt_envio_code_2' => 'Txt Envio Code 2',
            'txt_tipo_empaque' => 'Txt Tipo Empaque',
            'txt_tipo_servicio' => 'Txt Tipo Servicio',
            'txt_etiqueta_formato' => 'Txt Etiqueta Formato',
            'txt_data' => 'Txt Data',
        ];
    }
    
    public function fields(){
        $fields = parent::fields(); 
        $fields[] = "etiquetaUrl";

        return $fields;
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEnvio()
    {
        return $this->hasOne(WrkEnvios::className(), ['id_envio' => 'id_envio']);
    }


    

    public function generarPDF($etiquetaB64){

        //$file = base64_encode($etiquetaB64);

        $decoded = base64_decode($etiquetaB64);//echo $decoded;exit;
        $basePath = "trackings/" . $this->envio->uddi . '/' ;

        Files::validarDirectorio($basePath);

        $file2 = $basePath . $this->uddi . '-tracking.pdf';
        $fp = fopen($file2, "w+");
        file_put_contents($file2, $decoded);

        
    }


    public function getEtiquetaUrl(){
            $res = Yii::$app->urlManager->createAbsoluteUrl([''])."envios/descargar-etiqueta?uddi=" . $this->envio->uddi  . '&uddilabel='. $this->uddi;
        return $res;
    }
}
