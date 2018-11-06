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
 * UpdateValidateAction 实现了用于更新模型时，验证数据的 API 端点。
 * 
 * @author ZhangYanJiong <zhangyanjiong@163.com>
 * @since 1.0
 */
class ValidateUpdateAction extends Action
{
    /**
     * @var string 在模型被验证之前，要分配给它的场景。
     */
    public $scenario = Model::SCENARIO_DEFAULT;

    /**
     * 验证一个已存在的模型。
     * 
     * 该方法依次执行以下步骤：
     * 1. 当设置了 [[$checkAccess]] 时，调用该回调方法检查动作权限；
     * 2. 调用 [[findModel()]]，查找数据模型；
     * 3. 当设置了 [[$checkModelAccess]] 时，调用该回调方法检查模型权限；
     * 4. 调用 [[afterPrepareModel()]]，触发 [[EVENT_AFTER_PREPARE_MODEL]] 事件；
     * 5. 调用 [[afterLoadModel()]]，触发 [[EVENT_AFTER_LOAD_MODEL]] 事件；
     * 
     * @param string $id 模型主键。
     * @return \yii\db\ActiveRecordInterface|null 验证时有错误的模型。没有错误时返回 `null`。
     */
    public function run($id)
    {
        if ($this->checkAccess) {
            call_user_func($this->checkAccess, $this);
        }

        $model = $this->prepareModel($id);
        $params = Yii::$app->getRequest()->getBodyParams();
        $model = $this->loadModel($model, $params, $this->scenario);
        if ($this->validateModel($model)) {
            Yii::$app->getResponse()->setStatusCode(204);
            return;
        }
        
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
