<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "wrk_consolidado_facturas".
 *
 * @property int $id_consolidado_factura
 * @property string $uddi
 * @property int $num_envios
 * @property string $txt_tipo
 * @property string $fch_facturacion
 * @property string $fch_evento
 * @property string $txt_ruta_pdf
 * @property string $txt_ruta_xml
 * @property string $txt_monto
 */
class WrkConsolidadoFacturas extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'wrk_consolidado_facturas';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['uddi', 'num_envios', 'txt_tipo', 'fch_facturacion', 'txt_ruta_pdf', 'txt_ruta_xml'], 'required'],
            [['num_envios'], 'integer'],
            [['fch_evento'], 'safe'],
            [['uddi', 'fch_facturacion', 'txt_monto'], 'string', 'max' => 45],
            [['txt_tipo'], 'string', 'max' => 45],
            [['txt_ruta_pdf', 'txt_ruta_xml'], 'string', 'max' => 100],
            [['uddi'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id_consolidado_factura' => 'Id Consolidado Factura',
            'uddi' => 'Uddi',
            'num_envios' => 'Num Envios',
            'txt_tipo' => 'Txt Tipo',
            'fch_facturacion' => 'Fch Facturacion',
            'fch_evento' => 'Fch Evento',
            'txt_ruta_pdf' => 'Txt Ruta Pdf',
            'txt_ruta_xml' => 'Txt Ruta Xml',
            'txt_monto' => 'Txt Monto',
        ];
    }
}
