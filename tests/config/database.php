<?php

return [

    'connections' => [

        'couchbase' => [
            'name' => 'couchbase',
            'driver' => 'couchbase',
            'host' => env('CB_HOST', env('DB_HOST', 'localhost')),
            'port' => env('CB_PORT', env('DB_PORT', 8093)),
            'bucket' => env('CB_BUCKET', env('CB_DATABASE', env('DB_DATABASE'))),
            'database' => env('CB_BUCKET', env('CB_DATABASE', env('DB_DATABASE'))),
            'username' => env('CB_USERNAME', env('DB_USERNAME')),
            'password' => env('CB_PASSWORD', env('DB_PASSWORD')),
            'auth_type' => env('CB_AUTH_TYPE', env('DB_AUTH_TYPE', \ORT\Interactive\Couchbase\Connection::AUTH_TYPE_CLUSTER_ADMIN)),
            'admin_username' => env('CB_ADMIN_USERNAME', env('CB_USERNAME', env('DB_ADMIN_USERNAME', env('DB_USERNAME', 'Administrator')))),
            'admin_password' => env('CB_ADMIN_PASSWORD', env('CB_PASSWORD', env('DB_ADMIN_PASSWORD', env('DB_PASSWORD', 'password')))),
            'inline_parameters' => true
        ],

        'mysql' => [
            'driver' => 'mysql',
            'host' => env('MYSQL_HOST', env('DB_HOST', '127.0.0.1')),
            'database' => env('MYSQL_DATABASE', env('DB_DATABASE', 'testing')),
            'username' => env('MYSQL_USERNAME', env('DB_USERNAME', 'root')),
            'password' => env('MYSQL_PASSWORD', env('DB_PASSWORD', '')),
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
        ],
    ],

];
