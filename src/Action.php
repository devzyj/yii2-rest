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
     * @event ActionEvent 在准备完数据源后触发的事件。
     */
    const EVENT_AFTER_PREPARE_DATA_PROVIDER = 'afterPrepareDataProvider';
    
    /**
     * @event ActionEvent 在准备完模型后触发的事件。
     */
    const EVENT_AFTER_PREPARE_MODEL = 'afterPrepareModel';

    /**
     * @event ActionEvent 在模型加载数据前触发的事件。
     * 设置 [[ActionEvent::$isValid]] 为 `false`，可以阻止模型加载数据。
     */
    const EVENT_BEFORE_LOAD_MODEL = 'beforeLoadModel';
    
    /**
     * @event ActionEvent 在模型成功加载完数据后触发的事件。
     */
    const EVENT_AFTER_LOAD_MODEL = 'afterLoadModel';

    /**
     * @event ActionEvent 在处理模型前触发的事件。
     * 设置 [[ActionEvent::$isValid]] 为 `false`，可以阻止处理模型。
     */
    const EVENT_BEFORE_PROCESS_MODEL = 'beforeProcessModel';
    
    /**
     * @event ActionEvent 在成功处理完模型后触发的事件。
     */
    const EVENT_AFTER_PROCESS_MODEL = 'afterProcessModel';

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
     * 
     * @deprecated 使用 [[$checkActionAccess]] 和 [[$checkModelAccess]] 检查权限。
     */
    public $checkAccess;
    
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
    public $checkActionAccess;
    
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
     * @var string 模型不存在时的错误信息。支持变量 `{id}`。
     * @see findModel()
     */
    public $notFoundMessage;

    /**
     * @var integer 模型不存在时的错误编码。
     * @see findModel()
     */
    public $notFoundCode;

    /**
     * @var string 复合主键时使用的分隔符。
     */
    public $idSeparator;
    
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
            $this->notFoundMessage = 'Object not found: `{id}`';
        }

        if ($this->notFoundCode === null) {
            $this->notFoundCode = 0;
        }

        if ($this->idSeparator === null) {
            $this->idSeparator = ',';
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
            $values = explode($this->idSeparator, $id);
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
    
        $notFoundMessage = strtr($this->notFoundMessage, ['{id}' => $id]);
        throw new NotFoundHttpException($notFoundMessage, $this->notFoundCode);
    }
    
    /**
     * 根据指定的主键准备数据模型。
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
        if ($model) {
            // 执行在准备完模型后的方法和事件。
            $this->afterPrepareModel($model);
        }
        
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
     * 1. 调用 [[beforeLoadModel()]]，触发 [[EVENT_BEFORE_LOAD_MODEL]] 事件，如果方法返回 `false`，则跳过后续的处理；
     * 2. 调用 [[$model::load()]]，如果方法返回 `false`，则跳过后续的处理；
     * 3. 成功加载数据后调用 [[afterLoadModel()]]，触发 [[EVENT_AFTER_LOAD_MODEL]] 事件；
     * 
     * @param \yii\base\Model $model 需要加载数据的模型。
     * @param array $data 需要加载的数据。
     * @return boolean 加载是否成功。
     */
    public function loadModel($model, $data)
    {
        // 执行模型加载数据前的方法和事件。
        if ($this->beforeLoadModel($data)) {
            // 加载数据。
            if ($model->load($data, '')) {
                // 执行模型成功加载完数据后的方法和事件。
                $this->afterLoadModel($model);
                
                return true;
            }
        }
        
        return false;
    }

    /**
     * 在模型加载数据前调用此方法。
     * 默认实现了触发 [[EVENT_BEFORE_LOAD_MODEL]] 事件。
     * 
     * 在事件中设置 [[ActionEvent::$isValid]] 为 `false`，可以阻止模型加载数据。
     *
     * @param array $data 将要加载的数据。
     * @return boolean 动作是否有效。
     */
    public function beforeLoadModel(&$data)
    {
        $event = Yii::createObject([
            'class' => ActionEvent::className(),
            'object' => $data,
        ]);
    
        $this->trigger(self::EVENT_BEFORE_LOAD_MODEL, $event);
        $data = $event->object;
        
        return $event->isValid;
    }

    /**
     * 在模型成功加载完数据后调用此方法。
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
     * 在处理模型前调用此方法。
     * 默认实现了触发 [[EVENT_BEFORE_PROCESS_MODEL]] 事件。
     * 
     * 在事件中设置 [[ActionEvent::$isValid]] 为 `false`，可以阻止处理模型。
     *
     * @param object $object 对像实例。
     * @return boolean 动作是否有效。
     */
    public function beforeProcessModel($object)
    {
        $event = Yii::createObject([
            'class' => ActionEvent::className(),
            'object' => $object,
        ]);
    
        $this->trigger(self::EVENT_BEFORE_PROCESS_MODEL, $event);
        return $event->isValid;
    }
    
    /**
     * 在成功处理完模型后调用此方法。
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
