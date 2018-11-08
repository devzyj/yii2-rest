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
use devzyj\rest\Serializer;
use devzyj\rest\BatchViewAction;
use devzyj\rest\BatchUpdateAction;

/**
 * SerializerTest class.
 * 
 * @author ZhangYanJiong <zhangyanjiong@163.com>
 * @since 1.0
 */
class SerializerTest extends TestCase
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
     * test serialize BatchResult
     */
    public function testSerializeBatchResult()
    {
        $serializer = new Serializer([
            'request' => $this->request,
            'response' => $this->response,
        ]);
        
        // batch view
        $action = new BatchViewAction('batch-view', null, [
            'request' => $this->request,
            'response' => $this->response,
            'modelClass' => TestActive::className(),
        ]);
        $models = $action->run('1;3');
        $this->tester->assertEquals([
            1 => [
                'id' => 1,
                'name' => 'TestName1',
                'title' => 'TestTitle1',
            ],
            3 => [
                'id' => 3,
                'name' => 'TestName3',
                'title' => 'TestTitle3',
            ],
        ], $serializer->serialize($models));
        
        // batch process
        $action = new BatchUpdateAction('batch-update', null, [
            'request' => $this->request,
            'response' => $this->response,
            'modelClass' => TestActive::className(),
        ]);
        
        $this->request->setBodyParams([
            1 => ['name' => 'TestName11', 'title' => 'TestTitle11'],
            3 => ['name' => 'TestName13', 'title' => 'TestTitle13'],
            4 => ['id' => 'AA'],
        ]);
        $models = $action->run();
        $this->tester->assertEquals([
            1 => [
                'success' => true,
                'data' => [
                    'id' => 1,
                    'name' => 'TestName11',
                    'title' => 'TestTitle11',
                ]
            ],
            3 => [
                'success' => true,
                'data' => [
                    'id' => 3,
                    'name' => 'TestName13',
                    'title' => 'TestTitle13',
                ]
            ],
            4 => [
                'success' => false,
                'data' => [
                    [
                        'field' => 'id',
                        'message' => 'id error.',
                    ],
                ]
            ],
        ], $serializer->serialize($models));
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