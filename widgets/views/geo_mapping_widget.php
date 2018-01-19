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
 * @var $plugin    app\modules\plugins\geomapping\GeoMapping
 * @var $data      app\modules\plugins\geomapping\models\Geolocation
 * @var $node      app\models\Node
 * @var $node_id   int
 */

use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

/** Get location field from plugin params */
$field = $plugin->params['location_field'];

/** Set location if geolocation entry does not exists exists */
$location = $node[$field];

echo Html::script(
    /** @lang JavaScript */
    "
        /** Run geolocation collecting */
        $('.run-geo-collect').click(function () {
            
            var name = $(this).data('plugin-name');
            var url = $(this).data('ajax-url');
            
            //noinspection JSUnusedGlobalSymbols
            $.ajax({
                type: 'POST',
                url: url,
                beforeSend: function() {
                    $('#widget_loading_' + name).show();
                    $('#widget_content_' + name).hide();
                },
                success: function (data) {
                    showStatus(data);
                    $('a[href=\"#tab_' + name + '\"]').trigger('click');
                },
                error: function (data) {
                    toastr.error(data.responseText, '', {timeOut: 0, closeButton: true});
                }
            });
            
        });
    ", ['type' => 'text/javascript']
);

/** Check if geolocation exists in database */
if (!empty($data)) {

    /** Set location if geolocation entry exists */
    $location = $data->node->$field;

    /** Dataset for Google Maps API  */
    $data_set = [
        'api_key'   => $plugin->params['api_key'],
        'latitude'  => $data->latitude,
        'longitude' => $data->longitude,
        'node_data' => [
            'hostname' => $data->node->hostname,
            'ip'       => $data->node->ip,
            'device'   => "{$data->node->device->vendor} {$data->node->device->model}"
        ]
    ];

    /** Register dataset */
    echo Html::script("var data = " . Json::htmlEncode($data_set) . ";", ['type' => 'text/javascript']);

    /** Main geolocation script */
    echo Html::script(
        /** @lang JavaScript */
        "
            /** Default variables */
            var marker;
            
            /** Load map settings */
            var initializeMap = function() {
        
                var location = {lat: parseFloat(data['latitude']), lng: parseFloat(data['longitude'])};
        
                //noinspection JSUnresolvedVariable, JSUnresolvedFunction
                var map = new google.maps.Map(document.getElementById('map'), {
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
        
                marker.addListener('click', (function(marker, infowindow){
                    return function() {
                        infowindow.open(map, marker);
                    };
                })(marker, infowindow));
                
            };
        
            /** Get google api js and init Google map */
            $.getScript(\"https://maps.googleapis.com/maps/api/js?key=\" + data['api_key'], function(){
                $(document).ajaxComplete(function(e, xhr, opt) {
                    if (typeof opt !== typeof undefined && (opt.url.includes('maps.googleapis.com') || opt.url.includes('ajax-load-widget'))) {
                        initializeMap();
                    }
                });
            });
        ", ['type' => 'text/javascript']
    );
}
?>

<div class="row">
    <div class="col-md-12">
        <?php
            $link = Html::a($plugin::t('general', 'Click here'), 'javascript:void(0);', [
                'class'            => 'run-geo-collect',
                'data-plugin-name' => 'geo_mapping',
                'data-ajax-url'    => Url::to(['/plugins/geomapping/geo/ajax-recollect-geo',
                    'location'         => $location,
                    'prepend_location' => "{$node['prepend_location']}",
                    'node_id'          => $node_id
                ])
            ]);
        ?>
        <?php if (empty($data)): ?>
            <div class="callout callout-warning" style="margin-bottom: 10px;">
                <p><?= $plugin::t('general', 'Geolocation for this node is not collected yet. {0} to get geolocation.', $link) ?></p>
            </div>
        <?php else: ?>
            <?php if ($data->last_query !== $data->prepareNodeLocation($location, $node['prepend_location'], $plugin->params['location_regex'])): ?>
                <div class="callout callout-warning" style="margin-bottom: 10px;">
                    <p><?= $plugin::t('general', 'Current node address does not match last search address. {0} to update geolocation.', $link) ?></p>
                </div>
            <?php endif; ?>
            <div class="box box-default box-solid">
                <div class="box-header box-header-narrow text-center">
                    <h3 class="box-title"><?= $plugin::t('general', 'Info about geolocation') ?></h3>
                </div>
                <div class="box-body no-padding">
                    <table class="table">
                        <tbody>
                        <tr>
                            <th width="15%"><?= $plugin::t('general', 'Full address') ?></th>
                            <td width="40%"><?= (!empty($data['full_address'])) ? $data['full_address'] : Yii::t('yii', '(not set)') ?></td>
                            <th width="15%"><?= $plugin::t('general', 'Search address') ?></th>
                            <td width="30%"><?= (!empty($data['last_query'])) ? $data['last_query'] : Yii::t('yii', '(not set)') ?></td>
                        </tr>
                        </tbody>
                    </table>
                    <div id="map" style="width:100%; height:400px; background-color: #FBFBFB">
                        <div style="margin-left: 34%; padding-top:17%;"><?= Html::img('@web/img/modal_loading.gif', ['alt' => Yii::t('app', 'Loading...')]) ?></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
