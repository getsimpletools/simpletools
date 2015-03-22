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
 *
 */

	namespace Simpletools\Mvc;

	/**
	* MVC Controller
	*/
	class Controller extends \Simpletools\Mvc\Common
	{			
		protected static $_instance = null;
				
		public function __construct($env)
		{
			$this->_appDir 					= &$env->appDir;
			$this->_view					= &$env->view;
			$this->_autoRender				= &$env->autoRender;
			$this->_forwarded  				= &$env->forwarded;
			$this->_params					= &$env->params;
			$this->_objects					= &$env->objects;
			$this->_errorCode				= &$env->errorCode;
			$this->_shifts_params			= &$env->shifts_params;
			$this->_classes					= &$env->classes;
			$this->_current_controller 		= &$env->current_controller;
			$this->_404_error_header		= &$env->_404_error_header;
			$this->_view_enabled			= &$env->view_enabled;
			$this->_routingHooks				= &$env->_routingHooks;

			$this->_routingNamespaces				= &$env->routingNamespaces;
			$this->_activeRoutingNamespace			= &$env->activeRoutingNamespace;
			$this->_activeRoutingNamespaceUrlPath	= &$env->activeRoutingNamespaceUrlPath;
			
			if(empty(self::$_instance)) 
			{
				self::$_instance = &$this;
			}
			
			if($this->_objects)
			{
				foreach($this->_objects as $objectName => &$object)
				{
					$this->{$objectName} = &$object;
				}
			}
		}
		
		public static function &getInstance($empty=null)
		{
			if (!empty(self::$_instance)) 
			{
				return self::$_instance;
			}
			else
			{
				throw new \Exception('Asking for instance before instance has been created. This method should be use after SimpleMVC::dispatch() only',123);
			}
		}
		
		public function setCommonObject($objectName,&$obj)
		{
			$this->{$objectName} = $obj;
		}
		
		public function render($controller,$view=null)
		{
			if(!$this->_view_enabled) return;

			$this->_autoRender = false;
			
			if($view === null) 
			{
				$view = $controller;
				$controller = self::getCorrectControllerName($this->getParam('controller'));
			}
			else if(
				stripos($controller,'.') !== false ||
				stripos($controller,'-') !== false ||
				stripos($controller,' ') !== false
			)
				$controller = self::getCorrectControllerName($controller);
			else
				$controller = ucfirst($controller);
			
			/**/
			$namespace 			= $this->_activeRoutingNamespace;

			$n = substr($controller,0,1);
			if($n=='\\' OR $n == '/')
			{
				$controller 		= trim(str_replace('/','\\',$controller),'\\');
				$_path 				= explode('\\',$controller);
				$controller 		= array_pop($_path);
				$namespace 			= implode('\\',$_path);
			}

			if($namespace)
			{
				$namespacePath 		= str_replace('\\', DIRECTORY_SEPARATOR, $namespace)."/";

				if(strtolower($view) == 'error')
				{
					$path = $this->_appDir.'/views/'.$namespacePath.$controller.'/'.$view.'.'.$this->_view->getViewExt();
					
					if(!realpath($path))
					{
						$namespacePath  = '';
					}
				}
			}
			else
			{
				$namespacePath 		= '';
			}
			
			$v 				= realpath($this->_appDir.'/views/'.$namespacePath.$controller.'/'.$view.'.'.$this->_view->getViewExt());
			
			if($v)
			{
				$this->_autoRender = false;
				$this->_view->render($v);
			}
			else
			{
				if($view != 'error')
				{
					$this->error('v404');
				}
				else
				{
					trigger_error("<u>SimpleMVC ERROR</u> - There is a missing Error View.", E_USER_ERROR);
					exit;
				}
			}
		}

		public function error($errorCode='v404')
		{
			$this->_autoRender = true;
			if($this->_404_error_header) header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found'); 
			$this->_errorCode 			= $errorCode;
			
			$namespace = $this->_activeRoutingNamespace;
			$path = (!$namespace) ? '' : str_replace('\\',DIRECTORY_SEPARATOR,$namespace).'/';
			$path = $this->_appDir. '/controllers/'.$path.'ErrorController.php';

			$className = $namespace.'\ErrorController';

			if($namespace && !($_c = realpath($path))) 
			{
				$namespace = '';
				$path = $this->_appDir.'/controllers/ErrorController.php';
				if(!($_c = realpath($path)))
				{
					trigger_error("<u>SimpleMVC ERROR</u> - Missing ErrorController.php", E_USER_ERROR);
				}
				else
				{
					$className = 'ErrorController';
				}
			}
			elseif(!$namespace)
			{
				$className 	= 'ErrorController';
				$path 		= $this->_appDir.'/controllers/ErrorController.php';

				if(!($_c = realpath($path)))
				{
					trigger_error("<u>SimpleMVC ERROR</u> - Missing ErrorController.php", E_USER_ERROR);
				}
			}
			
			$this->forward('Error','error');
		}

		private function &_getEnv()
		{
			$env 						= new \StdClass();
			$env->appDir				= &$this->_appDir;
			$env->view					= &$this->_view;
			$env->autoRender			= &$this->_autoRender;
			$env->forwarded				= &$this->_forwarded;
			$env->params				= &$this->_params;
			$env->objects				= &$this->_objects;
			$env->errorCode				= &$this->_errorCode;
			$env->shifts_params 		= &$this->_shifts_params;
			$env->classes				= &$this->_classes;
			$env->current_controller	= &$this->_current_controller;
			$env->_404_error_header		= &$this->_404_error_header;
			$env->view_enabled			= &$this->_view_enabled;

			$env->routingNamespaces				= &$this->_routingNamespaces;
			$env->activeRoutingNamespace 		= &$this->_activeRoutingNamespace;
			$env->activeRoutingNamespaceUrlPath = &$this->_activeRoutingNamespaceUrlPath;
			
			return $env;
		}
		
		public function forward($controller,$action=null,$params=false)
		{			
			$this->_forwarded = true;
			
			$incontroller=$controller;
			if($action){$inaction=$action;}
			else
			{
				$inaction=$controller;
				$incontroller=$this->getParam('controller');
			}
			
			if($action === null) 
			{
				if(
					stripos($controller,'.') !== false ||
					stripos($controller,'-') !== false ||
					stripos($controller,' ') !== false
				)
					$controller = self::getCorrectActionName($controller);
				else
					$controller = lcfirst($controller);
					
				$action = $controller;				
				$controller = self::getCorrectControllerName($this->getParam('controller'));
			}
			else 
			{	
				if(
					stripos($controller,'.') !== false ||
					stripos($controller,'-') !== false ||
					stripos($controller,' ') !== false
				)
					$controller = self::getCorrectControllerName($controller);
				else
					$controller = ucfirst($controller);
					
				if(
					stripos($action,'.') !== false ||
					stripos($action,'-') !== false ||
					stripos($action,' ') !== false
				)
					$action = self::getCorrectActionName($action);
				else
					$action = lcfirst($action);
			}
			
			$this->setNewParams($incontroller,$inaction,$params);
			
			$this->_autoRender = true;
			
			if($controller == 'error' || $action == 'error') 
				$this->_errorCode = 'custom error';
			
			$_c = false;

			$namespace 			= $this->_activeRoutingNamespace;
			$orgController		= $controller;

			$n = substr($controller,0,1);
			if($n=='\\' OR $n == '/')
			{
				$controller 		= trim(str_replace('/','\\',$controller),'\\');
				$_path 				= explode('\\',$controller);
				$controller 		= array_pop($_path);
				$namespace 			= implode('\\',$_path);
			}

			$className 			= (!$namespace) ? $controller.'Controller' : $namespace."\\".$controller.'Controller';

			if($namespace && strtolower($controller) == 'error')
			{
				$path = str_replace('\\',DIRECTORY_SEPARATOR,$namespace).'/'.$controller.'Controller.php';
				$path = $this->_appDir.'/controllers/'.$path;

				if(
					!isset($this->_classes[$className]) && 
					!($_c = realpath($path))
				)
				{
					$namespace 			= '';
					$className 			= $controller.'Controller';
				}
			}

			$path = (!$namespace) ? $controller.'Controller.php' : str_replace('\\',DIRECTORY_SEPARATOR,$namespace).'/'.$controller.'Controller.php';
			$path = $this->_appDir.'/controllers/'.$path;
			
			if(
				isset($this->_classes[$className]) || 
				($_c = realpath($path))
			)
			{
				if(!isset($this->_classes[$className]) && $_c)
				{
					require($_c);
				}

				if(class_exists($className))
				{
					if(!isset($this->_classes[$className]))
					{
						$this->_classes[$className] = new $className($this->_getEnv());
						$this->_forwarded = false;
					}

					if($this->_routingHooks)
					{
						\Simpletools\Mvc\RoutingHook::fire('beforeForwardController',array('controller'=>$controller,'action'=>$action));
					}

					if(is_callable(array($this->_classes[$className],'init')) && !$this->_forwarded)
					{
						if($this->_routingHooks)
						{
							\Simpletools\Mvc\RoutingHook::fire('beforeControllerInit',array('controller'=>$controller,'action'=>$action));
							\Simpletools\Mvc\RoutingHook::fire('beforeForwardControllerInit',array('controller'=>$controller,'action'=>$action));
						}

						if($this->_current_controller != $controller) 
						{
							$this->_classes[$className]->init();
							$this->_current_controller = $controller;
						}
						$this->_forwarded = true;

						if($this->_routingHooks)
						{
							\Simpletools\Mvc\RoutingHook::fire('afterForwardControllerInit',array('controller'=>$controller,'action'=>$action));
						}
					}

					if($this->_routingHooks)
					{
						\Simpletools\Mvc\RoutingHook::fire('afterForwardController',array('controller'=>$controller,'action'=>$action));
					}

					if($this->_autoRender)
					{
						$actionMethod = $action.'Action';

						if(is_callable(array($this->_classes[$className],$actionMethod)))
						{
							if($this->_routingHooks)
							{
								\Simpletools\Mvc\RoutingHook::fire('beforeControllerAction',array('controller'=>$controller,'action'=>$action));
							}

							$this->_classes[$className]->$actionMethod();

							if($this->_routingHooks)
							{
								\Simpletools\Mvc\RoutingHook::fire('afterControllerAction',array('controller'=>$controller,'action'=>$action));
							}
						}
						elseif($className!='ErrorController')
						{
							if($this->_routingHooks)
							{
								\Simpletools\Mvc\RoutingHook::fire('missingControllerActionError',array('controller'=>$controller,'action'=>$action));
							}

							return $this->error('a404');
						}
						elseif($actionMethod=='errorAction')
						{
							if($this->_routingHooks)
							{
								\Simpletools\Mvc\RoutingHook::fire('missingControllerActionError',array('controller'=>$controller,'action'=>$action));
							}

							throw new \Exception("Missing errorAction() under ErrorController", 1);
						}
						else
						{
							if($this->_routingHooks)
							{
								\Simpletools\Mvc\RoutingHook::fire('missingControllerError',array('controller'=>$controller,'action'=>$action));
							}

							throw new \Exception("Missing correct error handling structure", 1);
						}
					}

					if($this->_autoRender)
					{
						$this->_render($orgController,$action);
					}

				}
				else
				{
					if($this->_routingHooks)
					{
						\Simpletools\Mvc\RoutingHook::fire('missingControllerError',array('controller'=>$controller,'action'=>$action));
					}

					$this->error('c405');
				}
			}
			else
			{
				if($this->_routingHooks)
				{
					\Simpletools\Mvc\RoutingHook::fire('missingControllerError',array('controller'=>$controller,'action'=>$action));
				}

				$this->error('c404');
			}
		}

		public function setNewParams($controller,$action,$params)
		{
			unset($this->_params['number']);
			unset($this->_params['associative']);
			
			$this->_params['associative']['controller'] = $controller;
			$this->_params['associative']['action'] = $action;
			
			$this->_params['number'][]	= $controller;
			$this->_params['number'][]	= $action;

			if($params)
			{
				foreach($params as $key=>$value)
				{
					$this->_params['number'][] = $value;
					$this->_params['associative'][$key] = $value;
				}
			}
		}

		public function &view()
		{
			return $this->_view;
		}

		public function setViewProperty($key,$value)
		{
			$this->_view->{$key} = $value;
		}

		public function getActiveRoutingNamespaceDir()
		{
			return str_replace('\\',DIRECTORY_SEPARATOR,$this->_activeRoutingNamespace);
		}

		public function getActiveRoutingNamespaceUrlPath()
		{
			return '/'.$this->_activeRoutingNamespaceUrlPath;
		}

		public function getActiveRoutingNamespace($useDirectorySeparator=false)
		{
			return $this->_activeRoutingNamespace;
		}

		//views rendering function
		public function enableView()
		{
			$this->_view_enabled = true;
		}
		
		public function disableView()
		{
			$this->_view_enabled = false;
		}

		public function isAction($action=null)
		{
			if(!$action)
				$action	= self::getCorrectActionName($this->getParam('action')).'Action';
			else
				$action	= self::getCorrectActionName($action).'Action';
				
			return is_callable(array($this,$action));
		}
	}
		
?>