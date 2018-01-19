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
use yii\console\Application;
use app\models\Severity;
use app\models\User;
use app\models\Node;


/**
 * This is the model class for table "{{%plg_geomapping_log_geo}}".
 *
 * @property integer $id
 * @property string $userid
 * @property string $time
 * @property integer $node_id
 * @property string $severity
 * @property string $action
 * @property string $message
 *
 * @property Node $node
 * @property Severity $logSeverity
 * @property User $user
 */
class LogGeo extends ActiveRecord
{

    /**
     * @var string
     */
    public $node_info = '';

    /**
     * @var string
     */
    public $date_from;

    /**
     * @var string
     */
    public $date_to;

    /**
     * Default page size
     * @var int
     */
    public $page_size = 20;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%plg_geomapping_log_geo}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['time'], 'safe'],
            [['node_id'], 'integer'],
            [['severity', 'message'], 'required'],
            [['message'], 'string'],
            [['userid'], 'string', 'max' => 128],
            [['severity'], 'string', 'max' => 32],
            [['action'], 'string', 'max' => 45],
            [['node_id'], 'exist', 'skipOnError' => true, 'targetClass' => Node::className(), 'targetAttribute' => ['node_id' => 'id']],
            [['severity'], 'exist', 'skipOnError' => true, 'targetClass' => Severity::className(), 'targetAttribute' => ['severity' => 'name']],
            [['userid'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['userid' => 'userid']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id'        => Yii::t('app', 'ID'),
            'userid'    => Yii::t('app', 'User'),
            'time'      => Yii::t('app', 'Time'),
            'node_id'   => Yii::t('app', 'Node ID'),
            'severity'  => Yii::t('log', 'Severity'),
            'action'    => Yii::t('log', 'Action'),
            'message'   => Yii::t('app', 'Message'),
            'node_info' => Yii::t('node', 'Node'),
            'date_from' => Yii::t('log', 'Date/time from'),
            'date_to'   => Yii::t('log', 'Date/time to'),
            'page_size' => Yii::t('app', 'Page size')
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
     * @return \yii\db\ActiveQuery
     */
    public function getlogSeverity()
    {
        return $this->hasOne(Severity::className(), ['name' => 'severity']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['userid' => 'userid']);
    }

    /**
     * Write custom log to DB
     *
     * @param array  $params
     * @param string $level
     */
    public function writeLog($params, $level)
    {
        $this->userid   = (Yii::$app instanceof Application) ? 'CONSOLE_APP' : Yii::$app->user->id;
        $this->severity = $level;
        $this->message  = $params[0];
        $this->action   = $params[1];
        $this->node_id  = (array_key_exists(2, $params)) ? $params[2] : null;
        $this->save(false);
    }

}
