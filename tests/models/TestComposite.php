<?php
/**
 * @link https://github.com/devzyj/yii2-rest
 * @copyright Copyright (c) 2018 Zhang Yan Jiong
 * @license http://opensource.org/licenses/BSD-3-Clause
 */
namespace devzyj\rest\tests\models;

/**
 * TestComposite class.
 * 
 * @property string $id1 ID1
 * @property string $id2 ID2
 * @property string $name Name
 * @property string $title Title
 * 
 * @author ZhangYanJiong <zhangyanjiong@163.com>
 * @since 1.0
 */
class TestComposite extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'devzyj_test_active_composite';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'title'], 'safe'],
            [['id1', 'id2'], 'integer', 'message' => '{attribute} error.'],
        ];
    }
}