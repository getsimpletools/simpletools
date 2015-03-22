<?php
/*
 * Simpletools Framework.
 * Copyright (c) 2009, Marcin Rosinski. (https://www.getsimpletools.com/)
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
 * @description		MVC framework
 * @copyright  		Copyright (c) 2009 Marcin Rosinski. (https://www.getsimpletools.com/)
 * @license    		(BSD)
 * @version    		Ver: 2.0.13 2014-11-30 11:19
 *
 */

	namespace Simpletools\Mvc;

	/**
	* Hook
	*/
	class RoutingHook
	{
		protected static $_listeners = array();
		protected static $_env = array();

		public static function setEnv($env)
		{
			self::$_env['routingNamespace'] = isset($env['routingNamespace']) ? $env['routingNamespace'] : null;
			self::$_env['router'] 			= isset($env['router']) ? $env['router'] : null;
		}

		public static function on($event,$handler)
		{
			self::$_listeners[$event][] = $handler;
		}

		private static function _callReflection($callable, array $args = array()) 
        {
        	$args['routingNamespace'] 	= self::$_env['routingNamespace'];
        	$args['router'] 			= self::$_env['router'];

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
			if(isset(self::$_listeners[$event]))
			{
				foreach(self::$_listeners[$event] as $handler)
				{
					if(is_callable($handler))
					{
						$res = self::_callReflection($handler,$args);
						if($res===false)
						{
							break;
						}
					}
				}
			}
		}
	}
?>