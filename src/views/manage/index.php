<?php

use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\StringHelper;
use yii\widgets\Pjax;
use yii2mod\moderation\enums\Status;
use yongtiger\comment\Module;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $searchModel \yongtiger\comment\models\CommentSearchModel */
/* @var $commentModel \yongtiger\comment\models\CommentModel */

$this->title = Module::t('message', 'Comments Management');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="comments-index">

    <h1><?php echo Html::encode($this->title) ?></h1>
    <?php Pjax::begin(['timeout' => 10000]); ?>
    <?php echo GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            [
                'attribute' => 'id',
                'contentOptions' => ['style' => 'max-width: 50px;'],
            ],
            [
                'attribute' => 'content',
                'contentOptions' => ['style' => 'max-width: 350px;'],
                'value' => function ($model) {
                    return StringHelper::truncate($model->content, 100);
                },
            ],
            'attribute' => 'relatedTo',
            [
                'attribute' => 'createdBy',
                'value' => function ($model) {
                    return $model->getAuthorName();
                },
                'filter' => $commentModel::getAuthors(),
                'filterInputOptions' => ['prompt' => Module::t('message', 'Select Author'), 'class' => 'form-control'],
            ],
            [
                'attribute' => 'status',
                'value' => function ($model) {
                    return Status::getLabel($model->status);
                },
                'filter' => Status::listData(),
                'filterInputOptions' => ['prompt' => Module::t('message', 'Select Status'), 'class' => 'form-control'],
            ],
            [
                'attribute' => 'createdAt',
                'value' => function ($model) {
                    return Yii::$app->formatter->asDatetime($model->createdAt);
                },
                'filter' => false,
            ],
            [
                'header' => 'Actions',
                'class' => 'yii\grid\ActionColumn',
                'template' => '{view}{update}{delete}',
                'buttons' => [
                    'view' => function ($url, $model, $key) {
                        $title = Module::t('message', 'View');
                        $options = [
                            'title' => $title,
                            'aria-label' => $title,
                            'data-pjax' => '0',
                            'target' => '_blank',
                        ];
                        $icon = Html::tag('span', '', ['class' => 'glyphicon glyphicon-eye-open']);
                        $url = $model->getViewUrl();

                        if (!empty($url)) {
                            return Html::a($icon, $url, $options);
                        }

                        return null;
                    },
                ],
            ],
        ],
    ]);
    ?>
    <?php Pjax::end(); ?>
</div>
