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
use devzyj\rest\IndexAction;
use devzyj\rest\behaviors\EagerLoadingBehavior;

/**
 * BehaviorTest class.
 * 
 * @author ZhangYanJiong <zhangyanjiong@163.com>
 * @since 1.0
 */
class BehaviorTest extends TestCase
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
     * test index action
     */
    public function testIndexAction()
    {
        $action = new IndexAction('index', null, [
            'request' => $this->request,
            'response' => $this->response,
            'modelClass' => TestActive::className(),
            'as eagerLoadingBehavior' => [
                'class' => EagerLoadingBehavior::className(),
                'request' => $this->request,
            ]
        ]);
        
        $this->request->setQueryParams([
            'expand' => 'user,order'
        ]);
        $dataProvider = $action->run();
        $this->tester->assertEquals(['user'], $dataProvider->query->with);
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