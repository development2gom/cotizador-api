<?php

namespace app\models;

use Yii;
use yii\web\HttpException;
use yii\db\Exception;
use yii\helpers\Url;

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
 * @property WrkResultadosEnvios[] $wrkResultadosEnvios 
 * @property WrkSobres[] $wrkSobres 
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
            [['id_tipo_empaque', 'id_destino', 'id_origen', 'id_proveedor'], 'required'],
            [['num_costo_envio', 'num_impuesto', 'num_subtotal'], 'number'],
            [['txt_folio', 'txt_tipo'], 'string', 'max' => 50],
            [['txt_currency'], 'string', 'max' => 20],
            [['uddi'], 'string', 'max' => 100],
            [['txt_currency'], 'safe'],
            [['uddi'], 'unique'],
            [['id_cliente'], 'exist', 'skipOnError' => true, 'targetClass' => EntClientes::className(), 'targetAttribute' => ['id_cliente' => 'id_cliente']],
            [['id_destino'], 'exist', 'skipOnError' => true, 'targetClass' => WrkDestino::className(), 'targetAttribute' => ['id_destino' => 'id_destino']],
            [['id_origen'], 'exist', 'skipOnError' => true, 'targetClass' => WrkOrigen::className(), 'targetAttribute' => ['id_origen' => 'id_origen']],
            [['id_pago'], 'exist', 'skipOnError' => true, 'targetClass' => EntPagosRecibidos::className(), 'targetAttribute' => ['id_pago' => 'id_pago_recibido']],
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
    public function getSobres()
    {
        return $this->hasMany(WrkSobres::className(), ['id_envio' => 'id_envio']);
    }

    /** 
    * @return \yii\db\ActiveQuery 
    */ 
   public function getExtras() 
   { 
       return $this->hasMany(RelEnvioExtras::className(), ['id_envio' => 'id_envio']); 
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
        return $this->hasOne(EntPagosRecibidos::className(), ['id_pago_recibido' => 'id_pago']);
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

    /**
    * @return \yii\db\ActiveQuery
    */
   public function getWrkResultadosEnvios()
   {
       return $this->hasMany(WrkResultadosEnvios::className(), ['id_envio' => 'id_envio']);
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
    public function generarEnvio($cliente, $origen, $destino, $proveedor, $tipoEmpaque, $paquetes, $sobre){

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

            if(strtoupper($this->id_tipo_empaque)=="SOBRE"){
                $datosSobre = new WrkSobres();
                $datosSobre->id_envio = $this->id_envio;
                $datosSobre->num_peso =$sobre["num_peso"];
                $datosSobre->save();
               
            }else{
                foreach($paquetes as $paquete){
                    
                    $paqueteGuardar = new WrkEmpaque();
                    $paqueteGuardar->num_paquetes = $paquete["num_paquetes"];
                    $paqueteGuardar->num_peso = $paquete["num_peso"];
                    $paqueteGuardar->num_alto = $paquete["num_alto"];
                    $paqueteGuardar->num_ancho = $paquete["num_ancho"];
                    $paqueteGuardar->num_largo = $paquete["num_largo"];
                    $paqueteGuardar->id_envio = $this->id_envio;
                    
                    if(!$paqueteGuardar->save()){
                        throw new HttpException(500, "No se pudo guardar en la base de datos\n".Utils::getErrors($paqueteGuardar));
                    }
                    
                }
            }

            $transaction->commit();
        }
      
        
        //$transaction = $envio->getDb()->beginTransaction();

    }

    public function guardarNumeroRastreo($rastreo, $identificador){
        $this->txt_identificador_proveedor = $identificador ;
        $this->txt_tracking_number = $rastreo;
        $this->save();
    }

    public function getEtiquetaUrl(){
        $res = [];
        foreach($this->wrkResultadosEnvios as $item){
            $res[] = Yii::$app->urlManager->createAbsoluteUrl([''])."envios/descargar-etiqueta?uddi=".$this->uddi.'&uddilabel='. $item->uddi;
        }
        return $res;
    }

    public function generarPDF($response){

        if (isset($response->HighestSeverity) && $response->HighestSeverity != "ERROR") {
            $file = base64_encode($response->CompletedShipmentDetail->CompletedPackageDetails->Label->Parts->Image);

            $decoded = base64_decode($file);//echo $decoded;exit;
            $basePath = "trackings/".$this->uddi.'/';

            Files::validarDirectorio($basePath);

            $file2 = $basePath.'tracking.pdf';
            $fp = fopen($file2, "w+");
            file_put_contents($file2, $decoded);

          
        }else{

        }
    }

    public function fields(){
        $fields = parent::fields();
        unset($fields["id_origen"], $fields["id_destino"], $fields["id_proveedor"], $fields["id_pago"], $fields["id_cliente"], $fields["id_tipo_empaque"]);
        
        $fields[] = "origen";
        $fields[] = "destino";
        $fields[] = "proveedor";
        $fields[] = "pago";
        $fields[] = "cliente";
        $fields[] = "etiquetaUrl";
        $fields[] = "tipoEmpaque";
        $fields[] = "empaque";
        $fields[] = "sobres";
        $fields[] = "extras";
        $fields[] = "costoTotalEnvio";
        $fields[] = "costoExtrasEnvio";
        $fields[] = "wrkResultadosEnvios";
        $fields[] = "puedeFacturar";
        $fields[] = "facturaXmlUrl";
        $fields[] = "facturaPdfUrl";
        
        return $fields;
    }

    public function extraFields(){
        
    }

    /**
     * Guarda el envio
     */
    public function generarNuevoEnvio($cliente = null, $origen, $destino, $proveedor, $tipoEmpaque, $paquetes = null, $sobre = null){

        $transaction = Yii::$app->getDb()->beginTransaction();

        $this->num_costo_envio = str_replace( ',', '', $this->num_costo_envio );
        $this->num_subtotal = str_replace( ',', '', $this->num_subtotal  );
        
        if($cliente){
            // Guardar datos del destino
            $destino->guardar($cliente->id_cliente);

            // Guardar datos del origen
            $origen->guardar($cliente->id_cliente);
        }else{
            // Guardar datos del destino
            $destino->guardar(null);

            // Guardar datos del origen
            $origen->guardar(null);
        }

        $this->id_origen = $origen->id_origen;
        $this->id_destino = $destino->id_destino;

        if($cliente)
            $this->id_cliente = $cliente->id_cliente;

        $this->id_proveedor = $proveedor->id_proveedor;
        $this->id_tipo_empaque = $tipoEmpaque->id_tipo_empaque;
        $this->uddi = Utils::generateToken("env_");

        // Guardar los datos del envio
        if(!$this->save()){

            $transaction->rollBack();
            throw new HttpException(500, "No se pudo guardar en la base de datos\n".Utils::getErrors($this));
        }else{

            if(strtoupper($tipoEmpaque->uddi)=="SOBRE"){
                $datosSobre = new WrkSobres();
                $datosSobre->id_envio = $this->id_envio;
                $datosSobre->num_peso =$sobre["num_peso"]/1000;
                $datosSobre->save();
               
            }else{
                foreach($paquetes as $paquete){
                    
                    $paqueteGuardar = new WrkEmpaque();
                    $paqueteGuardar->num_paquetes = $paquete["num_paquetes"];
                    $paqueteGuardar->num_peso = $paquete["num_peso"];
                    $paqueteGuardar->num_alto = $paquete["num_alto"];
                    $paqueteGuardar->num_ancho = $paquete["num_ancho"];
                    $paqueteGuardar->num_largo = $paquete["num_largo"];
                    $paqueteGuardar->id_envio = $this->id_envio;
                    
                    if(!$paqueteGuardar->save()){
                        throw new HttpException(500, "No se pudo guardar en la base de datos\n".Utils::getErrors($paqueteGuardar));
                    }
                    
                }
            }

            $transaction->commit();
        }
    }

    /**
     * Guarda el envio
     */
    public function actualizarEnvio($cliente = null, $origen, $destino){

        $transaction = Yii::$app->getDb()->beginTransaction();
        
        if($cliente){
            // Guardar datos del destino
            $destino->guardar($cliente->id_cliente);

            // Guardar datos del origen
            $origen->guardar($cliente->id_cliente);
        }else{
            // Guardar datos del destino
            $destino->guardar(null);

            // Guardar datos del origen
            $origen->guardar(null);
        }

        $this->id_origen = $origen->id_origen;
        $this->id_destino = $destino->id_destino;

        if($cliente)
            $this->id_cliente = $cliente->id_cliente;

       

        // Guardar los datos del envio
        if(!$this->save()){

            $transaction->rollBack();
            throw new HttpException(500, "No se pudo guardar en la base de datos\n".Utils::getErrors($this));
        }else{

            

            $transaction->commit();
        }
    }


    /**
     * Funcion que recupera el monto total del envio incluyendo los extras
     */
    public function getCostoTotalEnvio(){
        $monto = $this->num_costo_envio;

        foreach ($this->extras as $item){
            $monto += $item->num_precio * $item->num_unidades;
        }

        return $monto;
    }

    public function getCostoExtrasEnvio(){
        $monto = 0;

        foreach ($this->extras as $item){
            $monto += $item->num_precio * $item->num_unidades;
        }

        return $monto;
    }

    public function getFacturaPdfUrl(){
        if($this->cliente == null){
            return "";
        }
        return Yii::$app->urlManager->createAbsoluteUrl([''])."/facturas/" . $this->cliente->uddi . "/" . $this->uddi . "/factura.pdf";
    }

    public function getFacturaXmlUrl(){ 
        if($this->cliente == null){
            return "";
        }
        return Yii::$app->urlManager->createAbsoluteUrl([''])."/facturas/" . $this->cliente->uddi . "/" . $this->uddi . "/factura.xml";
    }

    public function getPuedeFacturar(){

        $maxDaysFacturar = 10; //Días maximos para poder facturar

        $interval = date_diff(new \DateTime(Calendario::getFechaActual()), new \DateTime($this->fch_creacion));
        if(abs($interval->format('%a')) > $maxDaysFacturar ){
            return false;
        }
        return true;
    }
}
