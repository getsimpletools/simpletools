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
 * @externalDependency 		http://mongodb.github.io/mongo-php-library/api/
 */

	namespace Simpletools\Db\Mongo;

	use Simpletools\Db\Mongo\QueryBuilder;

	class Client
	{
		protected $___connectionName 	= '';
		protected static $___instance 	= '';
		protected $___settings 			= '';
		protected $___modelsDir			= '';
		protected $___credentials		= array();
		protected $___client			= null;

		protected $___current_db 		= null;
		protected $___connected			= false;

		public function __construct(array $settings=null,$connectionName='default')
		{
			$this->___connectionName = $connectionName;
			$this->setSettings($settings);

			if(!isset(self::$___instance[$connectionName])) 
			{
				self::$___instance[$connectionName] = &$this;
			}
		}

		public static function &settings($settings)
		{
			$connectionName = (isset($settings['connectionName']) ? $settings['connectionName'] : 'default');

			if(!isset(self::$___instance[$connectionName]))
			    new static($settings,$connectionName);
		    else
		    	self::$___instance[$connectionName]->setSettings($settings);
		    	
		    return self::$___instance[$connectionName];
		}

		public function setSettings($settings)
		{
			$this->___settings	= $settings;
			$this->___settings['connectErrorFilepath'] 		= isset($settings['connectErrorFilepath']) ? $settings['connectErrorFilepath'] : false;
			
			if(isset($settings['modelsDir']))
			{
				$this->___modelsDir = $settings['modelsDir'];
			}
					
			$this->setCredentials($settings);
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

		public function setCredentials($settings)
		{
			$this->___credentials['connectionUri'] 			= isset($settings['connectionUri']) ? $settings['connectionUri'] : null;
			$this->___credentials['connectionOptions']	 	= isset($settings['connectionOptions']) ? $settings['connectionOptions'] : array();

			$this->___connectionName						= isset($settings['connectionName']) ? $settings['connectionName'] : 'default';

			//could be already defined e.g. under model
			if(!$this->___current_db)
			{
				$this->___current_db 					= isset($settings['db']) ? $settings['db'] : '';
			}

			return true;
		}

		public function isConnected()
		{
			return $this->___connected;
		}

		public function connect()
		{
			if($this->___connected) return true;

			if(($connector = Connection::getOne($this->___connectionName)))
			{
				$this->___client 		= &$connector;
				$this->___connected 	= true;
				return true;
			}

			$this->___client 						= new \MongoDB\Driver\Manager(
				$this->___credentials['connectionUri'],
				$this->___credentials['connectionOptions']
			);

			$this->___connected 	= true;
			Connection::setOne($this->___connectionName,$this->___client);

			return true;
		}

		public function db($db)
		{
			return $this->___client->{$db};
		}

		public function setDb($db)
		{
			$this->___current_db = $db;

			return true;
		}

		public function __get($collection)
		{
			if(!$this->isConnected())
			{
				$this->connect();
			}

			if(!$this->___current_db)
			{
				throw new \Exception('Please specify your default database first');
			}

			return new QueryBuilder($this->___current_db,$collection,$this->___client);
		}

		public function getConnectionName()
		{
			return $this->___connectionName;
		}

		public function getSettings()
		{
			return $this->___settings;
		}

		public function getObjectId($id=null)
		{
			return (!$id) ? new \MongoDB\BSON\ObjectID() : new \MongoDB\BSON\ObjectID($id);
		}
	}

?>