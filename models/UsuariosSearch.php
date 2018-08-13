<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\modules\ModUsuarios\models\EntUsuarios;
use app\modules\ModUsuarios\models\Utils;


/**
 * UsuariosSearch represents the model behind the search form about `app\models\EntUsuarios`.
 */
class UsuariosSearch extends EntUsuarios
{
    public $nombreCompleto;
    public $roleDescription;
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id_usuario', 'id_status'], 'integer'],
            [['roleDescription','id_call_center','roleDescription','nombreCompleto','txt_auth_item', 'txt_token', 'txt_imagen', 'txt_username', 'txt_apellido_paterno', 'txt_apellido_materno', 'txt_auth_key', 'txt_password_hash', 'txt_password_reset_token', 'txt_email', 'fch_creacion', 'fch_actualizacion'], 'safe'],
        ];
    }

    

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = EntUsuarios::find()->leftJoin("auth_item", "auth_item.name= mod_usuarios_ent_usuarios.txt_auth_item");

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 30,
                
            ],
            'sort' => [
                'defaultOrder' => [
                    'txt_username' => \SORT_ASC,
                    'txt_apellido_paterno' => \SORT_ASC,
                ]
            ],
        ]);
        $dataProvider->sort->attributes['nombreCompleto'] = [
        
            'asc' => ['txt_username' => SORT_ASC, 'txt_apellido_paterno' => SORT_ASC],
            'desc' => ['txt_username' => SORT_DESC, 'txt_apellido_paterno' => SORT_DESC], 
        ];
        
        $dataProvider->sort->attributes['roleDescription'] = [
        
            'asc' => ['auth_item.description' => SORT_ASC],
            'desc' => ['auth_item.description' => SORT_DESC], 
        ];

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            
            'fch_actualizacion' => $this->fch_actualizacion,
            'id_status' => $this->id_status,
        ]);


        if($this->fch_creacion){
            $this->fch_creacion = Utils::changeFormatDateInputShort($this->fch_creacion);
        }  
        
        
        $query->andFilterWhere(['in','txt_auth_item', $this->txt_auth_item])
            ->andFilterWhere(['like', 'txt_token', $this->txt_token])
            ->andFilterWhere(['like', 'txt_imagen', $this->txt_imagen])
            ->andFilterWhere(['like', 'txt_auth_key', $this->txt_auth_key])
            ->andFilterWhere(['like', 'txt_password_hash', $this->txt_password_hash])
            ->andFilterWhere(['like', 'txt_password_reset_token', $this->txt_password_reset_token])
            ->andFilterWhere(['like', 'txt_email', $this->txt_email])
            ->andFilterWhere(['like', 'fch_creacion', $this->fch_creacion])
            
            ->andFilterWhere(['like', 'txt_auth_item', $this->roleDescription])
            ->andFilterWhere(['like', 'CONCAT(txt_username, " ", IF(ISNULL(txt_apellido_paterno), "", txt_apellido_paterno))', $this->nombreCompleto]);
  

        if($this->fch_creacion){
            $this->fch_creacion = Utils::changeFormatDate($this->fch_creacion);
        }

         // filter by person full name
        //  $query->andWhere('txt_username LIKE "%' . $this->nombreCompleto . '%" ' .
        //  'OR txt_apellido_paterno LIKE "%' . $this->nombreCompleto . '%"');

        return $dataProvider;
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function searchByType($params)
    {
        $query = EntUsuarios::find()->leftJoin("auth_item", "auth_item.name= mod_usuarios_ent_usuarios.txt_auth_item");
        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 1,
            ],
            'sort' => [
                'defaultOrder' => [
                    'txt_username' => \SORT_ASC,
                    'txt_apellido_paterno' => \SORT_ASC,
                ]
            ],
        ]);

        $dataProvider->sort->attributes['nombreCompleto'] = [
        
            'asc' => ['txt_username' => SORT_ASC, 'txt_apellido_paterno' => SORT_ASC],
            'desc' => ['txt_username' => SORT_DESC, 'txt_apellido_paterno' => SORT_DESC],
            
            
        
    ];

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id_usuario' => $this->id_usuario,
            'fch_creacion' => $this->fch_creacion,
            'fch_actualizacion' => $this->fch_actualizacion,
            'id_status' => $this->id_status,
            'txt_auth_item'=>$this->txt_auth_item
        ]);

        $query->andFilterWhere(['like', 'txt_token', $this->txt_token])
            ->andFilterWhere(['like', 'txt_imagen', $this->txt_imagen])
            ->andFilterWhere(['like', 'txt_auth_key', $this->txt_auth_key])
            ->andFilterWhere(['like', 'txt_password_hash', $this->txt_password_hash])
            ->andFilterWhere(['like', 'txt_password_reset_token', $this->txt_password_reset_token])
            ->andFilterWhere(['like', 'txt_email', $this->txt_email])
            ->andFilterWhere(['like', 'CONCAT(txt_username, " ", IF(ISNULL(txt_apellido_paterno), "", txt_apellido_paterno))', $this->nombreCompleto]);
           

        return $dataProvider;
    }
}
