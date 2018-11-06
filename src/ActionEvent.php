<?php
/**
 * @link https://github.com/devzyj/yii2-rest
 * @copyright Copyright (c) 2018 Zhang Yan Jiong
 * @license http://opensource.org/licenses/BSD-3-Clause
 */
namespace devzyj\rest;

/**
 * ActionEvent 表示用于在执行 RESTful API 动作时的事件参数。
 * 
 * @author ZhangYanJiong <zhangyanjiong@163.com>
 * @since 1.0
 */
class ActionEvent extends \yii\base\Event
{
    /**
     * @var boolean 动作是否有效。在 `before` 事件中设置为 `false`，可以阻止后续事件和具体方法的执行。
     */
    public $isValid = true;
    
    /**
     * @var mixed 执行动作时的数据对像。
     */
    public $object;
}
