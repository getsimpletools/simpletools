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
 * @version    		Ver: 2.0.8 2014-11-22 17:20
 *
 */

	namespace Simpletools\Mvc;

	class Controller extends \Simpletools\Mvc\Etc
	{			
		protected static $_instance = null;
				
		public function __construct($env)
		{
			$this->_appDir 				= &$env->appDir;
			$this->_view				= &$env->view;
			$this->_autoRender			= &$env->autoRender;
			$this->_forwarded  			= &$env->forwarded;
			$this->_params				= &$env->params;
			$this->_objects				= &$env->objects;
			$this->_errorCode			= &$env->errorCode;
			$this->_shifts_params		= &$env->shifts_params;
			$this->_classes				= &$env->classes;
			$this->_current_controller 	= &$env->current_controller;
			$this->_404_error_header	= &$env->_404_error_header;
			$this->_view_enabled		= &$env->view_enabled;

			$this->_routingNamespaces				= &$env->routingNamespaces;
			$this->_activeRoutingNamespace			= &$env->activeRoutingNamespace;
			$this->_activeRoutingNamespaceUrlPath	= &$env->activeRoutingNamespaceUrlPath;
			
			if (empty(self::$_instance)) 
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
			$this->_autoRender = false;
			
			if($view === null) 
			{
				$view = $controller;
				$controller = \Simpletools\Mvc\Tools::getCorrectControllerName($this->getParam('controller'));
			}
			else if(
				stripos($controller,'.') !== false ||
				stripos($controller,'-') !== false ||
				stripos($controller,' ') !== false
			)
				$controller = \Simpletools\Mvc\Tools::getCorrectControllerName($controller);
			else
				$controller = ucfirst($controller);
			
			parent::_render($controller,$view);
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
					$controller = \Simpletools\Mvc\Tools::getCorrectActionName($controller);
				else
					$controller = lcfirst($controller);
					
				$action = $controller;				
				$controller = \Simpletools\Mvc\Tools::getCorrectControllerName($this->getParam('controller'));
			}
			else 
			{	
				if(
					stripos($controller,'.') !== false ||
					stripos($controller,'-') !== false ||
					stripos($controller,' ') !== false
				)
					$controller = \Simpletools\Mvc\Tools::getCorrectControllerName($controller);
				else
					$controller = ucfirst($controller);
					
				if(
					stripos($action,'.') !== false ||
					stripos($action,'-') !== false ||
					stripos($action,' ') !== false
				)
					$action = \Simpletools\Mvc\Tools::getCorrectActionName($action);
				else
					$action = lcfirst($action);
			}
			
			$this->setNewParams($incontroller,$inaction,$params);
			
			parent::forward($controller,$action);
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
	}
		
?>