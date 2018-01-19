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

namespace app\modules\plugins\geomapping\controllers;

use Yii;
use yii\web\Controller;
use yii\helpers\Json;
use yii\helpers\ArrayHelper;
use yii\filters\AccessControl;
use app\filters\AjaxFilter;
use app\modules\plugins\geomapping\models\Geolocation;
use app\modules\plugins\geomapping\models\GeolocationSearch;
use app\modules\plugins\geomapping\models\LogGeoSearch;
use app\modules\plugins\geomapping\models\LogGeo;
use app\models\User;
use app\models\Severity;
use cbackup\console\ConsoleRunner;


/**
 * @package app\modules\plugins\geomapping\controllers
 */
class GeoController extends Controller
{

    /**
     * @var \app\modules\plugins\geomapping\GeoMapping /
     */
    public $module;

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => [$this->module->params['plugin_access']],
                    ],
                ],
            ],
            'ajaxonly' => [
                'class' => AjaxFilter::className(),
                'only'  => [
                    'ajax-collect-geo',
                    'ajax-recollect-geo',
                    'ajax-get-geo-info'
                ]
            ]
        ];
    }


    /**
     * Render node geolocations
     *
     * @return string
     */
    public function actionIndex()
    {
        $searchModel  = new GeolocationSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider
        ]);
    }


    /**
     * Collect node geolocation in background via Ajax
     *
     * @return string
     */
    public function actionAjaxCollectGeo()
    {
        $lock     = Yii::getAlias('@runtime') . DIRECTORY_SEPARATOR . 'geo.lock';
        $response = ['status' => 'warning', 'msg' => $this->module::t('general', 'Geolocation collecting is already running.')];

        if (!file_exists($lock)) {
            $console = new ConsoleRunner(['file' => '@app/yii']);
            $console->run('plugins/geomapping/run/get-geolocation');
            $response = [
                'status' => 'success',
                'msg'    => $this->module::t('general', 'Node geolocation collecting started in background. See log for more info.')
            ];
        }

        return Json::encode($response);
    }


    /**
     * Get single node location via Ajax
     *
     * @param  string $location
     * @param  string $prepend_location
     * @param  int $node_id
     * @return string
     */
    public function actionAjaxRecollectGeo($location, $prepend_location, $node_id)
    {
        try {

            $model = new Geolocation();

            /** Save node location  */
            $prepend_location = (!empty($prepend_location)) ? $prepend_location : null;
            $save_location    = $model->saveNodeLocation($location, $prepend_location, $node_id);

            if ($save_location) {
                $response = ['status' => 'success', 'msg' => Yii::t('app', 'Action successfully finished')];
            }
            else if (is_null($save_location)) {
                $response = [
                    'status' => 'warning',
                    'msg'    => $this->module::t('general', 'Google API cannot find given address {0}', $model->prepareNodeLocation($location, $prepend_location))
                ];
            }
            else {
                $response = ['status' => 'error', 'msg' => Yii::t('app', 'An error occurred while processing your request')];
            }

        } catch (\Exception $e) {
            $response = ['status' => 'error', 'msg' => $e->getMessage()];
        }

        return Json::encode($response);
    }


    /**
     * Load geolocation info via Ajax
     *
     * @param  int $id
     * @return string
     */
    public function actionAjaxGetGeoInfo($id)
    {
        /** Do not load JqueryAsset in info view */
        Yii::$app->assetManager->bundles['yii\web\JqueryAsset'] = false;

        return $this->renderAjax('_info', [
            'data' => Geolocation::find()->where(['id' => $id])->one()
        ]);
    }


    /**
     * Render Geolocation log view
     *
     * @return string
     */
    public function actionLog()
    {
        $searchModel  = new LogGeoSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('log', [
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider,
            'users'        => (new User())->getUsers('name'),
            'severities'   => ArrayHelper::map(Severity::find()->all(), 'name', 'name'),
            'actions'      => LogGeo::find()->select('action')->indexBy('action')->asArray()->column()
        ]);
    }

}
