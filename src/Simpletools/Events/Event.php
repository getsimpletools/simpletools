<?php
/*
 * Simpletools Framework.
 * Copyright (c) 2009, Marcin Rosinski. (http://www.getsimpletools.com)
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
 * @description		Secure connections handler
 * @copyright  		Copyright (c) 2009 Marcin Rosinski. (http://www.getsimpletools.com)
 * @license    		http://www.opensource.org/licenses/bsd-license.php - BSD
 *
 */
	namespace Simpletools\Events;

	class Event
	{
		protected static $_listeners = array();

		public static function on($event,$handler)
		{
			self::$_listeners[$event]	= $handler;
		}

		public static function off($event)
		{
			unset(self::$_listeners[$event]);
		}

		private static function _callReflection($callable, array $args = array()) 
        {
        	if(is_array($callable))
	    	{
			    $reflection 	= new \ReflectionMethod($callable[0], $callable[1]);
			}
			elseif(is_string($callable))
			{
			    $reflection 	= new \ReflectionFunction($callable);
			}
			elseif(is_a($callable, 'Closure') || is_callable($callable, '__invoke')) 
			{
			    $objReflector 	= new \ReflectionObject($callable);
			    $reflection    	= $objReflector->getMethod('__invoke');
			}

	        $pass = array();
	        foreach($reflection->getParameters() as $param)
	        {
	        	$name = $param->getName();
	        	if(isset($args[$name]))
	        	{
	        		$pass[] = $args[$name];
	        	}
	        	else
	        	{
	        		try
	        		{
	          			$pass[] = $param->getDefaultValue();
	          		}
	          		catch(\Exception $e)
	          		{
	          			$pass[] = null;
	          		}
	          	}
	        }

	        return $reflection->invokeArgs($callable, $pass); 
	    }

	    public static function fire($event,$args=array())
		{
			self::trigger($event,$args);
		}

		public static function trigger($event,$args=array())
		{
			if(isset(self::$_listeners[$event]) && is_callable(self::$_listeners[$event]))
			{
				self::_callReflection(self::$_listeners[$event],$args);
			}
		}

		public static function triggerQueue($event)
		{
			if(isset(self::$_queue[$event]) && is_array(self::$_queue[$event]))
			{
				foreach(self::$_queue[$event] as $id => $args)
				{
					$r = self::trigger($event,$args);
					if($r===false) 
					{
						return $id;
					}
				}
			}

			return true;
		}

		public static function fireQueue($event)
		{
			return self::triggerQueue($event);
		}

		protected static $_queue = array();

		public static function queue($event,$args=array())
		{
			$id = uniqid();
			self::$_queue[$event][$id] = $args;

			return $id;
		}

		public static function unqueue($event,string|null $id=null)
		{
			if($id)
			{
				unset(self::$_queue[$event][$id]);
			}
			else
			{
				self::$_queue[$event] = array();
			}
		}
	}

?>