<?php
/*
 * Simpletools Framework.
 * Copyright (c) 2013, Marcin Rosinski. (http://www.getsimpletools.com)
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
 * @description		Efficient Autoloading Class, based on the SPL
 * @copyright  		Copyright (c) 2013 Marcin Rosinski. (http://www.getsimpletools.com)
 * @license    		http://www.opensource.org/licenses/bsd-license.php - BSD
 * @version    		Ver: 2.0.15 2014-12-30 23:36
 * 
 */

	namespace Simpletools\Autoload;

	class Loader
	{
	    public static $loader;

	    private $__namespaces 			= array();
	    private $__prefixes 			= array();
	    private $__fallbackAutoloader 	= false;

	    public static function &getInstance(mixed $options=null)
	    {
	        if (self::$loader == NULL)
	            self::$loader = new self($options);

	        return self::$loader;
	    }

	    public function __construct(mixed $options=null)
	    {
	        spl_autoload_register(array($this,'__load'));
	        if($options) $this->setOptions($options);
	    }

	    public function &setOptions($options)
	    {
	    	if(isset($options['autoregister_simpletools']) && $options['autoregister_simpletools']===true)
	    	{
	    		$this->autoregisterSimpleTools();
			}

	    	if(isset($options['prefixes']) && is_array($options['prefixes']))
	    	{
	    		$this->registerPrefixes($options['prefixes']);
	    	}

	    	if(isset($options['namespaces']) && is_array($options['namespaces']))
	    	{
	    		$this->registerNamespaces($options['namespaces']);
	    	}

			return $this;
	    }

	    public function &autoloadSimpletools()
	    {
	    	return $this->registerNamespaces(array('Simpletools'=>dirname(__FILE__)));
	    }

	    public function &autoregisterSimpletools()
	    {
	    	return $this->registerNamespaces(array('Simpletools'=>dirname(__FILE__)));
	    }

	    public function &registerNamespace($namespace,$path)
	    {
	    	$this->__namespaces[$namespace] = realpath($path);

	    	return $this;
	    }

	    public function &registerNamespaces($namespaces)
	    {
	    	foreach($namespaces as $namespace => $path)
	    	{
	    		$this->registerNamespace($namespace,$path);
	    	}

	    	return $this;
	    }

	    public function &registerPrefix($prefix,$path)
	    {
			$this->__prefixes[$prefix] = realpath($path);

			return $this;
	    }

	    public function &registerPrefixes($prefixes)
	    {
	    	foreach($prefixes as $prefix => $path)
	    	{
	    		$this->registerPrefix($prefix,$path);
	    	}

	    	return $this;
	    }

	    private function __loadPrefix($class)
	    {
	    	//print_r($this->__prefixes);
	    	foreach($this->__prefixes as $prefix => $path)
	    	{
	    		if(strpos($class,$prefix)===0)
	    		{
	    			if(is_file($path.DIRECTORY_SEPARATOR.$class.'.php'))
	    			{
	    				return require($path.DIRECTORY_SEPARATOR.$class.'.php');
	    			}
	    		}
	    	}

	    	$this->__fallbackAutoloader($class);
	    }

	    private function __loadNamespace($class)
	    {
	    	$dir 		= explode('\\',$class);
	    	$file 		= array_pop($dir);
	    	$dir 		= implode(DIRECTORY_SEPARATOR,$dir);

	    	foreach($this->__namespaces as $namespace => $path)
	    	{
	    		if(strpos($class,$namespace)===0)
	    		{
	    			$class = trim(str_replace(array($namespace,'\\'),array('',DIRECTORY_SEPARATOR),$class),DIRECTORY_SEPARATOR);

	    			if(is_file($path.'/'.$class.'.php'))
	    			{
	    				return require($path.'/'.$class.'.php');
	    			}
	    		}
	    	}

	    	$this->__fallbackAutoloader($class);
	    }

	    private function __load($class)
	    {
	    	if(stripos($class,'\\')) $this->__loadNamespace($class);
	    	else $this->__loadPrefix($class);

 			//set_include_path(get_include_path().PATH_SEPARATOR.'/lib/');
	        //spl_autoload_extensions('.library.php');
	        //spl_autoload($class);
	    }

	    public function setFallbackAutoloader($status)
	    {
	    	$this->__fallbackAutoloader = (boolean) $status;
	    }

	    public function isFallbackAutoloader()
	    {
	    	return $this->__fallbackAutoloader;
	    }

	    private function __fallbackAutoloader($class)
	    {
	    	if(!$this->__fallbackAutoloader) return;

	    	spl_autoload($class);
	    }
	}
?>