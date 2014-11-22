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
 * @packages    	Registry
 * @description		Simple ini files interpreter.
 * @copyright  		Copyright (c) 2009 Marcin Rosinski. (http://www.getsimpletools.com)
 * @license    		http://www.opensource.org/licenses/bsd-license.php - BSD
 * @version    		Ver: 2.0.6 2014-11-22 10:30
 * 
 */

	namespace Simpletools\Config;

	use Simpletools\Config;

	class Ini
	{
		
		protected $_ini 						= '';
		protected $_section 					= false;
		protected $_params						= false;
		protected $_settings					= null;
		
		protected static $__instance;
		
		public function __construct(array $settings=null)
		{ 
			$settings['uri_mvc_app_position'] = isset($settings['uri_mvc_app_position']) ? $settings['uri_mvc_app_position'] : 0;
			$settings['section'] = isset($settings['section']) ? $settings['section'] : false;

			$this->_settings = $settings;

			if(isset($settings['pathToIniFile']))
			{
				$this->setIniLocation($settings['pathToIniFile'],$settings['section']);
			}

			//depracted
			elseif(isset($settings['path_to_file']))
			{
				$this->setIniLocation($settings['path_to_file'],$settings['section']);
			}
			
			if (empty(self::$__instance)) 
			{
				self::$__instance = &$this;
			}

		}

		public static function &settings(array $settings)
		{	
			if(empty(self::$__instance)) 
			{
			   new Ini($settings);
		    }

		    $settings['uri_mvc_app_position'] = isset($settings['uri_mvc_app_position']) ? $settings['uri_mvc_app_position'] : 0;
		    $settings['section'] = isset($settings['section']) ? $settings['section'] : false;
		    	
		    self::$__instance->_settings = $settings;

		    if(isset($settings['pathToIniFile']))
			{
				self::$__instance->setIniLocation($settings['pathToIniFile'],$settings['section']);
			}

			//depracted
			elseif(isset($settings['path_to_file']))
			{
				self::$__instance->setIniLocation($settings['path_to_file'],$settings['section']);
			}
			
			return self::$__instance;
		}
		
		public static function &getInstance($settings=false)
		{
			if (empty(self::$__instance)) 
			 {
			     self::$__instance = new SimpleIni($settings);
		     }
		     
		   	 return self::$__instance;	
		}	
		
		public function setIniLocation($iniDir,$section=false)
		{
			$this->_iniDir = $iniDir;
			
			if(!file_exists($iniDir))
			{
				throw new Exception('Wrong file location: '.$this->_iniDir);
			}

			$this->_ini 	= @parse_ini_file($iniDir, true);
			$this->_iniMvc 	= $this->_ini;

			foreach($this->_iniMvc as $section => $value)
			{
				$ns = 0;

				if(isset($this->_settings['activeRoutingNamespace']) && $this->_settings['activeRoutingNamespace'])
				{
					$ns = $this->_settings['activeRoutingNamespace'];
					$ns = explode('\\',$ns);
					$ns = count($ns);
				}

				foreach($value as $field => $val) 
			    {
			    	if(stripos($field,'.')!==false)
			    	{
			    		$length = count(explode('.',$field));
			    		if($length!=$ns+2)
			    		{
			    			unset($value[$field]);
			    		}
			    	}
			    	else
			    	{
			    		$value[$field] = $val;	
			    	}
			    }

				$this->_iniMvc[$section] = $this->_parseIniSections($value);
			}
			
			if($section)
			{
				$this->_section = $section;
			}
		}

		protected function _parseIniSections($fields) 
		{
			$next 	= array(); $arr = array();

			foreach($fields as $field => $value) 
		    {
		    	if(stripos($field,'.')!==false)
		    	{
		    		$field = explode('.',$field);

		    		$f0 		= $field[0];
		    		unset($field[0]);

		    		if(count($field)<2)
		    		{
		    			$array = array(implode('.',$field) => $value);

			    		if(!isset($arr[$f0]))
			    		{
			    			$arr[$f0] 	= $this->_parseIniSections($array);
			    		}
			    		else
			    		{
			    			$arr[$f0] 	= array_merge($arr[$f0],$this->_parseIniSections($array));
			    		}
		    		}
		    		else
		    		{

		    			$next[$f0][implode('.',$field)] = $value;
		    		}
		    	}
		    	else
		    	{
		    		$arr[$field] = $value;
		    	}
		    }

		    if(count($next))
		    {
		    	foreach($next as $field => $fields)
		    	{
		    		if(!isset($arr[$field]))
			    	{
			    		$arr[$field] 	= $this->_parseIniSections($fields);
			    	}
			    	else
			    	{
			    		$arr[$field] 	= array_merge($arr[$field],$this->_parseIniSections($fields));
			    	}
		    	}
		    }

		    return $arr;
		}
		
		public function setSection($section)
		{
			$this->_section = $section;
		}
		
		public function getTree($section=false)
		{
			if(!$section)
			{
				return $this->_ini;
			}
			else
			{				
				if(!isset($this->_ini[$section]))
				{
					throw new Exception('There is no '.$section.' section in '.$this->_iniDir);
				}
				return $this->_ini[$section];
			}
		}
		
		public function getValue($name,$section=false)
		{			
			if(!$section)
			{
				if(!$this->_section)
				{
					throw new Exception('Please define section first');
				}
				
				$section = $this->_section;
			}
			
			return isset($this->_ini[$section][$name]) ? $this->_ini[$section][$name] : null;
		}

		public function setactiveRoutingNamespace($namespace)
		{
			$this->_settings['activeRoutingNamespace'] = $namespace;
		}
		
		public function getMvcValue($section=false)
		{
			$params 			= $this->getMvcParams();
			
			if(is_array($section))
			{
				if(isset($section['section'])) $section = $section['section'];
				else $section = $this->_section;
			}
			else if($section === false)
			{
				$section = $this->_section;
			}

			$ini 	= $this->_iniMvc[$section];
			$match 		= null;

			foreach($params as $param)
			{
				if(isset($ini[$param]) OR isset($ini['*']))
				{
					$match = isset($ini[$param]) ? $ini[$param] : $ini['*'];
					
					if(is_array($match))
					{
						$ini = $match;
					}
					elseif(!$match)
					{
						$match = null;
						break;
					}
					else
					{
						$ini = array();
					}
				}
				else
				{
					$match = null;
					break;
				}
			}

			if(is_array($match)) $match = null;
			
			return $match;
		}
		
		public function getMvcParams()
		{
			$ns = 0;
			$params = array();

			if(!$this->_params)
			{
				if($_SERVER['REQUEST_URI'] != '/')
				{
					$params = explode('/',trim($_SERVER['REQUEST_URI'],'/'));
					
					if($this->_settings['uri_mvc_app_position'] > 0)
					{
						for($i=0; $i<$this->_settings['uri_mvc_app_position']; $i++)
						{
							array_shift($params);
						}
					}
					elseif(isset($this->_settings['activeRoutingNamespace']) && $this->_settings['activeRoutingNamespace'])
					{
						$ns = $this->_settings['activeRoutingNamespace'];
						$ns = explode('\\',$ns);
						$ns = count($ns);
					}
					
					if(count($params) > 0)
					{
						$index = count($params)-1;
						if(($position = strpos($params[$index],'?')) !== false)
						{
							$params[$index] = substr($params[$index],0,$position);
							if($params[$index] == '') $params[$index] = null;
						}
					}
				}
				
				$params = array_slice($params,0,$ns+2);

				if(!isset($params[$ns])) {$params[$ns] = 'index'; $params[$ns+1] = 'index';}
				else if(!isset($params[$ns+1])) {$params[$ns+1] = 'index';}
				
				$this->_params = $params;
			}
			
			return $this->_params;	
		}
		
	}
	
?>