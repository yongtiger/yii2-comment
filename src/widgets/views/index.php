<?php

use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\web\View;
use yii\widgets\ListView;
use yii\widgets\Pjax;
use yongtiger\comment\Module;

/* @var $this \yii\web\View */
/* @var $commentModel \yongtiger\comment\models\CommentModel */
/* @var $maxLevel null|integer comments max level */
/* @var $encryptedEntity string */
/* @var $pjaxContainerId string */
/* @var $formId string comment form id */
/* @var $commentDataProvider \yii\data\ArrayDataProvider */
/* @var $listViewConfig array */
/* @var $commentWrapperId string */
/* @var $commentModelClass string class name of \yongtiger\comment\models\CommentModel */

$commentModelClass = Module::instance()->commentModelClass; ///[v0.0.10 (ADD# canCallback)]

///[v0.0.16 (ADD# sort)]
$params = Yii::$app->request->queryParams;
$orderBy = empty($params['orderby']) ? $this->context->sort : $params['orderby'];

?>
<div class="comment-wrapper" id="<?php echo $commentWrapperId; ?>">
    <?php Pjax::begin(['enablePushState' => false, 'timeout' => 30000, 'id' => $pjaxContainerId]); ?>
    <div class="comments row">
        <div class="col-md-12 col-sm-12">

            <?php echo ListView::widget(ArrayHelper::merge(
                [
                    'dataProvider' => $commentDataProvider,
                    'layout' => "{summary}\n{items}\n{pager}",
                    'itemView' => '_list',
                    'viewParams' => [
                        'maxLevel' => $maxLevel,
                        'commentModelClass' => $commentModelClass,
                    ],
                    'options' => [
                        'tag' => 'ol',
                        'class' => 'comments-list',
                    ],
                    'itemOptions' => [
                        'tag' => false,
                    ],

                    ///[v0.0.16 (ADD# sort)]
                    'summary' => '
                        <div class="title-block clearfix">
                            <h3 class="h3-body-title">
                                Comments ({totalCount})
                            </h3>
                            <div class="title-separator"></div>
                            <ul class="nav nav-tabs comment-action-buttons">
                                <li role="presentation" ' . ($orderBy == 'created-at-desc' ? 'class="active"' : '') . '>
                                    <a href="' . Url::current(['orderby' => 'created-at-desc']) . '">' . Module::t('message', 'Latest') . '</a>
                                </li>
                                <li role="presentation" ' . ($orderBy == 'created-at-asc' ? 'class="active"' : '') . '>
                                    <a href="' . Url::current(['orderby' => 'created-at-asc']) . '">' . Module::t('message', 'Earliest') . '</a>
                                </li>
                                <li role="presentation" ' . ($orderBy == 'vote-up-desc' ? 'class="active"' : '') . '>
                                    <a href="' . Url::current(['orderby' => 'vote-up-desc']) . '">' . Module::t('message', 'Most Vote Up') . '</a>
                                </li>
                                <li role="presentation" ' . ($orderBy == 'vote-up-asc' ? 'class="active"' : '') . '>
                                    <a href="' . Url::current(['orderby' => 'vote-up-asc']) . '">' . Module::t('message', 'Less Vote Up') . '</a>
                                </li>
                                <li role="presentation" ' . ($orderBy == 'vote-down-desc' ? 'class="active"' : '') . '>
                                    <a href="' . Url::current(['orderby' => 'vote-down-desc']) . '">' . Module::t('message', 'Most Vote Down') . '</a>
                                </li>
                                <li role="presentation" ' . ($orderBy == 'vote-down-asc' ? 'class="active"' : '') . '>
                                    <a href="' . Url::current(['orderby' => 'vote-down-asc']) . '">' . Module::t('message', 'Less Vote Down') . '</a>
                                </li>
                            </ul>
                        </div>
                    ',
                    
                    'emptyText' => Module::t('message', '(no comments)'),
                ],
                $listViewConfig
            )); ?>

            <?php if (!Yii::$app->user->isGuest) : ?>
                <?php echo $this->render('_form', [
                    'commentModel' => $commentModel,
                    'formId' => $formId,
                    'encryptedEntity' => $encryptedEntity,
                ]); ?>
            <?php endif; ?>
        </div>
    </div>
    <?php Pjax::end(); ?>
</div>
