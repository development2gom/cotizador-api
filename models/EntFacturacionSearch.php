<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\EntFacturacion;
use yii\db\Expression;

/**
 * EntFacturacionSearch represents the model behind the search form of `app\models\EntFacturacion`.
 */
class EntFacturacionSearch extends EntFacturacion
{
    public $parametrosBusqueda;
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id_factura', 'num_exterior', 'num_interior', 'id_cliente', 'b_habilitado'], 'integer'],
            [["parametrosBusqueda"], "safe"],
            [['uddi', 'txt_rfc', 'txt_razon_social', 'txt_nombre', 'txt_apellido_paterno', 'txt_apellido_materno', 'txt_calle', 'txt_colonia', 'txt_estado', 'txt_pais'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
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
        $query = EntFacturacion::find();

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
            'id_factura' => $this->id_factura,
            'num_exterior' => $this->num_exterior,
            'num_interior' => $this->num_interior,
            'id_cliente' => $this->id_cliente,
            'b_habilitado' => $this->b_habilitado,
        ]);

        $query->andFilterWhere(['like', 'uddi', $this->uddi])
            ->andFilterWhere(['like', 'txt_rfc', $this->txt_rfc])
            ->andFilterWhere(['like', 'txt_razon_social', $this->txt_razon_social])
            ->andFilterWhere(['like', 'txt_nombre', $this->txt_nombre])
            ->andFilterWhere(['like', 'txt_apellido_paterno', $this->txt_apellido_paterno])
            ->andFilterWhere(['like', 'txt_apellido_materno', $this->txt_apellido_materno])
            ->andFilterWhere(['like', 'txt_calle', $this->txt_calle])
            ->andFilterWhere(['like', 'txt_colonia', $this->txt_colonia])
            ->andFilterWhere(['like', 'txt_estado', $this->txt_estado])
            ->andFilterWhere(['like', 'txt_pais', $this->txt_pais]);

        return $dataProvider;
    }

    public function buscarFactura($params)
    {
        $query = EntFacturacion::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params, '');

        if(!$this->validate())
        {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id_factura' => $this->id_factura,
            'b_habilitado' => $this->b_habilitado,
        ]);

        $query->andFilterWhere(['like','uddi',$this->uddi])
            ->orFilterWhere(['like', new Expression("CONCAT_WS(' ', txt_nombre, txt_apellido_paterno, txt_apellido_materno)"), $this->parametrosBusqueda])
            ->orFilterWhere(['like','txt_razon_social', $this->parametrosBusqueda])
            ->orFilterWhere(['like','txt_rfc',$this->parametrosBusqueda]);
            
        return $dataProvider;
    }
}
