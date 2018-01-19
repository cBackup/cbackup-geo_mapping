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
 *
 * @var $this   yii\web\View
 * @var $data   \app\modules\plugins\geomapping\models\Geolocation
 * @var $module \app\modules\plugins\geomapping\GeoMapping
 */

use yii\helpers\Html;
use yii\helpers\Json;

/** @noinspection PhpUndefinedFieldInspection */
$module = $this->context->module;

$data_set = [
    'node_id'   => $data->id,
    'latitude'  => $data->latitude,
    'longitude' => $data->longitude,
    'node_data' => [
        'hostname' => $data->node->hostname,
        'ip'       => $data->node->ip,
        'device'   => "{$data->node->device->vendor} {$data->node->device->model}"
    ]
];

/** Register dataset */
$this->registerJs("var data = " . Json::htmlEncode($data_set) . ";");
?>

<div class="row">
    <div class="col-md-12">
        <div class="box box-default box-solid">
            <div class="box-header box-header-narrow text-center">
                <h3 class="box-title"><?= $module::t('general', 'Info about geolocation') ?></h3>
            </div>
            <div class="box-body no-padding">
                <table class="table">
                    <tbody>
                        <tr>
                            <th width="15%"><?= $module::t('general', 'Full address') ?></th>
                            <td width="40%"><?= (!empty($data->full_address)) ? $data->full_address : Yii::t('yii', '(not set)') ?></td>
                            <th width="15%"><?= $module::t('general', 'Search address') ?></th>
                            <td width="30%"><?= (!empty($data->last_query)) ? $data->last_query : Yii::t('yii', '(not set)') ?></td>
                        </tr>
                    </tbody>
                </table>
                <div id="map_<?= $data->id ?>" style="width:100%; height:400px; background-color: #FBFBFB">
                    <div style="margin-left: 34%; padding-top:17%;"><?= Html::img('@web/img/modal_loading.gif', ['alt' => Yii::t('app', 'Loading...')]) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>
