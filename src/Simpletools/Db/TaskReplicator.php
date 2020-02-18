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

namespace Simpletools\Db;


class TaskReplicator
{
	protected $_query;
	protected $_target;
	protected $_process;
	protected $_after;

	public function from($query)
	{
		$this->_query = $query;
		return $this;
	}

	public function to($target)
	{
		$this->_target = $target;
		return $this;
	}

	public function process($target)
	{
		$this->_process = $target;
		return $this;
	}

	public function after($caller)
	{
		$this->_after = $caller;
		return $this;
	}

	public function run()
	{
		if(!$this->_query) throw new \Exception('Missing Query. Use ->from($query) to add Query',400);
		if(!$this->_target) throw new \Exception('Missing Target. Use ->to($target) to add Target',400);
		if(!$this->_process) throw new \Exception('Missing process function. Use ->process(function($item, $target){}) to add process function',400);

		$res  = $this->_query->run();


		foreach ($res as $item)
		{
			$process = $this->_process;
			$process($item, $this->_target);
		}
		
		if($this->_after)
		{
			$after = $this->_after;
			$after($this->_query, $this->_target);
		}
		elseif ($this->_target instanceof \Simpletools\Db\Cassandra\Batch || $this->_target instanceof \Simpletools\Db\Elasticsearch\Batch)
		{
			$this->_target->runIfNotEmpty();
		}
		

		return $this;
	}


	
}

?>