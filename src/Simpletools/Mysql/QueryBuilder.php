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
 * @version    		Ver: 2.0.8 2014-11-22 19:20
 * 
 */

	namespace Simpletools\Mysql;

	class QueryBuilder implements \Iterator
	{
		protected $_query 	= '';
		protected $_mysql 	= '';
		
		protected $_result   = null;

		public function __construct($table,$mysql,$columns=array())
		{
			if (!($mysql instanceof \Simpletools\Mysql\Client))
			{
				throw new \Exception("2nd construct argument is not an instance of \Simpletools\Mysql\Client", 404);
			}

			if(count($columns))
			{
				if(count($columns) == 1)
				{
					$columns = $columns[0];
				}

				$this->_query['columns'] = $columns;
			}

			$this->setTable($table);
			$this->_mysql = $mysql;
		}

		public function &columns()
		{
			$args = func_get_args();

			if(count($args) == 1)
			{
				$args = $args[0];
			}

			$this->_query['columns'] = $args;

			return $this;
		}

		public function &db($db)
		{
			$this->_query['db'] = $db;

			return $this;
		}

		public function &group()
		{
			$args = func_get_args();

			if(count($args) == 1)
			{
				$args = $args[0];
			}

			$this->_query['groupBy'] = $args;

			return $this;
		}

		public function &sort()
		{
			$args = func_get_args();

			if(count($args) == 1)
			{
				$args = $args[0];
			}

			$this->_query['sort'] = $args;

			return $this;
		}

		public function &insertIgnore($data)
		{
			$this->_query['type'] = "INSERT IGNORE";
			$this->_query['data'] = $data;

			return $this;
		}

		public function &delete()
		{
			$this->_query['type'] = "DELETE FROM";

			$args = func_get_args();
			if(count($args)==1) $args = $args[0];

			$this->_query['where'][] 	= $args;
			
			return $this;
		}

		public function &insert($data)
		{
			$this->_query['type'] = "INSERT";
			$this->_query['data'] = $data;

			return $this;
		}

		public function &onDuplicate($data)
		{
			$this->_query['onDuplicateData'] = $data;

			return $this;
		}

		public function &update($data)
		{
			$this->_query['type'] = "UPDATE";
			$this->_query['data'] = $data;

			return $this;
		}

		public function run()
		{
			if($this->_result) return $this->_result;

			return $this->_result = $this->_mysql->query($this->getQuery());
		}

		public function get($id,$column='id')
		{
			$this->_query['type']		= "SELECT";
			$this->_query['where'][] 	= array($column,$id);

			return $this->run();
		}

		public function _escape($value)
		{
			return $this->_mysql->escape($value);
		}

		private function _prepareQuery($query, array $args)
		{
			foreach($args as $arg)
			{
				if(is_string($arg))
				{
					if(strpos($arg,'?') !== false)
					{
						$arg = str_replace('?','<--SimpleMySQL-QuestionMark-->',$arg);
					}
					
					$arg = "'".$this->_escape($arg)."'";
				}
				
				if($arg === null)
				{
					$arg = 'NULL';
				}
				
				$query = $this->replace_first('?', $arg, $query);
			}
			
			if(strpos($query,'<--SimpleMySQL-QuestionMark-->') !== false)
			{
				$query = str_replace('<--SimpleMySQL-QuestionMark-->','?',$query);
			}

			return $query;
		}
		
		public function replace_first($needle , $replace , $haystack)
		{			
			$pos = strpos($haystack, $needle);
			    
			if ($pos === false) 
		    {
		        // Nothing found
		   		return $haystack;
		   	}
				
		   	return substr_replace($haystack, $replace, $pos, strlen($needle));
		}

		public function getQuery()
		{
			if(!isset($this->_query['type']))
				$this->_query['type']		= "SELECT";

			if(!isset($this->_query['columns']))
				$this->_query['columns']		= "*";

			$query 		= array();
			$query[] 	= $this->_query['type'];

			if($this->_query['type']=='SELECT')
			{
				$query[] = is_array($this->_query['columns']) ? implode(', ',$this->_query['columns']) : $this->_query['columns'];
				$query[] = 'FROM';
			}
			elseif($this->_query['type']=='INSERT' OR $this->_query['type']=='INSERT IGNORE')
			{
				$query[] = 'INTO';
			}

			if(isset($this->_query['db']))
			{
				$query[] = $this->_query['db'].'.'.$this->_query['table'];
			}
			else
			{
				$query[] = $this->_query['table'];
			}

			if($this->_query['type']=='INSERT' OR $this->_query['type']=='UPDATE' OR $this->_query['type']=='INSERT IGNORE')
			{
				$query[] = 'SET';

				$set = array();

				foreach($this->_query['data'] as $key => $value)
				{
					$set[] = $key.' = "'.$this->_escape($value).'"';
				}

				$query[] = implode(', ',$set);
			}

			if(isset($this->_query['onDuplicateData']))
			{
				$query[] = 'ON DUPLICATE KEY UPDATE';

				$set = array();

				foreach($this->_query['onDuplicateData'] as $key => $value)
				{
					$set[] = $key.' = "'.$this->_escape($value).'"';
				}

				$query[] = implode(', ',$set);
			}

			if(isset($this->_query['where']))
			{
				$query['WHERE'] = 'WHERE';

				if(is_array($this->_query['where']))
				{
					foreach($this->_query['where'] as $operands)
					{
						if(!isset($operands[2]))
							$query[] = $operands[0]." = ".'"'.$this->_escape($operands[1]).'"';
						else
							$query[] = $operands[0]." ".$operands[1]." ".'"'.$this->_escape($operands[2]).'"';
					}
				}
				else
				{
					$query[] = 'id = "'.$this->_escape($this->_query['where']).'"';
				}
			}

			if(isset($this->_query['whereSql']))
			{
				if(!isset($query['WHERE'])) $query['WHERE'] = 'WHERE';

				if($this->_query['whereSql']['vars'])
				{
					$query[] = $this->_prepareQuery($this->_query['whereSql']['statement'],$this->_query['whereSql']['vars']);
				}
				else
				{
					$query[] = $this->_query['whereSql']['statement'];
				}
			}

			if(isset($this->_query['groupBy']))
			{
				$query[] = 'GROUP BY';

				if(!is_array($this->_query['groupBy']))
				{
					$query[] = $this->_query['groupBy'];
				}
				else
				{
					$groupBy = array();

					foreach($this->_query['groupBy'] as $column)
					{
						$groupBy[] = $column;
					}

					$query[] = imploder(', ',$groupBy);
				}
			}

			if(isset($this->_query['sort']))
			{
				$query[] = 'ORDER BY';

				if(!is_array($this->_query['sort']))
				{
					$query[] = $this->_query['sort'];
				}
				else
				{
					$sort = array();

					foreach($this->_query['sort'] as $column)
					{
						$sort[] = $column;
					}

					$query[] = imploder(', ',$sort);
				}
			}

			if(isset($this->_query['offset']))
			{
				$query[] = 'OFFSET '.$this->_query['offset'];
			}

			if(isset($this->_query['limit']))
			{
				$query[] = 'LIMIT '.$this->_query['limit'];
			}

			$this->_query = array();
			return implode(' ',$query);
		}

		public function &whereSql($statement,$vars=null)
		{
			$this->_query['whereSql'] = array('statement'=>$statement,'vars'=>$vars);

			return $this;
		}

		public function &select($columns)
		{
			$this->_query['type']		= "SELECT";
			$this->_query['columns']	= $columns;

			return $this;
		}

		public function &offset($offset)
		{
			$this->_query['offset'] 	= $offset;

			return $this;
		}

		public function &limit($limit)
		{
			$this->_query['limit'] 		= $limit;

			return $this;
		}

		public function &find()
		{
			$args = func_get_args();
			if(count($args)==1) $args = $args[0];

			$this->_query['where'][] 	= $args;

			return $this;
		}

		public function &alternatively()
		{
			$args = func_get_args();
			$args[0] = 'OR '.$args[0];
			
			$this->_query['where'][] 	= $args;

			return $this;
		}

		public function &also()
		{
			$args = func_get_args();
			$args[0] = 'AND '.$args[0];
			
			$this->_query['where'][] 	= $args;

			return $this;
		}

		public function &setTable($table)
		{
			$this->_query['table'] 		= $table;

			return $this;
		}

		/*
		* AUTO RUNNNERS
		*/

		public function __get($name)
		{
			$this->run();
			return $this->_result->{$name};
		}

		public function getAffectedRows()
		{
			$this->run();
			return $this->_result->getAffectedRows();
		}

		public function getInsertedId()
		{
			$this->run();
			return $this->_result->getInsertedId();
		}

		public function isEmpty()
		{
			$this->run();
			return $this->_result->isEmpty();
		}

		public function fetch()
		{
			$this->run();
			return $this->_result->fetch();
		}

		public function fetchAll()
		{
			$this->run();
			return $this->_result->fetchAll();
		}

		public function length()
		{
			$this->run();
			return $this->_result->length();
		}

		public function rewind()
		{
			$this->run();
			$this->_result->rewind();
		}

		public function current() 
		{
	        return $this->_result->current();
	    }

	    public function key() 
	    {
	        return $this->_result->key();
	    }

	    public function next() 
	    {
	    	return $this->_result->next();
	    }

	    public function valid() 
	    {
	        return $this->_result->valid();
	    }

	}
		
?>