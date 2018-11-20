<?php
/**
 * @link https://github.com/devzyj/yii2-rest
 * @copyright Copyright (c) 2018 Zhang Yan Jiong
 * @license http://opensource.org/licenses/BSD-3-Clause
 */
namespace devzyj\rest\behaviors;

use Yii;

/**
 * SuppressResponseCodeBehavior 实现了根据查询参数 [[$suppressResponseCodeParam]]，判断是否始终使用 `200` 作为 HTTP 状态，
 * 并将实际的 HTTP 状态码包含在响应内容中。
 * 
 * For example:
 * 
 * ```php
 * // config.php
 * return [
 *     'components' => [
 *         'response' => [
 *             'as suppressResponseCodeBehavior' => [
 *                 'class' => 'devzyj\rest\behaviors\SuppressResponseCodeBehavior',
 *                 //'suppressResponseCodeParam' => 'suppress_response_code',
 *             ]
 *         ]
 *     ]
 * ];
 * 
 * HTTP/1.1 200 OK
 * ...
 * Content-Type: application/json; charset=UTF-8
 * 
 * {
 *     "success": false,
 *     "data": {
 *         "name": "Not Found Exception",
 *         "message": "The requested resource was not found.",
 *         "code": 0,
 *         "status": 404
 *     }
 * }
 * ```
 * 
 * @link https://github.com/yiisoft/yii-rest/blob/master/docs/guide/error-handling.md
 * @author ZhangYanJiong <zhangyanjiong@163.com>
 * @since 1.0
 */
class SuppressResponseCodeBehavior extends \yii\base\Behavior
{
    /**
     * @var string 查询中的参数名称，表示是否在响应时禁用 HTTP 状态码。
     */
    public $suppressResponseCodeParam = 'suppress_response_code';

    /**
     * @var \yii\web\Request 当前的请求。如果没有设置，将使用 `Yii::$app->getRequest()`。
     */
    public $request;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        if ($this->request === null) {
            $this->request = Yii::$app->getRequest();
        }
    
        parent::init();
    }
    
    /**
     * {@inheritdoc}
     */
    public function events()
    {
        return [
            \yii\web\Response::EVENT_BEFORE_SEND => 'beforeSend',
        ];
    }
    
    /**
     * @param \yii\base\Event $event
     * @see \yii\web\Response::send()
     */
    public function beforeSend($event)
    {
        /* @var $response \yii\web\Response */
        $response = $event->sender;
        if ($response->data !== null && $this->request->get($this->suppressResponseCodeParam)) {
            $response->data = [
                'success' => $response->isSuccessful,
                'data' => $response->data,
            ];
            
            $response->statusCode = 200;
        }
    }
}