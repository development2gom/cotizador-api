<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\WrkEnvios;

/**
 * WrkEnviosSearch represents the model behind the search form of `app\models\WrkEnvios`.
 */
class WrkEnviosSearch extends WrkEnvios
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id_envio', 'id_origen', 'id_destino', 'id_proveedor', 'id_pago', 'id_cliente', 'id_tipo_empaque', 'b_habilitado'], 'integer'],
            [['num_costo_envio', 'num_impuesto', 'num_subtotal'], 'number'],
            [['txt_folio', 'txt_tipo', 'uddi'], 'safe'],
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
        $query = WrkEnvios::find();

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
            'id_envio' => $this->id_envio,
            'id_origen' => $this->id_origen,
            'id_destino' => $this->id_destino,
            'id_proveedor' => $this->id_proveedor,
            'id_pago' => $this->id_pago,
            'id_cliente' => $this->id_cliente,
            'id_tipo_empaque' => $this->id_tipo_empaque,
            'num_costo_envio' => $this->num_costo_envio,
            'num_impuesto' => $this->num_impuesto,
            'num_subtotal' => $this->num_subtotal,
            'b_habilitado' => $this->b_habilitado,
        ]);

        $query->andFilterWhere(['like', 'txt_folio', $this->txt_folio])
            ->andFilterWhere(['like', 'txt_tipo', $this->txt_tipo])
            ->andFilterWhere(['like', 'uddi', $this->uddi]);

        return $dataProvider;
    }

    
}
