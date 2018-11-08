<?php
/**
 * @link https://github.com/devzyj/yii2-rest
 * @copyright Copyright (c) 2018 Zhang Yan Jiong
 * @license http://opensource.org/licenses/BSD-3-Clause
 */
namespace devzyj\rest;

use Yii;
use yii\data\ActiveDataProvider;

/**
 * IndexAction 实现了列出多个模型的 API 端点。
 * 
 * @author ZhangYanJiong <zhangyanjiong@163.com>
 * @since 1.0
 */
class IndexAction extends Action
{
    /**
     * @var callable 准备数据源的回调方法。方法应该返回一个 [[\yii\data\ActiveDataProvider]] 实例。
     * 
     * 使用方法如下：
     * ```php
     * function (IndexAction $action, array $params, mixed $filter = null) {
     *     // $action 当前正在运行的操作对象。
     *     // $params 请求的参数。
     *     // $filter 构造的过滤条件。如果设置了 [[$dataFilter]]，则该参数不为 `null`。
     * }
     * ```
     */
    public $prepareDataProvider;
    
    /**
     * @var \yii\data\DataFilter|null 用于构造过滤条件的过滤器。
     * 
     * 使用方法如下：
     * ```php
     * [
     *     'class' => 'yii\data\ActiveDataFilter',
     *     'searchModel' => function () {
     *         return (new \yii\base\DynamicModel(['id' => null, 'name' => null, 'price' => null]))
     *             ->addRule('id', 'integer')
     *             ->addRule('name', 'string');
     *     },
     * ]
     * ```
     * 
     * @see \yii\data\DataFilter
     */
    public $dataFilter;
    
    /**
     * 查询并列出多个模型。
     * 
     * 该方法依次执行以下步骤：
     * 1. 当设置了 [[$checkAccess]] 时，调用该回调方法检查动作权限；
     * 2. 调用 [[afterPrepareDataProvider()]]，触发 [[EVENT_AFTER_PREPARE_DATA_PROVIDER]] 事件；
     * 
     * @return \yii\data\ActiveDataProvider 需要显示的数据源。
     */
    public function run()
    {
        // 检查动作权限。
        if ($this->checkAccess) {
            call_user_func($this->checkAccess, $this);
        }

        // 获取请求参数。
        $params = $this->request->getBodyParams();
        if (empty($params)) {
            $params = $this->request->getQueryParams();
        }

        // 构造过滤条件。
        $filter = null;
        if ($this->dataFilter) {
            if (!$this->dataFilter instanceof \yii\data\DataFilter) {
                $this->dataFilter = Yii::createObject($this->dataFilter);
            }
            
            if ($this->dataFilter->load($params)) {
                $filter = $this->dataFilter->build();
                if ($filter === false) {
                    return $this->dataFilter;
                }
            }
        }
        
        // 准备并且返回数据源。
        return $this->prepareDataProvider($params, $filter);
    }

    /**
     * 准备数据源。
     * 
     * @param array $params 请求的参数。
     * @param mixed $filter 构造的过滤条件。
     * @return \yii\data\ActiveDataProvider 准备完成的数据源。
     */
    protected function prepareDataProvider($params, $filter)
    {
        if ($this->prepareDataProvider) {
            $dataProvider = call_user_func($this->prepareDataProvider, $this, $params, $filter);
        } else {
            /* @var $modelClass \yii\db\ActiveRecordInterface */
            $modelClass = $this->modelClass;
            $query = $modelClass::find();
            if (!empty($filter)) {
                $query->andWhere($filter);
            }
            
            $dataProvider = Yii::createObject([
                'class' => ActiveDataProvider::className(),
                'query' => $query,
                'pagination' => [
                    'params' => $params,
                ],
                'sort' => [
                    'params' => $params,
                ],
            ]);
        }
        
        // 执行在准备完数据源后的方法和事件。
        $this->afterPrepareDataProvider($dataProvider);
        
        // 返回数据源。
        return $dataProvider;
    }
}
