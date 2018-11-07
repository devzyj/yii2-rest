<?php
/**
 * @link https://github.com/devzyj/yii2-rest
 * @copyright Copyright (c) 2018 Zhang Yan Jiong
 * @license http://opensource.org/licenses/BSD-3-Clause
 */
namespace devzyj\rest\tests\unit;

use Yii;
use yii\db\Migration;
use devzyj\rest\tests\models\TestActive;
use devzyj\rest\CreateAction;
use devzyj\rest\DeleteAction;
use devzyj\rest\UpdateAction;
use devzyj\rest\ViewAction;
use devzyj\rest\IndexAction;
use devzyj\rest\CreateValidateAction;
use devzyj\rest\UpdateValidateAction;

/**
 * EventTest class.
 * 
 * @author ZhangYanJiong <zhangyanjiong@163.com>
 * @since 1.0
 */
class EventTest extends TestCase
{
    /**
     * @var \yii\web\Request
     */
    protected $request;
    
    /**
     * @var \yii\web\Response
     */
    protected $response;

    /**
     * {@inheritdoc}
     */
    protected function _before()
    {
        parent::_before();
        
        $this->request = Yii::createObject('yii\web\Request');
        $this->response = Yii::createObject('yii\web\Response');
    }
    
    /**
     * test create action events
     */
    public function testCreateActionEvents()
    {
        $this->request->setBodyParams(['id' => 11, 'name' => 'TestName11', 'title' => 'TestTitle11']);
        
        $action = new CreateAction('create', null, [
            'request' => $this->request,
            'response' => $this->response,
            'modelClass' => TestActive::className(),
            'viewAction' => false,
            'on afterLoadModel' => function ($event) {
                $object = $event->object;
                $this->assertEquals([
                    'id' => 11, 
                    'name' => 'TestName11', 
                    'title' => 'TestTitle11'
                ], $object->attributes);
                
                $object->name .= '-afterLoadModel';
            },
            'on afterProcessModel' => function ($event) {
                $object = $event->object;
                $this->assertEquals([
                    'id' => 11, 
                    'name' => 'TestName11-afterLoadModel', 
                    'title' => 'TestTitle11'
                ], $object->attributes);
                
                $object->name .= '-afterProcessModel';
            },
        ]);
        
        $model = $action->run();
        $this->tester->seeRecord(TestActive::className(), [
            'id' => 11, 
            'name' => 'TestName11-afterLoadModel',
            'title' => 'TestTitle11'
        ]);
        
        $this->assertEquals([
            'id' => 11, 
            'name' => 'TestName11-afterLoadModel-afterProcessModel', 
            'title' => 'TestTitle11'
        ], $model->attributes);
    }
    
    /**
     * test delete action events
     */
    public function testDeleteActionEvents()
    {
        $action = new DeleteAction('delete', null, [
            'request' => $this->request,
            'response' => $this->response,
            'modelClass' => TestActive::className(),
            'on afterPrepareModel' => function ($event) {
                $object = $event->object;
                $this->assertEquals([
                    'id' => 1, 
                    'name' => 'TestName1', 
                    'title' => 'TestTitle1'
                ], $object->attributes);
                
                $object->name .= '-afterPrepareModel';
            },
            'on afterProcessModel' => function ($event) {
                $object = $event->object;
                $this->assertEquals([
                    'id' => 1, 
                    'name' => 'TestName1-afterPrepareModel', 
                    'title' => 'TestTitle1'
                ], $object->attributes);
            },
        ]);
        
        $action->run(1);
        $this->tester->dontSeeRecord(TestActive::className(), ['id' => 1]);
    }
    
    /**
     * test update action events
     */
    public function testUpdateActionEvents()
    {
        $this->request->setBodyParams(['title' => 'TestTitle11']);
        
        $action = new UpdateAction('update', null, [
            'request' => $this->request,
            'response' => $this->response,
            'modelClass' => TestActive::className(),
            'on afterPrepareModel' => function ($event) {
                $object = $event->object;
                $this->assertEquals([
                    'id' => 1, 
                    'name' => 'TestName1', 
                    'title' => 'TestTitle1'
                ], $object->attributes);
                
                $object->name .= '-afterPrepareModel';
            },
            'on afterLoadModel' => function ($event) {
                $object = $event->object;
                $this->assertEquals([
                    'id' => 1, 
                    'name' => 'TestName1-afterPrepareModel', 
                    'title' => 'TestTitle11'
                ], $object->attributes);
                
                $object->name .= '-afterLoadModel';
            },
            'on afterProcessModel' => function ($event) {
                $object = $event->object;
                $this->assertEquals([
                    'id' => 1, 
                    'name' => 'TestName1-afterPrepareModel-afterLoadModel', 
                    'title' => 'TestTitle11'
                ], $object->attributes);
                
                $object->name .= '-afterProcessModel';
            },
        ]);
        
        $model = $action->run(1);
        $this->tester->seeRecord(TestActive::className(), [
            'id' => 1, 
            'name' => 'TestName1-afterPrepareModel-afterLoadModel', 
            'title' => 'TestTitle11'
        ]);
        
        $this->assertEquals([
            'id' => 1, 
            'name' => 'TestName1-afterPrepareModel-afterLoadModel-afterProcessModel', 
            'title' => 'TestTitle11'
        ], $model->attributes);
    }
    
    /**
     * test view action events
     */
    public function testViewActionEvents()
    {
        $action = new ViewAction('view', null, [
            'request' => $this->request,
            'response' => $this->response,
            'modelClass' => TestActive::className(),
            'on afterPrepareModel' => function ($event) {
                $object = $event->object;
                $object->name .= '-afterPrepareModel';
            }
        ]);
        
        $model = $action->run(1);
        $this->assertEquals([
            'id' => 1, 
            'name' => 'TestName1-afterPrepareModel', 
            'title' => 'TestTitle1'
        ], $model->attributes);
    }
    
    /**
     * test index action events
     */
    public function testIndexActionEvents()
    {
        $action = new IndexAction('index', null, [
            'request' => $this->request,
            'response' => $this->response,
            'modelClass' => TestActive::className(),
        ]);

        $dataProvider = $action->run();
        $models = $dataProvider->getModels();
        $this->assertCount(5, $models);
        
        // query
        $action->off('afterPrepareDataProvider');
        $action->on('afterPrepareDataProvider', function ($event) {
            /* @var $object \yii\data\ActiveDataProvider */
            $object = $event->object;
            
            $object->query->andWhere(['id' => [1,2,3]]);
        });
        
        $dataProvider = $action->run();
        $models = $dataProvider->getModels();
        $this->assertCount(3, $models);
        
        // pagination
        $action->off('afterPrepareDataProvider');
        $action->on('afterPrepareDataProvider', function ($event) {
            /* @var $object \yii\data\ActiveDataProvider */
            $object = $event->object;
            
            $object->getPagination()->setPageSize(4);
        });
        
        $dataProvider = $action->run();
        $models = $dataProvider->getModels();
        $this->assertCount(4, $models);
    }
    
    /**
     * test create validate action events
     */
    public function testCreateValidateActionEvents()
    {
        $this->request->setBodyParams(['id' => 11, 'name' => 'TestName11', 'title' => 'TestTitle11']);
        
        $action = new CreateValidateAction('create-validate', null, [
            'request' => $this->request,
            'response' => $this->response,
            'modelClass' => TestActive::className(),
            'on afterLoadModel' => function ($event) {
                $object = $event->object;
                $this->assertEquals([
                    'id' => 11, 
                    'name' => 'TestName11', 
                    'title' => 'TestTitle11'
                ], $object->attributes);
                
                $object->id = 'aa';
            }
        ]);
        
        $this->assertNotNull($action->run());
    }
    
    /**
     * test update validate action events
     */
    public function testUpdateValidateActionEvents()
    {
        $this->request->setBodyParams(['name' => 'TestName11', 'title' => 'TestTitle11']);
        
        $action = new UpdateValidateAction('update-validate', null, [
            'request' => $this->request,
            'response' => $this->response,
            'modelClass' => TestActive::className(),
            'on afterPrepareModel' => function ($event) {
                $object = $event->object;
                $this->assertEquals([
                    'id' => 1, 
                    'name' => 'TestName1', 
                    'title' => 'TestTitle1'
                ], $object->attributes);
            },
            'on afterLoadModel' => function ($event) {
                $object = $event->object;
                $this->assertEquals([
                    'id' => 1, 
                    'name' => 'TestName11', 
                    'title' => 'TestTitle11'
                ], $object->attributes);
                
                $object->id = 'aa';
            }
        ]);
        
        $this->assertNotNull($action->run(1));
    }
    
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->createTestTable();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        $this->dropTestTable();
        parent::tearDown();
    }
    
    /**
     * create test table
     */
    protected function createTestTable()
    {
        $db = TestActive::getDb();
        $tableName = TestActive::tableName();
        $migration = new Migration();
        
        // create table
        $db->createCommand()->createTable($tableName, [
            'id' => $migration->integer()->notNull(),
            'name' => $migration->string()->notNull(),
            'title' => $migration->string()->notNull(),
        ])->execute();
        $db->createCommand()->addPrimaryKey('pk_id', $tableName, ['id'])->execute();

        // insert data
        $db->createCommand()->batchInsert($tableName, ['id', 'name', 'title'], [
            [1, 'TestName1', 'TestTitle1'],
            [2, 'TestName2', 'TestTitle2'],
            [3, 'TestName3', 'TestTitle3'],
            [4, 'TestName4', 'TestTitle4'],
            [5, 'TestName5', 'TestTitle5'],
        ])->execute();
        
        Yii::info("Create table `{$tableName}`", __METHOD__);
    }
    
    /**
     * drop test table
     */
    protected function dropTestTable()
    {
        $db = TestActive::getDb();
        $tableName = TestActive::tableName();
        $db->createCommand()->dropTable($tableName)->execute();

        Yii::info("Drop table `{$tableName}`", __METHOD__);
    }
}