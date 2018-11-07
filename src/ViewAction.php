<?php
/**
 * @link https://github.com/devzyj/yii2-rest
 * @copyright Copyright (c) 2018 Zhang Yan Jiong
 * @license http://opensource.org/licenses/BSD-3-Clause
 */
namespace devzyj\rest;

/**
 * ViewAction 实现了返回关于模型详细信息的 API 端点。
 * 
 * @author ZhangYanJiong <zhangyanjiong@163.com>
 * @since 1.0
 */
class ViewAction extends Action
{
    /**
     * 显示一个模型。
     * 
     * 该方法依次执行以下步骤：
     * 1. 当设置了 [[$checkAccess]] 时，调用该回调方法检查动作权限；
     * 2. 调用 [[findModel()]]，查找数据模型；
     * 3. 调用 [[afterPrepareModel()]]，触发 [[EVENT_AFTER_PREPARE_MODEL]] 事件；
     * 4. 当设置了 [[$checkModelAccess]] 时，调用该回调方法检查模型权限；
     * 
     * @param string $id 模型的主键。
     * @return \yii\db\ActiveRecordInterface 显示的模型。
     */
    public function run($id)
    {
        // 检查动作权限。
        if ($this->checkAccess) {
            call_user_func($this->checkAccess, $this);
        }

        // 准备并且返回模型。
        return $this->prepareModel($id);
    }
}
