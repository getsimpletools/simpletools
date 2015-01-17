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

	class Session
	{
		private static $default_return = 'exception';
		private static $settings = array(
				'cookie_expire' 					=> 2592000,
				'cookie_path'						=> '/',
				'cookie_secure' 					=> false,
				'cookie_httponly' 					=> false,
				'cookies_to_sessions'				=> false,
				'sessions_to_cookies'				=> false,
				'cookie_auto_serialize'				=> false,
				'autostart_if_session_cookie_set' 	=> false
		);

		const MASTER_COOKIE_SELF_SIGN 	= '::ST-MCookie';

		public static function register($id,$data,$cookie=false)
		{
			if(!isset(self::$settings['cookie_domain'])) self::$settings['cookie_domain'] = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : false;

			if(!$cookie && session_id() == '')
			{
				if(isset(self::$settings['session_auto_start'])){session_start();}
				elseif(!self::$settings['autostart_if_session_cookie_set']){throw new \Exception('Please start session before using SimpleSession or set session_auto_start in ::options method.',11111);}
				else return;
			}

			if($cookie === false)
			{
				if(!isset($_SESSION[$id])) 
				{
					$_SESSION[$id] = $data;					
				}
				else
				{
					return false;
				}
				
				if(self::$settings['sessions_to_cookies'])
				{
					if(self::$settings['sessions_to_cookies'] === true)
					{
						self::replaceCookie(self::MASTER_COOKIE_SELF_SIGN,json_encode(self::$cache));
						self::replaceCookie($id,$data);
					}
					else if(is_array(self::$settings['sessions_to_cookies']))
					{
						if(array_search($id,self::$settings['sessions_to_cookies']) !== false)
						{
							self::replaceCookie(self::MASTER_COOKIE_SELF_SIGN,json_encode(self::$cache));
							self::replaceCookie($id,$data);
						}
					}
				}
			}
			else
			{
				if(self::$settings['cookies_to_sessions']) 
				{
					if(!isset($_SESSION[$id])) 
					{
						$_SESSION[$id] = $data;
					}
				}
				
				if(!isset($_COOKIE[$id]))
				{	
					self::replaceCookie($id,$data);
				}
			}
		}
		
		private static function replaceCookie($id,$data,$expire=null)
		{
			
				if(self::$settings['cookie_auto_serialize'])
				{
					if(is_array($data) || is_object($data)){$data = json_encode($data);}
				}

				
				$expire = ($expire==null) ? time()+self::$settings['cookie_expire'] : $expire;
				
				
				setcookie(
							$id,
							$data,
							$expire,
							self::$settings['cookie_path'],
							self::$settings['cookie_domain'],
							self::$settings['cookie_secure'],
							self::$settings['cookie_httponly']
						);
		}

		public static function set($id,$data,$cookie=false)
		{
			return self::replace($id,$data,$cookie);
		}
		
		public static function replace($id,$data,$cookie=false)
		{
			if(!isset(self::$settings['cookie_domain'])) self::$settings['cookie_domain'] = $_SERVER['SERVER_NAME'];
			
			if(!$cookie && session_id() == '')
			{
				if(isset(self::$settings['session_auto_start'])){session_start();}
				elseif(!self::$settings['autostart_if_session_cookie_set']){throw new \Exception('Please start session before using SimpleSession or set session_auto_start in ::options method.',11111);}
				else return;
			}

			if($cookie === false)
			{
				$_SESSION[$id] = $data;
				
				if(self::$settings['sessions_to_cookies'])
				{
					if(self::$settings['sessions_to_cookies'] === true)
					{
						self::replaceCookie(self::MASTER_COOKIE_SELF_SIGN,json_encode(self::$cache));
						self::replaceCookie($id,$data);
					}
					else if(is_array(self::$settings['sessions_to_cookies']))
					{
						if(array_search($id,self::$settings['sessions_to_cookies']) !== false)
						{
							self::replaceCookie(self::MASTER_COOKIE_SELF_SIGN,json_encode(self::$cache));
							self::replaceCookie($id,$data);
						}
					}
				}
			}
			else
			{
				if(self::$settings['cookies_to_sessions']) $_SESSION[$id] = $data;
				
				self::replaceCookie($id,$data);
			}
		}
		
		public static function remove($id=false)
		{
			if($id)
			{
				unset($_SESSION[$id]);
			}
			else
			{
				self::destroy();

				/*
				foreach(SimpleSession::load(self::MASTER_SESSION_SELF_SIGN) as $session)
				{
					unset($_SESSION[$session]);
				}
				
				unset($_SESSION[self::MASTER_SESSION_SELF_SIGN]);
				*/
			}
		}
		
		public static function destroy($autoStart=true)
		{
			if(isset($_SESSION)) session_destroy();
			if($autoStart) session_start();
		}
		
		public static function destroyCookie($id=null)
		{
			if($id == null)
			{
				foreach($_COOKIE as $c => $v)
				{
					self::replaceCookie($c,null,1985);
				}
			}
			else
			{
				self::replaceCookie($id,null,1985);
			}
		}
		

		//depracated
		public static function getInternalKey($id=null)
		{
			if($id!=null)
			{
				return $id;
			}
			else
			{
				return;
			}
		}

		public static function get($id='',$cookie=false)
		{
			return self::load($id,$cookie);
		}
		
		public static function load($id='',$cookie=false)
		{
			if(!$cookie)
			{
				if(!$id) return $_SESSION;

				if(isset($_SESSION[$id]))
				{
					return $_SESSION[$id];
				}
				else
				{
					if(self::$default_return == 'Exception')
					{
						throw new \Exception('Couldn\'t return id: '.$id.' because it hasn\'t been set',12345);
					}
					else
					{
						return self::$default_return;
					}
				}
			}
			else
			{
				if(!$id) return $_COOKIE;

				if(isset($_COOKIE[$id]))
				{
					if(self::$settings['cookie_auto_serialize'] || $id == self::MASTER_COOKIE_SELF_SIGN)
					{
						return json_decode($_COOKIE[$id]);
					}
					else
					{
						return $_COOKIE[$id];
					}
				}
				else
				{
					if(self::$default_return == 'Exception')
					{
						throw new \Exception('Couldn\'t return id: '.$id.' because it hasn\'t been set',12345);
					}
					else
					{
						return self::$default_return;
					}
				}
			}
		}
		
		public static function settings(array $options)
		{
			self::$settings['autostart_if_session_cookie_set'] 	= isset($options['autostart_if_session_cookie_set']) ? (boolean) $options['autostart_if_session_cookie_set'] : false;
			self::$settings['cookies_to_sessions'] 				= isset($options['cookies_to_sessions']) ? (boolean) $options['cookies_to_sessions'] : false;
			self::$settings['sessions_to_cookies'] 				= isset($options['sessions_to_cookies']) ? $options['sessions_to_cookies'] : false;

			if(self::$settings['autostart_if_session_cookie_set'])
			{
				if(isset($_COOKIE[session_name()]) && session_id() == '') session_start();
			}
			
			$SERVER_NAME = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost';
			
			self::$settings['cookie_expire'] 		= isset($options['cookie_expire']) ? (integer) $options['cookie_expire'] : 2592000;
			self::$settings['cookie_path']			= isset($options['cookie_path']) ? $options['cookie_path'] : '/';
			self::$settings['cookie_domain']		= isset($options['cookie_domain']) ? $options['cookie_domain'] : $SERVER_NAME;
			self::$settings['cookie_secure']		= isset($options['cookie_secure']) ? (boolean) $options['cookie_secure'] : false;
			self::$settings['cookie_httponly']  	= isset($options['cookie_httponly']) ? (boolean) $options['cookie_httponly'] : false;
			self::$settings['cookie_auto_serialize']= isset($options['cookie_auto_serialize']) ? (boolean) $options['cookie_auto_serialize'] : false;
			
			self::$default_return = (array_key_exists('defaultReturn',$options)) ? $options['defaultReturn'] : ((array_key_exists('default_return',$options)) ? $options['default_return'] : 'Exception');
			
			if(self::$settings['cookies_to_sessions'])
			{
				self::$cache = (self::load(self::MASTER_COOKIE_SELF_SIGN,true) != self::$default_return) ? self::load(self::MASTER_COOKIE_SELF_SIGN,true) : array();
				
				if(count(self::$cache) > 0)
				{
					foreach(self::$cache as $cookie)
					{
						if(isset($_COOKIE[$cookie])) self::register($cookie,self::load($cookie,true));
					}
				}
				
				if(is_array(self::$settings['sessions_to_cookies']))
				{
					foreach(self::$settings['sessions_to_cookies'] as $id)
					{
						if(array_search($id,self::$cache) !== false)
						{
							self::replaceCookie($id,self::load($id));
							continue;
						}
						
						self::$cache[] = $id; 
						self::replaceCookie($id,self::load($id));
					}
					
					self::replaceCookie(self::MASTER_COOKIE_SELF_SIGN,json_encode(self::$cache));
				}
			}
		}
	}

?>