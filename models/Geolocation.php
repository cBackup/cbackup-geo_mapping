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

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use yii\helpers\Json;
use app\models\Node;
use deka6pb\geocoder\Geocoder;
use cbackup\console\ConsoleRunner;


/**
 * This is the model class for table "{{%plg_geomapping_geolocation}}".
 *
 * @property integer $id
 * @property integer $node_id
 * @property string $last_query
 * @property string $full_address
 * @property string $address_data
 * @property string $latitude
 * @property string $longitude
 * @property string $created
 * @property string $modified
 *
 * @property Node $node
 */
class Geolocation extends ActiveRecord
{

    /**
     * @var \app\modules\plugins\geomapping\GeoMapping /
     */
    public $module;

    /**
     * @var string
     */
    public $node_info = '';

    /**
     * @var string
     */
    private $log_namespace = 'app\modules\plugins\geomapping\models';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%plg_geomapping_geolocation}}';
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->module = Yii::$app->controller->module;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['node_id', 'last_query', 'full_address', 'address_data', 'latitude', 'longitude'], 'required'],
            [['node_id'], 'integer'],
            [['address_data'], 'string'],
            [['latitude', 'longitude'], 'number'],
            [['created', 'modified'], 'safe'],
            [['last_query', 'full_address'], 'string', 'max' => 255],
            [['node_id'], 'exist', 'skipOnError' => true, 'targetClass' => Node::className(), 'targetAttribute' => ['node_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id'           => $this->module::t('general', 'ID'),
            'node_id'      => $this->module::t('general', 'Node ID'),
            'last_query'   => $this->module::t('general', 'Last Query'),
            'full_address' => $this->module::t('general', 'Full Address'),
            'address_data' => $this->module::t('general', 'Address Data'),
            'latitude'     => $this->module::t('general', 'Latitude'),
            'longitude'    => $this->module::t('general', 'Longitude'),
            'created'      => $this->module::t('general', 'Created'),
            'modified'     => $this->module::t('general', 'Modified'),
            'node_info'    => $this->module::t('general', 'Node')
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNode()
    {
        return $this->hasOne(Node::className(), ['id' => 'node_id']);
    }

    /**
     * Behaviors
     *
     * @return array
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'created',
                'updatedAtAttribute' => 'modified',
                'value' => new Expression('NOW()'),
            ],
        ];
    }


    public function runGeoCollecting()
    {
        $console = new ConsoleRunner(['file' => '@app/yii']);
        $console->run('');
        return null;
    }


    /**
     * Collect node geolocations
     *
     * Method output will be displayed in log table
     *
     * @return bool
     */
    public function getGeolocations()
    {

        $lock    = Yii::getAlias('@runtime') . DIRECTORY_SEPARATOR . 'geo.lock';
        $status  = true;
        $action  = '';
        $created = 0;
        $updated = 0;
        $failed  = 0;

        /** Get list of nodes */
        $limit    = ($this->module->params['debug_mode'] == '1') ? 5 : null;
        $excluded = array_filter(explode(';', $this->module->params['excluded_nodes']));
        $nodes    = Node::find()->where(['NOT IN', 'id', $excluded])->asArray()->limit($limit)->all();

        Yii::info(['Node geolocation collecting started', 'GEO START'], "geo.writeLog.{$this->log_namespace}");
        @file_put_contents($lock, date('d.m.Y H:i:s')); // Prevent multiple startups

        /** Max execution time */
        $dalay = $this->module->params['delay_between_requests'];
        set_time_limit(count($nodes) * $dalay + 60);

        try {
            if (!empty($nodes)) {

                $requests = 0;
                foreach ($nodes as $node) {

                    $node_geo  = static::find()->where(['node_id' => $node['id']]);
                    $node_name = (!empty($node['hostname'])) ? $node['hostname'] : $node['ip'];

                    if (!$node_geo->exists()) {

                        /** Make delay between requests */
                        if ($requests >= 1) {
                            usleep($dalay);
                        }

                        $action        = 'CREATE';
                        $location      = $node[$this->module->params['location_field']];
                        $save_location = $this->saveNodeLocation($location, $node['prepend_location'], $node['id']);


                        if ($save_location) {
                            $message = "Node {$node_name} location successfully saved\nSearch address: {$this->prepareNodeLocation($location, $node['prepend_location'])}";
                            Yii::info([$message, $action, $node['id']], "geo.writeLog.{$this->log_namespace}");
                            $created++;
                        }

                        if (is_null($save_location)) {
                            $message = "Node {$node_name} location cannot be found or API returned empty response.\n";
                            $message.= "Search address: {$this->prepareNodeLocation($location, $node['prepend_location'])}";
                            Yii::warning([$message, $action, $node['id']], "geo.writeLog.{$this->log_namespace}");
                            $failed++;
                        }

                    } else {
                        $action   = 'UPDATE';
                        $model    = $node_geo->one();
                        $location = $node[$this->module->params['location_field']];
                        if ($model->last_query != $this->prepareNodeLocation($location, $node['prepend_location'])) {

                            /** Make delay between requests */
                            if ($requests >= 1) {
                                usleep($dalay);
                            }

                            $update_location = $this->saveNodeLocation($location, $node['prepend_location'], $node['id']);

                            if ($update_location) {
                                $message = "Node {$node_name} location successfully updated\nSearch address: {$this->prepareNodeLocation($location, $node['prepend_location'])}";
                                Yii::info([$message, $action, $node['id']], "geo.writeLog.{$this->log_namespace}");
                                $updated++;
                            }

                            if (is_null($update_location)) {
                                $message = "Node {$node_name} location cannot be found or API returned empty response.";
                                $message.= "\nSearch address: {$this->prepareNodeLocation($location, $node['prepend_location'])}";
                                Yii::warning([$message, $action, $node['id']], "geo.writeLog.{$this->log_namespace}");
                                $failed++;
                            }
                        }
                    }

                    $requests++;

                    /** Flush yii logger */
                    \Yii::getLogger()->flush(true);
                }

                if ($created == 0 && $updated == 0 && $failed == 0) {
                    Yii::info(['Nothing to update your node geolocations are up-to-date', $action], "geo.writeLog.{$this->log_namespace}");
                }

            }
            else {
                Yii::info(['You do not have any nodes. Please add some nodes first.', 'GEO START'], "geo.writeLog.{$this->log_namespace}");
            }

        } catch (\Exception $e) {
            $message = "Something went wrong while collecting geolocation.\nException:\n{$e->getMessage()}";
            Yii::error([$message, $action], "geo.writeLog.{$this->log_namespace}");
            $status = false;
            @unlink($lock);
        }

        $message = "Node geolocation collecting finished.\nSummary:\nCreated nodes: {$created}\nUpdated nodes: {$updated}\nFailed nodes: {$failed}";
        Yii::info([$message, 'GEO FINISH'], "geo.writeLog.{$this->log_namespace}");
        @unlink($lock);

        return $status;

    }

    /**
     * Save node location
     *
     * @param  string $location
     * @param  string $prepend_location
     * @param  int $node_id
     * @return bool|null
     * @throws \Exception
     */
    public function saveNodeLocation($location, $prepend_location, $node_id)
    {

        try {

            $status = null;

            /** Prepare location */
            $location = $this->prepareNodeLocation($location, $prepend_location);

            /** Get node location via API */
            $api_key = (!empty($this->module->params['api_key'])) ? ['key' => $this->module->params['api_key']] : [];
            $coder   = Geocoder::build(Geocoder::TYPE_GOOGLE);
            $geo     = $coder::findOneByAddress($location, $api_key);

            if (!empty($geo) && array_key_exists('house', $geo->data)) {

                /** Init model */
                $mapping = static::find()->where(['node_id' => $node_id]);
                $model   = (!$mapping->exists()) ? new Geolocation() : $mapping->one();

                $model->node_id      = $node_id;
                $model->last_query   = $location;
                $model->full_address = $geo->address;
                $model->address_data = Json::encode($geo->data);
                $model->latitude     = $geo->point->latitude;
                $model->longitude    = $geo->point->longitude;

                $status = ($model->save()) ? true : false;
            }

            return $status;

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

    }

    /**
     * Prepare node location for Geocoder
     *
     * @param  string $location
     * @param  string $prepend_location
     * @param  null $regex
     * @return string
     */
    public function prepareNodeLocation($location, $prepend_location,  $regex = null)
    {
        /** Set method params */
        $regex = (is_null($regex)) ? $this->module->params['location_regex'] : $regex;
        $prepend_location = (!is_null($prepend_location)) ? $prepend_location : \Y::param('defaultPrependLocation');

        /** Prepare address */
        $address = preg_replace(["/{$regex}/i", "/([a-z])([0-9])/i"], ["", "$1 $2"], $location);
        return "{$prepend_location} {$address}";
    }

}
