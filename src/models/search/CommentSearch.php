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
            [['id', 'created_by', 'content', 'status', 'related_to'], 'safe'],
            [['created_at', 'updated_at'], 'date', 'format' => 'yyyy-MM-dd'],  ///[v0.0.6 (ADD# datepicker)]
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
        $query->andFilterWhere(['created_by' => $this->created_by]);
        $query->andFilterWhere(['status' => $this->status]);
        $query->andFilterWhere(['like', 'content', $this->content]);
        $query->andFilterWhere(['like', 'related_to', $this->related_to]);

        ///[v0.0.6 (ADD# datepicker)]
        $query->andFilterWhere(['DATE(FROM_UNIXTIME(created_at))' => $this->created_at]);

        return $dataProvider;
    }
}
