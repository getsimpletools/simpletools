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

	class View extends \Simpletools\Mvc\Etc
	{	
		protected $_shifted_params 	= 0;
		protected static $_instance 	= null;
		protected $_view_ext			= 'phtml';
				
		public function __construct($view_extension='phtml')
		{
			$this->_view_ext = $view_extension;
			
			if (empty(self::$_instance)) 
			{
				self::$_instance = &$this;
			}
		}
		
		public function getViewExt()
		{
			return $this->_view_ext;
		}
		
		public static function &getInstance()
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
		
		public function registerObject($objectName,&$object)
		{
			$this->{$objectName} = &$object;
		}
		
		public function render($dir)
		{
			require($dir);
		}
		
		public function url(Array $urls, $absolute=false, $https=false, $slashEnd=false)
		{
			$counter = 0;
			$url = null;
			
			if(is_array($this->_shifted_params) && count($this->_shifted_params))
			{
				foreach($this->_shifted_params as $p)
				{
					$url .= '/'.$p;
				}
			}
			
			if(isset($urls['controller']))
			{
				foreach($urls as $key => $value)
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
				foreach($urls as $value)
				{
					
					$url .= '/'.$value;
					
				}
			}
			
			if($absolute)
			{
				if($https)
				{
					$protocol = 'https://';
				}
				else
				{
					$protocol = 'http://';
				}
				
				$url = $protocol.$_SERVER['SERVER_NAME'].$url;
			}
			
			if($slashEnd)
			{
				$url .= '/';
			}
			
			return $url;
		}
	}
	
	
?>