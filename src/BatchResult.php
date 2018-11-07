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
 * BatchResult 是批量处理的结果集类。
 * 
 * @author ZhangYanJiong <zhangyanjiong@163.com>
 * @since 1.0
 */
class BatchResult extends \yii\base\BaseObject implements \IteratorAggregate, \ArrayAccess, \Countable
{
    use ArrayAccessTrait;

    /**
     * @var array the data rows.
     */
    public $data = [];
    
    /**
     * 添加成功数据。
     * 
     * @param string $key
     * @param mixed $value
     */
    public function success($key, $value)
    {
        $this->data[$key] = [
            'success' => true,
            'data' => $value
        ];
    }

    /**
     * 添加失败数据。
     *
     * @param string $key
     * @param mixed $value
     */
    public function error($key, $value)
    {
        $this->data[$key] = [
            'success' => false,
            'data' => $value
        ];
    }
    
    /**
     * 根据主键进行索引。
     * 
     * @param string $separator 组合主键时的分隔符。
     */
    public function indexByPrimaryKey($separator = ',')
    {
        if ($this->isAfterProcessModels()) {
            $this->data = ArrayHelper::index($this->data, function ($element) use ($separator) {
                /* @var $model \yii\db\ActiveRecordInterface */
                $model = $element['data'];
                return implode($separator, array_values($model->getPrimaryKey(true)));
            });
        } else {
            $this->data = ArrayHelper::index($this->data, function ($element) use ($separator) {
                /* @var $element \yii\db\ActiveRecordInterface */
                return implode($separator, array_values($element->getPrimaryKey(true)));
            });
        }
    }
    
    /**
     * 检查是否为批量处理模型后的结果格式。
     * 
     * @return boolean
     */
    public function isAfterProcessModels()
    {
        if (is_array($this->data) && $this->data) {
            $row = reset($this->data);
            if (count($row) == 2 && isset($row['success']) && isset($row['data'])) {
                return true;
            }
        }
        
        return false;
    }
}
