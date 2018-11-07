<?php
/**
 * @link https://github.com/devzyj/yii2-rest
 * @copyright Copyright (c) 2018 Zhang Yan Jiong
 * @license http://opensource.org/licenses/BSD-3-Clause
 */
namespace devzyj\rest;

use Yii;

/**
 * Serializer 将资源对象和集合转换为数组形式。
 * 
 * @author ZhangYanJiong <zhangyanjiong@163.com>
 * @since 1.0
 */
class Serializer extends \yii\rest\Serializer
{
    /**
     * {@inheritdoc}
     */
    public function serialize($data)
    {
        // 序列化批量动作的结果。
        if ($data instanceof BatchResult) {
            return $this->serializeBatchResult($data);
        }
        
        // 执行父类序列化方法。
        return parent::serialize($data);
    }
    
    /**
     * 序列化批量动作的结果。
     * 
     * @param BatchResult $batchResult
     * @return array
     */
    protected function serializeBatchResult($batchResult)
    {
        $result = [];
        if ($batchResult->isAfterProcessModels()) {
            foreach ($batchResult as $key => $value) {
                $value['data'] = $this->serialize($value['data']);
                $result[$key] = $value;
            }
        } else {
            foreach ($batchResult as $key => $value) {
                $result[$key] = $this->serialize($value);
            }
        }

        // 批量动作的响应状态始终为 `200`。
        if (!$this->response->getIsOk()) {
            $this->response->setStatusCode(200);
        }
        
        // 返回转换后的数据。
        return $result;
    }
}
