<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use yongtiger\comment\Module;

/* @var $this \yii\web\View */
/* @var $commentModel \yongtiger\comment\models\CommentModel */
/* @var $encryptedEntity string */
/* @var $formId string comment form id */
?>
<div class="comment-form-container">

    <?php $form = ActiveForm::begin([
        'options' => [
            'id' => $formId,
            'class' => 'comment-box',
        ],
        'action' => Url::to(['/comment/default/create', 'entity' => $encryptedEntity]),
        'validateOnChange' => false,
        'validateOnBlur' => false,
    ]); 

    ///[v0.0.17 (ADD# editorCallback)]
    $field = $form->field($commentModel, 'content', ['template' => '{input}{error}']);
    $params = ['placeholder' => Module::t('message', 'Add a comment...'), 'rows' => 4, 'data' => ['comment' => 'content']];

    if (is_callable($editor = Module::instance()->editorCallback)) {
        echo call_user_func($editor, $field, $commentModel, 'content', null, $params);
    } else {
        echo $field->textarea($params);
    }
    ?>

    <?php echo $form->field($commentModel, 'parent_id', ['template' => '{input}'])->hiddenInput(['data' => ['comment' => 'parent-id']]); ?>

    <div class="comment-box-partial">
        <div class="button-container show">
            <?php echo Html::a(Module::t('message', 'Click here to cancel reply.'), '#', ['id' => 'cancel-reply', 'class' => 'pull-right', 'data' => ['action' => 'cancel-reply']]); ?>
            <?php echo Html::submitButton(Module::t('message', 'Comment'), ['class' => 'btn btn-primary comment-submit']); ?>
        </div>
    </div>

    <?php $form->end(); ?>

    <div class="clearfix"></div>
</div>
