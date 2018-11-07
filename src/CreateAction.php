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
     * 创建一个新模型。
     * 
     * 该方法依次执行以下步骤：
     * 1. 当设置了 [[$checkAccess]] 时，调用该回调方法检查动作权限；
     * 2. 调用 [[afterLoadModel()]]，触发 [[EVENT_AFTER_LOAD_MODEL]] 事件；
     * 3. 调用 [[createModel()]]，创建模型；
     * 4. 创建成功时调用 [[afterProcessModel()]]，触发 [[EVENT_AFTER_PROCESS_MODEL]] 事件；
     * 
     * @return \yii\db\ActiveRecordInterface 新创建的模型。
     * @throws \yii\web\ServerErrorHttpException 在创建模型时出现错误。
     */
    public function run()
    {
        // 检查动作权限。
        if ($this->checkAccess) {
            call_user_func($this->checkAccess, $this);
        }
        
        /* @var $model \yii\db\BaseActiveRecord */
        $model = Yii::createObject($this->modelClass);
        
        // 获取请求参数。
        $params = $this->request->getBodyParams();
        
        // 设置场景。
        $model->setScenario($this->scenario);
        
        // 加载数据。
        $model = $this->loadModel($model, $params);
        
        // 创建模型。
        if ($this->createModel($model)) {
            // 创建成功。
            // 设置响应码。
            $this->response->setStatusCode(201);
            
            // 设置响应头。
            if ($this->viewAction !== false) {
                $id = implode(',', array_values($model->getPrimaryKey(true)));
                $this->response->getHeaders()->set('Location', Url::toRoute([$this->viewAction, 'id' => $id], true));
            }
            
            // 调用创建成功后的方法和事件。
            $this->afterProcessModel($model);
        }
        
        // 返回模型。
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
        } else {
            throw new ServerErrorHttpException('Failed to create the object for unknown reason.');
        }
    }
}
