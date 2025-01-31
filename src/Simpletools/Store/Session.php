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

    class Session
	{
		private static $default_return                  = 'Exception';
		private static $settings = array(
			'autostart_if_session_cookie_set' 			=> false,
			'session_auto_start'						=> true,

            'handlerType'                               => "PHP",
            'onSessionIdRegenerate'                     => false,


            'maxLifeTime'                               => 0, //don't set, use php.ini defaults
		);
		private static $_sessionStarted					= false;
		private static $_regenerateSessionIdEverySec 	= 600;

		public static function register($id,$data)
		{
			self::_autoStart();

			if(!isset($_SESSION[$id]))
			{
				$_SESSION[$id] = $data;
			}
			else
			{
				return false;
			}
		}

		public static function set($id,$data)
		{
			self::_autoStart();

			$_SESSION[$id] = $data;
		}

		//depracated
		public static function replace($id,$data)
		{
			return self::set($id,$data);
		}

		public static function remove($id=false)
		{
			self::_autoStart();

			if($id)
			{
				unset($_SESSION[$id]);
			}
			else
			{
				self::destroy();
			}
		}

		public static function destroy($autoStart=true)
		{
            if(isset($_SESSION))
                session_destroy();

			//if(isset($_SESSION)) session_destroy();
            if($autoStart) self::_autoStart();
		}

		public static function get($id='')
		{
			self::_autoStart();

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

		public static function is($key)
		{
			self::_autoStart();

			return isset($_SESSION[$key]);
		}

		//depracted
		public static function load($id='')
		{
			return self::get($id);
		}

		public static function settings(array $options)
		{
            if(isset($options['sessionCookieParams']) && !is_array($options['sessionCookieParams']))
            {
                throw new \Exception("sessionCookieParams needs to be of an array type",400);
            }
            elseif(isset($options['sessionCookieParams']) && is_array($options['sessionCookieParams']))
            {
                session_set_cookie_params($options['sessionCookieParams']);
            }

			self::$settings['autostart_if_session_cookie_set'] 	= isset($options['autostartIfSessionCookieSet']) ? (boolean) $options['autostartIfSessionCookieSet'] : self::$settings['autostart_if_session_cookie_set'];
            self::$settings['maxLifeTime'] 	= isset($options['maxLifeTime']) ? (int) $options['maxLifeTime'] : self::$settings['maxLifeTime'];

            self::$settings['session_auto_start'] 	= isset($options['session_auto_start']) ? (boolean) $options['sessionAutoStart'] : self::$settings['session_auto_start'];
			self::$_regenerateSessionIdEverySec 	= isset($options['regenerateSessionIdEverySec']) ? (int) $options['regenerateSessionIdEverySec'] : self::$_regenerateSessionIdEverySec;

			if(isset($options['handler']) && $options['handler'] instanceof \SessionHandlerInterface)
			{
                if(is_a($options['handler'],'Simpletools\Db\Cassandra\SessionHandler'))
                    self::$settings['handlerType'] = 'Simpletools\Cassandra';

				session_set_save_handler($options['handler'], true);
			}

            if(isset($options['onSessionIdRegenerate']) && is_callable($options['onSessionIdRegenerate']))
            {
                self::$settings['onSessionIdRegenerate'] = $options['onSessionIdRegenerate'];
            }

            self::$default_return = (array_key_exists('defaultReturn',$options)) ? $options['defaultReturn'] : ((array_key_exists('default_return',$options)) ? $options['default_return'] : 'Exception');

            if(self::$settings['maxLifeTime'])
            {
                @ini_set('session.cookie_lifetime', self::$settings['maxLifeTime']);
                @ini_set('session.gc_maxlifetime', self::$settings['maxLifeTime']);
            }

            if(self::$settings['autostart_if_session_cookie_set'])
			{
				self::startIfSessionCookieSet();
			}
		}

        public static function hasStarted()
        {
            return self::$_sessionStarted;
        }

		public static function startIfSessionCookieSet()
        {
            if(isset($_COOKIE[session_name()]) && session_id() == ''){self::_autoStart();}
            return false;
        }

        public static function start(mixed $sessionId=null)
        {
            self::_autoStart($sessionId);
        }

		protected static function _autoStart(mixed $sessionId=null)
		{
			if(self::$_sessionStarted)
			{
				return;
			}

			if(!self::$_sessionStarted && session_id() == '')
			{
				if(self::$settings['session_auto_start']){
                    if (php_sapi_name() != "cli") {
                        if($sessionId) {session_id($sessionId);}

                        @session_start();
                    }
                    self::$_sessionStarted = true;
				}
				elseif(self::$settings['autostart_if_session_cookie_set'] && isset($_COOKIE[session_name()]) && session_id() == ''){if($sessionId) {session_id($sessionId);}@session_start();self::$_sessionStarted = true;}
				elseif(!self::$settings['autostart_if_session_cookie_set']){throw new \Exception('Please start session before using \Simpletools\Store\Session or set sessionAutoStart under ::settings() method.',11111);}
				else return;
			}
			else
			{
				self::$_sessionStarted = true;
			}

			if(self::$_regenerateSessionIdEverySec)
			{
                $now = time();

                if (!isset($_SESSION['__regenerateSessionIdEverySec']) or $_SESSION['__regenerateSessionIdEverySec'] < $now) {
                    if (isset($_SESSION['__regenerateSessionIdEverySec']) && $_SESSION['__regenerateSessionIdEverySec'] < $now) {
                        if (
                            self::$settings['handlerType'] == 'Simpletools\Cassandra' &&
                            method_exists('\Simpletools\Db\Cassandra\SessionHandler', 'regenerateSessionId')
                        ) {
                            \Simpletools\Db\Cassandra\SessionHandler::regenerateSessionId();
                        } else {
                            @session_regenerate_id(true);
                        }

                        if (self::$settings['onSessionIdRegenerate']) {
                            $method = self::$settings['onSessionIdRegenerate'];
                            $method(session_id());
                        }
                    }

                    $_SESSION['__regenerateSessionIdEverySec'] = time() + self::$_regenerateSessionIdEverySec;
                }
            }
		}
	}