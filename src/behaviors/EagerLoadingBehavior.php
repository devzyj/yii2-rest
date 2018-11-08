<?php
/**
 * @link https://github.com/devzyj/yii2-rest
 * @copyright Copyright (c) 2018 Zhang Yan Jiong
 * @license http://opensource.org/licenses/BSD-3-Clause
 */
namespace devzyj\rest\behaviors;

use Yii;

/**
 * EagerLoadingBehavior 实现了在执行 [[Action::EVENT_AFTER_PREPARE_DATA_PROVIDER]] 事件时，即时加载指定的额外资源。
 * 
 * For example:
 * 
 * ```php
 * // UserController.php
 * class UserController extends \devzyj\rest\ActiveController
 * {
 *     public function actions()
 *     {
 *         return \yii\helpers\ArrayHelper::merge(parent::actions(), [
 *             'index' => [
 *                 'as eagerLoadingBehavior' => [
 *                     'class' => 'devzyj\rest\behaviors\EagerLoadingBehavior',
 *                 ]
 *             ],
 *         ]);
 *     }
 * }
 * ```
 * 
 * @author ZhangYanJiong <zhangyanjiong@163.com>
 * @since 1.0
 */
class EagerLoadingBehavior extends \yii\base\Behavior
{
    /**
     * @var string 显示额外资源的参数名称。
     */
    public $expandParam = 'expand';

    /**
     * @var \yii\web\Request 当前的请求。如果没有设置，将使用 `Yii::$app->getRequest()`。
     */
    public $request;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        if ($this->request === null) {
            $this->request = Yii::$app->getRequest();
        }
    
        parent::init();
    }

    /**
     * {@inheritdoc}
     */
    public function events()
    {
        return [
            \devzyj\rest\Action::EVENT_AFTER_PREPARE_DATA_PROVIDER => 'afterPrepareDataProvider',
        ];
    }

    /**
     * @param \devzyj\rest\ActionEvent $event
     * @see \devzyj\rest\Action::afterPrepareDataProvider()
     */
    public function afterPrepareDataProvider($event)
    {
        if ($event->object instanceof \yii\data\ActiveDataProvider) {
            $query = $event->object->query;
            if ($query instanceof \yii\db\ActiveQuery) {
                /* @var $model \yii\db\ActiveRecord */
                $modelClass = $query->modelClass;
                $model = $modelClass::instance();
                $expand = $this->getRequestedExpand();
                $with = [];
                foreach ($expand as $name) {
                    if ($model->getRelation($name, false)) {
                        $with[] = $name;
                    }
                }
                
                if ($with) {
                    $query->with($with);
                }
            }
        }
    }
    
    /**
     * 获取请求的额外资源名称列表。
     * 
     * @return array 额外资源名称列表。
     */
    protected function getRequestedExpand()
    {
        $expand = $this->request->get($this->expandParam);
        return is_string($expand) ? preg_split('/\s*,\s*/', $expand, -1, PREG_SPLIT_NO_EMPTY) : [];
    }
}