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
 * 
 */

	namespace Simpletools\Db\Mongo;

	use \MongoDB\Driver\Command;
	use \MongoDB\Driver\Query;
	use \MongoDB\Driver\BulkWrite;


	class QueryBuilder implements \Iterator
	{
		protected $_query 		= array();
		protected $_client 		= '';
		protected $_db 			= '';

		protected $_firstResult = null;
		
		protected $_cursor   	= null;
		protected $_iterator	= null;

		protected $_insertedId 	= null;

		protected $_queryTypes = array(
			'insert'		=> array('WRITE'),
			'insertIgnore'	=> array('WRITE','insert'),
			'update'		=> array('WRITE'),
			'upsert'		=> array('WRITE','update'),
			'delete'		=> array('WRITE'),
			'deleteOne'		=> array('WRITE','delete'),
			'find'			=> array('QUERY'),
			'findOne'		=> array('QUERY','find'),
			'count'			=> array('CMD'),
			'createIndex'	=> array('CMD'),
			'dropIndex'		=> array('CMD')
		);

		public function __construct($db,$collection,$client,$columns=array())
		{
			$this->_db = $db;

			if(count($columns))
			{
				if(count($columns) == 1)
				{
					$columns = $columns[0];
				}

				$this->_query['columns'] = $columns;
			}

			$this->setTable($collection);
			$this->_client = $client;
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

		protected $_currentJoinIndex = 0;

		public function count()
		{
			$this->_query['type'] = 'count';

			$this->run();
			return $this->n;
		}

		public function &dropIndex($index,$options=array())
		{
			$this->_query['type'] 	= 'dropIndex';

			$this->_query['index']['key'] 	= $index;

			$name = '';
		    foreach ($index as $field => $type) {
		        $name .= ($name != '' ? '_' : '') . $field . '_' . $type;
		    }

			$this->_query['index']['name'] 	= $name;

			if(count($options))
			{
				$this->_query['index'] 			+= $options;
			}

			return $this;
		}

		public function &createIndex($index,$options=array())
		{
			$this->_query['type'] 	= 'createIndex';

			$this->_query['index']['key'] 	= $index;

			$name = '';
		    foreach ($index as $field => $type) {
		        $name .= ($name != '' ? '_' : '') . $field . '_' . $type;
		    }

			$this->_query['index']['name'] 	= $name;

			if(count($options))
			{
				$this->_query['index'] 			+= $options;
			}

			return $this;
		}

		public function &db($db)
		{
			$this->_query['db'] = $db;
			return $this;
		}

		public function &inDb($db)
		{
			return $this->db($db);
		}

		public function &sort()
		{
			$args = func_get_args();

			if(count($args) == 1)
			{
				$args = $args[0];
			}
			elseif(count($args) == 2)
			{
				$args = [$args[0] => $args[1]];
			}

			$this->_query['sort'] = $args;

			return $this;
		}

		public function &insertIgnore($data)
		{
			$this->_query['type'] = "insertIgnore";
			$this->_query['data'] = $data;

			return $this;
		}

		public function &insert($data)
		{
			$this->_query['type'] = "insert";
			$this->_query['data'] = $data;

			return $this;
		}

		public function &onDuplicate($data)
		{
			$this->_query['onDuplicateData'] = $data;

			return $this;
		}

		public function &upsert($data)
		{
			$this->_query['type'] 	= "upsert";
			$this->_query['data'] 	= $data;
			
			return $this;
		}

		public function &update($data)
		{
			$this->_query['type'] 	= "update";
			$this->_query['data'] 	= $data;
			$this->_query['multi'] 	= true;

			return $this;
		}

		public function &updateOne($data)
		{
			$this->_query['type'] 	= "update";
			$this->_query['data'] 	= $data;
			$this->_query['limit'] 	= 1;
			$this->_query['multi'] 	= false;

			return $this;
		}

		public function &replace($data)
		{
			$this->_query['type'] = "REPLACE";
			$this->_query['data'] = $data;

			return $this;
		}

		public function &concern($w)
		{
			$this->_query['writeConcern-w'] = $w;

			return $this;
		}

		public function &timeout($wtimeoutMs)
		{
			$this->_query['writeConcern-wtimeout'] 	= (int) $wtimeoutMs;
			$this->_query['$maxTimeMS']				= (int) $wtimeoutMs;

			return $this;
		}

		public function &journal($journal=true)
		{
			$this->_query['writeConcern-journal'] = $journal;

			return $this;
		}

		public function getCursor()
		{
			$this->run();
			return $this->_cursor;
		}

		public function &run()
		{
			if($this->_cursor) return $this;

			try
			{
				$this->_cursor 		= $this->_execute($this->getQuery());
			}
			catch(\Exception $e)
			{
				if($this->_query['type']=='insert')
				{
					$errors = $e->getWriteResult()->getWriteErrors();
					throw new \Exception($errors[0]->getMessage(),$errors[0]->getCode());
				}
				elseif($this->_query['type']=='insertIgnore')
				{
					$errors = $e->getWriteResult()->getWriteErrors();
					if($errors[0]->getCode()!=11000)
					{
						throw new \Exception($errors[0]->getMessage(),$errors[0]->getCode());
					}
					else
					{
						return false;
					}
				}
				else
				{
					throw $e;
				}
			}

			if($this->_queryTypes[$this->_query['type']][0]!='WRITE')
			{
				$this->_iterator 	= new \IteratorIterator($this->_cursor);
				$this->_iterator->rewind();

				$this->_firstResult = $this->_iterator->current();
			}
			
			return $this;
		}

		public function &get($id,$column='id')
		{
			$this->_query['type']		= "SELECT";
			$this->_query['where'][] 	= array($column,$id);

			return $this;
			//return $this->run();
		}

		private function _prepareQuery()
		{
			$queryType = $this->_queryTypes[$this->_query['type']][0];

			if($queryType=='CMD')
			{
				return $this->_getCommand();
			}
			elseif($queryType=='QUERY')
			{
				return $this->_getQuery();
			}
			elseif($queryType=='WRITE')
			{
				return $this->_getWrite();
			}
		}
		
		protected function _execute($query)
		{
			if($query instanceof Command)
			{
				return $this->_client->executeCommand($this->_db,$query);
			}
			elseif($query instanceof Query)
			{
				return $this->_client->executeQuery($this->_db.'.'.$this->_query['table'],$query);
			}
			elseif($query instanceof BulkWrite)
			{
				$w 			= isset($this->_query['writeConcern-w']) ? $this->_query['writeConcern-w'] : 1;
				$wtimeout 	= isset($this->_query['writeConcern-wtimeout']) ? $this->_query['writeConcern-wtimeout'] : 0;
				$journal 	= isset($this->_query['writeConcern-journal']) ? $this->_query['writeConcern-journal'] : false;

				$concern = new \MongoDB\Driver\WriteConcern($w,$wtimeout,$journal);

				return $this->_client->executeBulkWrite($this->_db.'.'.$this->_query['table'],$query,$concern);
			}
		}

		protected function _getCommand()
		{
			$cmd = array();
			if($this->_query['type']=='count')
			{
				$cmd['count'] = $this->_query['table'];

				if(isset($this->_query['filters']) && $this->_query['filters'])
				{
					$cmd['query'] = $this->_query['filters'];
				}
			}
			elseif($this->_query['type']=='createIndex')
			{
				$cmd['createIndexes'] 	= $this->_query['table'];
				$cmd['indexes'] 		= array($this->_query['index']);
			}
			elseif($this->_query['type']=='dropIndex')
			{
				$cmd['dropIndexes'] 	= $this->_query['table'];
				$cmd['indexes'] 		= array($this->_query['index']);
			}

			return new Command($cmd);
		}

		protected function _getQuery()
		{
			$options = array();

			if(isset($this->_query['limit']))
			{
				$options['limit'] 						= (int) $this->_query['limit'];
			}
			
			if(isset($this->_query['sort']))
			{
				$options['sort'] 						= $this->_query['sort'];
			}

			if(isset($this->_query['columns']) && is_array($this->_query['columns']))
			{
				$options['projection'] 						= $this->_query['columns'];
			}
			
			if(isset($this->_query['$maxTimeMS']))
			{
				$options['modifiers']['$maxTimeMS'] 	= (int) $this->_query['$maxTimeMS'];
			}

			return new Query($this->_query['filters'],$options);
		}

		protected function _getWrite()
		{
			$bulk = new BulkWrite(['ordered' => true]);

			$type = $this->_queryTypes[$this->_query['type']];
			$type = isset($type[1]) ? $type[1] : $this->_query['type'];

			if($type=='insert')
			{
				if(!isset($this->_query['data']['_id']))
				{
					$this->_query['data']['_id'] = new \MongoDB\BSON\ObjectID();
				}

				$this->_insertedId = $this->_query['data']['_id'];

				$bulk->insert($this->_query['data']);
			}
			elseif($type=='update')
			{
				$options 				= array();
				$options['multi']	 	= isset($this->_query['multi']) ? $this->_query['multi'] : true;

				if($this->_query['type'] == 'upsert')
				{
					$options['upsert']	= true;
				}

				if(isset($this->_query['limit']))
				{
					$options['limit']	= $this->_query['limit'];

					if($options['limit']==1)
					{
						$options['multi'] = false;
					}
				}

				$bulk->update($this->_query['filters'],$this->_query['data'],$options);
			}
			elseif($type=='delete')
			{
				$options 				= array();
				
				if(isset($this->_query['limit']))
				{
					$options['limit']	= $this->_query['limit'];
				}

				$bulk->delete($this->_query['filters'],$options);
			}

			return $bulk;
		}

		public function getQuery()
		{
			if(!isset($this->_query['type']))
			{
				$this->_query['type']		= "find";
			}

			if(!isset($this->_query['columns']))
			{
				$this->_query['columns']		= "*";
			}

			if(!is_array($this->_query['columns']) && $this->_query['columns'] != '*')
			{
				$this->_query['columns'] = explode(',',$this->_query['columns']);
			}

			if(!isset($this->_query['filters']))
			{
				$this->_query['filters'] = array();
			}

			return $this->_prepareQuery();
		}

		public function &truncate()
		{
			$this->_query['type']		= "TRUNCATE";

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

		public function &findOne()
		{
			$this->_query['limit'] 		= 1;
			call_user_func_array(array($this, 'where'),func_get_args());

			return $this;
		}

		public function &find()
		{
			call_user_func_array(array($this, 'where'),func_get_args());

			return $this;
		}

		public function &deleteOne()
		{
			call_user_func_array(array($this, 'delete'),func_get_args());
				
			$this->_query['type'] 	= 'deleteOne';
			$this->_query['limit']	= 1;

			return $this;
		}

		public function &delete()
		{
			$args = func_get_args();

			if(count($args))
			{
				call_user_func_array(array($this, 'where'),$args);
			}
				
			$this->_query['type'] = 'delete';

			return $this;
		}

		public function &where()
		{
			if(!isset($this->_query['type']))
			{
				$this->_query['type'] = 'find';
			}

			$args = func_get_args();
			if(count($args)==1 && is_array($args)) $args = $args[0];
			elseif(!count($args))
			{
				$args = array();
			}
			else
			{
				$args = array(
					$args[0] => $args[1]
				);
			}

			$this->_query['filters'] 	= $args;

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
		public function isEmpty()
		{
			$this->run();
			return !$this->_firstResult ? true : false;
		}

		public function __isset($name)
		{
			$this->run();
			return isset($this->_firstResult->{$name});
		}

		public function __get($name)
		{
			$this->run();

			if(!$this->_firstResult)
			{
				throw new \Exception('Your query returned no results',404);
			}

			return @$this->_firstResult->{$name};
		}

		public function getInsertedCount()
		{
			$this->run();
			return $this->_cursor->getInsertedCount();
		}

		public function getDeletedCount()
		{
			$this->run();
			return $this->_cursor->getDeletedCount();
		}

		public function getAffectedRows()
		{
			return $this->getModifiedCount();
		}

		public function getModifiedCount()
		{
			$this->run();
			return $this->_cursor->getModifiedCount();
		}

		public function getUpsertedIds()
		{
			$this->run();
			return $this->_cursor->getUpsertedIds();
		}

		public function getUpsertedCount()
		{
			$this->run();
			return $this->_cursor->getUpsertedCount();
		}

		public function getInsertedId()
		{
			$this->run();
			return $this->_insertedId;
		}

		public function getFirstRow()
		{
			$this->run();
			return $this->_firstResult;
		}

		public function toArray()
		{
			return $this->fetchAll();
		}

		public function fetchAll()
		{
			$this->run();
			return iterator_to_array($this->_iterator);
		}

		public function length()
		{
			$this->run();
			return $this->_cursor->length();
		}

		public function rewind()
		{
			$this->run();
			$this->_iterator->rewind();
		}

		public function current() 
		{
	        return $this->_iterator->current();
	    }

	    public function key() 
	    {
	        return $this->_iterator->key();
	    }

	    public function next() 
	    {
	    	return $this->_iterator->next();
	    }

	    public function valid() 
	    {
	        return $this->_iterator->valid();
	    }

	}
		
?>