<?php
/*
 * Simpletools Framework.
 * Copyright (c) 2009, Marcin Rosinski. (https://www.getsimpletools.com)
 * All rights reserved.
 *
 * LICENCE
 *
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *
 * - 	Redistributions of source code must retain the above copyright notice,
 * 		this list of conditions and the following disclaimer.
 *
 * -	Redistributions in binary form must reproduce the above copyright notice,
 * 		this list of conditions and the following disclaimer in the documentation and/or other
 * 		materials provided with the distribution.
 *
 * -	Neither the name of the Simpletools nor the names of its contributors may be used to
 * 		endorse or promote products derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR
 * IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
 * AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER
 * IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF
 * THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @framework		Simpletools
 * @copyright  		Copyright (c) 2009 Marcin Rosinski. (http://www.getsimpletools.com)
 * @license    		http://www.opensource.org/licenses/bsd-license.php - BSD
 */

namespace Simpletools\Store;

class Credentials implements \JsonSerializable
{
    protected static $_CIPHER = 'AES-256-CBC';
    const CREATOR_VERSION = 'st(php)_1.0.0';

    private static $_CRYPT_KEY;

    private $_cryptKey;
    private $_cryptIv;

    protected $_payloadDecrypted;
    protected $_payloadEncrypted;
    protected $_payloadInput;

    public function __construct($data)
    {
        if(!($data instanceof Credentials) && !is_array($data) && !is_string($data))
        {
            throw new \Exception('Not supported data format, accepted formats: array (raw data) or string (encrypted credentials)',400);
        }

        $this->_cryptKey        = static::$_CRYPT_KEY;

        $this->_resetIv();

        if($data instanceof Credentials)
        {
            $data = (string) $data;
        }

        $this->_payloadInput    = $data;
    }

    protected function _resetIv()
    {
        $iv_size            = openssl_cipher_iv_length(static::$_CIPHER);
        $this->_cryptIv     = openssl_random_pseudo_bytes($iv_size);
    }

    public static function cryptCipher($cryptCipher)
    {
        self::$_CIPHER = $cryptCipher;
    }

    public static function cryptKey($cryptKey)
    {
        self::$_CRYPT_KEY = $cryptKey;
    }

    public function key($key)
    {
        $this->_cryptKey        = $key;

        return $this;
    }

    public function has($name)
    {
        $this->_decrypt();

        return isset($this->_payloadDecrypted['body'][$name]);
    }

    public function get($name)
    {
        $this->_decrypt();
        if(isset($this->_payloadDecrypted['body'][$name]))
        {
            return $this->_payloadDecrypted['body'][$name];
        }
        else
        {
            throw new \Exception("Specified field: $name has not been found",404);
        }
    }

    public function set($name,$value)
    {
        $this->_decrypt();

        $this->_payloadDecrypted['body'][$name] = $value;

        $this->_payloadInput = $this->_payloadDecrypted['body'];

        return $this;
    }

    protected function _encrypt()
    {
        if(is_string($this->_payloadInput))
        {
            $this->_decrypt();
        }
        elseif(is_array($this->_payloadInput))
        {
            if(!$this->_payloadDecrypted) {
                $this->_payloadDecrypted['meta'] = $this->_getMeta();
                $this->_payloadDecrypted['body'] = $this->_payloadInput;
            }

            $this->_resetIv();

            $this->_payloadEncrypted = base64_encode($this->_cryptIv).'.'.openssl_encrypt(json_encode($this->_payloadDecrypted),self::$_CIPHER,$this->_cryptKey,null,$this->_cryptIv);
        }
    }

    protected function _getMeta()
    {
        return array(
            'createdAt'     => time(),
            'creatorVer'    => self::CREATOR_VERSION
        );
    }

    public function decrypt()
    {
        $this->_decrypt();
    }

    protected function _decrypt()
    {
        if($this->_payloadDecrypted) return $this->_payloadDecrypted;

        if(is_string($this->_payloadInput))
        {
            $cryptChunks = explode('.',$this->_payloadInput);

            $this->_payloadEncrypted = $this->_payloadInput;

            $this->_cryptIv = base64_decode($cryptChunks[0]);
            $this->_payloadInput = $cryptChunks[1];

            $decrypted = openssl_decrypt($this->_payloadInput,self::$_CIPHER,$this->_cryptKey,null,$this->_cryptIv);
            if($decrypted===false)
            {
                throw new \Exception('Provided credentials can\'t be decrypted, please check your encryption key',400);
            }

            $this->_payloadDecrypted = json_decode($decrypted,true);
        }
        elseif(is_array($this->_payloadInput))
        {
            $this->_payloadDecrypted['body'] = $this->_payloadInput;
            $this->_payloadDecrypted['meta'] = $this->_getMeta();
        }
    }

    public function toString()
    {
        return $this->__toString();
    }

    public function __toString()
    {
        $this->_encrypt();

        return (string) $this->_payloadEncrypted;
    }

    public function __debugInfo()
    {
        $credentials = $this->__toString();
        $iv = explode('.',$credentials);
        $iv = $iv[0];

        $overheadBytes = strlen($credentials)-strlen(json_encode($this->_payloadDecrypted));

        $debug = array(
            'credentials'       => $credentials,
            'meta'              => $this->_payloadDecrypted['meta'],
            'size'              => array(
                'bytes'        => strlen($credentials),
                'overhead'      => array(
                    'bytes'     => $overheadBytes,
                    'percent'   => round($overheadBytes/strlen($credentials)*100,2)
                )
            ),
            'salt/iv'           => $iv
        );

        $debug['meta']['createdAt'] = date('c',$debug['meta']['createdAt']); //stored as unix timestamp to save the byte space

        return $debug;
    }

    public function jsonSerialize()
    {
        return $this->__toString();
    }
}