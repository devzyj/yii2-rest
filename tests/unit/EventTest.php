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
     * test index action events
     */
    public function testIndexActionEvents()
    {
        $action = new IndexAction('index', null, [
            'request' => $this->request,
            'response' => $this->response,
            'modelClass' => TestActive::className(),
        ]);
        
        // query
        $action->off('afterPrepareDataProvider');
        $action->on('afterPrepareDataProvider', function ($event) {
            /* @var $object \yii\data\ActiveDataProvider */
            $object = $event->object;
            
            $object->query->andWhere(['id' => [1,2,3]]);
        });
        
        $dataProvider = $action->run();
        $models = $dataProvider->getModels();
        $this->tester->assertCount(3, $models);
        
        // pagination
        $action->off('afterPrepareDataProvider');
        $action->on('afterPrepareDataProvider', function ($event) {
            /* @var $object \yii\data\ActiveDataProvider */
            $object = $event->object;
            
            $object->getPagination()->setPageSize(4);
        });
        
        $dataProvider = $action->run();
        $models = $dataProvider->getModels();
        $this->tester->assertCount(4, $models);
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
                $event->object->name .= '-afterPrepareModel';
            }
        ]);
        
        $model = $action->run(1);
        $this->tester->assertEquals('TestName1-afterPrepareModel', $model->name);
    }
    
    /**
     * test create action events
     */
    public function testCreateActionEvents()
    {
        $action = new CreateAction('create', null, [
            'request' => $this->request,
            'response' => $this->response,
            'modelClass' => TestActive::className(),
            'viewAction' => false,
            'on beforeLoadModel' => function ($event) {
                $event->object['name'] .= '-beforeLoadModel';
            },
            'on afterLoadModel' => function ($event) {
                $event->object->name .= '-afterLoadModel';
            },
            'on beforeProcessModel' => function ($event) {
                $event->object->name .= '-beforeProcessModel';
            },
            'on afterProcessModel' => function ($event) {
                $event->object->name .= '-afterProcessModel';
            },
        ]);

        $this->request->setBodyParams(['id' => 10, 'name' => 'TestName10', 'title' => 'TestTitle10']);
        $model = $action->run();
        
        $name = 'TestName10-beforeLoadModel-afterLoadModel-beforeProcessModel';
        $this->tester->seeRecord(TestActive::className(), [
            'id' => 10, 
            'name' => $name,
            'title' => 'TestTitle10'
        ]);
        
        $name .= '-afterProcessModel';
        $this->tester->assertEquals($name, $model->name);
    }
    
    /**
     * test update action events
     */
    public function testUpdateActionEvents()
    {
        $action = new UpdateAction('update', null, [
            'request' => $this->request,
            'response' => $this->response,
            'modelClass' => TestActive::className(),
            'on afterPrepareModel' => function ($event) {
                $event->object->name .= '-afterPrepareModel';
            },
            'on beforeLoadModel' => function ($event) {
                $event->object['title'] .= '-beforeLoadModel';
            },
            'on afterLoadModel' => function ($event) {
                $event->object->name .= '-afterLoadModel';
                $event->object->title .= '-afterLoadModel';
            },
            'on beforeProcessModel' => function ($event) {
                $event->object->name .= '-beforeProcessModel';
                $event->object->title .= '-beforeProcessModel';
            },
            'on afterProcessModel' => function ($event) {
                $event->object->name .= '-afterProcessModel';
                $event->object->title .= '-afterProcessModel';
            },
        ]);

        $this->request->setBodyParams(['title' => 'TestTitle10']);
        $model = $action->run(1);
        
        $name = 'TestName1-afterPrepareModel-afterLoadModel-beforeProcessModel';
        $title = 'TestTitle10-beforeLoadModel-afterLoadModel-beforeProcessModel';
        $this->tester->seeRecord(TestActive::className(), [
            'id' => 1, 
            'name' => $name, 
            'title' => $title
        ]);
        
        $name .= '-afterProcessModel';
        $this->tester->assertEquals($name, $model->name);
        
        $title .= '-afterProcessModel';
        $this->tester->assertEquals($title, $model->title);
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
                $event->object->name .= '-afterPrepareModel';
            },
            'on beforeProcessModel' => function ($event) {
                $event->object->name .= '-beforeProcessModel';
            },
            'on afterProcessModel' => function ($event) {
                $this->tester->assertEquals('TestName1-afterPrepareModel-beforeProcessModel', $event->object->name);
            },
        ]);
        
        $action->run(1);
        $this->tester->dontSeeRecord(TestActive::className(), ['id' => 1]);
    }
    
    /**
     * test create validate action events
     */
    public function testCreateValidateActionEvents()
    {
        $action = new CreateValidateAction('create-validate', null, [
            'request' => $this->request,
            'response' => $this->response,
            'modelClass' => TestActive::className(),
            'on beforeLoadModel' => function ($event) {
                $event->object['name'] .= '-beforeLoadModel';
            },
            'on afterLoadModel' => function ($event) {
                $event->object->name .= '-afterLoadModel';
            },
            'on beforeProcessModel' => function ($event) {
                $event->object->name .= '-beforeProcessModel';
            },
            'on afterProcessModel' => function ($event) {
                $this->tester->assertEquals('TestName10-beforeLoadModel-afterLoadModel-beforeProcessModel', $event->object->name);
            },
        ]);

        $this->request->setBodyParams(['id' => 10, 'name' => 'TestName10', 'title' => 'TestTitle10']);
        $this->tester->assertNull($action->run());
    }
    
    /**
     * test update validate action events
     */
    public function testUpdateValidateActionEvents()
    {
        $action = new UpdateValidateAction('update-validate', null, [
            'request' => $this->request,
            'response' => $this->response,
            'modelClass' => TestActive::className(),
            'on afterPrepareModel' => function ($event) {
                $event->object->name .= '-afterPrepareModel';
            },
            'on beforeLoadModel' => function ($event) {
                $event->object['title'] .= '-beforeLoadModel';
            },
            'on afterLoadModel' => function ($event) {
                $event->object->name .= '-afterLoadModel';
                $event->object->title .= '-afterLoadModel';
            },
            'on beforeProcessModel' => function ($event) {
                $event->object->name .= '-beforeProcessModel';
                $event->object->title .= '-beforeProcessModel';
            },
            'on afterProcessModel' => function ($event) {
                $this->tester->assertEquals('TestName1-afterPrepareModel-afterLoadModel-beforeProcessModel', $event->object->name);
                $this->tester->assertEquals('TestTitle10-beforeLoadModel-afterLoadModel-beforeProcessModel', $event->object->title);
            },
        ]);

        $this->request->setBodyParams(['title' => 'TestTitle10']);
        $this->tester->assertNull($action->run(1));
    }
    
    /**
     * test batch view action events
     */
    public function testBatchViewActionEvents()
    {
        $action = new BatchViewAction('batch-view', null, [
            'request' => $this->request,
            'response' => $this->response,
            'modelClass' => TestActive::className(),
            'on afterPrepareModel' => function ($event) {
                $event->object->name .= '-afterPrepareModel';
            }
        ]);
        
        $models = $action->run('1;3;4');
        $this->tester->assertInstanceOf(BatchResult::className(), $models);
        $this->tester->assertFalse($models->isAfterProcessModels());
        $this->tester->assertCount(3, $models);
        foreach ($models as $key => $model) {
            $this->tester->assertEquals("TestName{$key}-afterPrepareModel", $model->name);
        }
    }
    
    /**
     * test batch create action events
     */
    public function testBatchCreateActionEvents()
    {
        $action = new BatchCreateAction('batch-create', null, [
            'request' => $this->request,
            'response' => $this->response,
            'modelClass' => TestActive::className(),
            'on beforeLoadModel' => function ($event) {
                $event->object['name'] .= '-beforeLoadModel';
            },
            'on afterLoadModel' => function ($event) {
                $event->object->name .= '-afterLoadModel';
            },
            'on beforeProcessModel' => function ($event) {
                $event->object->name .= '-beforeProcessModel';
            },
            'on afterProcessModel' => function ($event) {
                $event->object->name .= '-afterProcessModel';
            },
            'on afterProcessModels' => function ($event) {
                $object = $event->object;
                foreach ($object as $key => $value) {
                    $value['data']->name .= '-afterProcessModels';
                }
            },
        ]);

        $this->request->setBodyParams([
            ['id' => 10, 'name' => 'TestName10', 'title' => 'TestTitle10'],
            ['id' => 11, 'name' => 'TestName11', 'title' => 'TestTitle11'],
            ['id' => 12, 'name' => 'TestName12', 'title' => 'TestTitle12'],
        ]);
        $models = $action->run();
        $this->tester->assertInstanceOf(BatchResult::className(), $models);
        $this->tester->assertTrue($models->isAfterProcessModels());
        $this->tester->assertCount(3, $models);
        foreach ($models as $data) {
            $model = $data['data'];
            
            $name = "TestName{$model->id}-beforeLoadModel-afterLoadModel-beforeProcessModel";
            $this->tester->seeRecord(TestActive::className(), [
                'id' => $model->id,
                'name' => $name,
                'title' => "TestTitle{$model->id}",
            ]);
            
            $name .= '-afterProcessModel-afterProcessModels';
            $this->tester->assertEquals($name, $model->name);
        }
    }
    
    /**
     * test batch update action events
     */
    public function testBatchUpdateActionEvents()
    {
        $action = new BatchUpdateAction('batch-update', null, [
            'request' => $this->request,
            'response' => $this->response,
            'modelClass' => TestActive::className(),
            'on afterPrepareModel' => function ($event) {
                $event->object->name .= '-afterPrepareModel';
            },
            'on beforeLoadModel' => function ($event) {
                $event->object['title'] .= '-beforeLoadModel';
            },
            'on afterLoadModel' => function ($event) {
                $event->object->name .= '-afterLoadModel';
                $event->object->title .= '-afterLoadModel';
            },
            'on beforeProcessModel' => function ($event) {
                $event->object->name .= '-beforeProcessModel';
                $event->object->title .= '-beforeProcessModel';
            },
            'on afterProcessModel' => function ($event) {
                $event->object->name .= '-afterProcessModel';
                $event->object->title .= '-afterProcessModel';
            },
            'on afterProcessModels' => function ($event) {
                $object = $event->object;
                foreach ($object as $key => $value) {
                    $value['data']->name .= '-afterProcessModels';
                    $value['data']->title .= '-afterProcessModels';
                }
            },
        ]);

        $this->request->setBodyParams([
            1 => ['title' => 'TestTitle11'],
            3 => ['title' => 'TestTitle13'],
            4 => ['title' => 'TestTitle14'],
        ]);
        $models = $action->run();
        $this->tester->assertInstanceOf(BatchResult::className(), $models);
        $this->tester->assertTrue($models->isAfterProcessModels());
        $this->tester->assertCount(3, $models);
        foreach ($models as $key => $data) {
            $model = $data['data'];
            
            $name = "TestName{$key}-afterPrepareModel-afterLoadModel-beforeProcessModel";
            $title = "TestTitle1{$key}-beforeLoadModel-afterLoadModel-beforeProcessModel";
            $this->tester->seeRecord(TestActive::className(), [
                'id' => $key,
                'name' => $name,
                'title' => $title,
            ]);

            $name .= '-afterProcessModel-afterProcessModels';
            $this->tester->assertEquals($name, $model->name);
            
            $title .= '-afterProcessModel-afterProcessModels';
            $this->tester->assertEquals($title, $model->title);
        }
    }
    
    /**
     * test batch delete action events
     */
    public function testBatchDeleteActionEvents()
    {
        $action = new BatchDeleteAction('batch-delete', null, [
            'request' => $this->request,
            'response' => $this->response,
            'modelClass' => TestActive::className(),
            'on afterPrepareModel' => function ($event) {
                $event->object->name .= '-afterPrepareModel';
            },
            'on beforeProcessModel' => function ($event) {
                $event->object->name .= '-beforeProcessModel';
            },
            'on afterProcessModel' => function ($event) {
                $event->object->name .= '-afterProcessModel';
            },
            'on afterProcessModels' => function ($event) {
                foreach ($event->object as $key => $value) {
                    $name = "TestName{$key}-afterPrepareModel-beforeProcessModel-afterProcessModel";
                    $this->tester->assertEquals($name, $value['data']->name);
                }
            },
        ]);
        
        $models = $action->run('1;3;4');
        $this->tester->assertInstanceOf(BatchResult::className(), $models);
        $this->tester->assertTrue($models->isAfterProcessModels());
        $this->tester->assertCount(3, $models);
        foreach ($models as $key => $data) {
            $this->tester->dontSeeRecord(TestActive::className(), [
                'id' => $key,
            ]);
        }
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