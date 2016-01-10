<?php
/*
 * Simpletools Framework.
 * Copyright (c) 2009, Marcin Rosinski. (http://www.getsimpletools.com)
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
 * @description		Secure connections handler
 * @copyright  		Copyright (c) 2009 Marcin Rosinski. (http://www.getsimpletools.com)
 * @license    		http://www.opensource.org/licenses/bsd-license.php - BSD
 * 
 */

namespace Simpletools\Http\Multi;

use Simpletools\Http\Multi\Client;

class Client
{
	private static $_onUrlRequest	= null;
	private static $_onSuccess		= null;
	private static $_onError		= null;
	
	private static $_storage		= array();

	public static function onUrlRequest($onUrlRequest)
	{
		self::$_onUrlRequest = $onUrlRequest;
	}

	public static function onSuccess($onSuccess)
	{
		self::$_onSuccess = $onSuccess;
	}
	
	public static function onError($onError)
	{
		self::$_onError = $onError;
	}
	
	private static function _store($id,$u)
	{
		self::$_storage[(integer) $id] = $u;
	}
	
	private static function _getRetriesAndIncr($id)
	{
		if(!isset(self::$_storage[(integer) $id])) return false;
		return self::$_storage[(integer) $id]->increaseRetries();
	}
	
	private static function _retrieve($id,$delete=true)
	{
		if(!isset(self::$_storage[(integer) $id])) return false;
		
		$u = self::$_storage[(integer) $id];
		if($delete)
			unset(self::$_storage[(integer) $id]);
			
		return $u;
	}
	
	public static function run($threads=5, $throttling=100)
	{
		$stopAsking=false;
		$urls = array();	

		$mcurl = curl_multi_init();
		$threadsRunning = 0;
		$urls_id = 0;
		$urls_processed = 0;
		
		$onUrlRequest 	= self::$_onUrlRequest;
		$onSuccess 		= self::$_onSuccess;
		$onError 		= self::$_onError;
		
		for($i=0;$i<($threads-1);$i++)
		{
			$nextUrl = $onUrlRequest($i);
			if(!$nextUrl) break;
			$urls[] = $nextUrl;
		}
		
		if(count($urls) == 0) return 0;

		for(;;) 
		{
			// Fill up the slots
			while ($threadsRunning < $threads && $urls_id < count($urls)) 
			{
				$ch = curl_init();
				$u = $urls[$urls_id++];
				
				self::_store($ch,$u);
				
				$settings = $u->getSettings();
				foreach($settings as $k => $s){
					curl_setopt($ch, $k, $s);
				}
				
				curl_setopt($ch, CURLOPT_URL, $u->getRequestedUrl());
				
				$urls_processed++;
				curl_multi_add_handle($mcurl, $ch);
				$threadsRunning++;
				
				if($urls_processed > ($threads-2))
				{
					if(!$stopAsking)
					{
						$nextUrl 	= $onUrlRequest($urls_processed);
						//@TODO - typecheck if($nextUrl!==false OR )
						
						if($nextUrl === false) 
						{
							$stopAsking=true;
							continue;
						}
					
						$urls[] 	= $nextUrl;
					}
				}
			}
			
			// Check if done
			if ($threadsRunning == 0 && $urls_id >= count($urls))
				break;
			// Let mcurl do it's thing
			curl_multi_select($mcurl);
			while(($mcRes = curl_multi_exec($mcurl, $mcActive)) == CURLM_CALL_MULTI_PERFORM) usleep($throttling);
			if($mcRes != CURLM_OK) break;
			
			while($done = curl_multi_info_read($mcurl)) 
			{
				$ch 				= $done['handle'];
				$_EFFECTIVE_URL 	= curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
				$_BODY		 		= curl_multi_getcontent($ch);
				$_HEADERS 			= curl_getinfo($ch);
				
				if(curl_errno($ch) == 0 && substr($_HEADERS['http_code'],0,1) == 2) 
				{
					array_shift($urls);
					$urls_id--;
					$u = self::_retrieve($done['handle']);
					if($u) 
					{
						$u->setEffectiveUrl($_EFFECTIVE_URL)->setHeaders($_HEADERS)->setBody($_BODY);
						$onSuccess($u);
					}
				} 
				else 
				{
					//$ret = self::_getRetriesAndIncr($done['handle']);
					array_shift($urls);
					$urls_id--;
					$u = self::_retrieve($done['handle']);
					if($u)
					{
						$u->setEffectiveUrl($_EFFECTIVE_URL)->setHeaders($_HEADERS)->setBody($_BODY);
						$onError($u);
					}
				}
				curl_multi_remove_handle($mcurl, $ch);
				curl_close($ch);
				$threadsRunning--;
			}
			
			if($nextUrl === false) continue;
		}
		
		curl_multi_close($mcurl);
		return $urls_processed;
	}
}

?>