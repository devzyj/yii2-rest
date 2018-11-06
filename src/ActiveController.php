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
     * @var string 查询数据的模型类名。如果没有设置，则使用 [[$modelClass]]。
     */
    public $searchModelClass;
    
    /**
     * @var string 模型不存在时的异常信息。
     */
    public $notFoundMessage;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
    
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
                'checkAccess' => [$this, 'checkAccess'],
                'scenario' => $this->createScenario,
            ],
            'update-validate' => [
                'class' => 'devzyj\rest\UpdateValidateAction',
                'modelClass' => $this->modelClass,
                'checkAccess' => [$this, 'checkAccess'],
                'scenario' => $this->updateScenario,
                'checkModelAccess' => [$this, 'checkModelAccess'],
                'notFoundMessage' => $this->notFoundMessage,
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
        ]);
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
    public function checkAccess($action, $params = [])
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
