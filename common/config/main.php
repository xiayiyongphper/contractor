<?php
return [
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'id' => 'app-customer',
    'basePath' => dirname(__DIR__),
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm' => '@vendor/npm-asset',
    ],
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'mainDb' => __env_get_mysql_db_config('lelai_slim_customer'),
        'merchantDb' => __env_get_mysql_db_config('lelai_slim_merchant'),
        'commonDb' => __env_get_mysql_db_config('lelai_slim_common'),
        'productDb' => __env_get_mysql_db_config('lelai_booking_product_a'),
        'coreReadOnlyDb' => __env_get_mysql_readonly_db_config('lelai_slim_core'),
        'logDb' => __env_get_mysql_db_config('swoole_log'),
        'proxyDb' => __env_get_mysql_db_config('swoole_proxy'),
        'redisCache' => __env_get_redis_config(),
        'elasticSearch' => __env_get_elasticsearch_config(),
        'es_logger' => [
            'class' => '\framework\components\log\RedisLogger',
            'redis' => __env_get_elk_redis_config(),
            'logKey' => 'logstash-lelai-dinghuo'
        ],
        'routeRedisCache' => __env_get_route_redis_config(),
        'mq' => __env_get_mq_config(),
        'consumer_mq' => __env_get_mq_config(),
        'session' => __env_get_session_config(),
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            'viewPath' => '@common/mail',
            'useFileTransport' => true,
        ],
    ],
];