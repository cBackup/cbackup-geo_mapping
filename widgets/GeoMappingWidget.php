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

namespace app\modules\plugins\geomapping\widgets;

use yii\base\Widget;
use app\models\Node;
use app\modules\plugins\geomapping\models\Geolocation;


/**
 * @package app\modules\plugins\geomapping\widgets
 */
class GeoMappingWidget extends Widget
{

    /**
     * @var int
     */
    public $node_id;

    /**
     * Plugin context
     *
     * @var object
     */
    public $plugin;

    /**
     * @var array
     */
    public $data = [];

    /**
     * @var array
     */
    public $node = [];

    /**
     * Prepare dataset
     *
     * @return void
     */
    public function init()
    {
        /** Access plugin data */
        $this->plugin = \Yii::$app->getModule('plugins/geomapping');
        $this->data   = Geolocation::find()->where(['node_id' => $this->node_id])->one();
        $this->node   = Node::find()->select(['hostname', 'prepend_location', 'location'])->where(['id' => $this->node_id])->asArray()->one();
    }

    /**
     * Render geo mapping view
     *
     * @return string
     */
    public function run()
    {
        return $this->render('geo_mapping_widget', [
            'plugin'  => $this->plugin,
            'node_id' => $this->node_id,
            'data'    => $this->data,
            'node'    => $this->node
        ]);
    }

}
