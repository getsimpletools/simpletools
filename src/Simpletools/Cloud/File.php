<?php

namespace Simpletools\Cloud;

use Google\Cloud\Storage\StorageObject;
use Simpletools\Cloud\Google;

class File
{
    protected $_file;
    protected static $_defaultProtocol;
    protected static $_bucketSuffix;
    protected static $_bucketPrefix;

    protected static $_tmpDir;

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

    public static function setTempDir($tempDir)
    {
        if(!is_dir($tempDir))
        {
            throw new \Exception("Provided temp dir doesn't exist",404);
        }

        self::$_tmpDir = $tempDir;
    }

    public function __construct($file,array $meta=[])
    {
        if($file instanceof StorageObject)
        {
            $this->_file = new Google\Storage\File($file,$meta);
        }
        elseif(is_string($file))
        {
            $path = $this->_parsePath($file);

            if (!isset($path['protocol']) OR !$path['protocol']) {
                throw new \Exception('Please specify your location following the pattern - {platform}://{bucket}/{file-key}');
            }

            if($path['protocol']=='gs')
                $this->_file = new Google\Storage\File($path['path'],$meta);
            else
                throw new \Exception('An unknown protocol '.$path['protocol']);
        }
        else
        {
            throw new \Exception('An unknown file type');
        }

        if(self::$_tmpDir)
        {
            $this->_file->tempDir(self::$_tmpDir);
        }
    }

    public function tempDir($tempDir)
    {
        if(!is_dir($tempDir))
        {
            throw new \Exception("Provided temp dir doesn't exist",404);
        }

        $this->_file->tempDir($tempDir);

        return $this;
    }

    protected function _parsePath($path)
    {
        if(strpos($path,'://')===false && !self::$_defaultProtocol)
            return array();

        if(strpos($path,'//')===false && !self::$_defaultProtocol)
            return array();

        if(strpos($path,'://')===false)
            $path       = self::$_defaultProtocol.':'.$path;

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
            'bucket'    => $path[0],
            'key'       => $path[1]
        );
    }

    /*
     * Interfaced methods
     */
		public function importFile($filepath, $flag='a')
		{
			return $this->_file->importFile($filepath, $flag);
		}

		public function exportFile($filepath)
		{
			return $this->_file->exportFile($filepath);
		}

    public function getHandler($flag)
    {
        return $this->_file->getHandler($flag);
    }

    public function save()
    {
        $this->_file->save();
    }

    public function delete()
    {
        return $this->_file->delete();
    }

    public function getName()
    {
        return $this->_file->getName();
    }

    public function getBody()
    {
        return $this->_file->getBody();
    }

    public function setBody($body,$flag=null)
    {
        $this->_file->setBody($body,$flag);
        return $this;
    }

    public function appendBody($body)
    {
        $this->setBody($body,'a');
        return $this;
    }

    public function getMeta()
    {
        return $this->_file->getMeta();
    }

    public function exists()
    {
        return $this->_file->exists();
    }

    public function getUrl()
    {
        return $this->_file->getUrl();
    }

    public function getUri()
    {
        return $this->_file->getUri();
    }

    public function setJson($body)
    {
        $this->setBody(json_encode($body));
        return $this;
    }

    public function getJson($assoc=false,$depth=512,$options=null)
    {
        return json_decode($this->getBody(),$assoc,$depth,$options);
    }

    public function setMeta(array $meta)
    {
        $this->_file->setMeta($meta);
        return $this;
    }

    public function getSize()
    {
        return $this->_file->getSize();
    }

    public function renameTo($path)
    {
        $path = $this->_parsePath($path);
        return $this->_file->renameTo($path['path']);
    }

    public function makePublic()
    {
        $this->_file->makePublic();
        return $this;
    }

    public function makePrivate()
    {
        $this->_file->makePrivate();
        return $this;
    }
}