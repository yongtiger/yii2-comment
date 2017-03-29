<?php

namespace yongtiger\comment;

use yii\web\AssetBundle;

/**
 * Class CommentAsset
 *
 * @package yongtiger\comment
 */
class CommentAsset extends AssetBundle
{
    /**
     * {@inheritdoc}
     */
    public $sourcePath = '@yongtiger/comment/assets';

    /**
     * {@inheritdoc}
     */
    public $js = [
        'js/comment.js',
    ];

    /**
     * {@inheritdoc}
     */
    public $css = [
        'css/comment.css',
    ];

    /**
     * {@inheritdoc}
     */
    public $depends = [
        'yii\web\JqueryAsset',
        'yii\web\YiiAsset',
    ];
}
