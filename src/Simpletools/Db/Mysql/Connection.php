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

use \Simpletools\Events\Event;

class Connection
{
	protected static $_selfObject 		= null;
	protected static $_connectors 		= array();

	protected static $_logQuerySettings = null;
	protected static $_logTrace 		= array();

	public static function cleanOne($name)
    {
        if(isset(self::$_connectors[$name])) {
            unset(self::$_connectors[$name]);
        }
    }

	public static function getOne($name)
	{
		return isset(self::$_connectors[$name]) ? self::$_connectors[$name] : null;
	}

	public static function setOne($name,$connector)
	{
		self::$_connectors[$name] = $connector;
	}

	public static function logQuery($start,$end,$query,$errMsg=null,$errNo=null)
	{
		if(self::$_logQuerySettings)
		{
			$query 		= trim($query);
			$duration 	= $end - $start;

			if(isset(self::$_logQuerySettings['ignore']))
			{
				foreach(self::$_logQuerySettings['ignore'] as $ignore)
				{
					if(stripos($query,$ignore.' ')===0)
					{
						return ;
					}
				}
			}

			if(
				$errMsg OR
				!isset(self::$_logQuerySettings['minTimeSec']) OR
				$duration > self::$_logQuerySettings['minTimeSec']
			)
			{
				$trace = self::getQueryLogTrace($start, $end, $query, $duration, $errMsg, $errNo);

				if(isset(self::$_logQuerySettings['emitEvent']))
				{
					Event::fire(self::$_logQuerySettings['emitEvent'], array('query'=>$trace));
				}

				if(!isset(self::$_logQuerySettings['emitEventOnly']) OR !self::$_logQuerySettings['emitEventOnly'])
				{
					self::$_logTrace[] = $trace;
				}
			}
		}
	}

	public static function logSettings($settings)
	{
		if(isset($settings['ignore']) && !is_array($settings['ignore']))
		{
			$settings['ignore'] = array($settings['ignore']);
		}

		self::$_logQuerySettings = $settings;
	}

	public static function getQueryLog()
	{
		return self::$_logTrace;
	}

	protected static function getQueryLogTrace($start,$end,$query,$duration,$errMsg,$errNo)
	{
		$trace = array(
			'startedAt'	=> $start,
			'endedAt'	=> $end,
			'queryTime'	=> $duration,
			'query'		=> (string) $query
		);

		if($errMsg)
		{
			$trace['error'] = array(
				'msg'	=> $errMsg,
				'code'	=> $errNo
			);
		}

		return $trace;
	}
}

?>