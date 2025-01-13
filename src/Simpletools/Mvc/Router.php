<?php
/**
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
	* MVC Router
	*/
	class Router extends \Simpletools\Mvc\Common
	{
		//anti dupliate content settings
		const NOSLASH_NOINDEX 			= 1;
		const SLASH_NOINDEX				= 2;
		const NOSLASH_INDEX				= 3;
		const SLASH_INDEX				= 4;

		private $_duplicate_content		= 0;

		//settings properties
		protected $_appDir 				= '';
		protected $_forwarded   		= false;
		protected $_params 				= '';
		protected $_autoRender 			= true;
		protected $_errorCode			= false;
		protected $_error				= null;
		protected $_classes    			= array();
		protected $_404_error_header 	= true;
		protected $_enableRouterHooks	= false;

		//instance holder
		private static $_instance		= null;
		protected $_shifts_params 		= array();

		//custom objects
		protected $_objects					= false;

		//routing namespaces
		protected $_routingNamespaces				= array();
		protected $_routingNamespacesMinLength 		= null;
		protected $_routingNamespacesMaxLength 		= null;

		protected $_activeRoutingNamespace			= '';
		protected $_activeRoutingNamespaceUrlPath 	= '';

		private $_settings 				= array(
			'defaultController' 					=> 'index',
			'defaultAction'							=> 'index',
			'uri_app_position' 						=> 0,
			'redirect_missing_location_to_default'	=> false,
			'404_error_header'						=> true,
			'overrideController'					=> false,
			'overrideAction'						=> 'index'
		);

		// Public as per default constructor
		public $_routingEvents						= false;
		// Public as per default constructor
		public $_current_controller					= '';

		//view object
		protected $_view			= '';
		protected $_view_enabled	= true;
		protected $_viewExtension   = 'phtml';
		protected $_contentTypeSent = false;

		protected $_httpMethod      = '';

		public function __construct(array $settings=[])
		{
			if(
					(isset($settings['applicationDir']) && ($settings['applicationDir']))
				OR
					(isset($settings['application_dir']) && ($settings['application_dir']))
			)
			{
				$this->_viewExtension										= isset($settings['view_extension']) ? $settings['view_extension'] : $this->_viewExtension;
                $this->_viewExtension										= isset($settings['viewExtension']) ? $settings['viewExtension'] : $this->_viewExtension;

                $this->_appDir 												= isset($settings['applicationDir']) ? rtrim($settings['applicationDir'],'/') : rtrim($settings['application_dir'],'/');

				$this->_settings['uri_app_position'] 						= isset($settings['uriAppPosition']) ? (integer) $settings['uriAppPosition'] : 0;
				$this->_settings['redirect_missing_location_to_default'] 	= isset($settings['redirect_missing_location_to_default']) ? (boolean) $settings['redirect_missing_location_to_default'] : false;
				$this->_settings['use_subdomain'] 							= isset($settings['use_subdomain']) ? $settings['use_subdomain'] : false;

				$this->_settings['overrideController'] 						= isset($settings['overrideController']) ? $settings['overrideController'] : false;
				$this->_settings['overrideAction'] 							= isset($settings['overrideAction']) ? $settings['overrideAction'] : $this->_settings['overrideAction'];

				$this->_404_error_header									= isset($settings['404_error_header']) ? (boolean) $settings['404_error_header'] : true;
				$this->_duplicate_content									= isset($settings['duplicate_content']) ? (int) $settings['duplicate_content'] : 0;

				$this->_routingEvents										= isset($settings['routingEvents']) ? (bool) $settings['routingEvents'] : false;

				$this->_settings['uriExtMime'] 							    = isset($settings['uriExtMime']) ? $settings['uriExtMime'] : [];
                $this->_settings['failoverView'] 						    = isset($settings['failoverView']) ? $settings['failoverView'] : false;
                $this->_settings['forcedView'] 						        = isset($settings['forcedView']) ? $settings['forcedView'] : false;

                $this->_httpMethod                                          = ($rMethod = strtoupper(@$_SERVER['REQUEST_METHOD'] ?? '')) ? $rMethod : false;

				if(isset($settings['routingNamespaces']))
				{
					$this->registerRoutingNamespaces($settings['routingNamespaces']);
				}

				if(isset($settings['customRoutes']))
				{
					$this->_customRoutes = array();
					$this->_addCustomRoutes($settings['customRoutes']);
				}

				$this->_params 	= $this->getParams(true);

				if(!$this->_contentTypeSent && isset($settings['defaultContentType']) && $settings['defaultContentType'])
                {
                    @header('Content-Type: '.$settings['defaultContentType']);
                }

                $this->_view = new \Simpletools\Mvc\View($this->_viewExtension);
				$this->_view->setParams($this->_params,$this->_shifts_params);

				new \Simpletools\Mvc\Model($this->_appDir,$this->_activeRoutingNamespace);
			}
			else
			{
				trigger_error("<br />You must to specify correct directory to application folder as an argument of SimpleMVC object constructor to be able to use SimpleMVC framework<br />", E_USER_ERROR);
			}
		}

		protected $_customRoutes 			= false;
		protected $_activeCustomRouteArgs	= false;
		protected $_httpMethods				= array(
			"OPTIONS"	=> "OPTIONS",
			"GET"		=> "GET",
			"HEAD"		=> "HEAD",
			"POST"		=> "POST",
			"PUT"		=> "PUT",
			"DELETE"	=> "DELETE",
			"TRACE"		=> "TRACE",
			"CONNECT"	=> "CONNECT",
		);

		protected function _addCustomRoutes($routes,$method='ANY')
		{
			foreach($routes as $route=>$invoke)
			{
				$httpMethod = isset($this->_httpMethods[$route]) ? $this->_httpMethods[$route] : false;

				if($httpMethod)
				{
					$this->_addCustomRoutes($invoke,$httpMethod);
				}
				else
				{
					$this->_customRoutes[$method][$route]	= $this->_parseCustomRoutes($route,$invoke);
				}
			}
		}

		/**
		* Custom routes parser
		*
		* Internal custom routes parser/compiler
		*
		* @param string $path Uri path to match against
		* @param array $invoke Action to perform if $path matches current URI.
		*
		* @return array Array of compiled custom routes
		*/
		protected function _parseCustomRoutes($path,$invoke)
		{
            $paramBased = true;
            if(strpos($path,'{')!==false)
            {
                preg_match_all('/\{(.*?)\}/', $path, $matches);
            }
            else
            {
                $paramBased = false;
                preg_match_all('/\:(.*?)\:/', $path, $matches);
            }

            if(isset($matches[0]))
            {
                $path = str_replace(array('\*','\^','\?'),array('.*','^','?'),preg_quote($path,'/'));

                $map = array();
                foreach($matches[0] as $index => $match)
                {
                    if($paramBased)
                        $path = str_replace(preg_quote($match),'([A-Za-z0-9\-_]*)',$path);
                    else
                        $path = str_replace(preg_quote($match),'([A-Za-z0-9\-_\/]*)',$path);

                    $map[] = $matches[1][$index];
                }
            }

            return array(
                'pattern'	=> '/'.$path.'$/',
                'map'		=> $map,
                'invoke'	=> $invoke
            );
		}

		public function registerRoutingNamespaces($namespaces)
		{
			$routingNamespacesMinLength = null;
			$routingNamespacesMaxLength = 0;

			foreach($namespaces as $namespace)
			{
				$namespace  = str_replace('/','\\',$namespace);
				$namespace 	= explode('\\',$namespace);

				$_namespace = array();
				foreach($namespace as $n)
				{
					$_namespace[] = self::getCorrectControllerName($n);
				}

				$_nsLength = count($_namespace);
				if(!$routingNamespacesMinLength OR $routingNamespacesMinLength>$_nsLength)
				{
					$routingNamespacesMinLength = $_nsLength;
				}

				if($routingNamespacesMaxLength<$_nsLength)
				{
					$routingNamespacesMaxLength = $_nsLength;
				}

				$this->_routingNamespaces[implode('\\',$_namespace)] = 1;
			}

			$this->_routingNamespacesMinLength = $routingNamespacesMinLength;
			$this->_routingNamespacesMaxLength = $routingNamespacesMaxLength;
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

		public static function &getInstance(array $settings=[])
		{
			 if (empty(self::$_instance))
			     self::$_instance = new \Simpletools\Mvc\Router($settings);
		     
		   	 return self::$_instance;
		}
		
		public static function &settings(array $settings)
		{
			 if(empty(self::$_instance)) 
			     self::$_instance = new \Simpletools\Mvc\Router($settings);
			 
			 return self::$_instance;
		}
		
		public function registerObject($objectName,$object)
		{
			$this->registerViewObject($objectName,$object);
		}
		
		public function registerViewObject($objectName,$object)
		{
			$this->_objects[$objectName] = &$object;
			$this->_view->registerObject($objectName,$object);
		}

		public static function &run($dir_emulate=false)
		{
			 self::getInstance()->dispatch($dir_emulate);
			 return self::$_instance;
		}

		//controller dispatcher
		public function &dispatch($dir_emulate=false)
		{
			if($this->_routingEvents)
			{
				\Simpletools\Events\Event::trigger('dispatchStart');
			}

			/*
			 * subdomain usage only
			 */
			if(
				isset($this->_settings['use_subdomain']) && 
				isset($this->_settings['use_subdomain']['controller']) &&
				isset($this->_settings['use_subdomain']['action'])
			)
			{
				$subdomain 		= $this->_getSubdomain();
				if($subdomain)
				{
					$old_action 		= $this->getParam('action');
					$old_controller		= $this->getParam('controller');

					$this->setParam('controller',$this->_settings['use_subdomain']['controller']);
					$this->setParam('action',$this->_settings['use_subdomain']['action']);
					$this->setParam('subdomain',$subdomain);
					$this->setParam('old_controller',$old_controller);
					$this->setParam('old_action',$old_action);
				}
			}

			$param = 2;

			if(
				($dir_emulate === true) && '/' != substr($_SERVER['REQUEST_URI'],-1) && 
				($this->getParam_($param) === null)
			)
			{
				$this->redirect($_SERVER['REQUEST_URI'],true);
			}
			
			if(!$this->_settings['overrideController'])
				$this->forwardDispatch($this->getParam('controller'),$this->getParam('action'));
			else
			{
				if(strtolower($this->_settings['overrideController'])==strtolower($this->getParam('controller')))
					$this->forwardDispatch($this->_settings['overrideController'],'self.reference');
				else
					$this->forwardDispatch($this->_settings['overrideController'],$this->_settings['overrideAction']);
			}
				
			return $this;
		}
		
		public function disable404OnError()
		{
			$this->_404_error_header = false;
		}
		
		public function enable404OnError()
		{
			$this->_404_error_header = true;
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
			$env->_routingEvents			= &$this->_routingEvents;

			$env->routingNamespaces				= &$this->_routingNamespaces;
			$env->activeRoutingNamespace 		= &$this->_activeRoutingNamespace;
			$env->activeRoutingNamespaceUrlPath = &$this->_activeRoutingNamespaceUrlPath;
			$env->httpMethod                    = &$this->_httpMethod;
			
			return $env;
		}

		private function _callReflectionMethod($controller, $methodName, array $args = array()) 
        { 
	    	$reflection = new \ReflectionMethod($controller, $methodName); 

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

	        return $reflection->invokeArgs($controller, $pass); 
	    } 
		
		private function forwardDispatch($controller,$action,$params=false)
		{
			$controller 		= self::getCorrectControllerName($controller);
			$action				= self::getCorrectActionName($action);

			$namespace 			= $this->_activeRoutingNamespace;

			$path = (!$namespace) ? $controller.'Controller.php' : str_replace('\\',DIRECTORY_SEPARATOR,$namespace).'/'.$controller.'Controller.php';
			$path = $this->_appDir.'/controllers/'.$path;

			if(!$this->_forwarded)
			{
				if(($_c = realpath($path)))
				{
					require_once($_c);
					$className = (!$namespace) ? $controller.'Controller' : $namespace."\\".$controller.'Controller';

					if(class_exists($className))
					{
						$this->_current_controller = $controller;

						$this->_classes[$className] = new $className($this->_getEnv());

						if($this->_routingEvents)
						{
							\Simpletools\Events\Event::trigger('beforeControllerInit',array('controller'=>$controller,'action'=>$action));
						}

						try {

						    if (!$this->_forwarded) {
                                if (is_callable(array($this->_classes[$className], 'init'))) {
                                    $this->_classes[$className]->init();
                                }

                                if ($this->_routingEvents) {
                                    \Simpletools\Events\Event::trigger('afterControllerInit',
                                        array('controller' => $controller, 'action' => $action));
                                }

                                if (!$this->_forwarded && $this->_autoRender) {
                                    $actionMethod = $action . 'Action';
                                    $this->_forwarded = true;

                                    /*
                                     * HTTP METHOD ACTION OVERLOAD
                                     */
                                    if($this->_httpMethod)
                                    {
                                        if (is_callable(array($this->_classes[$className], $actionMethod.'_'.$this->_httpMethod))) {
                                            $actionMethod = $actionMethod.'_'.$this->_httpMethod;
                                        }
                                    }

                                    //overloader

                                    if(!is_callable(array($this->_classes[$className], $actionMethod))) {

                                        if (
                                            is_callable(array($this->_classes[$className], '_'))
                                        ) {
                                            $actionMethod = '_';
                                        } elseif (
                                            $this->_httpMethod && is_callable(array($this->_classes[$className], '_'.$this->_httpMethod))
                                        ) {
                                            $actionMethod = '_'.$this->_httpMethod;
                                        }
                                    }

                                    if (is_callable(array($this->_classes[$className], $actionMethod))) {
                                        if ($this->_routingEvents) {
                                            \Simpletools\Events\Event::trigger('beforeControllerAction',
                                                array('controller' => $controller, 'action' => $action));
                                        }

                                        if ($this->_activeCustomRouteArgs) {
                                            $this->_callReflectionMethod($this->_classes[$className], $actionMethod,
                                                $this->_activeCustomRouteArgs);
                                        } else {
                                            $this->_classes[$className]->$actionMethod();
                                        }

                                        if ($this->_routingEvents) {
                                            \Simpletools\Events\Event::trigger('afterControllerAction',
                                                array('controller' => $controller, 'action' => $action));
                                        }
                                    } else {
                                        if ($this->_routingEvents) {
                                            \Simpletools\Events\Event::trigger('missingControllerActionError',
                                                array('controller' => $controller, 'action' => $action));
                                        }

                                        if ($this->_autoRender) {
                                            $this->error('a404');
                                        }
                                    }
                                }
                            }
                        }
                        catch(\Exception $e)
                        {
                            if($this->_routingEvents)
                            {
                                \Simpletools\Events\Event::trigger('controllerException',
                                    array('controller'=>$controller,'action'=>$action, 'exception'=>$e, 'e'=>$e)
                                );
                            }
                            else
                            {
                                throw $e;
                            }
                        }
												catch (\Error $e) {
													if($this->_routingEvents)
													{
														\Simpletools\Events\Event::trigger('controllerException',
																array('controller'=>$controller,'action'=>$action, 'exception'=>$e, 'e'=>$e)
														);
													}
													else
													{
														throw $e;
													}
												}

					}
					else
					{
						if($this->_routingEvents)
						{
							\Simpletools\Events\Event::trigger('missingControllerError',array('controller'=>$controller,'action'=>$action));
						}

						$this->error('c405');
					}

					if($this->_autoRender)
					{
						$this->_render($controller,$action);
					}
				}
				else
				{
					if($this->_routingEvents)
					{
						\Simpletools\Events\Event::trigger('missingControllerError',array('controller'=>$controller,'action'=>$action));
					}

					$this->error('c404');
				}
			}

			if($this->_routingEvents)
			{
				\Simpletools\Events\Event::trigger('dispatchEnd');
			}
		}

		public function forward($controller,string|null $action=null,$params=false)
		{
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

			if(!$action)
			{
				$action = $this->_settings['defaultAction'];
				$this->setParam('action',$this->_settings['defaultAction']);
			}

			$this->setParam('controller',$controller);
			$this->setParam('action',$action);

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
					
					if(is_callable(array($this->_classes[$className],'init')) && !$this->_forwarded)
					{
						if($this->_current_controller != $controller) 
						{
							$this->_classes[$className]->init();
							$this->_current_controller = $controller;
						}
					}

					$this->_forwarded = true;
					
					if($this->_autoRender)
					{
						$actionMethod = $action.'Action';
						
						if(is_callable(array($this->_classes[$className],$actionMethod)))
						{
							$this->_classes[$className]->$actionMethod();
						}
						elseif($className!='ErrorController') 
						{
							return $this->error('a404');
						}
						elseif($actionMethod=='errorAction')
						{
							throw new \Exception("Missing errorAction() under ErrorController", 1);
						}
						else
						{
							throw new \Exception("Missing correct error handling structure controller: $className, action: $actionMethod", 1);
						}
					}

					if($this->_autoRender)
					{
						$this->_render($orgController,$action);
					}

				}
				else
				{
					$this->error('c405');
				}
			}
			else
			{
				$this->error('c404');
			}
			
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
		
		protected function _render($controller,string|null $view=null)
		{
			if(!$this->_view_enabled) return;

			/**/
			$namespace 			= $this->_activeRoutingNamespace;

			if($this->_routingEvents)
			{
				\Simpletools\Events\Event::trigger('beforeRenderView',array('controller'=>$controller,'view'=>$view));
			}

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

			if($this->_settings['forcedView'])
            {
                $v = realpath($this->_appDir.'/views/'.$this->_settings['forcedView'].'.'.$this->_view->getViewExt());
            }
            else
            {
                $v = realpath($this->_appDir.'/views/'.$namespacePath.$controller.'/'.$view.'.'.$this->_view->getViewExt());
                if(!$v && $this->_settings['failoverView'])
                {
                    $v = realpath($this->_appDir.'/views/'.$this->_settings['failoverView'].'.'.$this->_view->getViewExt());
                }
            }

            if($v)
			{
				$this->_autoRender = false;
				$this->_view->render($v);

				if($this->_routingEvents)
				{
					\Simpletools\Events\Event::trigger('afterRenderView',array('controller'=>$controller,'view'=>$view));
				}
			}
			else
			{
				if($view != 'error')
				{
					if($this->_routingEvents)
					{
						\Simpletools\Events\Event::trigger('missingViewError',array('controller'=>$controller,'view'=>$view));
					}

					$this->error('v404');
				}
				else
				{
					if($this->_routingEvents)
					{
						\Simpletools\Events\Event::trigger('missingViewError',array('controller'=>$controller,'view'=>$view));
					}

					trigger_error("<u>SimpleMVC ERROR</u> - There is a missing Error View.", E_USER_ERROR);
					exit;
				}
			}
		}

		protected function _getSubdomain()
		{
			$domain = $_SERVER['SERVER_NAME'];
			$sub = trim(str_ireplace($this->_settings['use_subdomain']['domain'],'',$domain),'.');

			if(strtolower($sub) == 'www' || '')
				return null;

			return $sub;
		}

		public function isController($controller)
		{
			$controller 		= self::getCorrectControllerName($controller);
			$className			= $controller.'Controller';

			return (isset($this->_classes[$className]) || realpath($this->_appDir.'/controllers/'.$controller.'Controller.php'));
		}

		protected function _setExtMime($meta)
        {
            if(isset($meta['viewExtension']) && $meta['viewExtension'])
                $this->_viewExtension = $meta['viewExtension'];

            if(isset($meta['contentType']) && $meta['contentType'])
            {
                $this->_contentTypeSent = true;

                header('Content-Type: '.$meta['contentType']);
            }
        }

		public function getParams($fix_duplicate_content=false)
		{
			$_params = array();

			//to avoid notice errors if running from command line
			$SERVER_REQUEST_URI = isset($_SERVER['REQUEST_URI']) ? parse_url('http://simpletools.php'.$_SERVER['REQUEST_URI'],PHP_URL_PATH) : null;

			if(($p = stripos($SERVER_REQUEST_URI ?? '','#')) !== false)
				$SERVER_REQUEST_URI = substr($SERVER_REQUEST_URI,0,$p);

			$params = explode('/',rtrim(substr($SERVER_REQUEST_URI ?? '',1),'/'));

			//sanitasation
            $currentExt = '';
			foreach($params as $index => $param)
			{
//				$param = filter_var(urldecode($param),FILTER_SANITIZE_STRING,array('flags'=>FILTER_FLAG_STRIP_HIGH));
//                If tags required to be stripped. Is safer to use `strip_tags()`
//                Ref: https://www.php.net/manual/en/filter.filters.sanitize.php#118186
				$param = htmlspecialchars(urldecode($param));

				if($this->_settings['uriExtMime'])
                {
                    foreach($this->_settings['uriExtMime'] as $ext => $meta)
                    {
                        $paramsTmp = explode('.',$param);

                        if(count($paramsTmp)>1 && ($lastParam = array_pop($paramsTmp)) == $ext)
                        {
                            $param = implode('.',$paramsTmp);
                            $this->_setExtMime($meta);
                            $currentExt = $ext;
                            break;
                        }
                    }
                }

				$params[$index] = $param;
			}

            if($currentExt && strpos($SERVER_REQUEST_URI,'.')!==false)
            {
                $SERVER_REQUEST_URI = explode('.', $SERVER_REQUEST_URI);
                $ext = array_pop($SERVER_REQUEST_URI);

                if($ext != $currentExt)
                {
                    $SERVER_REQUEST_URI[] = $ext;
                }

                $SERVER_REQUEST_URI = implode('.', $SERVER_REQUEST_URI);
            }

			if($this->_settings['uri_app_position'] > 0)
			{
				for($i=0; $i<$this->_settings['uri_app_position']; $i++)
				{
					$this->_shifts_params[] = array_shift($params);
				}
			}

			if(count($this->_routingNamespaces))
			{
				$params_ = array();
				foreach($params as $param)
				{
					$params_[] = self::getCorrectControllerName($param);
				}

				$length  = count($params);
				$start   = $this->_routingNamespacesMinLength;
				$max 	 = $this->_routingNamespacesMaxLength;

				for($i=0;$i<=$length;$i++)
				{
					$position = $max-$i;

					$key = implode('\\',array_slice($params_,0,$position));

					if(isset($this->_routingNamespaces[$key]))
					{
						$this->_activeRoutingNamespace = $key;

						$this->_activeRoutingNamespaceUrlPath = implode('/',array_slice($params,0,$position));
						$params = array_slice($params,$position);

						break;
					}
				}

				unset($params_);
			}

			if($this->_customRoutes)
			{
				$METHOD = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';

				$routes = isset($this->_customRoutes[$METHOD]) ? $this->_customRoutes[$METHOD] : array();
				$routes = isset($this->_customRoutes['ANY']) ? array_merge($this->_customRoutes['ANY'],$routes) : $routes;

				foreach($routes as $route)
				{
					if(preg_match($route['pattern'], $SERVER_REQUEST_URI, $matches))
					{
						$invoke = explode('@',$route['invoke']);

						$invoke[0] 	= str_replace('Controller','',$invoke[0]);
						$invoke[1] 	= isset($invoke[1]) ? str_replace('Action','',$invoke[1]) : null;

						$controller = implode('-',preg_split('/(?<=\\w)(?=[A-Z])/', $invoke[0]));

						$_params['associative']['controller'] 	= $controller;
						$_params['associative']['action'] 		= isset($invoke[1]) ? $invoke[1] : $this->_settings['defaultAction'];

						array_shift($matches);

						foreach($matches as $i=>$m)
						{
							if(!isset($route['map'][$i])) continue;

							$this->_activeCustomRouteArgs[$route['map'][$i]] = $m;
						}

						return $_params;
					}
				}				
			}

			if(count($params))
			{
				$index = count($params)-1;
				if(($position = strpos($params[$index],'?')) !== false)
				{
					$params[$index] = substr($params[$index],0,$position);
					if($params[$index] == '') $params[$index] = null;
				}
				
				$controllerKey = 0; $actionKey = 1;
				
				$_params['associative']['controller'] = null;
				$_params['associative']['action'] = null;
				
				foreach($params as $key => $val)
				{
					$_params['number'][$key] = $val;
						
					if($key == $controllerKey)
					{
						if($val != null) $_params['associative']['controller'] =  $val;
					}
					else if($key == $actionKey)
					{					
						if($val != null) $_params['associative']['action'] =  $val;
					}
					else
					{
						if(!($key%2))
						{
							$prev_key = $val;
							$_params['associative'][$val] =  '';
							unset($val);
							continue;
						}
						else
						{
							if(isset($prev_key)) $_params['associative'][$prev_key] =  $val;
						}
					}
				}
			}
			
			if($fix_duplicate_content)
				$this->_fixDuplicateContent($_params,$SERVER_REQUEST_URI);
			
			if(!isset($_params['associative']['action']))
			{
				$_params['associative']['action'] = $this->_settings['defaultAction'];
			}
			
			if(!isset($_params['associative']['controller']))
			{
				$_params['associative']['controller'] = $this->_settings['defaultController'];
			}

			return $_params;	
		}
		
		private function _fixDuplicateContent($_params,$uri_path)
		{
			$GET = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
						
			switch($this->_duplicate_content)
			{
				//NOSLASH_NOINDEX
				case 1:
				{
					if(
						(!isset($_params['associative']['action']) && isset($_params['associative']['controller']) && $_params['associative']['controller'] == $this->_settings['defaultController'])
                        OR
						(isset($_params['associative']) && count($_params['associative']) == 2 && $_params['associative']['controller'] == $this->_settings['defaultController'] && $_params['associative']['action'] == $this->_settings['defaultAction'])
					){
						$this->redirect('/',false,false,301,$GET);
					}
					else if(isset($_params['associative']) && count($_params['associative']) == 2 && $_params['associative']['action'] == $this->_settings['defaultAction']) {
						$this->redirect('/'.$_params['associative']['controller'],false,false,301,$GET);
					}
					else if(strlen($uri_path) > 1 && substr($uri_path,-1) == '/'){
						$this->redirect($_params['associative'],false,false,301,$GET);
					}
					
					break;
				}
				
				//SLASH_NOINDEX
				case 2:
				{
					if(
						(!isset($_params['associative']['action']) && $_params['associative']['controller'] == $this->_settings['defaultController']) OR
						(count($_params['associative']) == 2 && $_params['associative']['controller'] == $this->_settings['defaultController'] && $_params['associative']['action'] == $this->_settings['defaultAction'])	
					){
						$this->redirect('/',false,false,301,$GET);
					}
					else if(count($_params['associative']) == 2 && $_params['associative']['action'] == $this->_settings['defaultAction']) {
						$this->redirect('/'.$_params['associative']['controller'].'/',false,false,301,$GET);
					}
					else if(strlen($uri_path) > 1 && substr($uri_path,-1) != '/'){
						$this->redirect($_params['associative'],true,false,301,$GET);
					}
					
					break;
				}
				
				//NOSLASH_INDEX
				case 3:
				{
					if(!isset($_params['associative']['controller'])){
						$this->redirect('/index/index',false,false,301,$GET);
					}
					else if(!isset($_params['associative']['action'])){
						$this->redirect('/'.$_params['associative']['controller'].'/index',false,false,301,$GET);
					}
					else if(strlen($uri_path) > 1 && substr($uri_path,-1) == '/'){
						$this->redirect($_params['associative'],false,false,301,$GET);
					}
					
					break;
				}
				
				//SLASH_INDEX
				case 4:
				{
					if(!isset($_params['associative']['controller'])){
						$this->redirect('/index/index/',false,false,301,$GET);
					}
					else if(!isset($_params['associative']['action'])){
						$this->redirect('/'.$_params['associative']['controller'].'/index/',false,false,301,$GET);
					}
					else if(strlen($uri_path) > 1 && substr($uri_path,-1) != '/'){
						$this->redirect($_params['associative'],true,false,301,$GET);
					}

					break;
				}
			}
		}

		public function setParam($id,$value)
		{
			$this->_params['associative'][$id] = $value;
		}

		public function getDisplayedParam($id)
		{
			$params = $this->getParams();
			return isset($params['associative'][$id]) ? (string) $params['associative'][$id] : null;
		}

		public function getDisplayedParam_($id)
		{
			$params = $this->getParams();
			return isset($params['number'][$id]) ? (string) $params['number'][$id] : null;
		}

		public function url(Array $urls, $slashEnd=false, $https=false, $absolute=false)
		{
			return $this->_view->url($urls, $absolute, $https, $slashEnd);	
		}

		public function isError404()
		{
			if($this->_errorCode !== false) return true;
			else return false;
		}

		public function setViewProperty($key,$value)
		{
			$this->_view->{$key} = $value;
		}

		public function __destruct()
		{
			if($this->_routingEvents)
			{
				\Simpletools\Events\Event::trigger('routerDestruct');
			}
		}
	}

?>