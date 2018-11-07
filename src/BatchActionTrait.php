<?php
/**
 * @link https://github.com/devzyj/yii2-rest
 * @copyright Copyright (c) 2018 Zhang Yan Jiong
 * @license http://opensource.org/licenses/BSD-3-Clause
 */
namespace devzyj\rest;

use Yii;

/**
 * BatchActionTrait 提供了批量动作时的方法。
 * 
 * @author ZhangYanJiong <zhangyanjiong@163.com>
 * @since 1.0
 */
trait BatchActionTrait
{
    /**
     * 转换请求中的字符串IDs为数组。
     *
     * @param string $ids 请求的IDs。
     * @param string $separator 分隔ID的字符。默认为 `;`。
     * @return array 转换后的ID数组。
     */
    public function convertRequestIds($ids, $separator = ';')
    {
        if ($ids !== '') {
            return explode($separator, trim($ids, $separator));
        }
        
        return [];
    }
}
