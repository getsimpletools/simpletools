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
 * @version    		Ver: 2.0.3 2014-11-21 18:29
 *
 */

	namespace Simpletools\Mvc;

	class Etc
	{
		public static function getCorrectActionName($action)
		{
			$action = strtolower($action);
			$action = str_replace(array('-','.'),' ',$action);
			$action = str_replace(' ','',ucwords($action));
			
			//$action = (string)(strtolower(substr($action,0,1)).substr($action,1));
			return lcfirst($action);
		}
		
		public static function getCorrectControllerName($controller)
		{
			$controller = strtolower($controller);
			$controller = str_replace(array('-','.'),' ',$controller);
			$controller = ucwords($controller);
			$controller = str_replace(' ','',$controller);
			
			return $controller;
		}

		public function isPost($id=false)
		{
			if(!$id)
			{
				return (isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) == 'POST') ? true : false;
			}
			else
			{
				return isset($_POST[$id]) ? $_POST[$id] : false;	
			}
		}
		
		public function isQuery($id=false)
		{
			if(!$id)
			{
				return (isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) == 'GET') ? true : false;
			}
			else
			{
				return isset($_GET[$id]) ? $_GET[$id] : false;	
			}
		}
		
		public function isRequest($id=false)
		{
			if(!$id)
			{
				return (isset($_SERVER['REQUEST_METHOD']) && (strtoupper($_SERVER['REQUEST_METHOD']) == 'GET' OR strtoupper($_SERVER['REQUEST_METHOD']) == 'POST')) ? true : false;
			}
			else
			{
				return isset($_REQUEST[$id]) ? $_REQUEST[$id] : false;
			}
		}
		
		//return $_GET method key=>value
		public function getQuery($id=null)
		{
			if($id==null) return $_GET;
			return isset($_GET[$id]) ? $_GET[$id] : null;	
		}
		
		//return $_POST method key=>value
		public function getPost($id=null)
		{
			if($id==null) return $_POST;
			return isset($_POST[$id]) ? $_POST[$id] : null;	
		}
		
		//return $_POST method key=>value
		public function getRequest($id=null)
		{
			if($id==null) return $_REQUEST;
			return isset($_REQUEST[$id]) ? $_REQUEST[$id] : null;	
		}


		public function returnParams($type)
		{
			return $this->_params[$type];
		}
		
		public function setParams(&$params,&$shifted_params)
		{
			$this->_params = &$params;
			$this->_shifted_params = &$shifted_params;
		}
		
		public function isParam($id)
		{
			return isset($this->_params['associative'][$id]) ? true : false;
		}
		
		public function getParam($id)
		{
			return isset($this->_params['associative'][$id]) ? $this->_params['associative'][$id] : null;
		}
		
		public function getParam_($id)
		{
			return isset($this->_params['number'][$id]) ? (string) $this->_params['number'][$id] : null;
		}
		
		public function isParam_($id)
		{
			return isset($this->_params['number'][$id]) ? true : false;
		}


		public function location($location,$type=302)
		{
			$this->redirect($location,false,false,$type);
		}
		
		public function redirect($params, $addslash=false, $protocol=false, $type=301, $HTTP_GET='')
		{
			$url	=	null;
			$https 	= 	isset($_SERVER['HTTPS']) ? true : false;
			
			if(is_array($params))
			{
				if($protocol)
				{
					$url = $protocol.'://'.$_SERVER['SERVER_NAME'];
				}
				else
				{
					if(!$https)
						$url = 'http://'.$_SERVER['SERVER_NAME'];
					else
						$url = 'https://'.$_SERVER['SERVER_NAME'];
				}
			
				$counter = 0;
				
				if(count($this->_shifts_params) > 0)
				{
					foreach($this->_shifts_params as $p)
					{
						$url .= '/'.$p;
					}
				}
				
				if(isset($params['controller']))
				{
					foreach($params as $key => $value)
					{
						if($key == 'controller' || $key == 'action')
						{
							$url .= '/'.$value;
						}
						else
						{
							$url .= '/'.$key.'/'.$value;
						}
					}
				}
				else
				{
					foreach($params as $value)
					{
						$url .= '/'.$value;
					}
				}
				
				$url = rtrim($url,'/');
				if($addslash) $url .= '/';
				
				if($HTTP_GET)
					$url .= '?'.$HTTP_GET;
				
				header('Location: '.$url,true,$type);
				exit;
			}
			else
			{
				if(stripos($params,'://') === false)
				{
					if(substr($params,0,1) == '/')
					{
						if(!$https && !$protocol)
							$url = 'http://'.$_SERVER['SERVER_NAME'].$params;
						else if($https && !$protocol)
							$url = 'https://'.$_SERVER['SERVER_NAME'].$params;
						else
							$url = $protocol.'://'.$_SERVER['SERVER_NAME'].$params;
					}
					else
					{
						$uri = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
						$uri .= '/';
						
						if(!$https && !$protocol)
							$url = 'http://'.$_SERVER['SERVER_NAME'].$uri.$params;
						else if($https && !$protocol)
							$url = 'https://'.$_SERVER['SERVER_NAME'].$uri.$params;
						else
							$url = $protocol.'://'.$_SERVER['SERVER_NAME'].$uri.$params;
					}
				}
				else
				{
					$url = $params;
				}
				
				if($addslash) $url .= '/';
				
				if($HTTP_GET)
					$url .= '?'.$HTTP_GET;
					
				header('Location: '.$url,true,$type);
				exit;
			}
		}
	}

?>