<?php
$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/../../common/config/params-local.php'),
    require(__DIR__ . '/params.php'),
    require(__DIR__ . '/params-local.php'),
    require(__DIR__ . '/server-config.php'),
    require(__DIR__ . '/soap.php'),
    require(__DIR__ . '/events.php')
);

return [
    'id' => 'app-customer',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'service\controllers',
    'components' => [
        'log' => [
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'request' => ['class' => 'framework\core\SOARequest'],
        'response' => ['class' => 'framework\core\Response'],
        'errorHandler' => [
            'class' => 'framework\ErrorHandler',
        ],
    ],
    'resources' => [
        'customers' => 'service\resources\customers',
        'contractor' => 'service\resources\contractor',
        'driver' => 'service\resources\driver',
    ],
    'params' => $params,
];
