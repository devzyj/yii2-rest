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
     * 3. 当设置了 [[$checkModelAccess]] 时，调用该回调方法检查模型权限；
     * 4. 调用 [[afterPrepareModel()]]，触发 [[EVENT_AFTER_PREPARE_MODEL]] 事件；
     * 5. 调用 [[afterLoadModel()]]，触发 [[EVENT_AFTER_LOAD_MODEL]] 事件；
     * 6. 更新成功时调用 [[afterProcessModel()]]，触发 [[EVENT_AFTER_PROCESS_MODEL]] 事件；
     * 
     * @param string $id 模型的主键。
     * @return \yii\db\ActiveRecordInterface 正在更新的模型。
     * @throws \yii\web\ServerErrorHttpException 更新模型时有错误。
     */
    public function run($id)
    {
        if ($this->checkAccess) {
            call_user_func($this->checkAccess, $this);
        }
        
        $model = $this->prepareModel($id);
        $params = Yii::$app->getRequest()->getBodyParams();
        $model = $this->loadModel($model, $params, $this->scenario);
        if ($this->updateModel($model)) {
            $this->afterProcessModel($model);
        }
        
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
        } else {
            throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
        }
    }
}
