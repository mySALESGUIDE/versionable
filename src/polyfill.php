<?php

namespace Mpociot\Couchbase\Eloquent {
    if (!class_exists('Mpociot\Couchbase\Eloquent\Model') && class_exists('ORT\Interactive\Couchbase\Eloquent\Model')) {
        class Model extends \ORT\Interactive\Couchbase\Eloquent\Model
        {
        }
    }
}

namespace Mpociot\Couchbase {
    if (!class_exists('Mpociot\Couchbase\CouchbaseServiceProvider') && class_exists('ORT\Interactive\Couchbase\CouchbaseServiceProvider')) {
        class CouchbaseServiceProvider extends \ORT\Interactive\Couchbase\CouchbaseServiceProvider
        {
        }
    }
}
