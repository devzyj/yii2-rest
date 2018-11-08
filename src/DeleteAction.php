<?php
/**
 * @link https://github.com/devzyj/yii2-rest
 * @copyright Copyright (c) 2018 Zhang Yan Jiong
 * @license http://opensource.org/licenses/BSD-3-Clause
 */
namespace devzyj\rest;

use Yii;
use yii\web\ServerErrorHttpException;

/**
 * DeleteAction 实现了用于删除模型的 API 端点。
 * 
 * @author ZhangYanJiong <zhangyanjiong@163.com>
 * @since 1.0
 */
class DeleteAction extends Action
{
    /**
     * 删除一个模型。
     * 
     * 该方法依次执行以下步骤：
     * 1. 当设置了 [[$checkAccess]] 时，调用该回调方法检查动作权限；
     * 2. 调用 [[findModel()]]，查找数据模型；
     * 3. 调用 [[afterPrepareModel()]]，触发 [[EVENT_AFTER_PREPARE_MODEL]] 事件；
     * 4. 当设置了 [[$checkModelAccess]] 时，调用该回调方法检查模型权限；
     * 5. 调用 [[beforeProcessModel()]]，触发 [[EVENT_BEFORE_PROCESS_MODEL]] 事件，如果方法返回 `false`，则跳过后续的处理；
     * 6. 调用 [[deleteModel()]]，删除模型；
     * 7. 删除成功时调用 [[afterProcessModel()]]，触发 [[EVENT_AFTER_PROCESS_MODEL]] 事件；
     * 
     * @param string $id 模型的主键。
     * @throws \yii\web\ServerErrorHttpException 删除模型时有错误，或者在 [[beforeProcessModel()]] 返回 `false` 时 `$model` 中没有指定错误内容。
     */
    public function run($id)
    {
        // 检查动作权限。
        if ($this->checkAccess) {
            call_user_func($this->checkAccess, $this);
        }

        // 准备模型。
        $model = $this->prepareModel($id);
        
        // 检查模型权限。
        if ($this->checkModelAccess) {
            call_user_func($this->checkModelAccess, $model, $this);
        }
        
        // 处理模型。
        $this->processModel($model);
    }
    
    /**
     * 处理模型。
     * 
     * @param \yii\db\BaseActiveRecord $model 需要删除的模型。
     * @throws \yii\web\ServerErrorHttpException 如果在 [[beforeProcessModel()]] 返回 `false` 时 `$model` 中没有指定错误内容。
     */
    protected function processModel($model)
    {
        // 调用删除模型前的方法和事件。
        if ($this->beforeProcessModel($model)) {
            // 删除模型。
            $this->deleteModel($model);
            
            // 删除成功后设置响应码。
            $this->response->setStatusCode(204);
    
            // 调用删除成功后的方法和事件。
            $this->afterProcessModel($model);
        } elseif (!$model->hasErrors()) {
            throw new ServerErrorHttpException('Skipped delete the object for unknown reason.');
        }
    }
    
    /**
     * 删除模型。
     * 
     * @param \yii\db\ActiveRecordInterface $model 需要删除的模型。
     * @throws \yii\web\ServerErrorHttpException 删除模型时有错误。
     */
    protected function deleteModel($model)
    {
        if ($model->delete() === false) {
            throw new ServerErrorHttpException('Failed to delete the object for unknown reason.');
        }
    }
}
