<?php

namespace Simpletools\Cloud\Google\Storage;

use Google\Cloud\Storage\StorageObject;

/*
 * http://googlecloudplatform.github.io/google-cloud-php/#/docs/v0.22.0/storage/storageobject
 */
class File
{
    protected $_fileLocation;
    protected $_isTempFile;
    protected $_fileHandler;
    protected $_isImport =false;

    protected $_fileSettings;
    protected $_client;

    protected $_bucketObject;
    protected $_remoteFile;

    protected $_bodyTouched = false;
    protected $_acl;

    protected $_tmpDir;

    protected $_gzip = false;
    protected $_gzipChunk = 100000;
    protected $_gzipCompressionLevel = 9;

    public function __construct($file,array $meta=[], $client = null)
    {
        if($client)
        {
            $this->_client = $client;
        }

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

        $this->_tmpDir = sys_get_temp_dir();
    }

    protected function _getBucket($bucket)
    {
        if($this->_bucketObject) return $this->_bucketObject;

        if($this->_client)
        {
            return $this->_bucketObject = $this->_client->getStorage()->storage()->bucket($bucket);
        }

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

    public function tempDir($tempDir)
    {
        if(!is_dir($tempDir))
        {
            throw new \Exception("Provided temp dir doesn't exist",404);
        }

        $this->_tmpDir = $tempDir;
    }

    protected function _createEmptyFile()
    {
        if($this->_fileLocation) return $this->_fileLocation;

        $this->_isTempFile = true;
        return $this->_fileLocation = tempnam($this->_tmpDir,uniqid());
    }

    protected function _download()
    {
        $TMPDIR = sys_get_temp_dir();
        if($this->_tmpDir) putenv('TMPDIR=/' . $this->_tmpDir);

        if(!$this->_fileLocation)
        {
            $this->_initStorageObject();
            $this->_createEmptyFile();

            if(!$this->_remoteFile->exists())
            {
                if($this->_tmpDir) putenv('TMPDIR=/' . $TMPDIR);

                return false;
            }

            try
            {
                $this->_remoteFile->downloadToFile($this->_fileLocation);
            }
            catch(\Exception $e){

                if($this->_tmpDir) putenv('TMPDIR=/' . $TMPDIR);

                //no file found - $e->getCode() = 404
                $this->_remoteFile = null;
                return false;
            }
        }

        if($this->_tmpDir) putenv('TMPDIR=/' . $TMPDIR);

        return true;
    }

		public function importFile($filepath, $flag='a')
		{
			if(!file_exists($filepath))
			{
				throw new \Exception('File '.$filepath.' doesn\'t exists');
			}

			if($this->_fileHandler)
			{
				@fclose($this->_fileHandler);
			}

			if($this->_fileLocation)
			{
				@unlink($this->_fileLocation);
			}

			$this->_fileLocation = $filepath;
			$this->_isTempFile = false;
			$this->_isImport = true;

			$this->_fileHandler = fopen($this->_fileLocation,$flag);
			return $this;
		}

		public function exportFile($filepath)
		{
			$this->_initStorageObject();

			if(!$this->_remoteFile->exists())
			{
				throw new \Exception('File '.$this->_fileSettings['path']. "doesn't exists.");
			}

			if(!$fp= @fopen($filepath,'w'))
			{
				throw new \Exception("Couldn't open a file pointer for this location: ".$filepath);
			}
			fclose($fp);

			$this->_remoteFile->downloadToFile($filepath);
			return $this;
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

        if($this->_bodyTouched || $this->_isImport)
        {
            if($this->_gzip)
            {
                $compressed_file_path 	= tempnam($this->_tmpDir,uniqid());
                $compressed_file_h		= gzopen($compressed_file_path,'wb'.$this->_gzipCompressionLevel);

                $original_file_h = fopen($this->_fileLocation,'rb');

                while($content = fread($original_file_h,$this->_gzipChunk))
                {
                    gzwrite($compressed_file_h,$content);
                }

                fclose($original_file_h);
                gzclose($compressed_file_h);

                $beforeGzip = filesize($this->_fileLocation);
                $afterGzip = filesize($compressed_file_path);

                $file_path = $compressed_file_path;

                if($beforeGzip > $afterGzip) {
                    $this->setMeta([
                        'contentEncoding' => 'gzip',
                        'metadata' => [
                            'contentLengthBeforeGzip' => $beforeGzip,
                            'contentLengthAfterGzip' => $afterGzip
                        ]
                    ]);
                }
                else
                {
                    $file_path = $this->_fileLocation;
                    @unlink($compressed_file_path);
                    $compressed_file_path = null;
                }
            }
            else
            {
                $file_path = $this->_fileLocation;
            }

            $settings = [
                "name"      => $this->_fileSettings['key'],
                "metadata"  => $this->_fileSettings['meta']
            ];

            if($this->_acl)
                $settings['predefinedAcl'] = $this->_acl;

            try {
                $this->_remoteFile = $bucket->upload(
                    fopen($file_path, 'rb'),
                    $settings
                );

                if(isset($compressed_file_path))
                    @unlink($compressed_file_path);
            }
            catch(\Exception $e)
            {
                if(isset($compressed_file_path))
                    @unlink($compressed_file_path);

                throw $e;
            }
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

        if(file_put_contents($this->_fileLocation,$body,$flag)===false)
        {
            throw new \Exception("Can't write to file",401);
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

        if($this->_fileLocation && $this->_isTempFile)
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

    /**
     * Get a signed URL referencing the object
     * 
     * @param \DateTime $expiry Expiry of the url
     * 
     * @return string
     */
    public function getSignedUrl($expiry)
    {
        if(!$expiry instanceof \DateTime) {
            throw new \Exception(sprintf('Expiry must be a DateTime instance, %s given', gettype($expiry)));
        }
        $this->_initStorageObject();
        return $this->_remoteFile->signedUrl($expiry);
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

    public function gzip($compressionLevel=9,$chunkSize=100000)
    {
        $this->_gzipChunk = $chunkSize;
        $this->_gzipCompressionLevel = $compressionLevel;
        $this->_gzip = true;

        return $this;
    }

    public function gzipOff()
    {
        $this->_gzip = false;

        return $this;
    }

    public function stream()
    {
        $this->_initStorageObject();
        return $this->_remoteFile->downloadAsStream();
    }
}