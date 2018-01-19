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
 * @var $this        yii\web\View
 * @var $model       app\modules\plugins\geomapping\models\LogGeo
 * @var $form        yii\widgets\ActiveForm
 * @var $users       array
 * @var $severities  array
 * @var $actions     array
 */

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
?>

<div class="geo-log-search-form">
    <div class="row">
        <div class="col-md-12">
            <?php $form = ActiveForm::begin(['action' => ['log'], 'method' => 'get', 'enableClientValidation' => false]); ?>
                <div class="search-body">
                    <div class="row">
                        <div class="col-md-4">
                            <?php
                                echo $form->field($model, 'date_from', [
                                    'inputTemplate' =>
                                        '
                                            <div class="input-group">
                                                {input}
                                                <div class="input-group-btn">
                                                    <a href="javascript:;" id="systemFrom_clear" class="btn btn-default date-clear" title="'.Yii::t('app', 'Clear date').'">
                                                        <i class="fa fa-times"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        '
                                ])->textInput([
                                    'id'          => 'systemFrom_date',
                                    'class'       => 'form-control',
                                    'placeholder' => Yii::t('log', 'Pick date/time'),
                                    'readonly'    => true,
                                    'style'       => 'background-color: white; cursor: pointer;'
                                ]);
                            ?>
                        </div>
                        <div class="col-md-4">
                            <?php
                                echo $form->field($model, 'date_to', [
                                    'inputTemplate' =>
                                        '
                                            <div class="input-group">
                                                {input}
                                                <div class="input-group-btn">
                                                    <a href="javascript:;" id="systemTo_clear" class="btn btn-default date-clear" title="'.Yii::t('app', 'Clear date').'">
                                                        <i class="fa fa-times"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        '
                                ])->textInput([
                                    'id'          => 'systemTo_date',
                                    'class'       => 'form-control',
                                    'placeholder' => Yii::t('log', 'Pick date/time'),
                                    'readonly'    => true,
                                    'style'       => 'background-color: white; cursor: pointer;'
                                ]);
                            ?>
                        </div>
                        <div class="col-md-4">
                            <?php
                                echo $form->field($model, 'userid')->dropDownList($users, [
                                    'prompt' => Yii::t('log', 'All users'),
                                    'class'  => 'select2-search',
                                ]);
                            ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3">
                            <?php
                                echo $form->field($model, 'severity')->dropDownList($severities, [
                                    'prompt' => Yii::t('log', 'All levels'),
                                    'class'  => 'select2',
                                ]);
                            ?>
                        </div>
                        <div class="col-md-3">
                            <?php
                                echo $form->field($model, 'action')->dropDownList($actions, [
                                    'prompt' => Yii::t('log', 'All actions'),
                                    'class'  => 'select2',
                                ]);
                            ?>
                        </div>
                        <div class="col-md-3">
                            <?php
                                echo $form->field($model, 'node_info')->textInput([
                                    'class'       => 'form-control',
                                    'placeholder' => Yii::t('network', 'Enter node hostname or IP')
                                ]);
                            ?>
                        </div>
                        <div class="col-md-1">
                            <?php
                                echo $form->field($model, 'page_size')->dropDownList(\Y::param('page_size'), ['class' => 'select2']);
                            ?>
                        </div>
                        <div class="col-md-2">
                            <div class="pull-right" style="padding-top: 30px">
                                <?= Html::submitButton(Yii::t('app', 'Search'), ['id' => 'spin_btn', 'class' => 'btn bg-light-blue ladda-button', 'data-style' => 'zoom-in']) ?>
                                <?= Html::a(Yii::t('app', 'Reset'), yii\helpers\Url::to(['log']), ['class' => 'btn btn-default']) ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
