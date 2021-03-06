<?php
/**
 * @link https://github.com/devzyj/yii2-rest
 * @copyright Copyright (c) 2018 Zhang Yan Jiong
 * @license http://opensource.org/licenses/BSD-3-Clause
 */
namespace devzyj\rest;

use Yii;

/**
 * BatchViewAction 实现了返回关于多个模型详细信息的 API 端点。
 * 
 * For example:
 * 
 * ```
 * // 假如用户 `11` 不存在，或者没有权限。
 * $ GET /users/10;11;12
 * 
 * HTTP/1.1 200 OK
 * ...
 * Content-Type: application/json; charset=UTF-8
 * 
 * {
 *     "10": {"id" => 10, "username": "example10", "email": "user10@example.com"},
 *     "12": {"id" => 12, "username": "example12", "email": "user12@example.com"}
 * }
 * ```
 * 
 * @author ZhangYanJiong <zhangyanjiong@163.com>
 * @since 1.0
 */
class BatchViewAction extends BatchAction
{
    /**
     * 显示多个模型。
     * 
     * 该方法依次执行以下步骤：
     * 1. 当设置了 [[$checkActionAccess]] 时，调用该回调方法检查动作权限；
     * 2. 调用 [[findModels()]]，查找数据模型列表；
     * 3. 循环中调用 [[afterPrepareModel()]]，触发 [[EVENT_AFTER_PREPARE_MODEL]] 事件；
     * 4. 当设置了 [[$checkModelAccess]] 时，调用该回调方法检查模型权限，并且过滤掉没有权限的模型；
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
        $ids = $this->convertRequestIds($ids, $this->idsSeparator);
        
        // 去除重复的ID。
        $ids = array_unique($ids);
        
        // 检查允许执行批量操作的个数。
        $this->checkAllowedCount($ids);
        
        // 准备模型列表。
        $models = $this->prepareModels($ids);
        
        // 确认并返回有权限的模型列表。
        $models = $this->ensureModelsAccess($models);
        
        // 对模型列表使用主键索引。
        $models->indexByPrimaryKey($this->idSeparator);
        
        // 返回批量处理的结果。
        return Yii::createObject([
            'class' => BatchResult::className(),
            'data' => $models->data,
        ]);
    }
}
