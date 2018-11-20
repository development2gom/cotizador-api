<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\EntClientes;
use yii\db\Expression;

/**
 * EntClientesSearch represents the model behind the search form of `app\models\EntClientes`.
 */
class EntClientesSearch extends EntClientes
{
    public $parametrosBusqueda;
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id_cliente', 'b_habilitado'], 'integer'],
            [["parametrosBusqueda"], "safe"],
            [['uddi', 'txt_nombre', 'txt_apellido_paterno', 'txt_apellido_materno', 'num_telefono', 'txt_correo'], 'safe'],
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
        $query = EntClientes::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params, '');

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id_cliente' => $this->id_cliente,
            'b_habilitado' => $this->b_habilitado,
        ]);

        $query->andFilterWhere(['like', 'uddi', $this->uddi])
            ->andFilterWhere(['like', 'txt_nombre', $this->txt_nombre])
            ->andFilterWhere(['like', 'txt_apellido_paterno', $this->txt_apellido_paterno])
            ->andFilterWhere(['like', 'txt_apellido_materno', $this->txt_apellido_materno])
            ->andFilterWhere(['like', 'num_telefono', $this->num_telefono])
            ->andFilterWhere(['like', 'txt_correo', $this->txt_correo]);

        return $dataProvider;
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function searchClientes($params, $page=0)
    {
        $query = EntClientes::find();        

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 10,
                'page' => $page
            ],
            'sort' => [
                'defaultOrder' => [
                    'txt_nombre' => SORT_ASC,
                ]
            ],
        ]);

        $this->attributes = $params;

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        /// grid filtering conditions
        $query->andFilterWhere([
            'id_cliente' => $this->id_cliente,
            'b_habilitado' => $this->b_habilitado,
        ]);

        $query->andFilterWhere(['like', 'uddi', $this->uddi])
            ->andFilterWhere(['like', 'txt_nombre', $this->txt_correo])
            ->andFilterWhere(['like', 'txt_apellido_paterno', $this->txt_apellido_paterno])
            ->andFilterWhere(['like', 'txt_apellido_materno', $this->txt_apellido_materno])
            ->andFilterWhere(['like', 'num_telefono', $this->num_telefono])
            ->orFilterWhere(['like', 'txt_correo', $this->txt_correo]);

        return $dataProvider;
    }

    public function buscarClientes($params)
    {
        $query = EntClientes::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params, '');


        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id_cliente' => $this->id_cliente,
            'b_habilitado' => $this->b_habilitado,
        ]);

        $query->andFilterWhere(['like', 'uddi', $this->uddi])
            ->orFilterWhere(['like', new Expression("CONCAT_WS(' ', txt_nombre, txt_apellido_paterno, txt_apellido_materno)"), $this->parametrosBusqueda])
            ->orFilterWhere(['like', 'num_telefono', $this->parametrosBusqueda])
            ->orFilterWhere(['like', 'txt_correo', $this->parametrosBusqueda]);

        return $dataProvider;
    }
}
