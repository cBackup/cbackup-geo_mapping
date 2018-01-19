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
 * @var $searchModel   app\modules\plugins\geomapping\models\GeolocationSearch
 * @var $module        app\modules\plugins\geomapping\GeoMapping
 */

use yii\helpers\Html;
use yii\widgets\Pjax;
use yii\helpers\Url;
use yii\grid\GridView;
use app\models\Setting;

app\assets\LaddaAsset::register($this);

/** @noinspection PhpUndefinedFieldInspection */
$module = $this->context->module;

$this->title = $module::t('general', 'Node geolocation');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Plugins')];
$this->params['breadcrumbs'][] = ['label' => $this->title];

/** Register Google maps API and set API key */
$this->registerJsFile("https://maps.googleapis.com/maps/api/js?key={$module->params['api_key']}");

/** Check if plugin debug mode is enabled */
if ($module->params['debug_mode'] == '1') {
    $this->registerJs(/** @lang JavaScript */
        "toastr.warning('{$module::t('general', 'Debug mode is enabled. After plugin testing disable debug mode in plugin settings.')}', '', {timeOut: 0, closeButton: true});"
    );
}

/** Register JS */
$this->registerJs(
    /** @lang JavaScript */
    "
        /** Default variables */
        var marker;

        /** Start geolocation collecting */
        $('body').on('click', '#run_geo_collect', function() {
            
            var ajax_url = $(this).data('ajax-url');
            var btn_lock = Ladda.create(document.querySelector('#run_geo_collect'));
            
            //noinspection JSUnusedGlobalSymbols
            $.ajax({
                type: 'POST',
                url: ajax_url,
                beforeSend: function() {
                    btn_lock.start();
                },
                success: function (data) {
                    showStatus(data);
                },
                error: function (data) {
                    toastr.error(data.responseText, '', {timeOut: 0, closeButton: true});
                }
            }).always(function () {
                btn_lock.stop();
            });
            
        });
        
        /** Load map settings */
        var initializeMap = function() {
    
            var location = {lat: parseFloat(data['latitude']), lng: parseFloat(data['longitude'])};
            
            //noinspection JSUnresolvedVariable, JSUnresolvedFunction
            var map = new google.maps.Map(document.getElementById('map_' + data['node_id']), {
                zoom: 16,
                center: location
            });
    
            var contentString =
                '<div class=\"text-center\">' +
                    data['node_data']['hostname'] + '</br>' +
                    data['node_data']['ip'] + '</br>' + 
                    data['node_data']['device'] +
                '</div>';
    
            //noinspection JSUnresolvedVariable, JSUnresolvedFunction
            var infowindow = new google.maps.InfoWindow({
                content: contentString
            });
    
            //noinspection JSUnresolvedVariable, JSUnresolvedFunction
            marker = new google.maps.Marker({
                map: map,
                position: location
            });
            
            //noinspection JSUnresolvedVariable, JSUnresolvedFunction
            google.maps.event.addListener(map, 'idle', function() {
                infowindow.open(map, marker);
            });
            
            //noinspection JSReferencingArgumentsOutsideOfFunctionInspection
            marker.addListener('click', (function(marker, infowindow){
                return function() {
                    infowindow.open(map, marker);
                };
            })(marker, infowindow));
            
        };

        /** Init map when info window was fully loaded */
        $(document).ajaxComplete(function() {
            if (typeof data !== typeof undefined) {
                $('#info_' + data['node_id']).promise().done(function(e){
                    var active_element = e.context.activeElement;
                    if (typeof active_element !== typeof undefined && active_element.className === 'ajaxGridExpand') {
                        initializeMap();
                    }
                });
            }
        });
        
    "
);
?>

<div class="row">
    <div class="col-md-12">
        <div class="box">
            <div class="box-header">
                <i class="fa fa-list"></i><h3 class="box-title box-title-align"><?= $module::t('general', 'List of node geolocations') ?></h3>
                <div class="pull-right">
                    <?php
                        echo Html::a($module::t('general', 'View geolocations logs'), ['log'], [
                            'class' => 'btn btn-sm bg-light-blue margin-r-5',
                        ]);
                        echo Html::a($module::t('general', 'Collect geolocations'), 'javascript:void(0);', [
                            'id'            => 'run_geo_collect',
                            'class'         => 'btn btn-sm bg-light-blue ladda-button',
                            'data-ajax-url' => Url::to(['ajax-collect-geo']),
                            'data-style'    => 'zoom-in'
                        ]);
                    ?>
                </div>
            </div>
            <div class="box-body no-padding">
                <?php Pjax::begin(['id' => 'geo-location-pjax']); ?>
                    <?php
                        /** @noinspection PhpUnhandledExceptionInspection */
                        echo GridView::widget([
                            'id'           => 'geo-location-grid',
                            'tableOptions' => ['class' => 'table table-bordered'],
                            'dataProvider' => $dataProvider,
                            'filterModel'  => $searchModel,
                            'afterRow'     => function($model) { /** @var $model \app\modules\plugins\geomapping\models\Geolocation */
                                $id = "info_{$model->id}";
                                return '<tr><td class="grid-expand-row" colspan="8"><div class="grid-expand-div" id="'.$id.'"></div></td></tr>';
                            },
                            'layout'  => '{items}<div class="row"><div class="col-sm-3"><div class="gridview-summary">{summary}</div></div><div class="col-sm-9"><div class="gridview-pager">{pager}</div></div></div>',
                            'columns' => [
                                [
                                    'format'         => 'raw',
                                    'options'        => ['style' => 'width:3%'],
                                    'contentOptions' => ['class' => 'text-center'],
                                    'value'          => function($model) use ($module) { /** @var $model \app\modules\plugins\geomapping\models\Geolocation */
                                        return Html::a('<i class="fa fa-caret-square-o-down"></i>', 'javascript:void(0);', [
                                            'class'         => 'ajaxGridExpand',
                                            'title'         => $module::t('general', 'Show full info'),
                                            'data-ajax-url' => Url::to(['ajax-get-geo-info', 'id' => $model->id]),
                                            'data-div-id'   => "#info_{$model->id}",
                                            'data-multiple' => ($module->params['expand_multiple'] == '1') ? 'true' : 'false'
                                        ]);
                                    },
                                ],
                                [
                                    'attribute' => 'node_id',
                                    'options'   => ['style' => 'width:10%']
                                ],
                                [
                                    'format'    => 'raw',
                                    'attribute' => 'node_info',
                                    'options'   => ['style' => 'width:25%'],
                                    'value'     => function($data) { /** @var $data \app\modules\plugins\geomapping\models\Geolocation */
                                        $link = Yii::t('yii', '(not set)');
                                        if (!is_null($data->node_id)) {
                                            $text = (empty($data->node->hostname)) ? $data->node->ip : $data->node->hostname;
                                            $link = Html::a($text, ['/node/view', 'id' => $data->node->id], ['data-pjax' => '0', 'target' => '_blank']);
                                        }
                                        return $link;
                                    },
                                ],
                                [
                                    'attribute' => 'latitude',
                                ],
                                [
                                    'attribute' => 'longitude',
                                ],
                                [
                                    'attribute' => 'created',
                                    'value'     => function($data) { /** @var $data \app\modules\plugins\geomapping\models\Geolocation */
                                        return Yii::$app->formatter->asDatetime($data->created, 'php:'.Setting::get('datetime'));
                                    },
                                ],
                                [
                                    'attribute' => 'modified',
                                    'value'     => function($data) { /** @var $data \app\modules\plugins\geomapping\models\Geolocation */
                                        return Yii::$app->formatter->asDatetime($data->modified, 'php:'.Setting::get('datetime'));
                                    },
                                ],
                                [
                                    'class'          => 'yii\grid\ActionColumn',
                                    'contentOptions' => ['class' => 'narrow'],
                                    'template'       => '{recollect}',
                                    'buttons'        => [
                                        /** @var $model \app\modules\plugins\geomapping\models\Geolocation */
                                        'recollect' => function (/** @noinspection PhpUnusedParameterInspection */$url, $model) use ($module) {
                                            $field = $module->params['location_field'];
                                            return Html::a('<i class="fa fa-refresh"></i>', 'javascript:void(0);', [
                                                'class'             => 'ajaxGridUpdate',
                                                'title'             => $module::t('general', 'Re-collect geolocation'),
                                                'data-ajax-confirm' => false,
                                                'data-ajax-url'     => Url::to(['ajax-recollect-geo',
                                                    'location'         => $model->node->$field,
                                                    'prepend_location' => "{$model->node->prepend_location}",
                                                    'node_id'          => $model->node_id
                                                ]),
                                            ]);
                                        },
                                    ],
                                ]
                            ],
                        ]);
                    ?>
                <?php Pjax::end(); ?>
            </div>
        </div>
    </div>
</div>
