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
	namespace Simpletools\Http;
	
	use Simpletools\Http\Ssl;

	class Ssl
	{
		private $_loop	  	  			= false;
		protected static $_instance		= null;
		
		public static function &getInstance()
		{
			 if (empty(self::$_instance)) 
			 {
			     self::$_instance = new Ssl();
		     }
		     
		   	 return self::$_instance;					
		}
				
		public function https($flag=true,$redirectionMethod=301)
		{
			if(strtolower($flag) !== 'allowed')
			{
				$url = $_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
				
				if(isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) 
				{
					$https = ($_SERVER['HTTP_X_FORWARDED_PROTO']=='https') ? true : false;
				}
				else
				{
					$https = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']!='off') ? true : false;
				}
				
				if(!$this->_loop && $flag)
				{
					$this->_loop = 'https';
				}
				else if(!$this->_loop)
				{
					$this->_loop = 'http';
				}
				
				if($flag && (!$https))
				{
					if($this->_loop != 'http')
					{
						$url = 'https://'.$url;
						header('Location: '.$url,true,$redirectionMethod);
						exit;
					}
				}
				else if(!$flag && $https)
				{
					if($this->_loop != 'https')
					{
						$url = 'http://'.$url;
						header('Location: '.$url,true,$redirectionMethod);
						exit;
					}
				}
			}
		}		
	}
	
?>