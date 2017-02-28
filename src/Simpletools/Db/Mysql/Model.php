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

	use \Simpletools\Db\Mysql\QueryBuilder;

	class Model extends \Simpletools\Db\Mysql\Client
	{
		public function __construct($settings=false,$connectionName='default')
		{
			$this->___connectionName 	= $connectionName;
			$this->___current_db 		= defined('static::CURRENT_DB') ? static::CURRENT_DB : '';
			
			if($settings)
			{
				parent::__construct($settings,$connectionName);
			}
		}

		public function __get($table)
		{
			$query = new QueryBuilder($table,$this);
			$this->___switchTmpDb($query);

			return $query;
		}

		public function __call($table,$args)
		{
			$query = new QueryBuilder($table,$this,$args);
			$this->___switchTmpDb($query);

			return $query;
		}

		protected $___tmpDb = '';

		public function db($db)
		{
			$this->___tmpDb = $db;

			return $this;
		}

		protected function ___switchTmpDb($query)
		{
			if($this->___tmpDb)
			{
				$query->inDb($this->___tmpDb);
				$this->___tmpDb = '';
			}
		}

		public function table($table)
		{
			$args 	= func_get_args();
			$table 	= array_shift($args);

			$query = new QueryBuilder($table,$this,$args);
			$this->___switchTmpDb($query);

			return $query;
		}

		public function getConnectionName()
		{
			return defined('static::CONNECTION_NAME') ? static::CONNECTION_NAME : 'default';
		}

		public function getClient()
		{
			return \Simpletools\Db\Mysql\Client::getInstance($this->getConnectionName());
		}

		public function injectDependency()
		{
			$this->setSettings($this->getClient()->getSettings());
		}
					
	}
?>