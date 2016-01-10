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

namespace Simpletools\Http\Multi;

use Simpletools\Http\Multi\Request;

	class Request
	{
		private $_requested_url 	= null;
		private $_effective_url 	= null;
		private $_retries 			= 0;
		private $_my_data			= null;
		private $_headers			= null;
		private $_body				= null;
		private $_settings			= array(
			CURLOPT_RETURNTRANSFER 	=> true,
			CURLOPT_CONNECTTIMEOUT	=> 10,
			CURLOPT_TIMEOUT			=> 30,
			CURLOPT_USERAGENT		=> '',
			CURLOPT_FOLLOWLOCATION	=> true,
			CURLOPT_MAXREDIRS		=> 3
		);
		
		public function __construct($link,$data=null)
		{
			$this->__requested_url 	= str_replace(' ', '%20', $link);
			$this->__my_data 		= $data;
		}
		
		public function settings($settings)
		{
			/*
			$this->__settings[CURLOPT_RETURNTRANSFER] 	= isset($settings[CURLOPT_RETURNTRANSFER]) ? $settings[CURLOPT_RETURNTRANSFER] : $this->__settings[CURLOPT_RETURNTRANSFER];
			$this->__settings[CURLOPT_FILE] 			= isset($settings[CURLOPT_FILE]) ? $settings[CURLOPT_FILE] : $this->__settings[CURLOPT_FILE];
			$this->__settings[CURLOPT_POST]				= isset($settings[CURLOPT_POST]) ? $settings[CURLOPT_POST] : false;
			$this->__settings[CURLOPT_POSTFIELDS]		= isset($settings[CURLOPT_POSTFIELDS]) ? $settings[CURLOPT_POSTFIELDS] : null;
			$this->__settings[CURLOPT_USERAGENT]		= isset($settings[CURLOPT_USERAGENT]) ? $settings[CURLOPT_USERAGENT] : 'SimpleTools/SimpleMCurl 0.1';
			$this->__settings[CURLOPT_HTTPHEADER]		= isset($settings[CURLOPT_HTTPHEADER]) ? $settings[CURLOPT_HTTPHEADER] : array();
			$this->__settings[CURLOPT_COOKIE]			= isset($settings[CURLOPT_COOKIE]) ? $settings[CURLOPT_COOKIE] : null;
			$this->__settings[CURLOPT_SSL_VERIFYPEER]	= isset($settings[CURLOPT_SSL_VERIFYPEER]) ? $settings[CURLOPT_SSL_VERIFYPEER] : true;
			
			$this->__settings[CURLOPT_CONNECTTIMEOUT]	= isset($settings[CURLOPT_CONNECTTIMEOUT]) ? $settings[CURLOPT_CONNECTTIMEOUT] : false;
			$this->__settings[CURLOPT_TIMEOUT]			= isset($settings[CURLOPT_TIMEOUT]) ? $settings[CURLOPT_TIMEOUT] : false;
			*/
			
			foreach($settings as $k => $s){$this->_settings[$k] = $s;}
		}
		
		public function getSettings($name=null)
		{
			if(!$name) return $this->_settings;
			
			return isset($this->_settings[$name]) ? $this->_settings[$name] : null;
		}
		
		public function getMyData()
		{
			return $this->_my_data;
		}
		
		public function &setEffectiveUrl($url)
		{
			$this->_effective_url = $url;
			return $this;
		}
		
		public function getRequestedUrl()
		{
			return $this->_requested_url;
		}
		
		public function getEffectiveUrl()
		{
			return $this->_effective_url;
		}
		
		public function getRetries()
		{
			return $this->_retries;
		}
		
		public function increaseRetries()
		{
			return ++$this->_retries;
		}
		
		public function &setHeaders($headers)
		{
			$this->_headers = $headers;
			return $this;
		}
		
		public function getHeaders()
		{
			return $this->_headers;
		}
		
		public function &setBody($body)
		{
			$this->_body = $body;
			return $this;
		}
		
		public function getBody()
		{
			return $this->_body;
		}
	}

?>