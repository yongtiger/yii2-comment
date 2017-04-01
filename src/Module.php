<?php

namespace yongtiger\comment;

use Yii;

/**
 * Class Module
 *
 * @package yongtiger\comment
 */
class Module extends \yii\base\Module
{
    ///[v0.0.9 (ADD# getUserAvatar)]
    /**
     * Default avatar image
     */
    const DEFAULT_AVATAR = '/images/avatar.png';

    /**
     * @var string module name
     */
    public static $moduleName = 'comment';

    /**
     * @var string the class name of the [[identity]] object
     */
    public $userIdentityClass;

    /**
     * @var string the class name of the comment model object, by default its yongtiger\comment\models\CommentModel
     */
    public $commentModelClass = 'yongtiger\comment\models\CommentModel';

    /**
     * @var string the namespace that controller classes are in
     */
    public $controllerNamespace = 'yongtiger\comment\controllers';

    ///[v0.0.9 (ADD# getUserAvatar)]
    /**
     * @var string the url of comment asset bundle
     */
    public $commentAssetUrl;

    /**
     * The field for displaying user avatars.
     *
     * Is this field is NULL default avatar image will be displayed. Also it
     * can specify path to image or use callable type.
     *
     * If this property is specified as a callback, it should have the following signature:
     *
     * ```php
     * function ($user_id)
     * ```
     *
     * Example of module settings :
     * ```php
     * 'comment' => [
     *       'class' => 'yongtiger\comment\Comments',
     *       'userAvatar' => function($user_id){
     *           // customize your own avatar getter code in User ...
     *           return User::getAvatarByID($user_id);
     *       }
     *   ]
     * ```
     *
     * @see https://github.com/yeesoft/yii2-comments/blob/master/Comments.php#L113
     *
     * @var string|callable
     */
    public $userAvatar;
    ///[http://www.brainbook.cc]

    ///[v0.0.10 (ADD# canCallback)]
    /**
     * The field for checking user permissions.
     *
     * Callback should have the following signature:
     *
     * ```php
     * function($permissionName, $params = [], $allowCaching = true)
     * ```
     *
     * Example of module settings :
     * ```php
     * 'comment' => [
     *       'class' => 'yongtiger\comment\Comments',
     *       'canCallback' => function($permissionName, $params = [], $allowCaching = true) {
     *           // customize your own code ...
     *           return Yii::$app->getUser()->can($permissionName, $params, $allowCaching);
     *       }
     *   ]
     * ```
     *
     * @var callable
     */
    public $canCallback;

    ///[v0.0.17 (ADD# editorCallback)]
    /**
     *
     * @var callable
     */
    public $editorCallback;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if ($this->userIdentityClass === null) {
            $this->userIdentityClass = Yii::$app->getUser()->identityClass;
        }
    }

    /**
     * @return static
     */
    public static function instance()
    {
        return Yii::$app->getModule(static::$moduleName);
    }

    ///[v0.0.9 (ADD# getUserAvatar)]
    /**
     * Gets user avatar by UserID according to $userAvatar setting
     *
     * @param int $user_id
     * @return string
     */
    public function getUserAvatar($user_id)
    {
        $defaultAvatar = $this->commentAssetUrl . self::DEFAULT_AVATAR;
        if (is_string($this->userAvatar)) {
            return $this->userAvatar;
        } else if (is_callable($this->userAvatar)) {
            return ($avatar = call_user_func($this->userAvatar, $user_id)) ? $avatar : $defaultAvatar;
        } else {
            return $defaultAvatar;
        }
    }

    /**
     * Registers the translation files.
     */
    public static function registerTranslations()
    {
        ///[i18n]
        ///if no setup the component i18n, use setup in this module.
        if (!isset(Yii::$app->i18n->translations['extensions/yongtiger/yii2-comment/*']) && !isset(Yii::$app->i18n->translations['extensions/yongtiger/yii2-comment'])) {
            Yii::$app->i18n->translations['extensions/yongtiger/yii2-comment/*'] = [
                'class' => 'yii\i18n\PhpMessageSource',
                'sourceLanguage' => 'en-US',
                'basePath' => '@vendor/yongtiger/yii2-comment/src/messages',
                'fileMap' => [
                    'extensions/yongtiger/yii2-comment/message' => 'message.php',  ///category in Module::t() is message
                ],
            ];
        }
    }

    /**
     * Translates a message. This is just a wrapper of Yii::t().
     *
     * @see http://www.yiiframework.com/doc-2.0/yii-baseyii.html#t()-detail
     *
     * @param $category
     * @param $message
     * @param array $params
     * @param null $language
     * @return string
     */
    public static function t($category, $message, $params = [], $language = null)
    {
        static::registerTranslations();
        return Yii::t('extensions/yongtiger/yii2-comment/' . $category, $message, $params, $language);
    }
}
