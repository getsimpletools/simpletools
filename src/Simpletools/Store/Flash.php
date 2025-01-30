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
 * @description		Flash Message Storage
 * @copyright  		Copyright (c) 2011 Marcin Rosinski. (http://www.getsimpletools.com/)
 * @license    		BSD
 *
 */

	namespace Simpletools\Store;

	/**
	* Flash Message Storage
	*/
	class Flash
	{
		/**
		* Simpletools\Store\Flash Session key internal namespace to prevent direct session overwrite
		*/
		protected static $_sessionKey 		= 'ST_FLASH_32896hdiuqwgd6723gdy23g.';

		/**
		* Cleaning process controller
		*/
		protected static $_cleanedPrev 		= 0;


		/**
		* Setting clearing message, when set to true clears the message straight after 1st get, useful when flash message is accessed within same request that it was set, defaults to false
		*/
		protected static $_readOnce 		= false;

		/**
		* Setting what to return if get can't find a message with a key, ST__EXCEPTION will throw an exception;
		*/
		protected static $_defaultReturn	= 'ST__EXCEPTION';

		/**
		* Global settings setter
		*
		* @param array $settings Settings to be used as default
		* @return null
		*/
		public static function settings($settings=array())
		{
			self::$_readOnce 		= isset($settings['readOnce']) ? (boolean) $settings['readOnce'] : false;
			self::$_defaultReturn 	= (array_key_exists('defaultReturn',$settings)) ? $settings['defaultReturn'] : 'ST__EXCEPTION';
		}

		/**
		* Flash old messages cleaner
		*
		* @return boolean
		*/
		protected static function _cleanPrevious()
		{
			if(self::$_cleanedPrev) return true;
			self::$_cleanedPrev = 1;

			if(isset($_SESSION) && is_array($_SESSION)) {
                foreach ($_SESSION as $key => $value) {
                    if (strpos($key, self::$_sessionKey) === 0) {
                        if ($_SESSION[$key][0] < 1) {
                            unset($_SESSION[$key]);
                        } else {
                            $_SESSION[$key][0] = 0;
                        }
                    }
                }
            }
		}

		/**
		* Existing messages reflusher allowing them to last till next session
		*
		* @param mixed $keys Settings to be used as default
		* @return null
		*/
		public static function reflush(mixed $keys=null)
		{
			self::_cleanPrevious();

			if($keys)
			{
				if(!is_array($keys))
				{
					$keys = array($keys);
				}

				$keys = array_flip($keys);
			}

			foreach($_SESSION as $key => $value)
			{
				if(strpos($key,self::$_sessionKey)===0)
				{
					if($keys)
					{
						$k = str_replace(self::$_sessionKey,'',$key);
						if(isset($keys[$k]))
						{
							$_SESSION[$key][0] = 1;
						}
					}
					else
					{
						$_SESSION[$key][0] = 1;
					}
				}
			}
		}

		/**
		* Message setter
		*
		* @param string $key Message key
		* @param mixed $value Message value
		* @param array $settings Message additional settings e.g. readOnce
		* @return null
		*/
		public static function set($key,$value,$settings=array())
		{
			self::_cleanPrevious();

			$settings['readOnce'] = isset($settings['readOnce']) ? (boolean) $settings['readOnce'] : self::$_readOnce;

			$_SESSION[self::$_sessionKey.$key] = array(1,$value,$settings);
		}

		/**
		* Message getter
		*
		* @param string $key Message key
		* @return mixed Returns message value
		*/
		public static function get($key)
		{
			self::_cleanPrevious();

			if(!isset($_SESSION[self::$_sessionKey.$key]) && self::$_defaultReturn=="ST__EXCEPTION")
			{
				throw new \Exception("Message with key: ".$key." doesn't exist",404);
			}

			$message = isset($_SESSION[self::$_sessionKey.$key]) ? $_SESSION[self::$_sessionKey.$key] : null;
			if(isset($message[2]['readOnce']) && $message[2]['readOnce'])
			{
				self::remove($key);
			}

			return $message ? $message[1] : self::$_defaultReturn;
		}

		/**
		* Message existence checker
		*
		* @param string $key Message key
		* @return boolean
		*/
		public static function is($key)
		{
			self::_cleanPrevious();

			return isset($_SESSION[self::$_sessionKey.$key]);
		}

		/**
		* Message unsetter
		*
		* @param string $key Message key
		* @return boolean Returns true on successful unset, false otherwise
		*/
		public static function remove($key)
		{
			self::_cleanPrevious();

			if(self::is($key))
			{
				unset($_SESSION[self::$_sessionKey.$key]);
				return true;
			}
			else
			{
				return false;
			}
		}
	}

?>