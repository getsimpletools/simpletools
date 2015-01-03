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
 * @version    		Ver: 2.0.15 2014-12-31 10:45
 * 
 */

	namespace Simpletools\Db\Mysql;

	class Result implements \Iterator
	{
		protected $_mysqlResult 	= '';
		protected $_mysqli 			= '';

		protected $_firstRowCache	= null;
		protected $_firstRowCached	= false;

		protected $_position 		= 0;
		protected $_currentRow 		= false;

		public function __construct(&$mysqliResult,&$mysqli)
		{
			$this->_mysqlResult = $mysqliResult;
			$this->_mysqli		= $mysqli;

			if(is_object($mysqliResult))
			{
				$this->_position 	= 0;
				$this->_loadFirstRowCache();
			}
		}

		public function isEmpty()
		{
			return !mysqli_num_rows($this->_mysqlResult);
		}

		public function length()
		{
			return mysqli_num_rows($this->_mysqlResult);
		}

		public function fetch($returnObject=true)
		{
			if($returnObject)
				return mysqli_fetch_object($this->_mysqlResult);	
			else
				return mysqli_fetch_assoc($this->_mysqlResult);
		}

		public function fetchAll($returnObject=true)
		{
			if($this->isEmpty()) return array();
			
			$datas = array();
			while($data = $this->fetch($returnObject))
			{
				$datas[] = $data;
			}
				
			$this->free();
			return $datas;
		}

		public function &getRawResult()
		{
			return $this->_mysqlResult;
		}

		public function free()
		{
			mysqli_free_result($this->_mysqlResult);
		}

		public function __desctruct()
		{
			mysqli_free_result($this->_mysqlResult);
		}

		public function getAffectedRows()
		{
			return $this->_mysqli->affected_rows;
		}

		public function getInsertedId()
		{
			return $this->_mysqli->insert_id;
		}

		protected function _loadFirstRowCache()
		{
			if(!$this->_firstRowCached)
			{
				$this->_firstRowCache 	= $this->fetch();
				$this->_firstRowCached 	= true;
				mysqli_data_seek($this->_mysqlResult,0);
			}
		}

		public function __get($name)
		{
			//$this->_loadFirstRowCache();
			return isset($this->_firstRowCache->{$name}) ? $this->_firstRowCache->{$name} : null;
		}

		public function __isset($name)
		{
			//$this->_loadFirstRowCache();
			return isset($this->_firstRowCache->{$name});
		}

		public function rewind()
		{
			mysqli_data_seek($this->_mysqlResult,0);
			$this->_position 	= 0;

			if($this->_currentRow===false)
			{
				$this->_currentRow = $this->fetch();
			}
		}

		public function current() 
		{
	        return $this->_currentRow;
	    }

	    public function key() 
	    {
	        return $this->_position;
	    }

	    public function next() 
	    {
	    	$this->_currentRow = $this->fetch();
	        ++$this->position;
	        return $this->_currentRow;
	    }

	    public function valid() 
	    {
	        return ($this->_currentRow!==null) ? true : false;
	    }
	}
		
?>