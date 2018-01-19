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
 * LogGeoSearch represents the model behind the search form about `app\modules\plugins\geomapping\models\LogGeo`.
 */
class LogGeoSearch extends LogGeo
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id'], 'integer'],
            [['userid', 'time', 'severity', 'action', 'message', 'node_info', 'date_from', 'date_to', 'page_size'], 'safe'],
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
        $query = LogGeo::find();

        $query->joinWith(['node n']);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['id' => SORT_DESC],
            ]
        ]);

        $this->load($params);

        /** Set page size dynamically */
        $dataProvider->pagination->pageSize = $this->page_size;

        /** Time interval search  */
        if (!empty($this->date_from) && !empty($this->date_to)) {
            $query->andFilterWhere(['between', 'CAST(time AS DATE)', $this->date_from, $this->date_to]);
        }
        elseif (!empty($this->date_from)) {
            $query->andFilterWhere(['>=', 'CAST(time AS DATE)', $this->date_from]);
        }
        elseif (!empty($this->date_to)) {
            $query->andFilterWhere(['<=', 'CAST(time AS DATE)', $this->date_to]);
        }

        if (!$this->validate()) {
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'time' => $this->time,
        ]);

        $query->andFilterWhere(['like', 'userid', $this->userid])
            ->andFilterWhere(['like', 'severity', $this->severity])
            ->andFilterWhere(['like', 'action', $this->action])
            ->andFilterWhere(['like', 'message', $this->message])
            ->orFilterWhere(['like','n.hostname', $this->node_info])
            ->orFilterWhere(['like','n.ip', $this->node_info]);

        return $dataProvider;
    }
}
