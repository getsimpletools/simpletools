<?php

namespace Simpletools\Cloud\Google\Storage;

/*
 * http://googlecloudplatform.github.io/google-cloud-php/#/docs/v0.22.0/storage/bucket
 */
class Bucket
{
    protected static $_bucket   = [];
    protected $_bucketName      = '';

    public static function get($bucket)
    {
        if(isset(self::$_bucket[$bucket])) return self::$_bucket[$bucket];

        return self::$_bucket[$bucket]  = Client::get()->bucket($bucket);
    }

    public function __construct($bucket)
    {
        self::get($bucket);
        $this->_bucketName = $bucket;
    }

    public function search($options)
    {
        $storageObjects = self::$_bucket[$this->_bucketName]->objects($options);
        $results        = array();
        foreach($storageObjects as $object)
        {
            $results[] = new \Simpletools\Cloud\File($object);
        }

        return $results;
    }

    public function exists()
    {
        self::$_bucket[$this->_bucketName]->exists();
    }
}