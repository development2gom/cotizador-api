<?php

namespace app\models;

use Yii;
use yii\web\IdentityInterface;

/**
 * This is the model class for table "ent_clientes".
 *
 * @property string $id_cliente
 * @property string $txt_nombre
 * @property string $txt_apellido_paterno
 * @property string $txt_apellido_materno
 * @property int $num_telefono
 * @property string $txt_correo
 
 * @property string $b_habilitado
 *
 * @property EntFacturacion[] $entFacturacions
 * @property WrkDestino[] $wrkDestinos
 * @property WrkEnvios[] $wrkEnvios
 * @property WrkOrigen[] $wrkOrigens
 */
class EntClientes extends \yii\db\ActiveRecord implements IdentityInterface
{
    public $repeatPassword;
    public $password;
    public $repeatEmail;
    public $auth_key;
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
            [
                ['txt_correo', 'repeatEmail'], 'trim'
            ],
            [
                ['txt_correo', 'repeatEmail'], 'email'
            ],

            [
                'repeatEmail',
                'compare',
                'compareAttribute' => 'txt_correo',
                'message' => 'Los correo eléctronicos deben coincidir'
            ],
            [
                'repeatPassword',
                'compare',
                'compareAttribute' => 'password',
                'on' => 'registerInput',
                'message' => 'Las contraseñas deben coincidir'
            ],
            [
                ['txt_nombre', "txt_apellido_paterno"], 'required', 'on' => 'contacto'
            ],
            [
                ['txt_nombre', "txt_apellido_paterno", 'repeatEmail', "repeatPassword"], 'required', 'on' => 'registerInput'
            ],


            [['uddi', 'txt_nombre', "txt_correo", "password"], 'required'],
            [['fch_alta'], 'safe'],
            [['b_habilitado'], 'integer'],
            [['uddi', 'txt_nombre', 'txt_correo'], 'string', 'max' => 100],
            [['num_telefono'], 'string', 'max' => 45],
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
            'txt_nombre' => 'Nombre',
            'txt_apellido_paterno' => 'Apellido Paterno',
            'txt_apellido_materno' => 'Apellido Materno',
            'num_telefono' => 'Telefono',
            'txt_correo' => 'Email',
            'repeatPassword' => 'Repetir contraseña',
            'password' => 'Contraseña',
            'repeatEmail' => 'Repetir email',
            'b_habilitado' => 'Habilitado',
        ];
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

    /**
     * Finds an identity by the given ID.
     *
     * @param string|int $id the ID to be looked for
     * @return IdentityInterface|null the identity object that matches the given ID.
     */
    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    /**
     * Finds an identity by the given token.
     *
     * @param string $token the token to be looked for
     * @return IdentityInterface|null the identity object that matches the given token.
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        return static::findOne(['uddi' => $token]);
    }

    /**
     * @return int|string current user ID
     */
    public function getId()
    {
        return $this->id_cliente;
    }

    /**
     * @return string current user auth key
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * @param string $authKey
     * @return bool if auth key is valid for current user
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    public static function getUsuarioLogueado()
    {
        return Yii::$app->user->identity;
    }

    public function getNombreCompleto()
    {
        return $this->txt_nombre . " " . $this->txt_apellido_paterno;
    }
}
