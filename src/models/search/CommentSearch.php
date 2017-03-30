<?php

namespace yongtiger\comment\models\search;

use yii\data\ActiveDataProvider;
use yongtiger\comment\models\CommentModel;

/**
 * Class CommentSearch
 *
 * @package yongtiger\comment\models\search
 */
class CommentSearch extends CommentModel
{
    /**
     * @var int the default page size
     */
    public $pageSize = 10;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'createdBy', 'content', 'status', 'relatedTo'], 'safe'],
        ];
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = CommentModel::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $this->pageSize,
            ],
        ]);

        $dataProvider->setSort([
            'defaultOrder' => ['id' => SORT_DESC],
        ]);

        // load the search form data and validate
        if (!($this->load($params))) {
            return $dataProvider;
        }

        // adjust the query by adding the filters
        $query->andFilterWhere(['id' => $this->id]);

        ///[v0.0.6 (ADD# datepicker)]
        $query->andFilterWhere(['DATE(FROM_UNIXTIME(createdAt))' => $this->createdAt]);

        $query->andFilterWhere(['status' => $this->status]);
        $query->andFilterWhere(['like', 'content', $this->content]);
        $query->andFilterWhere(['like', 'relatedTo', $this->relatedTo]);

        return $dataProvider;
    }
}
