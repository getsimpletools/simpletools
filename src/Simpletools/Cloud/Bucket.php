<?php

namespace Simpletools\Cloud;

class Bucket
{
    protected $_bucket;
    protected static $_defaultProtocol;
    protected static $_bucketSuffix;
    protected static $_bucketPrefix;

    public static function setBucketPrefix($prefix)
    {
        self::$_bucketPrefix = $prefix;
    }

    public static function setBucketSuffix($suffix)
    {
        self::$_bucketSuffix = $suffix;
    }

    public static function setBaseProtocol($protocol)
    {
        self::$_defaultProtocol = $protocol;
    }

    public function __construct($path)
    {
        if(is_string($path))
        {
            $path = $this->_parsePath($path);

            if (!isset($path['protocol']) OR !$path['protocol']) {
                throw new \Exception('Please specify your location following the pattern - {platform}://{bucket}');
            }

            if($path['protocol']=='gs')
                $this->_bucket = new Google\Storage\Bucket($path['bucket']);
            else
                throw new \Exception('An unknown protocol '.$path['protocol']);
        }
        else
        {
            throw new \Exception('An unknown file type');
        }
    }

    public function search($options)
    {
        return $this->_bucket->search($options);
    }

    public function exists()
    {
        return $this->_bucket->exists();
    }

    protected function _parsePath($path)
    {
        if(strpos($path,'://')===false && !self::$_defaultProtocol)
            return array();

        if(strpos($path,'//')===false && !self::$_defaultProtocol)
            return array();

        if(strpos($path,'://')===false)
            $path       = $fullPath = self::$_defaultProtocol.':'.$path;

        $fullPath   = $path;

        $protocol   = explode('://',$path);
        $path       = explode('/', $protocol[1], 2);

        $protocol   = $protocol[0];

        if(self::$_bucketPrefix)
        {
            $path[0]    = self::$_bucketPrefix.$path[0];
            $fullPath   = $protocol.'://'.$path[0].'/'.$path[1];
        }

        if(self::$_bucketSuffix)
        {
            $path[0]    .= self::$_bucketSuffix;
            $fullPath   = $protocol.'://'.$path[0].'/'.$path[1];
        }

        return array(
            'path'      => $fullPath,
            'protocol'  => $protocol,
            'bucket'    => $path[0]
        );
    }
}