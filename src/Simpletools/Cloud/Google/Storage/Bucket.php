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

  public function listFiles($directory = '', $sortBy='',$sortDirection ='asc')
  {
    // List objects in a specific directory
    $options = [];
    if ($directory) {
      $options['prefix'] = $directory;
    }

    $objects = [];
    foreach (self::$_bucket[$this->_bucketName]->objects($options) as $object) {
      $info = $object->info();
      $objects[] = [
        'name' => $object->name(),
        'created' => strtotime($info['timeCreated'])
      ];
    }

    if($objects)
    {
      // Sort by creation time
      if($sortBy == 'created')
      {
        usort($objects, function ($a, $b) use ($sortDirection) {
          if($sortDirection == 'desc')
            return $b['created'] <=> $a['created'];
          else
            return $a['created'] <=> $b['created'];
        });
      }
      elseif($sortBy == 'name')
      {
        usort($objects, function ($a, $b) use ($sortDirection) {
          if($sortDirection == 'desc')
            return $b['name'] <=> $a['name'];
          else
            return $a['name'] <=> $b['name'];
        });
      }
    }

    return $objects;
  }

  public function getIterator($directory = '')
  {
    // List objects in a specific directory
    $options = [];
    if ($directory) {
      $options['prefix'] = $directory;
    }
    return self::$_bucket[$this->_bucketName]->objects($options);
  }
}