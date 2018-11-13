<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\WrkDestino;
use yii\db\Expression;

/**
 * WrkDestinoSearch represents the model behind the search form of `app\models\WrkDestino`.
 */
class WrkDestinoSearch extends WrkDestino
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id_destino', 'num_exterior', 'num_interior', 'id_cliente', 'b_habilitado'], 'integer'],
            [['txt_nombre', 'uddi', 'txt_pais', 'txt_calle', 'txt_estado', 'txt_municipio', 'num_codigo_postal', 'num_telefono', 'num_telefono_movil', 'txt_colonia', 'txt_correo', 'txt_nombre_ubicacion', 'txt_empresa', 'txt_puesto'], 'safe'],
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
        $query = WrkDestino::find();

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
            'id_destino' => $this->id_destino,
            'num_exterior' => $this->num_exterior,
            'num_interior' => $this->num_interior,
            'id_cliente' => $this->id_cliente,
            'b_habilitado' => $this->b_habilitado,
        ]);

        $query->andFilterWhere(['like', 'txt_nombre', $this->txt_nombre])
            ->andFilterWhere(['like', 'uddi', $this->uddi])
            ->andFilterWhere(['like', 'txt_pais', $this->txt_pais])
            ->andFilterWhere(['like', 'txt_calle', $this->txt_calle])
            ->andFilterWhere(['like', 'txt_estado', $this->txt_estado])
            ->andFilterWhere(['like', 'txt_municipio', $this->txt_municipio])
            ->andFilterWhere(['like', 'num_codigo_postal', $this->num_codigo_postal])
            ->andFilterWhere(['like', 'num_telefono', $this->num_telefono])
            ->andFilterWhere(['like', 'num_telefono_movil', $this->num_telefono_movil])
            ->andFilterWhere(['like', 'txt_colonia', $this->txt_colonia])
            ->andFilterWhere(['like', 'txt_correo', $this->txt_correo])
            ->andFilterWhere(['like', 'txt_nombre_ubicacion', $this->txt_nombre_ubicacion])
            ->andFilterWhere(['like', 'txt_empresa', $this->txt_empresa])
            ->andFilterWhere(['like', 'txt_puesto', $this->txt_puesto]);

        return $dataProvider;
    }

    public function buscarDirecciones($params)
    {
        $query = WrkDestino::find()->groupBy(new Expression("CONCAT_WS(' ', txt_calle, num_exterior, num_interior)"));

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => ['defaultOrder'=>'id_destino desc']
        ]);

        $this->load($params, '');

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id_destino' => $this->id_destino,
            'num_exterior' => $this->num_exterior,
            'num_interior' => $this->num_interior,
            'id_cliente' => $this->id_cliente,
            'b_habilitado' => $this->b_habilitado,
        ]);

        $query->andFilterWhere(['like', 'txt_nombre', $this->txt_nombre])
            ->andFilterWhere(['like', 'uddi', $this->uddi])
            ->andFilterWhere(['like', 'txt_pais', $this->txt_pais])
            ->andFilterWhere(['like', 'txt_calle', $this->txt_calle])
            ->andFilterWhere(['like', 'txt_estado', $this->txt_estado])
            ->andFilterWhere(['like', 'txt_municipio', $this->txt_municipio])
            ->andFilterWhere(['like', 'num_codigo_postal', $this->num_codigo_postal])
            ->andFilterWhere(['like', 'num_telefono', $this->num_telefono])
            ->andFilterWhere(['like', 'num_telefono_movil', $this->num_telefono_movil])
            ->andFilterWhere(['like', 'txt_colonia', $this->txt_colonia])
            ->andFilterWhere(['like', 'txt_correo', $this->txt_correo])
            ->andFilterWhere(['like', 'txt_nombre_ubicacion', $this->txt_nombre_ubicacion])
            ->andFilterWhere(['like', 'txt_empresa', $this->txt_empresa])
            ->andFilterWhere(['like', 'txt_puesto', $this->txt_puesto]);

        return $dataProvider;
    }
}
