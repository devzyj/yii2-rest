<?php
/**
 * @link https://github.com/devzyj/yii2-rest
 * @copyright Copyright (c) 2018 Zhang Yan Jiong
 * @license http://opensource.org/licenses/BSD-3-Clause
 */
namespace devzyj\rest;

use Yii;

/**
 * BatchDeleteAction 实现了用于删除多个模型的 API 端点。
 * 
 * For example:
 * 
 * ```
 * // 假如用户 `11` 不存在，或者没有权限。
 * $ DELETE /users/10;11;12
 * 
 * HTTP/1.1 200 OK
 * ...
 * Content-Type: application/json; charset=UTF-8
 * 
 * {
 *     "10": {
 *         "success": true,
 *         "data": {"id": 10, "username": "example10", "email": "user10@example.com"}
 *     },
 *     "12": {
 *         "success": false,
 *         "data": [
 *             {"field": "id", "message": "Failed to delete the object for unknown reason."}
 *         ]
 *     }
 * }
 * ```
 * 
 * @author ZhangYanJiong <zhangyanjiong@163.com>
 * @since 1.0
 */
class BatchDeleteAction extends BatchAction
{
    /**
     * 删除多个模型。
     * 
     * 该方法依次执行以下步骤：
     * 1. 当设置了 [[$checkActionAccess]] 时，调用该回调方法检查动作权限；
     * 2. 调用 [[findModels()]]，查找数据模型列表；
     * 3. 循环中调用 [[afterPrepareModel()]]，触发 [[EVENT_AFTER_PREPARE_MODEL]] 事件；
     * 4. 当设置了 [[$checkModelAccess]] 时，调用该回调方法检查模型权限，并且过滤掉没有权限的模型；
     * 5. 循环中调用 [[beforeProcessModel()]]，触发 [[EVENT_BEFORE_PROCESS_MODEL]] 事件，如果方法返回 `false`，则跳过后续的处理；
     * 6. 循环中调用 [[deleteModel()]]，删除模型；
     * 7. 循环中删除成功时调用 [[afterProcessModel()]]，触发 [[EVENT_AFTER_PROCESS_MODEL]] 事件；
     * 8. 调用 [[afterProcessModels()]]，触发 [[EVENT_AFTER_PROCESS_MODELS]] 事件；
     * 
     * @param string $ids 多个模型的主键。
     * @return BatchResult 批量处理的结果集。
     */
    public function run($ids)
    {
        // 检查动作权限。
        if ($this->checkActionAccess) {
            call_user_func($this->checkActionAccess, $this);
        }

        // 转换请求中的字符串IDs为数组。
        $ids = $this->convertRequestIds($ids);
        
        // 去除重复的ID。
        $ids = array_unique($ids);

        // 检查允许执行批量操作的个数。
        $this->checkAllowedCount($ids);

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
            // 处理模型。
            if ($this->processModel($model)) {
                // 添加成功结果。
                $result->success($key, $model);
            } else {
                // 添加失败结果。
                $result->error($key, $model);
            }
        }

        // 调用删除多个模型后的方法和事件。
        $this->afterProcessModels($result);
        
        // 返回批量处理结果。
        return $result;
    }
    
    /**
     * 处理模型。
     * 
     * @param \yii\db\BaseActiveRecord $model 模型。
     * @return boolean 是否处理成功。
     */
    protected function processModel($model)
    {
        // 调用删除模型前的方法和事件。
        if ($this->beforeProcessModel($model)) {
            // 删除模型。
            if ($this->deleteModel($model)) {
                // 调用删除成功后的方法和事件。
                $this->afterProcessModel($model);
                
                return true;
            }
        } elseif (!$model->hasErrors()) {
            $model->addErrors(array_fill_keys($model::primaryKey(), 'Skipped delete the object for unknown reason.'));
        }
        
        return false;
    }
    
    /**
     * 删除模型。
     * 
     * @param \yii\db\BaseActiveRecord $model 需要删除的模型实例。
     * @return integer|false `integer` 为删除的记录条数，`false` 为删除失败。
     */
    protected function deleteModel($model)
    {
        if ($model->delete() === false) {
            $model->addErrors(array_fill_keys($model::primaryKey(), 'Failed to delete the object for unknown reason.'));
            return false;
        }
        
        return true;
    }
}
