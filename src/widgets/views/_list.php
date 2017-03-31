<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yongtiger\comment\Module;

/* @var $this \yii\web\View */
/* @var $model \yongtiger\comment\models\CommentModel */
/* @var $maxLevel null|integer comments max level */
/* @var $commentModelClass string class name of \yongtiger\comment\models\CommentModel */

?>
<li class="comment" id="comment-<?php echo $model->id; ?>">
    <div class="comment-content" data-comment-content-id="<?php echo $model->id ?>">
        <div class="comment-author-avatar">
            <?php echo Html::img($model->getAvatar(), ['alt' => $model->getAuthorName()]); ?>
        </div>
        <div class="comment-details">
            <div class="comment-action-buttons">

                <!--///[v0.0.10 (ADD# canCallback)]-->
                <?php if ($model->can($commentModelClass::PERMISSION_DELETE)) : ?>

                    <?php echo Html::a('<span class="glyphicon glyphicon-trash"></span> ' . Module::t('message', 'Delete'), '#', ['data' => ['action' => 'delete', 'url' => Url::to(['/comment/default/delete', 'id' => $model->id]), 'comment-id' => $model->id]]); ?>
                <?php endif; ?>
                <?php if (!Yii::$app->user->isGuest && ($model->level < $maxLevel || is_null($maxLevel))) : ?>
                    <?php echo Html::a("<span class='glyphicon glyphicon-share-alt'></span> " . Module::t('message', 'Reply'), '#', ['class' => 'comment-reply', 'data' => ['action' => 'reply', 'comment-id' => $model->id]]); ?>
                <?php endif; ?>

                <!--///[v0.0.12 (ADD# vote)]-->
                <a href="javascript:void(0);" class="glyphicon glyphicon-thumbs-up" onclick="vote(<?= $model->id ?>, 1)"></a> <span><?= $model->up_vote ?></span>
                <a href="javascript:void(0);" class="glyphicon glyphicon-thumbs-down" onclick="vote(<?= $model->id ?>, -1)"></a> <span><?= $model->down_vote ?></span>

            </div>
            <div class="comment-author-name">
                <span><?php echo $model->getAuthorName(); ?></span>
                <?php echo Html::a($model->getPostedDate(), $model->getAnchorUrl(), ['class' => 'comment-date']); ?>
            </div>
            <div class="comment-body">
                <?php echo $model->getContent(); ?>
            </div>
        </div>
    </div>
</li>
<?php if ($model->hasChildren()) : ?>
    <ul class="children">
        <?php foreach ($model->getChildren() as $children) : ?>
            <li class="comment" id="comment-<?php echo $children->id; ?>">
                <?php echo $this->render('_list', ['model' => $children, 'maxLevel' => $maxLevel, 'commentModelClass' => $commentModelClass]) ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
