REST Extension for Yii2
=======================

对 `yiisoft/yii2-rest` 进行了增强。在 `Action` 中增加了事件。增加了对多个资源进行操作的 `Action`。


Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```bash
composer require --prefer-dist "devzyj/yii2-rest" "~1.0.0"
```

or add

```json
"devzyj/yii2-rest" : "~1.0.0"
```

to the require section of your application's `composer.json` file.


Usage
-----

```php
// UserController.php
class UserController extends \devzyj\rest\ActiveController
{
    public $modelClass = 'app\models\User';
    //public $notFoundMessage = 'User not found: `{id}`';
    //public $allowedCount = 100;
    //public $manyResourcesMessage = 'The number of users requested cannot exceed `{allowedCount}`.';
}

// config.php
return [
    'components' => [
        'urlManager' => [
            ......
            'rules' => [
                [
                    'class' => 'devzyj\rest\UrlRule',
                    'controller' => 'user',
                ]
            ]
        ],
    ],
];
```

调用方法，只列出部分新增的 API：

- `POST /users/validate`: 验证创建一个新用户时的数据
- `PATCH /users/123/validate` and `PUT /users/123/validate`: 验证更新用户 123 时的数据
- `POST /users/batch`: 创建多个新用户，
- `PATCH /users/batch` and `PUT /users/batch`: 更新多个用户
- `GET /users/10;11;12`: 显示用户 10, 11 和 12 的信息
- `DELETE /users/10;11;12`: 删除用户 10, 11 和 12

创建多个新用户时的数据格式：

```
-d [
    {"username": "example1", "email": "user1@example.com"},
    {"username": "example2", "email": "user2@example.com"}
]
```

更新用户 123 和 456 时的数据格式：

```
-d {
    "123": {"username": "example1", "email": "user1@example.com"},
    "456": {"username": "example2", "email": "user2@example.com"}
}
```


Controllers
-----------

- ActiveController
    - 增加 `$notFoundMessage`，模型不存在时的错误信息。
    - 增加 `$allowedCount`，允许批量执行的资源个数。
    - 增加 `$manyResourcesMessage`，批量操作请求资源过多的错误信息。
    - 修改 `checkAccess($action, $params = [])`，检查用户是否有执行当前动作的权限。
    - 增加 `checkModelAccess($model, $action, $params = [])`，检查用户是否有执行数据模型的权限。


Actions
-------

修改的 Actions：

- IndexAction
    - 增加 `afterPrepareDataProvider` 事件。
- ViewAction
    - 增加 `afterPrepareModel` 事件。
- CreateAction
    - 增加 `afterLoadModel` 事件。
    - 增加 `beforeProcessModel` 事件。
    - 增加 `afterProcessModel` 事件。
- UpdateAction
    - 增加 `afterPrepareModel` 事件。
    - 增加 `afterLoadModel` 事件。
    - 增加 `beforeProcessModel` 事件。
    - 增加 `afterProcessModel` 事件。
- DeleteAction
    - 增加 `afterPrepareModel` 事件。
    - 增加 `beforeProcessModel` 事件。
    - 增加 `afterProcessModel` 事件。

增加的 Actions：

- CreateValidateAction 创建新模型时，验证数据。
    - `afterLoadModel`
    - `beforeProcessModel`
- UpdateValidateAction 更新模型时，验证数据。
    - `afterPrepareModel`
    - `afterLoadModel`
    - `beforeProcessModel`
- BatchViewAction 显示多个模型。
    - `afterPrepareModel`
- BatchCreateAction 创建多个新模型。
    - `afterLoadModel`
    - `beforeProcessModel`
    - `afterProcessModel`
    - `afterProcessModels`
- BatchUpdateAction 更新多个模型。
    - `afterPrepareModel`
    - `afterLoadModel`
    - `beforeProcessModel`
    - `afterProcessModel`
    - `afterProcessModels`
- BatchDeleteAction 删除多个模型。
    - `afterPrepareModel`
    - `beforeProcessModel`
    - `afterProcessModel`
    - `afterProcessModels`


Events
------

- `afterPrepareDataProvider` 在准备完数据源后触发的事件。
- `afterPrepareModel` 在准备完模型后触发的事件。
- `afterLoadModel` 在模型加载完数据后触发的事件。
- `beforeProcessModel` 在处理模型前触发的事件。
- `afterProcessModel` 在处理完模型后触发的事件。
- `afterProcessModels` 在处理完模型列表后触发的事件。

事件参数说明：

- 事件参数的类型为 `ActionEvent`。
- `ActionEvent::$object` 执行事件时的数据对像，以下列出的是对应事件中的对像类型。
    - `afterPrepareDataProvider`：`\yii\data\ActiveDataProvider`
    - `afterPrepareModel`： `\yii\db\ActiveRecord`
    - `afterLoadModel`： `\yii\db\ActiveRecord`
    - `beforeProcessModel`： `\yii\db\ActiveRecord`
    - `afterProcessModel`： `\yii\db\ActiveRecord`
    - `afterProcessModels`： `\devzyj\rest\BatchResult`


Behaviors
---------

- `EagerLoadingBehavior` 附加到 `IndexAction` 时，即时加载指定的额外资源，防止多次查询。