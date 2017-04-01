<?php

namespace yongtiger\comment\widgets;

use Yii;
use yii\base\InvalidConfigException;
use yii\base\Widget;
use yii\helpers\Json;
use yii\helpers\ArrayHelper;
use yii\data\ArrayDataProvider;
use yongtiger\comment\CommentAsset;
use yongtiger\comment\Module;

/**
 * Class Comment
 *
 * @package yongtiger\comment\widgets
 */
class Comment extends Widget
{
    /**
     * @var \yii\db\ActiveRecord|null Widget model
     */
    public $model;

    /**
     * @var string relatedTo custom text, for example: cms url: about-us, john comment about us page, etc.
     * By default - class:primaryKey of the current model
     */
    public $relatedTo;

    /**
     * @var string the view file that will render the comment tree and form for posting comments
     */
    public $commentView = '@yongtiger/comment/widgets/views/index';

    /**
     * @var string comment form id
     */
    public $formId = 'comment-form';

    /**
     * @var string pjax container id
     */
    public $pjaxContainerId;

    /**
     * @var null|int maximum comments level, level starts from 1, null - unlimited level;
     */
    public $maxLevel = 10;

    /**
     * @var string entity id attribute
     */
    public $entityIdAttribute = 'id';

    /**
     * @var array DataProvider config
     */
    public $dataProviderConfig = [];

    ///[v0.0.16 (ADD# sort)]
    /**
     * @var string
     */
    public $sort = 'created-at-desc';

    /**
     * @var array ListView config
     */
    public $listViewConfig = [];

    /**
     * @var array comment widget client options
     */
    public $clientOptions = [];

    /**
     * @var string hash(crc32) from class name of the widget model
     */
    protected $entity;

    /**
     * @var int primary key value of the widget model
     */
    protected $entityId;

    /**
     * @var string encrypted entity
     */
    protected $encryptedEntity;

    /**
     * @var string comment wrapper tag id
     */
    protected $commentWrapperId;

    /**
     * Initializes the widget params.
     */
    public function init()
    {
        parent::init();

        if (empty($this->model)) {
            throw new InvalidConfigException('The "model" property must be set.');
        }

        if (empty($this->pjaxContainerId)) {
            $this->pjaxContainerId = 'comment-pjax-container-' . $this->getId();
        }

        if (empty($this->model->{$this->entityIdAttribute})) {
            throw new InvalidConfigException('The "entityIdAttribute" value for widget model cannot be empty.');
        }

        $this->entity = hash('crc32', get_class($this->model));
        $this->entityId = $this->model->{$this->entityIdAttribute};

        if (empty($this->relatedTo)) {
            $this->relatedTo = get_class($this->model) . ':' . $this->entityId;
        }

        $this->encryptedEntity = $this->getEncryptedEntity();
        $this->commentWrapperId = $this->entity . $this->entityId;

        ///[v0.0.3 (CHG# dataProviderConfig)]
        $this->dataProviderConfig = ArrayHelper::merge([
            'pagination' => [
                'pageParam' => 'page',
                'pageSizeParam' => 'per-page',
                'pageSize' => 10,
            ],
        ], $this->dataProviderConfig);

        $this->registerAssets();
    }

    /**
     * Executes the widget.
     *
     * @return string the result of widget execution to be outputted
     */
    public function run()
    {
        /* @var $commentModelClass string class name of \yongtiger\comment\models\CommentModel */
        $commentModelClass = Module::instance()->commentModelClass;
        $commentModel = Yii::createObject([
            'class' => $commentModelClass,
            'entity' => $this->entity,
            'entity_id' => $this->entityId,
        ]);
        $commentDataProvider = $this->getCommentDataProvider();

        return $this->render($this->commentView, [
            'commentDataProvider' => $commentDataProvider,
            'commentModel' => $commentModel,
            'maxLevel' => $this->maxLevel,
            'encryptedEntity' => $this->encryptedEntity,
            'pjaxContainerId' => $this->pjaxContainerId,
            'formId' => $this->formId,
            'listViewConfig' => $this->listViewConfig,
            'commentWrapperId' => $this->commentWrapperId,
        ]);
    }

    /**
     * Get encrypted entity
     *
     * @return string
     */
    protected function getEncryptedEntity()
    {
        return utf8_encode(Yii::$app->getSecurity()->encryptByKey(Json::encode([
            'entity' => $this->entity,
            'entity_id' => $this->entityId,
            'related_to' => $this->relatedTo,
        ]), Module::$moduleName));
    }

    /**
     * Register assets.
     */
    protected function registerAssets()
    {
        $view = $this->getView();

        ///[v0.0.9 (ADD# getUserAvatar)]
        ///@see https://github.com/yeesoft/yii2-comments/blob/86bc4887517520f962bbb3f8669ef14177af9564/widgets/Comments.php#L32
        $commentAsset = CommentAsset::register($view);
        Module::instance()->commentAssetUrl = $commentAsset->baseUrl;

        $view->registerJs("jQuery('#{$this->commentWrapperId}').comment({$this->getClientOptions()});");
    }

    /**
     * @return string
     */
    protected function getClientOptions()
    {
        $this->clientOptions['pjaxContainerId'] = '#' . $this->pjaxContainerId;
        $this->clientOptions['formSelector'] = '#' . $this->formId;

        return Json::encode($this->clientOptions);
    }

    /**
     * Get comment ArrayDataProvider
     *
     * Note: Compared to [[ActiveDataProvider]], ArrayDataProvider could be less efficient because it needs to have [[allModels]] ready.
     * @see [[ArrayDataProvider]]
     *
     * @param CommentModel $commentClass
     *
     * @return ArrayDataProvider
     */
    protected function getCommentDataProvider()
    {
        $dataProvider = new ArrayDataProvider($this->dataProviderConfig);
        if (!isset($this->dataProviderConfig['allModels'])) {
            $dataProvider->allModels = $this->getTree($this->entity, $this->entityId, $this->maxLevel);
        }
        return $dataProvider;
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
    public function getTree($entity, $entityId, $maxLevel = null)
    {
        /* @var $commentModelClass string class name of \yongtiger\comment\models\CommentModel */
        $commentModelClass = Module::instance()->commentModelClass;

        $query = $commentModelClass::find()
            ->approved()
            ->andWhere([
                'entity_id' => $entityId,
                'entity' => $entity,
            ])->with(['author']);

        if ($maxLevel > 0) {
            $query->andWhere(['<=', 'level', $maxLevel]);
        }

        ///[v0.0.16 (ADD# sort)]
        $params = Yii::$app->request->queryParams;
        if (empty($params['orderby'])) {
            $params['orderby'] = $this->sort;
        }
        if ($params['orderby'] == 'created-at-desc') {
            $query->orderBy(['created_at' => SORT_DESC]);
        } elseif ($params['orderby'] == 'created-at-asc') {
            $query->orderBy(['created_at' => SORT_ASC]);
        } elseif ($params['orderby'] == 'vote-up-desc') {
            $query->orderBy(['vote_up' => SORT_DESC]);
        } elseif ($params['orderby'] == 'vote-up-asc') {
            $query->orderBy(['vote_up' => SORT_ASC]);
        } elseif ($params['orderby'] == 'vote-down-desc') {
            $query->orderBy(['vote_down' => SORT_DESC]);
        } elseif ($params['orderby'] == 'vote-down-asc') {
            $query->orderBy(['vote_down' => SORT_ASC]);
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
            if (isset($node) && $node->parent_id == $rootID) {
                $data[$id] = (unset)$data[$id];///[v0.0.5 (FIX# buildTree)]As the php documentation reads:As foreach relies on the internal array pointer in PHP 5, changing it within the loop may lead to unexpected behavior.
                $node->children = self::buildTree($data, $node->id);
                $tree[] = $node;
            }
        }

        return $tree;
    }

}
