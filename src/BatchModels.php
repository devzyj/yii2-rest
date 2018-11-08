<?php
/**
 * @link https://github.com/devzyj/yii2-rest
 * @copyright Copyright (c) 2018 Zhang Yan Jiong
 * @license http://opensource.org/licenses/BSD-3-Clause
 */
namespace devzyj\rest;

use Yii;
use yii\base\ArrayAccessTrait;
use yii\helpers\ArrayHelper;

/**
 * BatchModels 是多个模型的聚合类。用于收集需要处理的模型列表。
 * 
 * @author ZhangYanJiong <zhangyanjiong@163.com>
 * @since 1.0
 */
class BatchModels extends \yii\base\BaseObject implements \IteratorAggregate, \ArrayAccess, \Countable
{
    use ArrayAccessTrait;

    /**
     * @var \yii\db\ActiveRecordInterface[]
     */
    public $data = [];
    
    /**
     * 根据主键进行索引。
     * 
     * @param string $separator 复合主键时使用的分隔符。
     */
    public function indexByPrimaryKey($separator = ',')
    {
        $this->data = ArrayHelper::index($this->data, function ($element) use ($separator) {
            /* @var $element \yii\db\ActiveRecordInterface */
            return implode($separator, array_values($element->getPrimaryKey(true)));
        });
    }
}
