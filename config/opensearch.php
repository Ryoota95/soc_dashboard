<?php

return [
    'driver'     => env('SECURITY_DATA_DRIVER', 'dummy'),
    'url'        => env('OPENSEARCH_URL'),
    'user'       => env('OPENSEARCH_USER'),
    'password'   => env('OPENSEARCH_PASSWORD'),
    'index'      => env('OPENSEARCH_INDEX', 'wazuh-alerts-4.x-*'),
    'verify_ssl' => env('OPENSEARCH_VERIFY_SSL', true),

    'fields' => [
        'time'  => '@timestamp',
        'level' => 'rule.level',
        'rule'  => 'rule.description',
        'agent' => 'agent.name',
    ],

    // batas level Wazuh -> label severity
    'levels' => ['medium' => 4, 'high' => 7, 'critical' => 12],
];