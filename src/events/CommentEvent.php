<?php

namespace yongtiger\comment\events;

use yii\base\Event;
use yongtiger\comment\models\CommentModel;

/**
 * Class CommentEvent
 *
 * @package yongtiger\comment\events
 */
class CommentEvent extends Event
{
    /**
     * @var CommentModel
     */
    private $_commentModel;

    /**
     * @return CommentModel
     */
    public function getCommentModel()
    {
        return $this->_commentModel;
    }

    /**
     * @param CommentModel $commentModel
     */
    public function setCommentModel(CommentModel $commentModel)
    {
        $this->_commentModel = $commentModel;
    }
}
