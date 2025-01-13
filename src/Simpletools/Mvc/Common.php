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
	* MVC Common Methods
	*/
    use \AllowDynamicProperties;
    #[\AllowDynamicProperties]
    class Common
	{
		/**
		* Initial Variable Values
		* @protected as per \Simpletools\Mvc\Controller $_instance initialisation
		*
		* As of PHP 8.2 you are no longer able create dynamic properties
		* @param array $_params Global params list to set on construction
		* @param array $_shifted_params Shifted global params list to set on construction
		*/
		protected $_params = [];
		protected $_shifted_params = [];
		
		/**
		* Action name normaliser
		*
		* @param string $action Action name to normalise
		* @return string Normalised action name
		*/
		public static function getCorrectActionName($action)
		{
			$action = strtolower($action);
			$action = str_replace(array('-','.'),' ',$action);
			$action = str_replace(' ','',ucwords($action));

			//$action = (string)(strtolower(substr($action,0,1)).substr($action,1));
			return lcfirst($action);
		}

		/**
		* Controller name normaliser
		*
		* @param string $controller Controller name to normalise
		* @return string Normalised controller name
		*/
		public static function getCorrectControllerName($controller)
		{
			$controller = strtolower($controller);
			$controller = str_replace(array('-','.'),' ',$controller);
			$controller = ucwords($controller);
			$controller = str_replace(' ','',$controller);

			return $controller;
		}

		/**
		* HTTP POST checker
		*
		* @param string $id POST key to check
		* @param int $filter Filter type - http://php.net/manual/en/filter.filters.sanitize.php
		* @param array $filterOptions Filter options
		* @return mixed boolean if no filter is set
		*/
		public function isPost($id=false,$filter=null,$filterOptions=array())
		{
			if(!$id)
			{
				return (isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) == 'POST') ? true : false;
			}
			else
			{
				if(isset($_POST[$id]))
				{
					return $filter ? filter_var($_POST[$id],$filter,$filterOptions) : true;
				}
				else
				{
					return false;
				}
			}
		}

		/**
		* HTTP GET checker
		*
		* @param string $id GET key to check
		* @param int $filter Filter type - http://php.net/manual/en/filter.filters.sanitize.php
		* @param array $filterOptions Filter options
		* @return mixed boolean if no filter is set
		*/
		public function isQuery($id=false,$filter=null,$filterOptions=array())
		{
			if(!$id)
			{
				return (isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) == 'GET') ? true : false;
			}
			else
			{
				if(isset($_GET[$id]))
				{
					return $filter ? filter_var($_GET[$id],$filter,$filterOptions) : true;
				}
				else
				{
					return false;
				}
			}
		}

		/**
		* HTTP REQUEST checker
		*
		* @param string $id REQUEST key to check
		* @param int $filter Filter type - http://php.net/manual/en/filter.filters.sanitize.php
		* @param array $filterOptions Filter options
		* @return mixed boolean if no filter is set
		*/
		public function isRequest($id=false,$filter=null,$filterOptions=array())
		{
			if(!$id)
			{
				return (isset($_SERVER['REQUEST_METHOD']) && (strtoupper($_SERVER['REQUEST_METHOD']) == 'GET' OR strtoupper($_SERVER['REQUEST_METHOD']) == 'POST')) ? true : false;
			}
			else
			{
				if(isset($_REQUEST[$id]))
				{
					return $filter ? filter_var($_REQUEST[$id],$filter,$filterOptions) : true;
				}
				else
				{
					return false;
				}
			}
		}

		/**
		* HTTP GET getter
		*
		* @param string $id GET key to return
		* @param int $sanitizeFilter Sanitize Filter type - http://php.net/manual/en/filter.filters.sanitize.php, defaults to FILTER_SANITIZE_SPECIAL_CHARS
		* @param array $sanitizeFilterOptions Sanitize Filter options
		* @return mixed GET value or values if $id is not provided
		*/
		public function getQuery($id=null,$sanitizeFilter=FILTER_SANITIZE_SPECIAL_CHARS,$sanitizeFilterOptions=array('flags'=>FILTER_FLAG_STRIP_HIGH))
		{
			if($id==null) return $_GET;

			if(isset($_GET[$id]))
			{
				if(is_array($_GET[$id]))
				{
					$RETURN = array();
					foreach($_GET[$id] as $index => $value)
					{
						$RETURN[$index] = filter_var($value,$sanitizeFilter,$sanitizeFilterOptions);
					}

					return $RETURN;
				}
				else
				{
					return filter_var($_GET[$id],$sanitizeFilter,$sanitizeFilterOptions);
				}
			}
			else
			{
				return null;
			}
		}

		/**
		* HTTP POST getter
		*
		* @param string $id POST key to return
		* @param int $sanitizeFilter Sanitize Filter type - http://php.net/manual/en/filter.filters.sanitize.php, defaults to FILTER_SANITIZE_SPECIAL_CHARS
		* @param array $sanitizeFilterOptions Sanitize Filter options
		* @return mixed POST value or values if $id is not provided
		*/
		public function getPost($id=null,$sanitizeFilter=FILTER_SANITIZE_SPECIAL_CHARS,$sanitizeFilterOptions=array('flags'=>FILTER_FLAG_STRIP_HIGH))
		{
			if($id==null) return $_POST;
			
			if(isset($_POST[$id]))
			{
				if(is_array($_POST[$id]))
				{
					$RETURN = array();
					foreach($_POST[$id] as $index => $value)
					{
						$RETURN[$index] = filter_var($value,$sanitizeFilter,$sanitizeFilterOptions);
					}

					return $RETURN;
				}
				else
				{
					return filter_var($_POST[$id],$sanitizeFilter,$sanitizeFilterOptions);
				}
			}
			else
			{
				return null;
			}
		}

		/**
		* HTTP REQUEST getter
		*
		* @param string $id REQUEST key to return
		* @param int $sanitizeFilter Sanitize Filter type - http://php.net/manual/en/filter.filters.sanitize.php, defaults to FILTER_SANITIZE_SPECIAL_CHARS
		* @param array $sanitizeFilterOptions Sanitize Filter options
		* @return mixed REQUEST value or values if $id is not provided
		*/
		public function getRequest($id=null,$sanitizeFilter=FILTER_SANITIZE_SPECIAL_CHARS,$sanitizeFilterOptions=array('flags'=>FILTER_FLAG_STRIP_HIGH))
		{
			if($id==null) return $_REQUEST;
			
			if(isset($_REQUEST[$id]))
			{
				if(is_array($_REQUEST[$id]))
				{
					$RETURN = array();
					foreach($_REQUEST[$id] as $index => $value)
					{
						$RETURN[$index] = filter_var($value,$sanitizeFilter,$sanitizeFilterOptions);
					}

					return $RETURN;
				}
				else
				{
					return filter_var($_REQUEST[$id],$sanitizeFilter,$sanitizeFilterOptions);
				}
			}
			else
			{
				return null;
			}
		}

		/**
		* Returns all params by type
		*
		* @param string $type Type of the params to return, accepted values - associative or number
		* @return array Returns all params a per specified type
		*/
		public function returnParams($type)
		{
			return isset($this->_params[$type]) ? $this->_params[$type] : array();
		}

		public function setParams(&$params,&$shifted_params)
		{
			$this->_params = &$params;
			$this->_shifted_params = &$shifted_params;
		}

		/**
		* Is URL Param by key name
		*
		* @param string $key Name of the requested param
		* @return boolean Returns true or false
		*
		*/
		public function isParam($key)
		{
			return isset($this->_params['associative'][$key]) ? true : false;
		}

		/**
		* Get URL Param by key name
		*
		* @param string $key Name of the requested param
		* @return mixed Returns requested param value or all params if key is not specified or null if key doesn't exist
		*
		*/
		public function getParam($key='')
		{
			if(!$key) return $this->_params['associative'];

			return isset($this->_params['associative'][$key]) ? (string) $this->_params['associative'][$key] : null;
		}

        public function getAction()
        {
            return $this->getParam('action');
        }

        public function getController()
        {
            return $this->getParam('controller');
        }

		/**
		* Get URL Param by index
		*
		* @param int $index Position of the param starting from 0
		* @return string Returns requested param value
		*
		* @deprecated 2.0.15 Use getParamByIndex() instead
		*/
		public function getParam_($index='')
		{
			if(!$index) return $this->_params['number'];

			return $this->getParamByIndex($index);
		}

		/**
		* Get URL Param by index
		*
		* @param int $index Position of the param starting from 0
		* @return string Returns requested param value
		*
		*/
		public function getParamByIndex($index='')
		{
			if(!$index) return $this->_params['number'];
			
			return isset($this->_params['number'][$index]) ? (string) $this->_params['number'][$index] : null;
		}

		/**
		* Is Param by index
		*
		* @param int $index Position of the param starting from 0
		* @return boolean Returns true or false
		*
		* @deprecated 2.0.15 Use isParamByIndex() instead
		*/
		public function isParam_($index)
		{
			return $this->isParamByIndex($index);
		}

		/**
		* Is Param by index
		*
		* @param int $index Position of the param starting from 0
		* @return boolean Returns true or false
		*
		*/
		public function isParamByIndex($index)
		{
			return isset($this->_params['number'][$index]) ? true : false;
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
				
				if(is_array($this->_shifts_params) && count($this->_shifts_params) > 0)
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