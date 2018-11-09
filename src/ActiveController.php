<?php
/**
 * @link https://github.com/devzyj/yii2-rest
 * @copyright Copyright (c) 2018 Zhang Yan Jiong
 * @license http://opensource.org/licenses/BSD-3-Clause
 */
namespace devzyj\rest;

use yii\helpers\ArrayHelper;

/**
 * ActiveController 实现了一组公共操作，用于支持对 ActiveRecord 的 RESTful API 访问。
 * 
 * @author ZhangYanJiong <zhangyanjiong@163.com>
 * @since 1.0
 */
class ActiveController extends \yii\rest\ActiveController
{
    /**
     * {@inheritdoc}
     */
    public $serializer = 'devzyj\rest\Serializer';

    /**
     * @var string 查询数据的模型类名。如果为不设置，则使用 [[$modelClass]]。
     */
    public $searchModelClass;
    
    /**
     * @var string 模型不存在时的错误信息。
     */
    public $notFoundMessage;
    
    /**
     * @var integer 允许批量执行的资源个数。
     */
    public $allowedCount;

    /**
     * @var string 批量操作请求资源过多的错误信息。
     */
    public $manyResourcesMessage;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        // 执行父类程序。
        parent::init();
        
        // 查询数据的模型类名。
        if ($this->searchModelClass === null) {
            $this->searchModelClass = $this->modelClass;
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return ArrayHelper::merge(parent::actions(), [
            'index' => [
                'class' => 'devzyj\rest\IndexAction',
                'modelClass' => $this->searchModelClass,
            ],
            'view' => [
                'class' => 'devzyj\rest\ViewAction',
                'checkModelAccess' => [$this, 'checkModelAccess'],
                'notFoundMessage' => $this->notFoundMessage,
            ],
            'create' => [
                'class' => 'devzyj\rest\CreateAction',
            ],
            'update' => [
                'class' => 'devzyj\rest\UpdateAction',
                'checkModelAccess' => [$this, 'checkModelAccess'],
                'notFoundMessage' => $this->notFoundMessage,
            ],
            'delete' => [
                'class' => 'devzyj\rest\DeleteAction',
                'checkModelAccess' => [$this, 'checkModelAccess'],
                'notFoundMessage' => $this->notFoundMessage,
            ],
            'create-validate' => [
                'class' => 'devzyj\rest\CreateValidateAction',
                'modelClass' => $this->modelClass,
                'checkActionAccess' => [$this, 'checkActionAccess'],
                'scenario' => $this->createScenario,
            ],
            'update-validate' => [
                'class' => 'devzyj\rest\UpdateValidateAction',
                'modelClass' => $this->modelClass,
                'checkActionAccess' => [$this, 'checkActionAccess'],
                'scenario' => $this->updateScenario,
                'checkModelAccess' => [$this, 'checkModelAccess'],
                'notFoundMessage' => $this->notFoundMessage,
            ],
            'batch-view' => [
                'class' => 'devzyj\rest\BatchViewAction',
                'modelClass' => $this->modelClass,
                'checkActionAccess' => [$this, 'checkActionAccess'],
                'checkModelAccess' => [$this, 'checkModelAccess'],
                'notFoundMessage' => $this->notFoundMessage,
                'allowedCount' => $this->allowedCount,
                'manyResourcesMessage' => $this->manyResourcesMessage,
            ],
            'batch-create' => [
                'class' => 'devzyj\rest\BatchCreateAction',
                'modelClass' => $this->modelClass,
                'checkActionAccess' => [$this, 'checkActionAccess'],
                'scenario' => $this->createScenario,
                'allowedCount' => $this->allowedCount,
                'manyResourcesMessage' => $this->manyResourcesMessage,
            ],
            'batch-update' => [
                'class' => 'devzyj\rest\BatchUpdateAction',
                'modelClass' => $this->modelClass,
                'checkActionAccess' => [$this, 'checkActionAccess'],
                'scenario' => $this->updateScenario,
                'checkModelAccess' => [$this, 'checkModelAccess'],
                'notFoundMessage' => $this->notFoundMessage,
                'allowedCount' => $this->allowedCount,
                'manyResourcesMessage' => $this->manyResourcesMessage,
            ],
            'batch-delete' => [
                'class' => 'devzyj\rest\BatchDeleteAction',
                'modelClass' => $this->modelClass,
                'checkActionAccess' => [$this, 'checkActionAccess'],
                'checkModelAccess' => [$this, 'checkModelAccess'],
                'notFoundMessage' => $this->notFoundMessage,
                'allowedCount' => $this->allowedCount,
                'manyResourcesMessage' => $this->manyResourcesMessage,
            ],
        ]);
    }
    
    /**
     * {@inheritdoc}
     */
    protected function verbs()
    {
        return ArrayHelper::merge(parent::verbs(), [
            'create-validate' => ['POST'],
            'update-validate' => ['PUT', 'PATCH'],
            'bartch-view' => ['GET'],
            'bartch-create' => ['POST'],
            'bartch-update' => ['PUT', 'PATCH'],
            'bartch-delete' => ['DELETE'],
        ]);
    }

    /**
     * {@inheritdoc}
     * 
     * @deprecated 使用 [[checkActionAccess()]] 和 [[checkModelAccess()]] 检查权限。
     */
    public function checkAccess($action, $model = null, $params = [])
    {
        parent::checkAccess($action, $model, $params);
    }
    
    /**
     * 检查用户是否有执行当前动作的权限。
     * 
     * 该方法应该被覆盖，以检查当前用户是否有权限运行指定的操作。
     * 如果用户没有访问权限，应该抛出 [[ForbiddenHttpException]]。
     * 
     * @param Action $action 要执行的操作。
     * @param array $params 附加参数。
     * @throws \yii\web\ForbiddenHttpException 没有访问权限。
     */
    public function checkActionAccess($action, $params = [])
    {}
    
    /**
     * 检查用户是否有执行数据模型的权限。
     * 
     * 该方法应该被覆盖，以检查当前用户是否有权限运行指定的数据模型。
     * 如果用户没有访问权限，应该抛出 [[ForbiddenHttpException]]。
     * 
     * @param object $model 要访问的模型。
     * @param Action $action 要执行的操作。
     * @param array $params 附加参数。
     * @throws \yii\web\ForbiddenHttpException 没有访问权限。
     */
    public function checkModelAccess($model, $action, $params = [])
    {}
}
