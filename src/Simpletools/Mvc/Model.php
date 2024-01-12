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
	* MVC Model
	*/
	class Model
	{
		protected $_appDir 		= '';
		private static $_instance = null;
		protected $_activeRoutingNamespace = '';
		public $objects = array();
		public function __construct($appDir,$_activeRoutingNamespace='')
		{
			self::$_instance 							= $this;
			self::$_instance->_appDir 					= $appDir;
			self::$_instance->objects 					= array();
			self::$_instance->_activeRoutingNamespace 	= $_activeRoutingNamespace;
		}
		
		public static function settings(array $settings)
		{
			if(empty(self::$_instance)) 
				new \Simpletools\Mvc\Model($settings['application_dir']);
		}
		
		public static function getModelDir()
		{
			return self::$_instance->_appDir;
		}

		public static function setActiveRoutingNamespace($ns)
		{
			$ns = str_replace('/','\\',$ns);
			self::$_instance->_activeRoutingNamespace = $ns;
		}

		public static function __callStatic($name, $args): mixed
		{
			array_unshift($args, $name);
			return call_user_func_array(array('self','getInstance'), $args);
		}

		public static function of($model)
		{
			$args = func_get_args();
			$callable = self::class . '::getInstance';
			return call_user_func_array($callable, $args);
		}
		
		public static function getInstance($model)
		{
			$initArgs = func_get_args();
			array_shift($initArgs);

			if(!isset(self::$_instance))
				throw new \Exception('\Simpletools\Mvc\Model: There are no settings provided.');
			
			if(!isset($model) OR $model == '')
				throw new \Exception('\Simpletools\Mvc\Model: Model name required.');

			//namespacing
			//$model = str_replace('/','\\',$model);

			$namespace 			= self::$_instance->_activeRoutingNamespace;
			$orgModel			= $model;

			$n = substr($model,0,1);
			if($n=='\\' OR $n == '/')
			{
				$model 			= trim(str_replace('/','\\',$model),'\\');
				$_path 			= explode('\\',$model);
				$model 			= array_pop($_path);
				$namespace 		= implode('\\',$_path);
			}

			$class 			= (!$namespace) ? $model.'Model' : $namespace."\\".$model.'Model';

			if(!isset(self::$_instance->objects[$class]))
			{	
				if(!class_exists($class)) 
				{
					$p = str_replace('\\',DIRECTORY_SEPARATOR,$namespace).'/'.$model.'Model.php';
					$p = realpath(self::$_instance->_appDir.'/models/'.$p);

					if($p===false)
					{
						$p = str_replace('\\',DIRECTORY_SEPARATOR,$namespace).'/'.$model.'.php';
						$p = realpath(self::$_instance->_appDir.'/models/'.$p);

						if($p===false)
						{
							throw new \Exception('SimpleModel: Couldn\'t find '.$model.' model in '.self::$_instance->_appDir.'/models/');
						}
					}	

					require($p);
				}
				
				$obj = new $class();

				if(is_callable(array($obj,'injectDependency')))
				{
					$obj->injectDependency();
				}
				
				if(is_callable(array($obj,'init')))
				{
					call_user_func_array(array($obj,'init'),$initArgs);
				}

				if(is_callable(array($obj,'setActiveRoutingNamespace')))
				{
					$obj->setActiveRoutingNamespace(self::$_instance->_activeRoutingNamespace);
				}

				self::$_instance->objects[$class] = $obj;
			}
			
			return self::$_instance->objects[$class];
		}
	}

?>