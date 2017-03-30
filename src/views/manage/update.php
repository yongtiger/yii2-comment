<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yongtiger\comment\Module;
use yii2mod\moderation\enums\Status;

/* @var $this yii\web\View */
/* @var $model \yongtiger\comment\models\CommentModel */
/* @var $form yii\widgets\ActiveForm */

$this->title = Module::t('message', 'Update Comment: {0}', $model->id);
$this->params['breadcrumbs'][] = ['label' => Module::t('message', 'Comments Management'), 'url' => ['index']];
$this->params['breadcrumbs'][] = Module::t('message', 'Update');
?>
<div class="comment-update">

    <h1><?php echo Html::encode($this->title) ?></h1>

    <div class="comment-form">
        <?php $form = ActiveForm::begin(); ?>

        <?php echo $form->field($model, 'content', ['template' => '{input}{error}'])->textarea(['rows' => 4, 'data' => ['comment' => 'content']]); ?>

        <?php echo $form->field($model, 'status')->dropDownList(Status::listData()); ?>
        <div class="form-group">
            <?php echo Html::submitButton(Module::t('message', 'Update'), ['class' => 'btn btn-primary']) ?>
            <?php echo Html::a(Module::t('message', 'Go Back'), ['index'], ['class' => 'btn btn-default']); ?>
        </div>
        <?php ActiveForm::end(); ?>
    </div>
</div>
