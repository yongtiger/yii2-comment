<?php

namespace yongtiger\comment\models;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yongtiger\comment\Module;
use yii2mod\moderation\enums\Status;
use yii2mod\moderation\ModerationBehavior;
use yii2mod\moderation\ModerationQuery;
use paulzi\adjacencyList\AdjacencyListBehavior;

/**
 * Class CommentModel
 *
 * @property int $id
 * @property string $entity
 * @property int $entity_id
 * @property string $content
 * @property int $parent_id
 * @property int $level
 * @property int $vote_up
 * @property int $vote_down
 * @property int $created_by
 * @property int $updated_by
 * @property string $related_to
 * @property string $url
 * @property int $status
 * @property int $created_at
 * @property int $updated_at
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
            [['entity', 'entity_id'], 'required'],
            ['content', 'required', 'message' => Module::t('message', 'Comment cannot be blank.')],
            [['content', 'entity', 'related_to', 'url'], 'string'],
            ['status', 'default', 'value' => Status::APPROVED],
            ['status', 'in', 'range' => Status::getConstantsByName()],
            ['level', 'default', 'value' => 1],
            ['parent_id', 'validateParentID'],
            [['entity_id', 'parent_id', 'status', 'level', 'vote_up', 'vote_down'], 'integer'],   ///[v0.0.12 (ADD# vote)]
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
                    'entity_id' => $this->entity_id,
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
                'createdByAttribute' => 'created_by',
                'updatedByAttribute' => 'updated_by',
            ],
            'timestamp' => [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
            ],
            'adjacencyList' => [
                'class' => AdjacencyListBehavior::class,
                'parentAttribute' => 'parent_id',
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
            'entity_id' => Module::t('message', 'Entity ID'),
            'parent_id' => Module::t('message', 'Parent ID'),
            'status' => Module::t('message', 'Status'),
            'level' => Module::t('message', 'Level'),
            'vote_up' => Module::t('message', 'Vote Up'), ///[v0.0.12 (ADD# vote)]
            'vote_down' => Module::t('message', 'Vote Down'), ///[v0.0.12 (ADD# vote)]
            'created_by' => Module::t('message', 'Created by'),
            'updated_by' => Module::t('message', 'Updated by'),
            'related_to' => Module::t('message', 'Related to'),
            'url' => Module::t('message', 'Url'),
            'created_at' => Module::t('message', 'Created date'),
            'updated_at' => Module::t('message', 'Updated date'),
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
            if ($this->parent_id > 0) {
                $parentNodeLevel = static::find()->select('level')->where(['id' => $this->parent_id])->scalar();
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
            if (empty($this->parent_id)) {
                return $this->makeRoot()->save();
            } else {
                $parentComment = static::findOne(['id' => $this->parent_id]);

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

        return $this->hasOne($module->userIdentityClass, ['id' => 'created_by']);
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
        return Yii::$app->formatter->asRelativeTime($this->created_at);
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
            ->select(['c.created_by', 'a.username'])
            ->joinWith('author a')
            ->groupBy(['c.created_by', 'a.username'])
            ->orderBy('a.username')
            ->asArray()
            ->all();

        return ArrayHelper::map($query, 'created_by', 'author.username');
    }

    /**
     * @return int
     */
    public function getCommentsCount()
    {
        return (int)static::find()
            ->approved()
            ->andWhere(['entity' => $this->entity, 'entity_id' => $this->entity_id])
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
