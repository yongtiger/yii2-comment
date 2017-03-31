<?php

namespace yongtiger\comment\models;

use paulzi\adjacencyList\AdjacencyListBehavior;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii2mod\behaviors\PurifyBehavior;
use yongtiger\comment\Module;
use yii2mod\moderation\enums\Status;
use yii2mod\moderation\ModerationBehavior;
use yii2mod\moderation\ModerationQuery;

/**
 * Class CommentModel
 *
 * @property int $id
 * @property string $entity
 * @property int $entityId
 * @property string $content
 * @property int $parentId
 * @property int $level
 * @property int $up_vote
 * @property int $down_vote
 * @property int $createdBy
 * @property int $updatedBy
 * @property string $relatedTo
 * @property string $url
 * @property int $status
 * @property int $createdAt
 * @property int $updatedAt
 *
 * @method ActiveRecord makeRoot()
 * @method ActiveRecord appendTo($node)
 * @method ActiveQuery getDescendants()
 */
class CommentModel extends ActiveRecord
{

    ///[v0.0.10 (ADD# canCallback)]
    /**
     * permissions
     */
    const PERMISSION_DELETE = 'permision_comment_delete';
    const PERMISSION_UPDATE = 'permision_comment_update';
    const PERMISSION_VOTE = 'permision_comment_vote';   ///[v0.0.12 (ADD# vote)]

    /**
     * @var null|array|ActiveRecord[] comment children
     */
    protected $children;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%comment}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['entity', 'entityId'], 'required'],
            ['content', 'required', 'message' => Module::t('message', 'Comment cannot be blank.')],
            [['content', 'entity', 'relatedTo', 'url'], 'string'],
            ['status', 'default', 'value' => Status::APPROVED],
            ['status', 'in', 'range' => Status::getConstantsByName()],
            ['level', 'default', 'value' => 1],
            ['parentId', 'validateParentID'],
            [['entityId', 'parentId', 'status', 'level', 'up_vote', 'down_vote'], 'integer'],   ///[v0.0.12 (ADD# vote)]
        ];
    }

    /**
     * @param $attribute
     */
    public function validateParentID($attribute)
    {
        if ($this->{$attribute} !== null) {
            $parentCommentExist = static::find()
                ->approved()
                ->andWhere([
                    'id' => $this->{$attribute},
                    'entity' => $this->entity,
                    'entityId' => $this->entityId,
                ])
                ->exists();

            if (!$parentCommentExist) {
                $this->addError('content', Module::t('message', 'Oops, something went wrong. Please try again later.'));
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'blameable' => [
                'class' => BlameableBehavior::class,
                'createdByAttribute' => 'createdBy',
                'updatedByAttribute' => 'updatedBy',
            ],
            'timestamp' => [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'createdAt',
                'updatedAtAttribute' => 'updatedAt',
            ],
            'purify' => [
                'class' => PurifyBehavior::class,
                'attributes' => ['content'],
                'config' => [
                    'HTML.SafeIframe' => true,
                    'URI.SafeIframeRegexp' => '%^(https?:)?//(www\.youtube(?:-nocookie)?\.com/embed/|player\.vimeo\.com/video/)%',
                    'AutoFormat.Linkify' => true,
                    'HTML.TargetBlank' => true,
                    'HTML.Allowed' => 'a[href], iframe[src|width|height|frameborder], img[src]',
                ],
            ],
            'adjacencyList' => [
                'class' => AdjacencyListBehavior::class,
                'parentAttribute' => 'parentId',
                'sortable' => false,
            ],
            'moderation' => [
                'class' => ModerationBehavior::class,
                'moderatedByAttribute' => false,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Module::t('message', 'ID'),
            'content' => Module::t('message', 'Content'),
            'entity' => Module::t('message', 'Entity'),
            'entityId' => Module::t('message', 'Entity ID'),
            'parentId' => Module::t('message', 'Parent ID'),
            'status' => Module::t('message', 'Status'),
            'level' => Module::t('message', 'Level'),
            'up_vote' => Module::t('message', 'Up Vote'), ///[v0.0.12 (ADD# vote)]
            'down_vote' => Module::t('message', 'Down Vote'), ///[v0.0.12 (ADD# vote)]
            'createdBy' => Module::t('message', 'Created by'),
            'updatedBy' => Module::t('message', 'Updated by'),
            'relatedTo' => Module::t('message', 'Related to'),
            'url' => Module::t('message', 'Url'),
            'createdAt' => Module::t('message', 'Created date'),
            'updatedAt' => Module::t('message', 'Updated date'),
        ];
    }

    /**
     * @return ModerationQuery
     */
    public static function find()
    {
        return new ModerationQuery(get_called_class());
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($this->parentId > 0) {
                $parentNodeLevel = static::find()->select('level')->where(['id' => $this->parentId])->scalar();
                $this->level = $parentNodeLevel + 1;
            }

            return true;
        } else {
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if (!$insert) {
            if (array_key_exists('status', $changedAttributes)) {
                $this->beforeModeration();
            }
        }
    }

    /**
     * @return bool
     */
    public function saveComment()
    {
        if ($this->validate()) {
            if (empty($this->parentId)) {
                return $this->makeRoot()->save();
            } else {
                $parentComment = static::findOne(['id' => $this->parentId]);

                return $this->appendTo($parentComment)->save();
            }
        }

        return false;
    }

    /**
     * Author relation
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAuthor()
    {
        $module = Module::instance();

        return $this->hasOne($module->userIdentityClass, ['id' => 'createdBy']);
    }

    /**
     * Get comments tree.
     *
     * @param string $entity
     * @param string $entityId
     * @param null $maxLevel
     *
     * @return array|ActiveRecord[]
     */
    public static function getTree($entity, $entityId, $maxLevel = null)
    {
        $query = static::find()
            ->approved()
            ->andWhere([
                'entityId' => $entityId,
                'entity' => $entity,
            ])->with(['author']);

        if ($maxLevel > 0) {
            $query->andWhere(['<=', 'level', $maxLevel]);
        }

        $models = $query->all();

        if (!empty($models)) {
            $models = self::buildTree($models);
        }

        return $models;
    }

    /**
     * Build comments tree.
     *
     * @param array $data comments list
     * @param int $rootID
     *
     * @return array|ActiveRecord[]
     */
    protected static function buildTree(&$data, $rootID = 0)
    {
        $tree = [];

        foreach ($data as $id => $node) {
            if (isset($node) && $node->parentId == $rootID) {
                $data[$id] = (unset)$data[$id];///[v0.0.5 (FIX# buildTree)]As the php documentation reads:As foreach relies on the internal array pointer in PHP 5, changing it within the loop may lead to unexpected behavior.
                $node->children = self::buildTree($data, $node->id);
                $tree[] = $node;
            }
        }

        return $tree;
    }

    /**
     * @return array|null|ActiveRecord[]
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @param $value
     */
    public function setChildren($value)
    {
        $this->children = $value;
    }

    /**
     * @return bool
     */
    public function hasChildren()
    {
        return !empty($this->children);
    }

    /**
     * @return string
     */
    public function getPostedDate()
    {
        return Yii::$app->formatter->asRelativeTime($this->createdAt);
    }

    /**
     * @return mixed
     */
    public function getAuthorName()
    {
        if ($this->author->hasMethod('getUsername')) {
            return $this->author->getUsername();
        }

        return $this->author->username;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return nl2br($this->content);
    }

    /**
     * Get avatar of the user
     *
     * @return string
     */
    public function getAvatar()
    {
        if ($this->author->hasMethod('getAvatar')) {
            return $this->author->getAvatar();
        } else {    ///[v0.0.9 (ADD# getUserAvatar)]
            return Module::instance()->getUserAvatar($this->author->id);
        }
    }

    /**
     * Get list of all authors
     *
     * @return array
     */
    public static function getAuthors()
    {
        $query = static::find()
            ->alias('c')
            ->select(['c.createdBy', 'a.username'])
            ->joinWith('author a')
            ->groupBy(['c.createdBy', 'a.username'])
            ->orderBy('a.username')
            ->asArray()
            ->all();

        return ArrayHelper::map($query, 'createdBy', 'author.username');
    }

    /**
     * @return int
     */
    public function getCommentsCount()
    {
        return (int)static::find()
            ->approved()
            ->andWhere(['entity' => $this->entity, 'entityId' => $this->entityId])
            ->count();
    }

    /**
     * @return string
     */
    public function getAnchorUrl()
    {
        return "#comment-{$this->id}";
    }

    /**
     * @return null|string
     */
    public function getViewUrl()
    {
        if (!empty($this->url)) {
            return $this->url . $this->getAnchorUrl();
        }

        return null;
    }

    /**
     * Before moderation event
     *
     * @return bool
     */
    public function beforeModeration()
    {
        $descendantIds = ArrayHelper::getColumn($this->getDescendants()->asArray()->all(), 'id');

        if (!empty($descendantIds)) {
            self::updateAll(['status' => $this->status], ['id' => $descendantIds]);
        }

        return true;
    }

    ///[v0.0.10 (ADD# canCallback)]
    /**
     * Checks if the user can perform the operation as specified by the given permission.
     *
     * @see [[yii\web\User]]
     *
     * @param string $permissionName the name of the permission (e.g. "permision_delete_comment") that needs access check.
     * @param array $params name-value pairs that would be passed to the rules associated
     * with the roles and permissions assigned to the user.
     * @param bool $allowCaching whether to allow caching the result of access check.
     * When this parameter is true (default), if the access check of an operation was performed
     * before, its result will be directly returned when calling this method to check the same
     * operation. If this parameter is false, this method will always call
     * [[\yii\rbac\CheckAccessInterface::checkAccess()]] to obtain the up-to-date access result. Note that this
     * caching is effective only within the same request and only works when `$params = []`.
     * @return bool whether the user can perform the operation as specified by the given permission.
     */
    public function can($permissionName, $params = [], $allowCaching = true)
    {
        $canCallback = Module::instance()->canCallback;
        if (is_callable($canCallback)) {
            return call_user_func($canCallback, $permissionName, $params, $allowCaching);
        } else {
            return false;
        }
    }

}
