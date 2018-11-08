<?php
/**
 * @link https://github.com/devzyj/yii2-rest
 * @copyright Copyright (c) 2018 Zhang Yan Jiong
 * @license http://opensource.org/licenses/BSD-3-Clause
 */
namespace devzyj\rest;

use Yii;
use yii\base\Model;
use yii\web\ServerErrorHttpException;

/**
 * UpdateAction 实现了用于更新模型的 API 端点。
 * 
 * @author ZhangYanJiong <zhangyanjiong@163.com>
 * @since 1.0
 */
class UpdateAction extends Action
{
    /**
     * @var string 在模型被验证和更新之前，要分配给它的场景。
     */
    public $scenario = Model::SCENARIO_DEFAULT;

    /**
     * 更新现有模型。
     * 
     * 该方法依次执行以下步骤：
     * 1. 当设置了 [[$checkAccess]] 时，调用该回调方法检查动作权限；
     * 2. 调用 [[findModel()]]，查找数据模型；
     * 3. 调用 [[afterPrepareModel()]]，触发 [[EVENT_AFTER_PREPARE_MODEL]] 事件；
     * 4. 当设置了 [[$checkModelAccess]] 时，调用该回调方法检查模型权限；
     * 5. 调用 [[afterLoadModel()]]，触发 [[EVENT_AFTER_LOAD_MODEL]] 事件；
     * 6. 调用 [[beforeProcessModel()]]，触发 [[EVENT_BEFORE_PROCESS_MODEL]] 事件，如果方法返回 `false`，则跳过后续的处理；
     * 7. 调用 [[updateModel()]]，更新模型；
     * 8. 更新成功时调用 [[afterProcessModel()]]，触发 [[EVENT_AFTER_PROCESS_MODEL]] 事件；
     * 
     * @param string $id 模型的主键。
     * @return \yii\db\ActiveRecordInterface 正在更新的模型。
     * @throws \yii\web\ServerErrorHttpException 更新模型时有错误，或者在 [[beforeProcessModel()]] 返回 `false` 时 `$model` 中没有指定错误内容。
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

        // 获取请求参数。
        $params = $this->request->getBodyParams();
        
        // 设置场景。
        $model->setScenario($this->scenario);
        
        // 加载数据。
        $model = $this->loadModel($model, $params);
        
        // 处理并且返回结果。
        return $this->processModel($model);
    }
    
    /**
     * 处理模型。
     * 
     * @param \yii\db\BaseActiveRecord $model 需要更新的模型。
     * @return \yii\db\BaseActiveRecord 处理后的模型。
     * @throws \yii\web\ServerErrorHttpException 如果在 [[beforeProcessModel()]] 返回 `false` 时 `$model` 中没有指定错误内容。
     */
    protected function processModel($model)
    {
        // 调用更新模型前的方法和事件。
        if ($this->beforeProcessModel($model)) {
            // 更新模型。
            if ($this->updateModel($model)) {
                // 调用更新成功后的方法和事件。
                $this->afterProcessModel($model);
            }
        } elseif (!$model->hasErrors()) {
            throw new ServerErrorHttpException('Skipped update the object for unknown reason.');
        }
        
        // 返回处理后的模型。
        return $model;
    }
    
    /**
     * 更新模型。
     * 
     * @param \yii\db\BaseActiveRecord $model 需要更新的模型。
     * @return boolean 更新是否成功。
     * @throws \yii\web\ServerErrorHttpException 更新模型时有错误。
     */
    protected function updateModel($model)
    {
        if ($model->save()) {
            return true;
        } elseif ($model->hasErrors()) {
            return false;
        }
        
        throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
    }
}
