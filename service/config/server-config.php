<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/1/8
 * Time: 12:24
 */
return [
    'soa_server_config' => __env_get_server_config(__FILE__),
    'custom_processes' => [
        'ReportProcess' => [
            'class' => 'framework\process\ReportProcess',
        ],
        'MessageProcess' => [
            'class' => 'service\models\process\MessageProcess',
        ],
        'ESProcess' => [
            'class' => 'framework\process\ESProcess',
        ],
//        'MQProcess' => [
//            'class' => 'service\models\process\MQProcess',
//        ],
    ],
    'soa_client_config' => __env_get_client_config(),
    'service_mapping' => [
        'local' => [
            ['module' => 'contractor', 'ip' => ENV_SERVER_LOCAL_IP, 'port' => ENV_SERVER_LOCAL_PORT],
            ['module' => ENV_SYS_NAME . '_msg', 'ip' => ENV_SERVER_LOCAL_IP, 'port' => ENV_SERVER_MSG_PORT],
        ],
        'remote' => [
            ['module' => 'contractor', 'ip' => ENV_SERVER_IP, 'port' => ENV_SERVER_PORT],
            ['module' => ENV_SYS_NAME . '_msg', 'ip' => ENV_SERVER_IP, 'port' => ENV_SERVER_MSG_PORT],
        ],
    ],
    'ip_port' => [
        'host' => '0.0.0.0', //eg.:0.0.0.0
        'hostV6' => '::(0:0:0:0:0:0:0:0)',
        'port' => ENV_SERVER_PORT,//eg.:9090
        'localHost' => ENV_SERVER_LOCAL_IP,//eg.:127.0.0.1
        'localPort' => ENV_SERVER_LOCAL_PORT,//eg.:19090
        'msgHost' => '0.0.0.0',//eg.:0.0.0.0
        'msgPort' => ENV_SERVER_MSG_PORT,//消息端口,eg.:9999
    ],
    'proxy_ip_port' => [
        'host' => ENV_PROXY_SERVER_IP,
        'port' => ENV_PROXY_SERVER_PORT,
        'localHost' => ENV_PROXY_SERVER_LOCAL_IP,
        'localPort' => ENV_PROXY_SERVER_LOCAL_PORT
    ],
    'es_cluster' => [
        'hosts' => explode(',', ENV_ES_CLUSTER_HOSTS),
        'size' => ENV_ES_CLUSTER_BULK_SIZE,
    ],
    'rabbitmq' => [
        'host' => ENV_RABBITMQ_HOST,
        'port' => ENV_RABBITMQ_PORT,
        'user' => ENV_RABBITMQ_USER,
        'pwd' => ENV_RABBITMQ_PASSWORD,
        'vhost' => ENV_RABBITMQ_VHOST,
    ]
];