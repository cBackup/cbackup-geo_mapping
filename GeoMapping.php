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

namespace app\modules\plugins\geomapping;

use yii\base\Module;
use yii\console\Application;
use app\traits\PluginTrait;

/**
 * geomapping module definition class
 */
class GeoMapping extends Module
{

    /**
     * Add necessary functionality to plugin
     */
    use PluginTrait;

    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'app\modules\plugins\geomapping\controllers';

    /**
     * @inheritdoc
     */
    public $defaultRoute = 'geo';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->initPlugin();
        require_once(__DIR__  . '/libraries/vendor/autoload.php');

        /** Change controller namespace to access commands */
        if (\Yii::$app instanceof Application && $this->params['plugin_enabled'] == 1) {
            $this->controllerNamespace = 'app\modules\plugins\geomapping\commands';
        }

    }

}
