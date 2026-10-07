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

        // data kerentanan (vulnerability detection)
    'vuln' => [
        'index'  => env('OPENSEARCH_VULN_INDEX', 'wazuh-states-vulnerabilities-*'),
        'fields' => [
            'agent'       => 'agent.name',
            'package'     => 'package.name',
            'version'     => 'package.version',
            'cve'         => 'vulnerability.id',
            'severity'    => 'vulnerability.severity',
            'description' => 'vulnerability.description',
        ],
    ],
];