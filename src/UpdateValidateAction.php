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
 * UpdateValidateAction 实现了用于更新模型时，验证数据的 API 端点。
 * 
 * @author ZhangYanJiong <zhangyanjiong@163.com>
 * @since 1.0
 */
class UpdateValidateAction extends Action
{
    /**
     * @var string 在模型被验证之前，要分配给它的场景。
     */
    public $scenario = Model::SCENARIO_DEFAULT;

    /**
     * 验证一个已存在的模型。
     * 
     * 该方法依次执行以下步骤：
     * 1. 当设置了 [[$checkActionAccess]] 时，调用该回调方法检查动作权限；
     * 2. 调用 [[findModel()]]，查找数据模型；
     * 3. 调用 [[afterPrepareModel()]]，触发 [[EVENT_AFTER_PREPARE_MODEL]] 事件；
     * 4. 当设置了 [[$checkModelAccess]] 时，调用该回调方法检查模型权限；
     * 5. 调用 [[beforeLoadModel()]]，触发 [[EVENT_BEFORE_LOAD_MODEL]] 事件，如果方法返回 `false`，则阻止模型加载数据；
     * 6. 加载数据成功后调用 [[afterLoadModel()]]，触发 [[EVENT_AFTER_LOAD_MODEL]] 事件；
     * 7. 调用 [[beforeProcessModel()]]，触发 [[EVENT_BEFORE_PROCESS_MODEL]] 事件，如果方法返回 `false`，则阻止验证模型；
     * 8. 调用 [[validateModel()]]，验证模型；
     * 
     * @param string $id 模型主键。
     * @return \yii\db\ActiveRecordInterface|null 验证时有错误的模型。没有错误时返回 `null`。
     * @throws \yii\web\ServerErrorHttpException 在 [[beforeProcessModel()]] 返回 `false` 时 `$model` 中没有指定错误内容。
     */
    public function run($id)
    {
        // 检查动作权限。
        if ($this->checkActionAccess) {
            call_user_func($this->checkActionAccess, $this);
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
        $this->loadModel($model, $params);
        
        // 处理并且返回结果。
        return $this->processModel($model);
    }
    
    /**
     * 处理模型。
     * 
     * @param \yii\db\BaseActiveRecord $model 需要验证的模型。
     * @return \yii\db\BaseActiveRecord|null 验证时有错误的模型。没有错误时返回 `null`。
     * @throws \yii\web\ServerErrorHttpException 如果在 [[beforeProcessModel()]] 返回 `false` 时 `$model` 中没有指定错误内容。
     */
    protected function processModel($model)
    {
        // 调用验证模型前的方法和事件。
        if ($this->beforeProcessModel($model)) {
            // 验证模型。
            if ($this->validateModel($model)) {
                // 验证成功，设置响应码。
                $this->response->setStatusCode(204);
                
                // 返回空结果。
                return;
            }
        } elseif (!$model->hasErrors()) {
            throw new ServerErrorHttpException('Skipped validate the object for unknown reason.');
        }

        // 返回验证错误的模型。
        return $model;
    }
    
    /**
     * 验证模型。
     * 
     * @param \yii\db\BaseActiveRecord $model 需要验证的模型。
     * @return boolean 验证是否有错误。
     */
    protected function validateModel($model)
    {
        return $model->validate();
    }
}
