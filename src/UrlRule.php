<?php
/**
 * @link https://github.com/devzyj/yii2-rest
 * @copyright Copyright (c) 2018 Zhang Yan Jiong
 * @license http://opensource.org/licenses/BSD-3-Clause
 */
namespace devzyj\rest;

/**
 * UrlRule 提供了简化创建基于 RESTful API 支持的 URL 规则。
 * 
 * @author ZhangYanJiong <zhangyanjiong@163.com>
 * @since 1.0
 */
class UrlRule extends \yii\rest\UrlRule
{
    /**
     * {@inheritdoc}
     */
    public $tokens = [
        '{id}' => '<id:\\d[\\d,]*>',
        '{validate}' => 'validate',
    ];

    /**
     * {@inheritdoc}
     */
    public $patterns = [
        'PUT,PATCH {id}/{validate}' => 'update-validate',
        'POST {validate}' => 'create-validate',
        
        'PUT,PATCH {id}' => 'update',
        'DELETE {id}' => 'delete',
        'GET,HEAD {id}' => 'view',
        'POST' => 'create',
        'GET,HEAD' => 'index',
        '{id}' => 'options',
        '' => 'options',
    ];
}
