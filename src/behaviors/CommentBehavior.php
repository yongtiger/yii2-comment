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