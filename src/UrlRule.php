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
        '{ids}' => '<ids:\\d[\\d,]*;[\\d,;]*>',
        '{batch}' => 'batch',
    ];

    /**
     * {@inheritdoc}
     */
    public $patterns = [
        'PUT,PATCH {batch}' => 'batch-update',
        'DELETE {ids}' => 'batch-delete',
        'GET,HEAD {ids}' => 'batch-view',
        'POST {batch}' => 'batch-create',
        
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
    
    /**
     * @var array 额外的令牌列表。键是令牌名称，值是相应的替换。额外的令牌将优先于 [[$tokens]]。
     */
    public $extraTokens = [];

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        if ($this->extraTokens) {
            $this->tokens = $this->extraTokens + $this->tokens;
        }
        
        parent::init();
    }
}
