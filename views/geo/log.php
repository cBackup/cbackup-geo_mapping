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
 * @var $this          yii\web\View
 * @var $dataProvider  yii\data\ActiveDataProvider
 * @var $searchModel   app\modules\plugins\geomapping\models\LogGeoSearch
 * @var $module        app\modules\plugins\geomapping\GeoMapping
 * @var $users         array
 * @var $severities    array
 * @var $actions       array
 */

use app\models\Setting;
use yii\helpers\Html;
use yii\widgets\Pjax;
use yii\grid\GridView;
use app\helpers\GridHelper;

app\assets\Select2Asset::register($this);
app\assets\LogAsset::register($this);
app\assets\LaddaAsset::register($this);
app\assets\DatetimepickerAsset::register($this);

/** @noinspection PhpUndefinedFieldInspection */
$module = $this->context->module;

$this->title = Yii::t('app', 'Logs');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Plugins')];
$this->params['breadcrumbs'][] = ['label' => $module::t('general', 'Geo mapping'), 'url' => ['/plugins/geomapping']];
$this->params['breadcrumbs'][] = ['label' => $this->title];

$this->registerJs(
/** @lang JavaScript */
    "
        /** Select2 init */
        $('.select2').select2({
            minimumResultsForSearch: -1,
            width : '100%'
        });
       
        /** Select2 with search */
        $('.select2-search').select2({
            width : '100%'
        });
       
        /** Show search form on button click */
        $('.search-button').click(function() {
            $('.geo-log-search').slideToggle('slow');
            return false;
        });
        
        /** Geo log search form submit and reload gridview */
        $('.geo-log-search-form form').submit(function(e) {
            e.stopImmediatePropagation(); // Prevent double submit
            gridLaddaSpinner('spin_btn'); // Show button spinner while search in progress
            $.pjax.reload({container:'#geo-log-pjax', url: window.location.pathname + '?' + $(this).serialize(), timeout: 10000}); // Reload GridView
            return false;
        });
       
    "
);
?>

<div class="row">
    <div class="col-md-12">
        <div class="box">
            <div class="box-header with-border">
                <i class="fa fa-list"></i><h3 class="box-title box-title-align"><?= $module::t('general', 'Geolocation logs') ?></h3>
                <div class="pull-right">
                    <?php
                        echo Html::a('<i class="fa fa-search"></i> ' . Yii::t('app', 'Search'), 'javascript:void(0);', [
                            'class' => 'btn btn-sm bg-light-black search-button'
                        ]);
                    ?>
                </div>
            </div>
            <div class="box-body no-padding">
                <div class="geo-log-search" style="display: none;">
                    <?php
                        echo $this->render('_search', [
                            'model'      => $searchModel,
                            'users'      => $users,
                            'severities' => $severities,
                            'actions'    => $actions,
                        ]);
                    ?>
                </div>
                <?php Pjax::begin(['id' => 'geo-log-pjax']); ?>
                    <?php
                        /** @noinspection PhpUnhandledExceptionInspection */
                        echo GridView::widget([
                            'id'           => 'geo-log-grid',
                            'tableOptions' => ['class' => 'table table-bordered log-table'],
                            'dataProvider' => $dataProvider,
                            'afterRow'     => function($model) { /** @var $model \app\modules\plugins\geomapping\models\LogGeo */
                                $id = 'message_' . $model->id;
                                return '<tr><td class="grid-expand-row" colspan="6"><div class="grid-expand-div" id="'.$id.'">'.nl2br($model->message).'</div></td></tr>';
                            },
                            'layout'  => '{items}<div class="row"><div class="col-sm-3"><div class="gridview-summary">{summary}</div></div><div class="col-sm-9"><div class="gridview-pager">{pager}</div></div></div>',
                            'columns' => [
                                [
                                    'format'         => 'raw',
                                    'options'        => ['style' => 'width:3%'],
                                    'contentOptions' => ['class' => 'text-center', 'style' => 'vertical-align: middle;'],
                                    'value'          => function($model) { /** @var $model \app\modules\plugins\geomapping\models\LogGeo */
                                        return Html::a('<i class="fa fa-caret-square-o-down"></i>', 'javascript:;', [
                                            'class'         => 'gridExpand',
                                            'title'         => Yii::t('log', 'Show full message'),
                                            'data-div-id'   => '#message_' . $model->id,
                                            'data-multiple' => 'true'
                                        ]);
                                    },
                                ],
                                [
                                    'attribute' => 'time',
                                    'value'     => function($data) {
                                        return Yii::$app->formatter->asDatetime($data->time, 'php:'.Setting::get('datetime'));
                                    },
                                    'options'   => ['style' => 'width:14%']
                                ],
                                [
                                    'format'    => 'raw',
                                    'attribute' => 'node_info',
                                    'options'   => ['style' => 'width:25%'],
                                    'value'     => function($data) { /** @var $data \app\modules\plugins\geomapping\models\LogGeo */
                                        $link = Yii::t('yii', '(not set)');
                                        if (!is_null($data->node_id)) {
                                            $text = (empty($data->node->hostname)) ? $data->node->ip : $data->node->hostname;
                                            $link = Html::a($text, ['/node/view', 'id' => $data->node->id], ['data-pjax' => '0', 'target' => '_blank']);
                                        }
                                        return $link;
                                    },
                                ],
                                [
                                    'attribute'     => 'userid',
                                    'value'         => 'user.fullname',
                                    'enableSorting' => false,
                                    'options'       => ['style' => 'width:11%']
                                ],
                                [
                                    'format'        => 'raw',
                                    'attribute'     => 'severity',
                                    'enableSorting' => false,
                                    'options'       => ['style' => 'width:8%'],
                                    'value'         => function($data) { /** @var $data \app\modules\plugins\geomapping\models\LogGeo */
                                        return GridHelper::colorSeverity($data->severity);
                                    }
                                ],
                                [
                                    'attribute'     => 'action',
                                    'enableSorting' => false,
                                    'options'       => ['style' => 'width:11%']
                                ],
                                [
                                    'format'         => 'raw',
                                    'attribute'      => 'message',
                                    'enableSorting'  => false,
                                    'contentOptions' => ['class' => 'hide-overflow', 'style' => 'max-width: 0;'],
                                    'value'          => function($model) {/** @var $model \app\modules\plugins\geomapping\models\LogGeo */
                                        return Html::tag('div', $model->message);
                                    },
                                ]
                            ],
                        ]);
                    ?>
                <?php Pjax::end(); ?>
            </div>
        </div>
    </div>
</div>

