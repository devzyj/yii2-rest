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
 * BatchCreateAction 实现了从给定的数据创建多个新模型的 API 端点。
 * 
 * @author ZhangYanJiong <zhangyanjiong@163.com>
 * @since 1.0
 */
class BatchCreateAction extends BatchAction
{
    /**
     * @var string 在新模型被验证和保存之前，要分配给它的场景。
     */
    public $scenario = Model::SCENARIO_DEFAULT;
    
    /**
     * 创建多个新模型。
     * 
     * 该方法依次执行以下步骤：
     * 1. 当设置了 [[$checkAccess]] 时，调用该回调方法检查动作权限；
     * 2. 循环中调用 [[afterLoadModel()]]，触发 [[EVENT_AFTER_LOAD_MODEL]] 事件；
     * 3. 循环中调用 [[beforeProcessModel()]]，触发 [[EVENT_BEFORE_PROCESS_MODEL]] 事件，如果方法返回 `false`，则跳过后续的处理；
     * 4. 循环中调用 [[createModel()]]，创建模型；
     * 5. 循环中创建成功时调用 [[afterProcessModel()]]，触发 [[EVENT_AFTER_PROCESS_MODEL]] 事件；
     * 6. 调用 [[afterProcessModels()]]，触发 [[EVENT_AFTER_PROCESS_MODELS]] 事件；
     * 
     * @return BatchResult 批量处理的结果集。
     */
    public function run()
    {
        // 检查动作权限。
        if ($this->checkAccess) {
            call_user_func($this->checkAccess, $this);
        }

        // 获取请求参数。
        $params = [];
        foreach ($this->request->getBodyParams() as $key => $value) {
            if (is_array($value)) {
                $params[$key] = $value;
            }
        }

        // 检查允许执行批量操作的个数。
        $this->checkAllowCount($params);
        
        // 批量处理结果。
        /* @var $result BatchResult */
        $result = Yii::createObject(BatchResult::className());
        
        // 循环处理请求的数据。
        foreach ($params as $key => $data) {
            /* @var $model \yii\db\BaseActiveRecord */
            $model = Yii::createObject($this->modelClass);

            // 设置场景。
            $model->setScenario($this->scenario);
            
            // 加载数据。
            $model = $this->loadModel($model, $data);

            // 处理模型。
            if ($this->processModel($model)) {
                // 添加成功结果。
                $result->success($key, $model);
            } else {
                // 添加失败结果。
                $result->error($key, $model);
            }
        }

        // 调用创建多个模型后的方法和事件。
        $this->afterProcessModels($result);
        
        // 返回批量处理结果。
        return $result;
    }
    
    /**
     * 处理模型。
     * 
     * @param \yii\db\BaseActiveRecord $model 需要创建的模型。
     * @return boolean 是否处理成功。
     */
    protected function processModel($model)
    {
        // 调用创建模型前的方法和事件。
        if ($this->beforeProcessModel($model)) {
            // 创建模型。
            if ($this->createModel($model)) {
                // 调用创建成功后的方法和事件。
                $this->afterProcessModel($model);
                
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 创建模型。
     * 
     * @param \yii\db\BaseActiveRecord $model 需要创建的模型。
     * @return boolean 创建是否成功。
     */
    protected function createModel($model)
    {
        if ($model->save()) {
            return true;
        } elseif ($model->hasErrors()) {
            return false;
        }

        $model->addErrors(array_fill_keys($model::primaryKey(), 'Failed to create the object for unknown reason.'));
        return false;
    }
}
