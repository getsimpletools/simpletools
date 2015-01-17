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
 * @version    		Ver: 2.0.15 2014-12-30 23:36
 * 
 */

	namespace Simpletools\Store;

	class Cookie
	{
		private static $settings = array(
			'cookie_expire' 					=> 2592000,
			'cookie_path'						=> '/',
			'cookie_secure' 					=> false,
			'cookie_httponly' 					=> false,
			'json_decode_array'					=> true,
			'default_return'					=> "ST__EXCEPTION"
		);

		public static function is($key)
		{
			return isset($_COOKIE[$key]);
		}

		public static function get($key)
		{
			if(!isset($_COOKIE[$key]) && self::$settings['default_return']=="ST__EXCEPTION")
			{
				throw new \Exception("Cookie with key: ".$key." doesn't exist",404);
			}
			else
			{
				return isset($_COOKIE[$key]) ? json_decode($_COOKIE[$key],self::$settings['json_decode_array']) : self::$settings['default_return'];
			}
		}

		public static function set($key,$value,$expire=null)
		{
			$value = json_encode($value);

			if($expire && !is_string($expire))
			{
				$expire = strtotime($expire);
			}

			setcookie(
				$key,
				$value,
				(!$expire) ? self::$settings['cookie_expire'] : $expire,
				self::$settings['cookie_path'],
				self::$settings['cookie_domain'],
				self::$settings['cookie_secure'],
				self::$settings['cookie_httponly']
			);
		}

		public static function remove($key)
		{
			if(is_array($key))
			{
				foreach($key as $k)
				{
					self::set($k,null,"NOW - 1 YEAR");
				}
			}
			else
			{
				self::set($key,null,"NOW - 1 YEAR");
			}
		}

		public static function settings(array $options)
		{
			$SERVER_NAME = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost';

			if(isset($options['cookie_expire']) && !is_integer($options['cookie_expire']))
			{
				$options['cookie_expire'] = strtotime($options['cookie_expire']);
			}

			self::$settings['cookie_expire'] 			= isset($options['cookieExpire']) ? $options['cookieExpire'] : time()+2592000;
			self::$settings['cookie_path']				= isset($options['cookiePath']) ? $options['cookiePath'] : '/';
			self::$settings['cookie_domain']			= isset($options['cookieDomain']) ? $options['cookieDomain'] : $SERVER_NAME;
			self::$settings['cookie_secure']			= isset($options['cookieSecure']) ? (boolean) $options['cookieSecure'] : false;
			self::$settings['cookie_httponly']  		= isset($options['cookieHttponly']) ? (boolean) $options['cookieHttponly'] : false;
			self::$settings['json_decode_array']		= isset($options['jsonDecodeArray']) ? (boolean) $options['jsonDecodeArray'] : true;
			self::$settings['default_return']			= (array_key_exists('defaultReturn',$options)) ? $options['defaultReturn'] : 'ST__EXCEPTION';
		}
	}

?>