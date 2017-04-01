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
 *                         'pageSizeLimit' => [1, 50],
 *                     ],
 *                 ],
 *                 'sort' => 'created-at-asc',
 *             ],
 *         ],
 *         // ...
 *     ];
 * }
 * ```
 *
 * Usage Example:
 *
 * ```php
 * echo $postModelClassName::findOne(5)->displayComment();
 * ```
 *
 * Note: Configs in `displayComment()` will overrides the configs in behaviors:
 * ```php
 * echo $postModelClassName::findOne(5)->displayComment(
 *     [
 *         'dataProviderConfig' => [
 *             'pagination' => [
 *                 'pageParam' => 'comment-page',
 *                 'pageSizeParam' => 'comment-per-page',
 *                 'pageSize' => 5,
 *                 'pageSizeLimit' => [1, 50],
 *             ],
 *         ],
 *         'sort' => 'created-at-asc',
 *     ],
 * );
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