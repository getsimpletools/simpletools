<?php
/*
 * Simpletools Framework.
 * Copyright (c) 2009, Marcin Rosinski. (http://www.getsimpletools.com/)
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
 * @packages    	Session
 * @description		Session & Cookies Handler
 * @copyright  		Copyright (c) 2011 Marcin Rosinski. (http://www.getsimpletools.com/)
 * @license    		BSD
 *
 */

	namespace Simpletools\Store;

	class Cookie
	{
		private static $settings = array(
			'expire' 							=> 2592000,
			'path'								=> '/',
			'secure' 							=> false,
			'httpOnly' 							=> false,
			'jsonDecodeArray'					=> true,
			'defaultReturn'						=> "ST__EXCEPTION",

			'encryptionPhrase'					=> "",
			'encryptionSalt'					=> "sh%3tf$12Df&yuXgF£gf"
		);

		public static function is($key)
		{
			return isset($_COOKIE[$key]);
		}

		protected static function _getRaw($key)
		{
			$value 	= isset($_COOKIE[$key]) ? $_COOKIE[$key] : null;
			$prefix = substr($value,0,7);

			if($prefix=="_st_/p.")
			{
				return array('st'=>json_decode(substr($value,7),self::$settings['jsonDecodeArray']),'raw'=>$value);
			}
			elseif($prefix=="_st_/e.")
			{
				if(!($decryptedValue = self::_decrypt(substr($value,7))))
				{
					if(self::$settings['defaultReturn']=="ST__EXCEPTION")
					{
						throw new \Exception("Cookie with key: ".$key." can't be decrypted",500);
					}
					else
					{
						return array('raw'=>self::$settings['defaultReturn']);
					}
				}
				else
				{
					return array('st'=>json_decode($decryptedValue,self::$settings['jsonDecodeArray']),'raw'=>$value);
				}
			}
			else
			{
				return array('raw'=>$value);
			}
		}

		public static function getMeta($key)
		{
			if(!isset($_COOKIE[$key]) && self::$settings['defaultReturn']=="ST__EXCEPTION")
			{
				throw new \Exception("Cookie with key: ".$key." doesn't exist",404);
			}

			$value 	= self::_getRaw($key);
			if(!isset($value['st']) OR !isset($value['st']['e']))
			{
				return null;
			}

			$meta 						= array();
			$meta['dateSetOn']			= date(DATE_COOKIE,$value['st']['s']);
			$meta['dateExpireOn']		= date(DATE_COOKIE,$value['st']['e']);
			$meta['secondSetOn']		= $value['st']['s'];
			$meta['secondExpireOn']		= $value['st']['e'];
			$meta['secondDuration']		= $value['st']['e'] - $value['st']['s'];

			return $meta;
		}

		public static function get($key)
		{
			if(!isset($_COOKIE[$key]) && self::$settings['defaultReturn']=="ST__EXCEPTION")
			{
				throw new \Exception("Cookie with key: ".$key." doesn't exist",404);
			}

			$value = self::_getRaw($key);

			return isset($value['st']['v']) ? $value['st']['v'] : $value['raw'];
		}

		public static function set($key,$value,$etc=array())
		{
			$expire = 0;

			if(isset($etc['expire']) && is_string($etc['expire']))
			{
				$expire = strtotime($etc['expire']);
			}
			elseif(isset($etc['expire']) && $etc['expire'])
			{
				$expire = $etc['expire'];
			}

			$expire = (!$expire) ? self::$settings['expire'] : $expire;

			if(!self::$settings['encryptionPhrase'])
			{
				$data = '_st_/p.'.json_encode(array('v'=>$value,'e'=>$expire,'s'=>time()));
			}
			else
			{
				$value 	= self::_encrypt(json_encode(array('v'=>$value,'e'=>$expire,'s'=>time())));
				$data 	= '_st_/e.'.$value;
			}

			return setcookie(
				$key,
				$data,
				$expire,
				isset($etc['path']) ? $etc['path'] : self::$settings['path'],
				isset($etc['domain']) ? $etc['domain'] : self::$settings['domain'],
				isset($etc['secure']) ? $etc['secure'] : self::$settings['secure'],
				isset($etc['httpOnly']) ? $etc['httpOnly'] : self::$settings['httpOnly']
			);
		}

		public static function remove($key)
		{
			if(is_array($key))
			{
				foreach($key as $k)
				{
					self::set($k,null,array(
						'expire' => "NOW - 1 YEAR"
					));
				}
			}
			else
			{
				self::set($key,null,array(
					'expire' => "NOW - 1 YEAR"
				));
			}
		}

		public static function settings(array $options)
		{
			$SERVER_NAME = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost';

			if(isset($options['expire']) && !is_integer($options['expire']))
			{
				$options['expire'] = strtotime($options['expire']);
			}

			self::$settings['expire'] 					= isset($options['expire']) ? $options['expire'] : time()+2592000;
			self::$settings['path']						= isset($options['path']) ? $options['path'] : '/';
			self::$settings['domain']					= isset($options['domain']) ? $options['domain'] : $SERVER_NAME;
			self::$settings['secure']					= isset($options['secure']) ? (boolean) $options['secure'] : false;
			self::$settings['httpOnly']  				= isset($options['httpOnly']) ? (boolean) $options['httpOnly'] : false;
			self::$settings['jsonDecodeArray']			= isset($options['jsonDecodeArray']) ? (boolean) $options['jsonDecodeArray'] : true;
			self::$settings['defaultReturn']			= (array_key_exists('defaultReturn',$options)) ? $options['defaultReturn'] : 'ST__EXCEPTION';

			self::$settings['encryptionPhrase']  		= isset($options['encryptionPhrase']) ? hash('SHA256', $options['encryptionPhrase'].(isset($options['encryptionSalt']) ? $options['encryptionSalt'] : self::$settings['encryptionSalt']), true) : false;
		}

		protected static function _encrypt($string)
		{
			$key 	= self::$settings['encryptionPhrase'];

			// Build $iv and $iv_base64.  We use a block size of 128 bits (AES compliant) and CBC mode.  (Note: ECB mode is inadequate as IV is not used.)
			srand(); $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC), MCRYPT_RAND);
			if (strlen($iv_base64 = rtrim(base64_encode($iv), '=')) != 22) return false;
			// Encrypt $decrypted and an MD5 of $decrypted using $key.  MD5 is fine to use here because it's just to verify successful decryption.
			$encrypted = base64_encode(@mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $string . md5($string), MCRYPT_MODE_CBC, $iv));
			// We're done!
			return $iv_base64 . $encrypted;
		}

		protected static function _decrypt($encrypted)
		{
			$key 	= self::$settings['encryptionPhrase'];

			// Retrieve $iv which is the first 22 characters plus ==, base64_decoded.
			$iv = base64_decode(substr($encrypted, 0, 22) . '==');
			// Remove $iv from $encrypted.
			$encrypted = substr($encrypted, 22);
			// Decrypt the data.  rtrim won't corrupt the data because the last 32 characters are the md5 hash; thus any \0 character has to be padding.
			$decrypted = rtrim(@mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, base64_decode($encrypted), MCRYPT_MODE_CBC, $iv), "\0\4");
			// Retrieve $hash which is the last 32 characters of $decrypted.
			$hash = substr($decrypted, -32);
			// Remove the last 32 characters from $decrypted.
			$decrypted = substr($decrypted, 0, -32);
			// Integrity check.  If this fails, either the data is corrupted, or the password/salt was incorrect.
			if (md5($decrypted) != $hash) return false;
			// Yay!
			return $decrypted;
		}
	}

?>