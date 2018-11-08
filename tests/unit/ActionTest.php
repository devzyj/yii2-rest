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
use devzyj\rest\ActiveController;
use devzyj\rest\IndexAction;
use devzyj\rest\ViewAction;
use devzyj\rest\CreateAction;
use devzyj\rest\UpdateAction;
use devzyj\rest\DeleteAction;
use devzyj\rest\CreateValidateAction;
use devzyj\rest\UpdateValidateAction;
use devzyj\rest\BatchResult;
use devzyj\rest\BatchViewAction;
use devzyj\rest\BatchCreateAction;
use devzyj\rest\BatchUpdateAction;
use devzyj\rest\BatchDeleteAction;

/**
 * ActionTest class.
 * 
 * @author ZhangYanJiong <zhangyanjiong@163.com>
 * @since 1.0
 */
class ActionTest extends TestCase
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
     * test ActiveController
     */
    public function testActiveController()
    {
        $controller = new ActiveController('test', null, [
            'modelClass' => TestActive::className(),
        ]);
    }
    
    /**
     * test index action
     */
    public function testIndexAction()
    {
        $action = new IndexAction('index', null, [
            'request' => $this->request,
            'response' => $this->response,
            'modelClass' => TestActive::className(),
            'dataFilter' => [
                'class' => 'yii\data\ActiveDataFilter',
                'searchModel' => function () {
                    return (new \yii\base\DynamicModel(['id' => null]))
                        ->addRule('id', 'safe');
                },
            ]
        ]);

        $dataProvider = $action->run();
        $this->tester->assertInstanceOf('yii\data\ActiveDataProvider', $dataProvider);
        $this->tester->assertCount(5, $dataProvider->getModels());

        // filter
        $this->request->setQueryParams([
            'filter' => [
                'id' => [1, 2, 3]
            ]
        ]);
        $dataProvider = $action->run();
        $this->tester->assertCount(3, $dataProvider->getModels());
    }
    
    /**
     * test view action
     */
    public function testViewAction()
    {
        $action = new ViewAction('view', null, [
            'request' => $this->request,
            'response' => $this->response,
            'modelClass' => TestActive::className(),
        ]);
        
        // success
        $this->tester->assertInstanceOf(TestActive::className(), $action->run(1));
        
        // fail
        $this->expectExceptionMessage('Object not found: `100`');
        $action->run(100);
    }
    
    /**
     * test create action
     */
    public function testCreateAction()
    {
        $action = new CreateAction('create', null, [
            'request' => $this->request,
            'response' => $this->response,
            'modelClass' => TestActive::className(),
            'viewAction' => false,
        ]);
        
        // fail
        $this->request->setBodyParams(['id' => 'AA', 'name' => 'TestNameAA', 'title' => 'TestTitleAA']);
        $model = $action->run();
        $this->tester->assertInstanceOf(TestActive::className(), $model);
        $this->tester->assertTrue($model->hasErrors());
        
        // success
        $this->request->setBodyParams(['id' => 10, 'name' => 'TestName10', 'title' => 'TestTitle10']);
        $model = $action->run();
        $this->tester->assertInstanceOf(TestActive::className(), $model);
        $this->tester->seeRecord(TestActive::className(), [
            'id' => 10, 
            'name' => 'TestName10',
            'title' => 'TestTitle10'
        ]);
    }
    
    /**
     * test update action
     */
    public function testUpdateAction()
    {
        $action = new UpdateAction('update', null, [
            'request' => $this->request,
            'response' => $this->response,
            'modelClass' => TestActive::className(),
        ]);
        
        // fail
        $this->request->setBodyParams(['id' => 'AA']);
        $model = $action->run(1);
        $this->tester->assertInstanceOf(TestActive::className(), $model);
        $this->tester->assertTrue($model->hasErrors());

        // success
        $this->request->setBodyParams(['name' => 'TestName10']);
        $model = $action->run(1);
        $this->tester->assertInstanceOf(TestActive::className(), $model);
        $this->tester->seeRecord(TestActive::className(), [
            'id' => 1, 
            'name' => 'TestName10', 
            'title' => 'TestTitle1'
        ]);
    }
    
    /**
     * test delete action
     */
    public function testDeleteAction()
    {
        $action = new DeleteAction('delete', null, [
            'request' => $this->request,
            'response' => $this->response,
            'modelClass' => TestActive::className(),
        ]);
        
        // success
        $this->tester->assertNull($action->run(1));
        $this->tester->dontSeeRecord(TestActive::className(), ['id' => 1]);
    }
    
    /**
     * test create validate action
     */
    public function testCreateValidateAction()
    {
        $action = new CreateValidateAction('create-validate', null, [
            'request' => $this->request,
            'response' => $this->response,
            'modelClass' => TestActive::className(),
        ]);
        
        // fail
        $this->request->setBodyParams(['id' => 'AA', 'name' => 'TestNameAA', 'title' => 'TestTitleAA']);
        $model = $action->run();
        $this->tester->assertInstanceOf(TestActive::className(), $model);
        $this->tester->assertTrue($model->hasErrors());
        
        // success
        $this->request->setBodyParams(['id' => 10, 'name' => 'TestName10', 'title' => 'TestTitle10']);
        $this->assertNull($action->run());
    }
    
    /**
     * test update validate action
     */
    public function testUpdateValidateAction()
    {
        $action = new UpdateValidateAction('update-validate', null, [
            'request' => $this->request,
            'response' => $this->response,
            'modelClass' => TestActive::className(),
        ]);
        
        // fail
        $this->request->setBodyParams(['id' => 'AA']);
        $model = $action->run(1);
        $this->tester->assertInstanceOf(TestActive::className(), $model);
        $this->tester->assertTrue($model->hasErrors());
        
        // success
        $this->request->setBodyParams(['name' => 'TestName10', 'title' => 'TestTitle10']);
        $this->assertNull($action->run(1));
    }
    
    /**
     * test batch view action
     */
    public function testBatchViewAction()
    {
        $action = new BatchViewAction('batch-view', null, [
            'request' => $this->request,
            'response' => $this->response,
            'modelClass' => TestActive::className(),
        ]);
        
        $models = $action->run('1;3;4;999;9999');
        $this->tester->assertInstanceOf(BatchResult::className(), $models);
        $this->tester->assertCount(3, $models);
        $this->tester->assertArrayHasKey('1', $models);
        $this->tester->assertArrayHasKey('3', $models);
        $this->tester->assertArrayHasKey('4', $models);
    }
    
    /**
     * test batch create action
     */
    public function testBatchCreateAction()
    {
        $action = new BatchCreateAction('batch-create', null, [
            'request' => $this->request,
            'response' => $this->response,
            'modelClass' => TestActive::className(),
        ]);

        $this->request->setBodyParams([
            'key1' => ['id' => 10, 'name' => 'TestName10', 'title' => 'TestTitle10'],
            'key2' => ['id' => 11, 'name' => 'TestName11', 'title' => 'TestTitle11'],
            'key3' => ['id' => 'AA', 'name' => 'TestNameAA', 'title' => 'TestTitleAA'],
        ]);
        $models = $action->run();
        $this->tester->assertInstanceOf(BatchResult::className(), $models);
        $this->tester->assertCount(3, $models);
        $this->tester->assertArrayHasKey('key1', $models);
        $this->tester->assertArrayHasKey('key2', $models);
        $this->tester->assertArrayHasKey('key3', $models);
        $this->tester->seeRecord(TestActive::className(), [
            'id' => 10, 
            'name' => 'TestName10',
            'title' => 'TestTitle10'
        ]);
        $this->tester->seeRecord(TestActive::className(), [
            'id' => 11, 
            'name' => 'TestName11',
            'title' => 'TestTitle11'
        ]);
        $this->tester->dontSeeRecord(TestActive::className(), [
            'id' => 'AA', 
            'name' => 'TestNameAA',
            'title' => 'TestTitleAA'
        ]);
    }
    
    /**
     * test batch update action
     */
    public function testBatchUpdateAction()
    {
        $action = new BatchUpdateAction('batch-update', null, [
            'request' => $this->request,
            'response' => $this->response,
            'modelClass' => TestActive::className(),
        ]);

        $this->request->setBodyParams([
            1 => ['name' => 'TestName11', 'title' => 'TestTitle11'],
            3 => ['name' => 'TestName13', 'title' => 'TestTitle13'],
            4 => ['id' => 'AA'],
            'BB' => ['id' => 'CC'],
        ]);
        $models = $action->run();
        $this->tester->assertInstanceOf(BatchResult::className(), $models);
        $this->tester->assertCount(3, $models);
        $this->tester->assertArrayHasKey('1', $models);
        $this->tester->assertArrayHasKey('3', $models);
        $this->tester->assertArrayHasKey('4', $models);
        $this->tester->seeRecord(TestActive::className(), [
            'id' => 1, 
            'name' => 'TestName11',
            'title' => 'TestTitle11'
        ]);
        $this->tester->seeRecord(TestActive::className(), [
            'id' => 3, 
            'name' => 'TestName13',
            'title' => 'TestTitle13'
        ]);
        $this->tester->seeRecord(TestActive::className(), [
            'id' => 4, 
            'name' => 'TestName4',
            'title' => 'TestTitle4'
        ]);
    }
    
    /**
     * test batch delete action
     */
    public function testBatchDeleteAction()
    {
        $action = new BatchDeleteAction('batch-delete', null, [
            'request' => $this->request,
            'response' => $this->response,
            'modelClass' => TestActive::className(),
        ]);
        
        $models = $action->run('1;3;4;999;9999');
        $this->tester->assertInstanceOf(BatchResult::className(), $models);
        $this->tester->assertCount(3, $models);
        $this->tester->assertArrayHasKey('1', $models);
        $this->tester->assertArrayHasKey('3', $models);
        $this->tester->assertArrayHasKey('4', $models);
        $this->tester->dontSeeRecord(TestActive::className(), [
            'id' => 1
        ]);
        $this->tester->dontSeeRecord(TestActive::className(), [
            'id' => 3, 
        ]);
        $this->tester->dontSeeRecord(TestActive::className(), [
            'id' => 4, 
        ]);
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