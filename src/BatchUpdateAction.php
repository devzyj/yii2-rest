<?php
/**
 * @link https://github.com/devzyj/yii2-rest
 * @copyright Copyright (c) 2018 Zhang Yan Jiong
 * @license http://opensource.org/licenses/BSD-3-Clause
 */
namespace devzyj\rest;

use Yii;
use yii\base\Model;

/**
 * BatchUpdateAction 实现了用于更新多个模型的 API 端点。
 * 
 * @author ZhangYanJiong <zhangyanjiong@163.com>
 * @since 1.0
 */
class BatchUpdateAction extends BatchAction
{
    /**
     * @var string 在模型被验证和更新之前，要分配给它的场景。
     */
    public $scenario = Model::SCENARIO_DEFAULT;

    /**
     * 更新多个现有模型。
     * 
     * 该方法依次执行以下步骤：
     * 1. 当设置了 [[$checkActionAccess]] 时，调用该回调方法检查动作权限；
     * 2. 调用 [[findModels()]]，查找数据模型列表；
     * 3. 调用 [[afterPrepareModel()]]，触发 [[EVENT_AFTER_PREPARE_MODEL]] 事件；
     * 4. 当设置了 [[$checkModelAccess]] 时，调用该回调方法检查模型权限；
     * 5. 调用 [[beforeLoadModel()]]，触发 [[EVENT_BEFORE_LOAD_MODEL]] 事件，如果方法返回 `false`，则阻止模型加载数据；
     * 6. 加载数据成功后调用 [[afterLoadModel()]]，触发 [[EVENT_AFTER_LOAD_MODEL]] 事件；
     * 7. 调用 [[beforeProcessModel()]]，触发 [[EVENT_BEFORE_PROCESS_MODEL]] 事件，如果方法返回 `false`，则阻止更新模型；
     * 8. 调用 [[updateModel()]]，更新模型；
     * 9. 更新成功时调用 [[afterProcessModel()]]，触发 [[EVENT_AFTER_PROCESS_MODEL]] 事件；
     * 10. 调用 [[afterProcessModels()]]，触发 [[EVENT_AFTER_PROCESS_MODELS]] 事件；
     * 
     * 注意：执行步骤 3 以及步骤 5 到 9 会被多次调用。
     * 
     * @return BatchResult 批量处理的结果集。
     */
    public function run()
    {
        // 检查动作权限。
        if ($this->checkActionAccess) {
            call_user_func($this->checkActionAccess, $this);
        }

        // 获取请求参数。
        $params = [];
        foreach ($this->request->getBodyParams() as $key => $value) {
            if (is_array($value)) {
                $params[$key] = $value;
            }
        }

        // 检查允许执行批量操作的个数。
        $this->checkAllowedCount($params);

        // 获取模型的主键值列表。
        $ids = array_keys($params);

        // 准备模型列表。
        $models = $this->prepareModels($ids);

        // 确认并返回有权限的模型列表。
        $models = $this->ensureModelsAccess($models);
        
        // 对模型列表使用主键索引。
        $models->indexByPrimaryKey();

        // 批量处理结果。
        /* @var $result BatchResult */
        $result = Yii::createObject(BatchResult::className());
        
        // 循环处理模型列表。
        foreach ($models as $key => $model) {
            // 设置场景。
            $model->setScenario($this->scenario);
            
            // 加载数据。
            $this->loadModel($model, $params[$key]);
            
            // 处理模型。
            if ($this->processModel($model)) {
                // 添加成功结果。
                $result->success($key, $model);
            } else {
                // 添加失败结果。
                $result->error($key, $model);
            }
        }

        // 调用更新多个模型后的方法和事件。
        $this->afterProcessModels($result);
        
        // 返回批量处理结果。
        return $result;
    }
    
    /**
     * 处理模型。
     * 
     * @param \yii\db\BaseActiveRecord $model 需要更新的模型。
     * @return boolean 是否处理成功。
     */
    protected function processModel($model)
    {
        // 调用更新模型前的方法和事件。
        if ($this->beforeProcessModel($model)) {
            // 更新模型。
            if ($this->updateModel($model)) {
                // 调用更新成功后的方法和事件。
                $this->afterProcessModel($model);
                
                return true;
            }
        } elseif (!$model->hasErrors()) {
            $model->addErrors(array_fill_keys($model::primaryKey(), 'Skipped update the object for unknown reason.'));
        }

        return false;
    }
    
    /**
     * 更新模型。
     * 
     * @param \yii\db\BaseActiveRecord $model 需要更新的模型。
     * @return boolean 更新是否成功。
     */
    protected function updateModel($model)
    {
        if ($model->save()) {
            return true;
        } elseif ($model->hasErrors()) {
            return false;
        }

        $model->addErrors(array_fill_keys($model::primaryKey(), 'Failed to update the object for unknown reason.'));
        return false;
    }
}
