<?php
/**
 * @link https://github.com/devzyj/yii2-rest
 * @copyright Copyright (c) 2018 Zhang Yan Jiong
 * @license http://opensource.org/licenses/BSD-3-Clause
 */
namespace devzyj\rest;

use Yii;
use yii\web\HttpException;
use yii\web\ForbiddenHttpException;

/**
 * BatchAction 是实现 RESTful API 的批量动作类的基类。
 * 
 * @author ZhangYanJiong <zhangyanjiong@163.com>
 * @since 1.0
 */
class BatchAction extends Action
{
    use BatchActionTrait;

    /**
     * @event ActionEvent 在处理完模型列表后触发的事件。
     */
    const EVENT_AFTER_PROCESS_MODELS = 'afterProcessModels';
    
    /**
     * @var integer 允许批量执行的资源个数。
     */
    public $allowedCount;

    /**
     * @var string 批量操作请求资源过多的错误信息。
     * 支持变量 `{allowedCount}` 和 `{requestedCount}`。
     */
    public $manyResourcesMessage;

    /**
     * @var callable 根据多个主键，获取多个模型的回调方法。
     * 方法应该只返回存在的数据，如果没有查询到数据，则返回空数组。
     * 如果没有设置，则使用 [[findModels()]]。
     *
     * ```php
     * function (array $ids, BatchAction $action) {
     *     // $ids 主键列表。
     *     // $action 当前正在执行的动作。
     * }
     * ```
     */
    public $findModels;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        if ($this->manyResourcesMessage === null) {
            $this->manyResourcesMessage = 'The number of resources requested cannot exceed `{allowedCount}`.';
        }
    
        parent::init();
    }
    
    /**
     * 检查允许批量执行的资源个数。
     *
     * @param array $data 请求的资源数组。
     * @throws \yii\web\HttpException 如果设置了 [[$allowedCount]] 并且超出设置的数量。
     */
    public function checkAllowedCount($data)
    {
        $requestedCount = count($data);
        if ($this->allowedCount !== null && $requestedCount > $this->allowedCount) {
            throw new HttpException(413, strtr($this->manyResourcesMessage, [
                '{allowedCount}' => $this->allowedCount,
                '{requestedCount}' => $requestedCount,
            ]));
        }
    }
    
    /**
     * 根据指定的多个主键，返回多个数据模型。
     *
     * @param array $ids 主键列表。如果是复合主键，则使用逗号分隔，主键值的顺序应该遵循模型的 `primaryKey()` 方法返回的值。
     * @return BatchModels 查询到的模型列表。返回的结果中只包含存在的数据，如果没有查询到数据，则返回空数组。
     */
    public function findModels($ids)
    {
        $models = [];
        
        if ($this->findModels) {
            $models = call_user_func($this->findModels, $ids, $this);
        } elseif ($ids && is_array($ids)) {
            /* @var $modelClass \yii\db\ActiveRecordInterface */
            $modelClass = $this->modelClass;
            $keys = $modelClass::primaryKey();
            if (count($keys) > 1) {
                // composite primary key.
                $condition = [];
                foreach ($ids as $id) {
                    $values = explode(',', $id);
                    if (count($keys) === count($values)) {
                        $condition[] = array_combine($keys, $values);
                    }
                }
            
                if ($condition) {
                    array_unshift($condition, 'OR');
                    $models = $modelClass::find()->where($condition)->all();
                }
            } else {
                // single primary key.
                $models = $modelClass::findAll(array_values($ids));
            }
        }
        
        return Yii::createObject([
            'class' => BatchModels::className(),
            'data' => $models
        ]);
    }
    
    /**
     * 根据指定的多个主键，准备多个数据模型。
     * 
     * 该方法依次执行以下步骤：
     * 1. 调用 [[findModels()]]，查找多个数据模型；
     * 2. 循环调用 [[afterPrepareModel()]]，触发 [[EVENT_AFTER_PREPARE_MODEL]] 事件；
     * 
     * @param string $ids 模型IDs。
     * @return BatchModels 查询到的模型列表。
     */
    public function prepareModels($ids)
    {
        // 根据指定的多个主键，返回多个数据模型。
        $models = $this->findModels($ids);
        foreach ($models as $model) {
            // 执行在准备完模型后的方法和事件。
            $this->afterPrepareModel($model);
        }
        
        // 返回模型列表。
        return $models;
    }
    
    /**
     * 确认并返回有权限的模型列表。
     * 
     * 该方法使用 [[$checkModelAccess]] 回调方法，检查并且过滤掉没有权限的模型。
     * 
     * @param BatchModels $models 模型列表。
     * @return BatchModels 有权限的模型列表。
     */
    public function ensureModelsAccess($models)
    {
        if ($this->checkModelAccess) {
            foreach ($models as $key => $model) {
                try {
                    call_user_func($this->checkModelAccess, $model, $this);
                } catch (ForbiddenHttpException $e) {
                    // 移除没有权限的模型。
                    unset($models[$key]);
                    continue;
                }
            }
        }
        
        return $models;
    }
    
    /**
     * 在处理完模型列表后调用此方法。
     * 默认实现了触发 [[EVENT_AFTER_PROCESS_MODELS]] 事件。
     *
     * @param object $object 对像实例。
     */
    public function afterProcessModels($object)
    {
        $event = Yii::createObject([
            'class' => ActionEvent::className(),
            'object' => $object,
        ]);
    
        $this->trigger(self::EVENT_AFTER_PROCESS_MODELS, $event);
    }
}
