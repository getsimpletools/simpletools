<?php

namespace Simpletools\Cloud\Google\Storage;

use Google\Cloud\Storage\StorageObject;

/*
 * http://googlecloudplatform.github.io/google-cloud-php/#/docs/v0.22.0/storage/storageobject
 */
class File
{
    protected $_fileLocation;
    protected $_fileHandler;

    protected $_fileSettings;
    protected $_client;

    protected $_bucketObject;
    protected $_remoteFile;

    protected $_bodyTouched = false;
    protected $_acl;

    public function __construct($file,array $meta=[])
    {
        if($file instanceof StorageObject)
        {
            $this->_remoteFile = $file;

            $info = $file->info();

            if(!isset($info['metadata']))
            {
                $info['metadata'] = [];
            }

            $this->_fileSettings = [
                'path'          => 'gs://'.$info['bucket'].'/'.$info['name'],
                'bucket'        => $info['bucket'],
                'key'           => $info['name'],
                'meta'      => [
                    'metadata'      => $info['metadata']
                ]
            ];
        }
        else
        {
            if (strpos($file, 'gs://') === false) {
                throw new \Exception('Please specify your location following the pattern - gs://{bucket}/{file-key}');
            }

            $path = $this->parsePath($file);

            $this->_fileSettings = [
                'path'  => $file,
                'bucket' => $path['bucket'],
                'key' => $path['key'],
                'meta' => $meta
            ];
        }
    }

    protected function _getBucket($bucket)
    {
        if($this->_bucketObject) return $this->_bucketObject;

        return $this->_bucketObject = Bucket::get($bucket);
    }

    protected function _initStorageObject()
    {
        if(!$this->_remoteFile)
        {
            $bucket = $this->_getBucket($this->_fileSettings['bucket']);
            $this->_remoteFile = $bucket->object($this->_fileSettings['key']);
        }
    }

    protected function _createEmptyFile()
    {
        if($this->_fileLocation) return $this->_fileLocation;

        return $this->_fileLocation = tempnam(sys_get_temp_dir(),uniqid());
    }

    protected function _download()
    {
        if(!$this->_fileLocation)
        {
            $this->_initStorageObject();
            $this->_createEmptyFile();

            if(!$this->_remoteFile->exists()) return false;

            try
            {
                $this->_remoteFile->downloadToFile($this->_fileLocation);
            }
            catch(\Exception $e){
                //no file found - $e->getCode() = 404
                $this->_remoteFile = null;
                return false;
            }
        }

        return true;
    }

    public function getHandler($flag)
    {
        if($flag!='r')
        {
            $this->_bodyTouched = true;
        }

        if($this->_fileHandler) return $this->_fileHandler;
        $this->_download();
        return $this->_fileHandler = fopen($this->_fileLocation,$flag);
    }

    public function save()
    {
        $bucket = $this->_getBucket($this->_fileSettings['bucket']);
        if($this->_fileHandler) fclose($this->_fileHandler);

        $this->_initStorageObject();
        if(!$this->exists())
        {
            $this->_download();
            $this->_bodyTouched = true;
        }

        if($this->_bodyTouched)
        {
            $settings = [
                "name"      => $this->_fileSettings['key'],
                "metadata"  => $this->_fileSettings['meta']
            ];

            if($this->_acl)
                $settings['predefinedAcl'] = $this->_acl;

            $this->_remoteFile = $bucket->upload(
                fopen($this->_fileLocation, 'r'),
                $settings
            );
        }
        else
        {
            $settings = array();

            if($this->_fileSettings['meta'])
                $settings['metadata'] = $this->_fileSettings['meta'];

            if($this->_acl)
                $settings['predefinedAcl'] = $this->_acl;

            $this->_remoteFile->update($settings);
        }
    }

    public function delete()
    {
        $this->_initStorageObject();
        if($this->_remoteFile)
        {
            try {
                $this->_remoteFile->delete();
                return true;
            }
            catch(\Exception $e)
            {
                return false;
            }
        }

        return false;
    }

    public function getName()
    {
        $file = explode('/',$this->_fileSettings['key']);
        return end($file);
    }

    public function getBody()
    {
        $this->_download();
        return file_get_contents($this->_fileLocation);
    }

    public function setBody($body,$flag=null)
    {
        $this->_bodyTouched = true;

        if($flag=='a') {
            $flag = FILE_APPEND;
            $this->_download();
        }
        else {
            $flag = 0; //default file_put_contents flag
            $this->_createEmptyFile();
        }

        if($this->_fileHandler)
        {
            fclose($this->_fileHandler);
            $this->_fileHandler = null;
        }

        if(!file_put_contents($this->_fileLocation,$body,$flag))
        {
            throw new \Exception(401,"Can't write to file");
        }

        return $this;
    }

    public function appendBody($body)
    {
        $this->setBody($body,'a');
        return $this;
    }

    public function getMeta()
    {
        $this->_initStorageObject();
        if($this->_remoteFile)
        {
            return $this->_remoteFile->info();
        }
        else
        {
            return false;
        }
    }

    public function setMeta(array $metadata)
    {
        $this->_fileSettings['meta'] = $metadata;
        return $this;
    }

    public function exists()
    {
        $this->_initStorageObject();
        return $this->_remoteFile->exists();
    }

    public function __destruct()
    {
        if($this->_fileHandler)
        {
            @fclose($this->_fileHandler);
        }

        if($this->_fileLocation)
        {
            @unlink($this->_fileLocation);
        }
    }

    public function parsePath($path)
    {
        $path = str_replace('gs://', '', $path);
        $path = explode('/', $path, 2);

        return [
            'bucket' => $path[0],
            'key' => $path[1]
        ];
    }

    public function getUri()
    {
        return $this->_fileSettings['path'];
    }

    public function renameTo($path)
    {
        $this->_initStorageObject();
        if(!$this->exists()) return;

        $meta = $this->parsePath($path);
        $this->_remoteFile->rename($meta['key']);
    }

    public function getUrl()
    {
        $this->_initStorageObject();
        $info = $this->_remoteFile->info();

        return $info['selfLink'];
    }

    public function getSize()
    {
        if(!$this->exists()) return 0;
        $meta = $this->getMeta();
        return $meta['size'];
    }

    public function makePublic()
    {
        $this->_acl = 'publicRead';
    }

    public function makePrivate()
    {
        $this->_acl = 'private';
    }
}