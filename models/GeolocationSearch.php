<?php
/**
 * cBackup GeoMapping Plugin
 * Copyright (C) 2017, Oļegs Čapligins, Imants Černovs, Dmitrijs Galočkins
 *
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 */

namespace app\modules\plugins\geomapping\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;


/**
 * GeolocationSearch represents the model behind the search form about `app\modules\plugins\geomapping\models\Geolocation`.
 */
class GeolocationSearch extends Geolocation
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'node_id'], 'integer'],
            [['full_address', 'last_query', 'address_data', 'latitude', 'longitude', 'created', 'modified', 'node_info'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
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
        $query = Geolocation::find();

        $query->joinWith(['node n']);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort'  => [
                'attributes'   => [
                    'node_id', 'latitude', 'longitude', 'created', 'modified',
                    'node_info' => [
                        'asc'  => ['n.hostname' => SORT_ASC],
                        'desc' => ['n.hostname' => SORT_DESC],
                    ],
                ]
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id'       => $this->id,
            'node_id'  => $this->node_id,
            'created'  => $this->created,
            'modified' => $this->modified,
        ]);

        $query->andFilterWhere(['like', 'full_address', $this->full_address])
            ->andFilterWhere(['like', 'last_query', $this->last_query])
            ->andFilterWhere(['like', 'address_data', $this->address_data])
            ->andFilterWhere(['like', 'latitude', $this->latitude])
            ->andFilterWhere(['like', 'longitude', $this->longitude])
            ->orFilterWhere(['like','n.hostname', $this->node_info])
            ->orFilterWhere(['like','n.ip', $this->node_info]);

        return $dataProvider;
    }
}
