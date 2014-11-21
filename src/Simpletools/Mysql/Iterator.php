<?php
/*
 * Simpletools Framework.
 * Copyright (c) 2009, Marcin Rosinski. (https://www.getsimpletools.com)
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
 * @copyright  		Copyright (c) 2009 Marcin Rosinski. (http://www.getsimpletools.com)
 * @license    		http://www.opensource.org/licenses/bsd-license.php - BSD
 * @version    		Ver: 2.0.3 2014-11-21 18:29
 * 
 */

	namespace Simpletools\Mysql;

	class Iterator implements \Iterator
	{
		private $_query 			= '';
		private $_currentId 		= 0;
		private $_startId			= 0;
		private $_sqlRes			= '';
		private $_mysql 			= '';
		private $_params			= array();
		private $_cursor 			= '';
		private $_iteratorField		= '';
		private $_currentRow		= false;

		public function __construct(&$mysql,$settings) 
		{
			$this->_query 			= $settings['query'];
			$this->_params 			= isset($settings['params']) ? $settings['params'] : array();
	        $this->_currentId 		= $settings['start'];
	        $this->_startId 		= $settings['start'];
	        $this->_mysql 			= $mysql;
	        $this->_iteratorField 	= $settings['iteratorField'];
	    }

	    private function _runQuery($rewind=false)
	    {
	    	if($rewind) 
	    	{
	    		$this->_currentId = $this->_startId;
	    	}

			$query = str_replace('::','"'.$this->_mysql->escape($this->_currentId).'"',$this->_query);
			
	    	if(!count($this->_params))
	    		$this->_cursor = $this->_mysql->query($query);
	    	else
	    		$this->_cursor = $this->_mysql->prepare($query)->execute($this->_params);
	    }

	    private function _setRow()
	    {
	    	if(!$this->_cursor OR !($row = $this->_mysql->fetch($this->_cursor)))
	    	{	
	    		$this->_runQuery();
	    		if(!($row = $this->_mysql->fetch($this->_cursor)))
	    		{
	    			$this->_currentRow = false;
	    		}
	    	}
	    	
	    	if($row)
	    	{
	    		$this->_currentId 	= $row->{$this->_iteratorField};
	    		$this->_currentRow = $row;
	    	}
	    }

	    function rewind() 
	    {
	    	$this->_runQuery(true);
	    }

	    function current() 
	    {
	    	return $this->_currentRow;
	    }

	    function key() 
	    {
	    	return $this->_currentId;
	    }

	    function next() 
	    {
	    	$this->_setRow();
	        return $this->_currentRow;
	    }

	    function valid() 
	    {
	    	if($this->_currentId === $this->_startId) 
	    	{
	    		$this->next();
			}
	    
	        return ($this->_currentRow===false) ? false : true;
	    }
	}
		
?>
