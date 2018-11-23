<?php
/**
 * @link https://github.com/devzyj/yii2-rest
 * @copyright Copyright (c) 2018 Zhang Yan Jiong
 * @license http://opensource.org/licenses/BSD-3-Clause
 */
namespace devzyj\rest;

use Yii;
use yii\base\Model;
use yii\helpers\Url;
use yii\web\ServerErrorHttpException;

/**
 * CreateAction 实现了从给定的数据创建新模型的 API 端点。
 * 
 * @author ZhangYanJiong <zhangyanjiong@163.com>
 * @since 1.0
 */
class CreateAction extends Action
{
    /**
     * @var string 在新模型被验证和保存之前，要分配给它的场景。
     */
    public $scenario = Model::SCENARIO_DEFAULT;
    
    /**
     * @var string|false 视图操作的名称。当模型成功创建时，需要使用此属性创建 URL。`false` 表示不创建 URL。
     */
    public $viewAction = 'view';
    
    /**
     * @var string 创建模型失败时的错误信息。
     */
    public $failedMessage = 'Failed to create the object for unknown reason.';

    /**
     * @var integer 创建模型失败时的错误编码。
     */
    public $failedCode = 0;

    /**
     * @var string 跳过创建模型时的错误信息。
     */
    public $skippedMessage = 'Skipped create the object for unknown reason.';
    
    /**
     * @var integer 跳过创建模型时的错误编码。
     */
    public $skippedCode = 0;

    /**
     * 创建一个新模型。
     * 
     * 该方法依次执行以下步骤：
     * 1. 当设置了 [[$checkActionAccess]] 时，调用该回调方法检查动作权限；
     * 2. 调用 [[beforeLoadModel()]]，触发 [[EVENT_BEFORE_LOAD_MODEL]] 事件，如果方法返回 `false`，则阻止模型加载数据；
     * 3. 加载数据成功后调用 [[afterLoadModel()]]，触发 [[EVENT_AFTER_LOAD_MODEL]] 事件；
     * 4. 调用 [[beforeProcessModel()]]，触发 [[EVENT_BEFORE_PROCESS_MODEL]] 事件，如果方法返回 `false`，则阻止创建模型；
     * 5. 调用 [[createModel()]]，创建模型；
     * 6. 创建成功时调用 [[afterProcessModel()]]，触发 [[EVENT_AFTER_PROCESS_MODEL]] 事件；
     * 
     * @return \yii\db\ActiveRecordInterface 新创建的模型。
     * @throws \yii\web\ServerErrorHttpException 在创建模型时出现错误，或者在 [[beforeProcessModel()]] 返回 `false` 时 `$model` 中没有指定错误内容。
     */
    public function run()
    {
        // 检查动作权限。
        if ($this->checkActionAccess) {
            call_user_func($this->checkActionAccess, $this);
        }
        
        /* @var $model \yii\db\BaseActiveRecord */
        $model = Yii::createObject($this->modelClass);
        
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
     * @param \yii\db\BaseActiveRecord $model 需要创建的模型。
     * @return \yii\db\BaseActiveRecord 处理后的模型。
     * @throws \yii\web\ServerErrorHttpException 如果在 [[beforeProcessModel()]] 返回 `false` 时 `$model` 中没有指定错误内容。
     */
    protected function processModel($model)
    {
        // 调用创建模型前的方法和事件。
        if ($this->beforeProcessModel($model)) {
            // 创建模型。
            if ($this->createModel($model)) {
                // 创建成功。
                // 设置响应码。
                $this->response->setStatusCode(201);
            
                // 设置响应头。
                if ($this->viewAction !== false) {
                    $id = implode($this->idSeparator, array_values($model->getPrimaryKey(true)));
                    $this->response->getHeaders()->set('Location', Url::toRoute([$this->viewAction, 'id' => $id], true));
                }
            
                // 调用创建成功后的方法和事件。
                $this->afterProcessModel($model);
            }
        } elseif (!$model->hasErrors()) {
            throw new ServerErrorHttpException($this->skippedMessage, $this->skippedCode);
        }

        // 返回处理后的模型。
        return $model;
    }
    
    /**
     * 创建模型。
     * 
     * @param \yii\db\BaseActiveRecord $model 需要创建的模型。
     * @return boolean 创建是否成功。
     * @throws \yii\web\ServerErrorHttpException 创建模型时出现错误。
     */
    protected function createModel($model)
    {
        if ($model->save()) {
            return true;
        } elseif ($model->hasErrors()) {
            return false;
        }
        
        throw new ServerErrorHttpException($this->failedMessage, $this->failedCode);
    }
}
