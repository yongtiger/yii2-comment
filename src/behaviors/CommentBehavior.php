<?php
namespace yongtiger\comment\behaviors;

use yii\base\Behavior;
use yii\helpers\ArrayHelper;
use yongtiger\comment\widgets\Comment;

/**
 * Comment Behavior
 *
 * Renders comment and form for owner model.
 *
 * Example Post model behaviors configuration:
 *
 * ```php
 * public function behaviors()
 * {
 *     return [
 *         'comment' => [
 *             'class' => \yongtiger\comment\behaviors\CommentBehavior::className(),
 *             'config' => [
 *                 'dataProviderConfig' => [
 *                     'pagination' => [
 *                         'pageParam' => 'comment-page',
 *                         'pageSizeParam' => 'comment-per-page',
 *                         'pageSize' => 10,
 *                     ],
 *                     'sort' => [
 *                          'defaultOrder' => [
 *                             'id' => SORT_DESC,
 *                             // 'id' => SORT_ASC,
 *                         ],
 *                     ],
 *                 ],
 *             ],
 *         ],
 *         // ...
 *     ];
 * }
 * ```
 *
 * [REFERENCES]
 *
 * @see https://github.com/yeesoft/yii2-comments#usage
 */
class CommentBehavior extends Behavior
{
    /**
     * @var array
     */
    public $config = [];

    /**
     * Displays comment widget.
     *
     * @param array $config
     * @return mixed the rendering result of the Comment Widget for owner model
     */
    public function displayComment($config = [])
    {
        $config = ArrayHelper::merge(['model' => $this->owner], $this->config, $config);
        return Comment::widget($config);
    }
}