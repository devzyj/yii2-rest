<?php
/**
 * @link https://github.com/devzyj/yii2-rest
 * @copyright Copyright (c) 2018 Zhang Yan Jiong
 * @license http://opensource.org/licenses/BSD-3-Clause
 */
namespace devzyj\rest\tests\unit;

use Yii;
use yii\db\Migration;
use devzyj\rest\tests\models\TestComposite;
use devzyj\rest\ViewAction;
use devzyj\rest\BatchResult;
use devzyj\rest\BatchViewAction;
use devzyj\rest\BatchUpdateAction;

/**
 * ActionCompositeTest class.
 * 
 * @author ZhangYanJiong <zhangyanjiong@163.com>
 * @since 1.0
 */
class ActionCompositeTest extends TestCase
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
     * test view action
     */
    public function testViewAction()
    {
        $action = new ViewAction('view', null, [
            'request' => $this->request,
            'response' => $this->response,
            'modelClass' => TestComposite::className(),
        ]);
        
        // success
        $this->tester->assertInstanceOf(TestComposite::className(), $action->run('1,2'));
        
        // fail
        $this->expectExceptionMessage('Object not found: `100,200`');
        $action->run('100,200');
    }
    
    /**
     * test batch view action
     */
    public function testBatchViewAction()
    {
        $action = new BatchViewAction('batch-view', null, [
            'request' => $this->request,
            'response' => $this->response,
            'modelClass' => TestComposite::className(),
        ]);
        
        $models = $action->run('1,2;3,4;5,6;100,200;300,400');
        $this->tester->assertInstanceOf(BatchResult::className(), $models);
        $this->tester->assertCount(3, $models);
        $this->tester->assertArrayHasKey('1,2', $models);
        $this->tester->assertArrayHasKey('3,4', $models);
        $this->tester->assertArrayHasKey('5,6', $models);
    }
    
    /**
     * test batch update action
     */
    public function testBatchUpdateAction()
    {
        $action = new BatchUpdateAction('batch-update', null, [
            'request' => $this->request,
            'response' => $this->response,
            'modelClass' => TestComposite::className(),
        ]);

        $this->request->setBodyParams([
            '1,2' => ['name' => 'TestName11-12', 'title' => 'TestTitle11-12'],
            '3,4' => ['name' => 'TestName13-14', 'title' => 'TestTitle13-14'],
        ]);
        $models = $action->run();
        $this->tester->assertInstanceOf(BatchResult::className(), $models);
        $this->tester->assertCount(2, $models);
        $this->tester->assertArrayHasKey('1,2', $models);
        $this->tester->assertArrayHasKey('3,4', $models);
        $this->tester->seeRecord(TestComposite::className(), [
            'id1' => 1, 
            'id2' => 2, 
            'name' => 'TestName11-12',
            'title' => 'TestTitle11-12'
        ]);
        $this->tester->seeRecord(TestComposite::className(), [
            'id1' => 3, 
            'id2' => 4, 
            'name' => 'TestName13-14',
            'title' => 'TestTitle13-14'
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
        $db = TestComposite::getDb();
        $tableName = TestComposite::tableName();
        $migration = new Migration();
        
        // create table
        $db->createCommand()->createTable($tableName, [
            'id1' => $migration->integer()->notNull(),
            'id2' => $migration->integer()->notNull(),
            'name' => $migration->string()->notNull(),
            'title' => $migration->string()->notNull(),
        ])->execute();
        $db->createCommand()->addPrimaryKey('pk_id1_id2', $tableName, ['id1', 'id2'])->execute();

        // insert data
        $db->createCommand()->batchInsert($tableName, ['id1', 'id2', 'name', 'title'], [
            [1, 2, 'TestName1-2', 'TestTitle1-2'],
            [3, 4, 'TestName3-4', 'TestTitle3-4'],
            [5, 6, 'TestName5-6', 'TestTitle5-6'],
            [7, 8, 'TestName7-8', 'TestTitle7-8'],
            [9, 10, 'TestName9-10', 'TestTitle9-10'],
        ])->execute();
        
        Yii::info("Create table `{$tableName}`", __METHOD__);
    }
    
    /**
     * drop test table
     */
    protected function dropTestTable()
    {
        $db = TestComposite::getDb();
        $tableName = TestComposite::tableName();
        $db->createCommand()->dropTable($tableName)->execute();

        Yii::info("Drop table `{$tableName}`", __METHOD__);
    }
}