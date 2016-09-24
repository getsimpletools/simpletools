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

	namespace Simpletools\Db\Mysql;

	use Simpletools\Db\Mysql\Connection;
	use Simpletools\Db\Mysql\FullyQualifiedQuery;

	class Client
	{
		protected 			$___credentials		= false;
		protected 			$___connected 		= false;
		protected 			$___modelDir 		= '';
		protected			$___quotes_on 		= '';
		protected			$___query			= '';
		protected static 	$___instance 		= null;
		protected 			$___settings 		= null;
		protected 			$___mysqli			= null;
		protected 			$___args 			= '';
		
		const 				_noArgs				= '$--SimpleMySQL--n0-aRg5--';

		protected 			$___current_db 		= null;
		protected 			$___connectionName	= 'default';
		
		public function __construct(array $settings=null,$connectionName='default')
		{
			$this->setSettings($settings);

			$this->___connectionName = $connectionName;
			
			if(!isset(self::$___instance[$connectionName])) 
			{
				self::$___instance[$connectionName] = &$this;
			}
		}

		public function getConnectionName()
		{
			return $this->___connectionName;
		}

		public function getSettings()
		{
			return $this->___settings;
		}
		
		public function setSettings($settings)
		{
			$settings['charset_type']	= isset($settings['charset']) ? $settings['charset'] : (isset($settings['charset_type']) ? $settings['charset_type'] : null);

			$this->___settings	= $settings;

			$this->___settings['time_zone'] 					= isset($settings['timezone']) ? $settings['timezone'] : @$settings['time_zone'];
			$this->___settings['die_on_error']				= isset($settings['die_on_error']) ? (boolean) $settings['die_on_error'] : true;
			$this->___settings['custom_mysqli_class_name'] 	= isset($settings['custom_mysqli_class_name']) ? (string) $settings['custom_mysqli_class_name'] : false;
			
			$this->___settings['connect_error_filepath'] 		= isset($settings['connect_error_filepath']) ? $settings['connect_error_filepath'] : false;
			
			
			if(isset($settings['model_dir']))
			{
				$this->___modelDir = $settings['model_dir'];
			}
			elseif(isset($settings['modelsDir']))
			{
				$this->___modelDir = $settings['modelsDir'];
			}

			$this->setCredentials($settings);
			$this->quotes_on = (get_magic_quotes_gpc()==1 || get_magic_quotes_runtime()==1) ? true : false;

			if(isset($settings['queryLog']))
			{
				Connection::logSettings($settings['queryLog']);
			}
		}
		
		public function setDb($db)
		{
			$this->___current_db = $db;

			//echo 'SET DB: '.$this->___current_db."--\n";

			return true;

			/*
			if(!$this->isConnected())
			{
				$this->___credentials['db'] = $db;
				return true;
				
				//$this->connect();
				//if(!$this->isConnected()) return false;
			}
			
			$this->___current_db = $db;
			return $this->___mysqli->select_db($db);
			*/
		}
		
		public function getCurrentDb()
		{
			/*
			if(!$this->isConnected())
			{
				$this->connect();
				if(!$this->isConnected()) return false;
			}
			*/

			return $this->___current_db;
		}
		
		public static function &getInstance($settings=false)
		{
			$connectionName = (isset($settings['connectionName']) ? $settings['connectionName'] : 'default');
			if(is_string($settings))
			{
				$connectionName = $settings;
			}

			if(!is_string($settings) && !isset(self::$___instance[$connectionName]) && $settings) 
			{
			    new self($settings,$connectionName);
		    }
		    elseif(is_string($settings) && !isset(self::$___instance[$connectionName]))
		    {
		    	throw new \Exception('No mysql settings defined with connectionName '.$connectionName);
		    }

		   	return self::$___instance[$connectionName];
		}
		
		public static function &settings($settings)
		{
			$connectionName = (isset($settings['connectionName']) ? $settings['connectionName'] : 'default');

			if(!isset(self::$___instance[$connectionName]))
			    new \Simpletools\Db\Mysql\Client($settings,$connectionName);
		    else
		    	self::$___instance[$connectionName]->setSettings($settings);
		    	
		    return self::$___instance[$connectionName];
		}
		
		public function setNewConnectionDetails($credentials, $user=false, $pass=false, $db=false)
		{
			if($this->___connected)
			{
				$this->close();
			}
			
			$this->setCredentials($credentials, $user, $pass, $db);
		}
		
		public function setCredentials($settings)
		{
			$this->___credentials['host'] 		= isset($settings['host']) ? $settings['host'] : 'localhost';
			$this->___credentials['user']	 		= isset($settings['user']) ? $settings['user'] : null;
			$this->___credentials['pass'] 		= isset($settings['pass']) ? $settings['pass'] : null;
			$this->___credentials['db'] 			= isset($settings['db']) ? $settings['db'] : null;

			//could be already defined e.g. under model
			if(!$this->___current_db)
			{
				$this->___current_db = $this->___credentials['db'];
			}

			$this->___credentials['port'] 		= isset($settings['port']) ? $settings['port'] : 3306;
			$this->___credentials['compression'] 	= isset($settings['compression']) ? (boolean) $settings['compression'] : false;
			$this->___credentials['ssl'] 			= isset($settings['ssl']) ? (boolean) $settings['ssl'] : false;
		}
		
		public function __destruct()
		{
			$this->close();
		}
		
		public function setMysqliClass($class=false)
		{
			$this->___settings['custom_mysqli_class_name'] = $class;
		}
		
		public function getMysqliClass()
		{
			return (($this->___settings['custom_mysqli_class_name'] === false) ? '\Simpletools\Db\Mysql\Driver' : $this->___settings['custom_mysqli_class_name']);
		}
		
		public function setTimeout($time=10)
		{
			if($this->___mysqli === null)
			{
				if($this->___settings['custom_mysqli_class_name'] != false) 
					$mysqli_class = $this->___settings['custom_mysqli_class_name'];
				else
					$mysqli_class = '\Simpletools\Db\Mysql\Driver';
			
				$this->___mysqli = new $mysqli_class();
				$this->___mysqli->init();
			}
			
			return $this->___mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT,$time);
		}
		
		public function setTimezone($timezone)
		{
			if(!$this->isConnected())
			{
				$this->connect();
				if(!$this->isConnected()) return false;
			}
			
			$this->query(new FullyQualifiedQuery('SET time_zone = "'.$this->escape($timezone).'"'));
		}
		
		public function getTimezone()
		{
			if(!$this->isConnected())
			{
				$this->connect();
				if(!$this->isConnected()) return false;
			}
			
			$r = $this->query('SELECT @@time_zone as tz;',false);
			if($this->isEmpty($r)) return false;
			else return $this->fetch($r)->tz;
		}

		public function connect($credentials=false, $user=false, $pass=false, $db=false, $port=3306, $die_on_error=null)
		{
			if($this->isConnected()) return true;

			$_credentials = '';
			if(!$credentials)
			{
				$_credentials['db']				= $this->___credentials['db'];
				$_credentials['host']			= $this->___credentials['host'];
				$_credentials['user']			= $this->___credentials['user'];
				$_credentials['pass']			= $this->___credentials['pass'];
				$_credentials['port']			= $this->___credentials['port'];
				$_credentials['compression']	= $this->___credentials['compression'];
				$_credentials['ssl']			= $this->___credentials['ssl'];
			}
			elseif(is_array($credentials))
			{
				$_credentials['db']				= isset($credentials['db']) ? $credentials['db'] : null;
				$_credentials['host']			= $credentials['host'];
				$_credentials['user']			= $credentials['user'];
				$_credentials['pass']			= $credentials['pass'];
				$_credentials['port']			= isset($credentials['port']) ? $credentials['port'] : 3306;	
				$_credentials['compression']	= isset($credentials['compression']) ? (boolean) $credentials['compression'] : false;
				$_credentials['ssl']			= isset($credentials['ssl']) ? (boolean) $credentials['ssl'] : false;
			}
			else
			{
				$_credentials['db']			= $db;
				$_credentials['host']		= $credentials;
				$_credentials['user']		= $user;
				$_credentials['pass']		= $pass;
				$_credentials['port']		= $port;
			}

			if(!$_credentials['host'])
			{
				throw new \Exception('Please specify connection settings before',111);
			}

			if(($connector = Connection::getOne($this->___connectionName)))
			{
				$this->___mysqli 		= &$connector;
				$this->___connected 	= true;

				return true;
			}
			
			$mysqli_class = '\Simpletools\Db\Mysql\Driver';
			
			if($this->___settings['custom_mysqli_class_name'] != false) 
				$mysqli_class = $this->___settings['custom_mysqli_class_name'];
			
			$this->___mysqli = new $mysqli_class();
			$this->___mysqli->init();
			$this->setTimeout();

			if(isset($_credentials['db']) && $_credentials['db'])
			{
				$this->___current_db 						= $_credentials['db'];
			}

			$flags = null;
			if($_credentials['compression'])
			{
				$flags = MYSQLI_CLIENT_COMPRESS;
			}

			if($_credentials['ssl'])
			{
				$flags = (isset($flags) ? ($flags|MYSQLI_CLIENT_SSL) : MYSQLI_CLIENT_SSL);
			}

			@$this->___mysqli->real_connect(
				$_credentials['host'], 
				$_credentials['user'], 
				$_credentials['pass'], 
				$_credentials['db'],
				$_credentials['port'],
				null,
				$flags
			);

			if(mysqli_connect_errno()) 
			{
				//if(isset($_SERVER['SERVER_PROTOCOL'])){header($_SERVER['SERVER_PROTOCOL'].' 503 Service Unavailable');}
				
				if(
					$die_on_error === true || 
					(isset($credentials['die_on_error']) && $credentials['die_on_error'] === true)
				)
				{
					if($this->___settings['connect_error_filepath'] && realpath($this->___settings['connect_error_filepath']))
					{
						include_once realpath($this->___settings['connect_error_filepath']);
						exit();
					}
					else
					{
						//echo "Connection error: ", mysqli_connect_error(),"
					    //  <br/>Please correct your details and try again.";

					    throw new \Exception("Connect Error (".$this->___connectionName."): ".mysqli_connect_error(),mysqli_connect_errno());
					}
					
				}
				else if($this->___settings['die_on_error'])
				{
					if($this->___settings['connect_error_filepath'] && realpath($this->___settings['connect_error_filepath']))
					{
						include_once realpath($this->___settings['connect_error_filepath']);
						exit();
					}
					else
					{
						//echo "Connection error: ", mysqli_connect_error(),"
						 // <br/>Please correct your details and try again.";

						throw new \Exception("Connect Error (".$this->___connectionName."): ".mysqli_connect_error(),mysqli_connect_errno());
					}
					
				}
				
				$this->___connected = false;
			}	
			else
			{
				if(isset($this->___settings['charset_type']))
				{
					$this->___mysqli->set_charset($this->___settings['charset_type']);
				}
			
				$this->___connected = true;
				
				if(isset($this->___settings['time_zone']))
				{
					$this->setTimezone($this->___settings['time_zone']);
				}
			}

			Connection::setOne($this->___connectionName,$this->___mysqli);
			
			return $this->___connected;
		}
		
		public function getServerInfo()
		{
			if(!$this->isConnected())
			{
				$this->connect();
				if(!$this->isConnected()) return false;
			}
			
			return $this->___mysqli->server_info;
		}
		
		public function getConnectError()
		{
			return $this->___mysqli->connect_error;
		}
		
		public function isThreadSafe()
		{
			if(!$this->isConnected())
			{
				$this->connect();
				if(!$this->isConnected()) return false;
			}
			
			return $this->___mysqli->thread_safe;
		}
		
		public function getConnectErrorNo()
		{
			return $this->___mysqli->connect_errno;
		}
		
		public function isTable($table,$db=null,$die_on_error=null)
		{
			$table = $this->escape($table);
			
			if($db === null)
				$res 	= $this->query('SHOW TABLES LIKE "'.$table.'"',$die_on_error);
			else
			{
				$db 	= $this->escape($db);
				$res 	= $this->query('SHOW TABLES FROM '.$db.' LIKE "'.$table.'"',$die_on_error);
			}
			
			return !$this->isEmpty($res);
		}
		
		/*
		 * START innoDB methods only
		 */
		public function setAutoCommit($autoCommit=true)
		{
			if($autoCommit)
				$this->query('SET AUTOCOMMIT=1');
			else
				$this->query('SET AUTOCOMMIT=0');
		}

		public function startTransaction()
		{
			$this->query('START TRANSACTION');
		}

		public function beginTransaction()
		{
			$this->query('BEGIN');
		}

		public function rollback()
		{
			$this->query('ROLLBACK');
		}

		public function commit()
		{
			$this->query('COMMIT');	
		}
		
		public function setUniqueChecks($uniqueChecks=true)
		{
			if(!$this->isConnected())
			{
				$this->connect();
				if(!$this->isConnected()) return false;
			}
			
			if($uniqueChecks)
				$this->query('SET UNIQUE_CHECKS=1');
			else
				$this->query('SET UNIQUE_CHECKS=0');
		}
		/*
		 * END innoDB methods only
		 */
		
		public function getInfo()
		{
			if(!$this->isConnected())
			{
				$this->connect();
				if(!$this->isConnected()) return false;
			}
			
			return $this->___mysqli->info;
		}
		
		public function getError()
		{
			return $this->___mysqli->error;
		}
		
		public function getErrorNo()
		{
			return $this->___mysqli->errno;
		}
		
		public function isError()
		{
			return (boolean) $this->___mysqli->errno;
		}
		
		public function getCharset()
		{
			if(!$this->isConnected())
			{
				$this->connect();
				if(!$this->isConnected()) return false;
			}
			
			return $this->___mysqli->get_charset();
		}
		
		public function setCharset($charset)
		{
			if(!$this->isConnected())
			{
				$this->connect();
				if(!$this->isConnected()) return false;
			}
			
			$this->___mysqli->set_charset($charset);
		}
		
		//mysql close connection
		public function close()
		{
			if($this->isConnected())
			{
				/* Moved under Driver
				if(!$this->___mysqli->isClosed())
				{
					$this->___mysqli->close();
				}
				*/

				$this->___connected = false;
			}
		}
			
		public function getConnectionStatus()
		{
			if($this->___connected)
			{
				$status = new \StdClass();
				$status->connected 	= true;
				$status->host		= $this->___credentials['host'];
				$status->db			= $this->___credentials['db'];
				$status->user		= $this->___credentials['user'];
				
				return $status;
			}
			else
			{
				return false;
			}
		}
		
		public function &prepare($query, $args=self::_noArgs, $prepare_type=true)
		{
			$this->___query 				= $query;
			$this->___args				= $args;
			$this->_prepare_typ 		= $prepare_type;
			
			return $this;
		}

		public function exec()
		{
			$args = func_get_args();
			if(!count($args))
			{
				$args=self::_noArgs;
			}
			
			if($args === self::_noArgs && is_array($this->___args))
				$args = $this->___args;
			else if($args === self::_noArgs && $this->___args !== self::_noArgs)
				$args = array($this->___args);
			else if(!is_array($args) && $args !== self::_noArgs)
				$args = array($args);
			else if($args === self::_noArgs)
				throw new \Exception("Please specify arguments for prepare() and/or execute() methods of \Simpletools\Db\Mysql class.",10001);
			
			if(!$this->isConnected())
			{
				$this->connect();
				if(!$this->isConnected()) return false;
			}
			
			$query = $this->___query;
			
			return $this->_query($this->_prepareQuery($query,$args),null);
			
		}
		
		public function execute($args=self::_noArgs,$die_on_error=null)
		{
			if($args === self::_noArgs && is_array($this->___args))
				$args = $this->___args;
			else if($args === self::_noArgs && $this->___args !== self::_noArgs)
				$args = array($this->___args);
			else if(!is_array($args) && $args !== self::_noArgs)
				$args = array($args);
			else if($args === self::_noArgs)
				throw new \Exception("Please specify arguments for prepare() and/or execute() methods of \Simpletools\Db\Mysql class.",10001);
			
			if(!$this->isConnected())
			{
				$this->connect();
				if(!$this->isConnected()) return false;
			}
			
			$query = $this->___query;
			
			return $this->_query($this->_prepareQuery($query,$args),$die_on_error);
			
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
					
					if($this->_prepare_typ === true)
					{
						$arg = "'".$this->_escape($arg)."'";
					}
					else
					{
						$arg = $this->_escape($arg);
					}
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
		
		public function query($query)
		{
			if(!$this->isConnected())
			{
				$this->connect();
				if(!$this->isConnected()) return false;
			}
			
			return $this->_query($query);
		}
		
		private function _query($query)
		{	
			if(
				!($query instanceof FullyQualifiedQuery) &&
				$this->___mysqli->getDb()!=$this->___current_db && $this->___current_db
			)
			{
				//echo "change DB $this->___current_db\n";
				$this->___mysqli->select_db($this->___current_db);
			}

			//echo $query.'  - '.$this->___current_db." - \n\n";

			$startedAt 		= microtime(true);

			$result = @$this->___mysqli->query($query);

			$endedAt 		= microtime(true);

			Connection::logQuery($startedAt,$endedAt,$query);
			
			/*
			* Connection Error - retrying 1 time
			*/
			if(!$result && $this->___mysqli->errno == 2006)
			{
				$this->___connected = false;
				$this->connect();


				$startedAt 		= microtime(true);

				$result = $this->___mysqli->query($query);

				$endedAt 		= microtime(true);

				Connection::logQuery($startedAt,$endedAt,$query);
			}

			if(
				!$result
			)
			{
				$errorMsg 	= $this->___mysqli->error;
				$errNo 		= $this->___mysqli->errno;

				Connection::logQuery($startedAt,$endedAt,$query,$errorMsg,$errNo);

				throw new \Exception($errorMsg,$errNo);
			}
			
			return new \Simpletools\Db\Mysql\Result($result,$this->___mysqli);
		}
			
		//escaping string against sql injection
		public function escape($string)
		{
			if(!$this->isConnected())
			{
				$this->connect();
				if(!$this->isConnected()) return false;
			}
			
			return $this->_escape($string);
		}
		
		private function _escape($string)
		{
			if($this->___quotes_on)
			{
				$string = stripslashes($string);
			}
			
			return $this->___mysqli->real_escape_string($string);
		}
			
		//returning latest id - after insert
		public function getInsertedId()
		{
			return $this->___mysqli->insert_id;
		}
		
		public function getAffectedRows()
		{
			return $this->___mysqli->affected_rows;
		}
		
		//depracted
		public function affectedRows()
		{
			return $this->getAffectedRows();
		}
		
		public function fetch($result,$returnObject=true)
		{
			return $result->fetch($returnObject);

			/*
			$result = $result->getRawResult();

			if($returnObject)
				return mysqli_fetch_object($result);	
			else
				return mysqli_fetch_array($result,$return_type);			
			*/
		}
		
		public function fetchAll($result,$returnObject=true)
		{
			return $result->fetchAll($returnObject);

			/*
			if($this->isEmpty($result)) return array();
			
			$datas = array();
			while($data = $this->fetch($result,$returnObject,$return_type))
			{
				$datas[] = $data;
			}
				
			$this->free($result);
			return $datas;
			*/
		}
		
		public function free($result)
		{
			$result->free();
			/*
			$r = $r->getRawResult();

			mysqli_free_result($r);
			*/
		}
		
		public function getNumRows($result)
		{
			return $result->length();

			/*
			$result = $result->getRawResult();

			return mysqli_num_rows($result);
			*/
		}
		
		//depracted
		public function checkResult($result)
		{
			return $result->isEmpty();

			/*
			$result = $result->getRawResult();

			return $this->getNumRows($result);
			*/
		}
		
		public function isEmpty($result)
		{
			return $result->isEmpty();
		}
		
		public function isConnected()
		{
			return $this->___connected;
		}
		
		public function getInstanceOfModel($modelName,$initArgs=null,$namespace='')
		{			
			$class = $namespace ? $namespace.'\\'.$modelName.'Model' : $modelName.'Model';

			if($namespace)
			{
				$path = $this->___modelDir.str_replace('\\',DIRECTORY_SEPARATOR,$namespace).'/'.$modelName.'.php';
			}
			else
			{
				$path = $this->___modelDir.'/'.$modelName.'.php';
			}

			if(!class_exists($class) && !@include($path))
			{
				throw new \Exception("Couldn't find model located under: ".$path."; Please specify modelsDir or check your settings.");
			}
			
			$obj = new $class($this->___settings,$this->getConnectionName());
			
			if($obj instanceof \Simpletools\Db\Mysql\Model)
				$obj->setMysqliClass($this->___settings['custom_mysqli_class_name']);
			
			if(is_callable(array($obj,'init')))
			{
				call_user_func_array(array($obj,'init'),$initArgs);
			}
			
			return $obj;
		}
		
		public function isPost($id=false)
		{
			if(!$id)
				return (count($_POST) === 0) ? false : true;
			else
				return isset($_POST[$id]);
		}
		
		public function isQuery($id=false)
		{
			if(!$id)
				return (count($_GET) === 0) ? false : true;
			else
				return isset($_GET[$id]);
		}
		
		public function isRequest($id=false)
		{
			if(!$id)
				return (count($_REQUEST) === 0) ? false : true;
			else
				return isset($_REQUEST[$id]);
		}
		
		public function getQuery($id=null)
		{
			if($id==null) return $_GET;
			return isset($_GET[$id]) ? $_GET[$id] : null;	
		}
		
		public function getPost($id=null)
		{
			if($id==null) return $_POST;
			return isset($_POST[$id]) ? $_POST[$id] : null;	
		}
		
		public function getRequest($id=null)
		{
			if($id==null) return $_REQUEST;
			return isset($_REQUEST[$id]) ? $_REQUEST[$id] : null;	
		}
		
		public function getIterator($query,$params=array())
		{
			if(!$query) return false;

			$query = trim($query);

			preg_match('/(\w+)\s*('.implode('|',array(
				'\=', '\>', '\<', '\>\=', '\<\=', '\<\>', '\!\='
			)).')\s*\:\:(\w+)/i',$query,$matches);
			
			if(!isset($matches[1]) OR !isset($matches[3]) OR !$matches[1]) return false;

			$settings['query'] 				= trim(str_replace('::'.$matches[3],'::',$query));
			$settings['start'] 				= $matches[3];
			$settings['iteratorField'] 		= $matches[1];
			$settings['iteratorDirection'] 	= $matches[2];
			$settings['params']				= $params;

			return new \Simpletools\Db\Mysql\Iterator($this,$settings);
		}

		public function getQueryLog()
		{
			return Connection::getQueryLog();
		}
	}

?>