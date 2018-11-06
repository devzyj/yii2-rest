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
     * 3. 当设置了 [[$checkModelAccess]] 时，调用该回调方法检查模型权限；
     * 4. 调用 [[afterPrepareModel()]]，触发 [[EVENT_AFTER_PREPARE_MODEL]] 事件；
     * 5. 删除成功时调用 [[afterProcessModel()]]，触发 [[EVENT_AFTER_PROCESS_MODEL]] 事件；
     * 
     * @param string $id 模型的主键。
     * @throws \yii\web\ServerErrorHttpException 删除模型时有错误。
     */
    public function run($id)
    {
        if ($this->checkAccess) {
            call_user_func($this->checkAccess, $this);
        }

        $model = $this->prepareModel($id);
        $this->deleteModel($model);
        Yii::$app->getResponse()->setStatusCode(204);

        $this->afterProcessModel($model);
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
