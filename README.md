REST Extension for Yii2
=======================

对 `yiisoft/yii2-rest` 进行了增强。在 `Action` 中增加了事件。增加了对多个资源进行操作的 `Action`。


Installation
------------


Usage
-----

```php
// UserController.php
class UserController extends \devzyj\rest\ActiveController
{
    public $modelClass = 'app\models\User';
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

Trying it Out
-------------

只列出部分新增的 API：

* `POST /users/validate`: 验证创建一个新用户时的数据
* `PATCH /users/123/validate` and `PUT /users/123/validate`: 验证更新用户 123 时的数据
* `POST /users/batch`: 创建多个新用户，
* `PATCH /users/batch` and `PUT /users/batch`: 更新多个用户
* `GET /users/10;11;12`: 显示用户 10, 11 和 12 的信息
* `DELETE /users/10;11;12`: 删除用户 10, 11 和 12

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

Events
------

- `afterPrepareDataProvider` 在准备完数据源后触发的事件。
- `afterPrepareModel` 在准备完模型后触发的事件。
- `afterLoadModel` 在模型加载完数据后触发的事件。
- `beforeProcessModel` 在处理模型前触发的事件。
- `afterProcessModel` 在处理完模型后触发的事件。
- `afterProcessModels` 在处理完模型列表后触发的事件。


Actions
-------

- IndexAction 查询并列出多个模型。
    - `afterPrepareDataProvider`
- ViewAction 显示一个模型。
    - `afterPrepareModel`
- CreateAction 创建一个新模型。
    - `afterLoadModel`
    - `beforeProcessModel`
    - `afterProcessModel`
- UpdateAction 更新一个模型。
    - `afterPrepareModel`
    - `afterLoadModel`
    - `beforeProcessModel`
    - `afterProcessModel`
- DeleteAction 删除一个模型。
    - `afterPrepareModel`
    - `beforeProcessModel`
    - `afterProcessModel`
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


Behaviors
---------

- `EagerLoadingBehavior`: 附加到 `IndexAction` 时，即时加载指定的额外资源，防止多次查询。