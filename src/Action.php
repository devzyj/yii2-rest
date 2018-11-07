<?php
/**
 * @link https://github.com/devzyj/yii2-rest
 * @copyright Copyright (c) 2018 Zhang Yan Jiong
 * @license http://opensource.org/licenses/BSD-3-Clause
 */
namespace devzyj\rest;

use Yii;
use yii\web\NotFoundHttpException;

/**
 * Action 是实现 RESTful API 的 Action 类的基类。
 * 
 * @author ZhangYanJiong <zhangyanjiong@163.com>
 * @since 1.0
 */
class Action extends \yii\rest\Action
{
    /**
     * @event ActionEvent 在准备完模型后触发的事件。
     */
    const EVENT_AFTER_PREPARE_MODEL = 'afterPrepareModel';

    /**
     * @event ActionEvent 在模型加载完数据后触发的事件。
     */
    const EVENT_AFTER_LOAD_MODEL = 'afterLoadModel';

    /**
     * @event ActionEvent 在处理完模型后触发的事件。
     */
    const EVENT_AFTER_PROCESS_MODEL = 'afterProcessModel';

    /**
     * @event ActionEvent 在准备完数据源后触发的事件。
     */
    const EVENT_AFTER_PREPARE_DATA_PROVIDER = 'afterPrepareDataProvider';
    
    /**
     * @var callable 检查动作权限的回调方法。
     * 回调方法没有返回值，如果没有权限，则抛出一个异常。
     * 
     * ```php
     * function ($action, $params = []) {
     *     // $action 正在执行的动作。
     *     // $params 额外的参数。
     * }
     * ```
     */
    public $checkAccess;
    
    /**
     * @var callable 检查模型权限的回调方法。
     * 回调方法没有返回值，如果没有权限，则抛出一个异常。
     *
     * ```php
     * function ($model, $action, $params = []) {
     *     // $model 需要检查的模型。
     *     // $action 正在执行的动作。
     *     // $params 额外的参数。
     * }
     * ```
     */
    public $checkModelAccess;
    
    /**
     * @var string 模型不存在时的错误信息，支持变量 `{id}`。
     * @see findModel()
     */
    public $notFoundMessage;
    
    /**
     * @var \yii\web\Request 当前的请求。如果没有设置，将使用 `Yii::$app->getRequest()`。
     */
    public $request;
    
    /**
     * @var \yii\web\Response 要发送的响应。如果没有设置，将使用 `Yii::$app->getResponse()`。
     */
    public $response;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        if ($this->request === null) {
            $this->request = Yii::$app->getRequest();
        }
        
        if ($this->response === null) {
            $this->response = Yii::$app->getResponse();
        }
        
        if ($this->notFoundMessage === null) {
            $this->notFoundMessage = 'Object not found: {id}';
        }
    
        parent::init();
    }

    /**
     * {@inheritdoc}
     * 
     * @return \yii\db\ActiveRecordInterface 查找到的数据模型。
     */
    public function findModel($id)
    {
        if ($this->findModel !== null) {
            return call_user_func($this->findModel, $id, $this);
        }
    
        /* @var $modelClass \yii\db\ActiveRecordInterface */
        $modelClass = $this->modelClass;
        $keys = $modelClass::primaryKey();
        if (count($keys) > 1) {
            // composite primary key.
            $values = explode(',', $id);
            if (count($keys) === count($values)) {
                $model = $modelClass::findOne(array_combine($keys, $values));
            }
        } elseif ($id !== null) {
            // single primary key.
            $model = $modelClass::findOne($id);
        }
    
        if (isset($model)) {
            return $model;
        }
    
        throw new NotFoundHttpException(strtr($this->notFoundMessage, [
            '{id}' => $id
        ]));
    }
    
    /**
     * 根据给定的主键准备数据模型。
     * 
     * 该方法依次执行以下步骤：
     * 1. 调用 [[findModel()]]，查找数据模型；
     * 2. 调用 [[afterPrepareModel()]]，触发 [[EVENT_AFTER_PREPARE_MODEL]] 事件；
     * 
     * @param string $id 模型的ID。
     * @return \yii\db\ActiveRecordInterface 查找到的数据模型。
     */
    public function prepareModel($id)
    {
        // 根据给定的主键查询数据模型。
        $model = $this->findModel($id);
        
        // 执行在准备完模型后的方法和事件。
        $this->afterPrepareModel($model);

        // 返回模型。
        return $model;
    }
    
    /**
     * 在准备完模型后调用此方法。
     * 默认实现了触发 [[EVENT_AFTER_PREPARE_MODEL]] 事件。
     * 
     * @param object $object 对像实例。
     */
    public function afterPrepareModel($object)
    {
        $event = Yii::createObject([
            'class' => ActionEvent::className(),
            'object' => $object,
        ]);
        
        $this->trigger(self::EVENT_AFTER_PREPARE_MODEL, $event);
    }
    
    /**
     * 为模型加载数据。
     * 
     * 该方法依次执行以下步骤：
     * 1. 调用 [[$model::load()]]；
     * 2. 调用 [[afterLoadModel()]]，触发 [[EVENT_AFTER_LOAD_MODEL]] 事件；
     * 
     * @param \yii\base\Model $model 需要加载数据的模型。
     * @param array $data 需要加载的数据。
     * @return \yii\base\Model 加载完数据后的模型。
     */
    public function loadModel($model, $data)
    {
        // 加载数据。
        $model->load($data, '');
        
        // 执行模型加载完数据后的方法和事件。
        $this->afterLoadModel($model);
        
        // 返回模型。
        return $model;
    }
    
    /**
     * 在模型加载完数据后调用此方法。
     * 默认实现了触发 [[EVENT_AFTER_LOAD_MODEL]] 事件。
     *
     * @param object $object 对像实例。
     */
    public function afterLoadModel($object)
    {
        $event = Yii::createObject([
            'class' => ActionEvent::className(),
            'object' => $object,
        ]);
    
        $this->trigger(self::EVENT_AFTER_LOAD_MODEL, $event);
    }
    
    /**
     * 在处理完模型后调用此方法。
     * 默认实现了触发 [[EVENT_AFTER_PROCESS_MODEL]] 事件。
     *
     * @param object $object 对像实例。
     */
    public function afterProcessModel($object)
    {
        $event = Yii::createObject([
            'class' => ActionEvent::className(),
            'object' => $object,
        ]);
    
        $this->trigger(self::EVENT_AFTER_PROCESS_MODEL, $event);
    }
    
    /**
     * 在准备完数据源后调用此方法。
     * 默认实现了触发 [[EVENT_AFTER_PREPARE_DATA_PROVIDER]] 事件。
     *
     * @param \yii\data\ActiveDataProvider $dataProvider 数据源。
     */
    public function afterPrepareDataProvider($dataProvider)
    {
        $event = Yii::createObject([
            'class' => ActionEvent::className(),
            'object' => $dataProvider,
        ]);
    
        $this->trigger(self::EVENT_AFTER_PREPARE_DATA_PROVIDER, $event);
    }
}
