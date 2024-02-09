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
    protected 			$___credentials		= [];
    protected 			$___connected 		= [];
    protected 			$___modelDir 		= '';
    protected			$___quotes_on 		= '';
    protected			$___query			= '';
    protected static 	$___instance 		= null;
    protected 			$___settings 		= null;
    protected 			$___mysqli			= null;
    protected 			$___args 			= '';
    protected           $_queryServer       = 'master';
    protected           $_forcedMasterServer = false;
    
    public              $_prepare_typ       = null;
    const 				_noArgs				= '$--SimpleMySQL--n0-aRg5--';

    protected 			$___current_db 		= null;
    protected 			$___connectionName	= 'default';

    public function __construct(array $settings=null,$connectionName='default')
    {
        mysqli_report(MYSQLI_REPORT_OFF);
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
        $settings['charset_type']	                        = isset($settings['charset']) ? $settings['charset'] : (isset($settings['charset_type']) ? $settings['charset_type'] : null);

        $this->___settings	= $settings;

        $this->___settings['time_zone'] 					= isset($settings['timezone']) ? $settings['timezone'] : @$settings['time_zone'];
        $this->___settings['die_on_error']				    = isset($settings['die_on_error']) ? (boolean) $settings['die_on_error'] : true;
        $this->___settings['custom_mysqli_class_name'] 	    = isset($settings['custom_mysqli_class_name']) ? (string) $settings['custom_mysqli_class_name'] : false;

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
        //$this->quotes_on = (get_magic_quotes_gpc()==1 || get_magic_quotes_runtime()==1) ? true : false;

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
        return $this->___mysqli[$this->_queryServer]->select_db($db);
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

    protected function _prepareCredentials($settings)
    {
        $creds = [];

        $creds['host'] 		    = isset($settings['host']) ? $settings['host'] : 'localhost';
        $creds['user']	 	    = isset($settings['user']) ? $settings['user'] : null;
        $creds['pass'] 		    = isset($settings['pass']) ? $settings['pass'] : null;
        $creds['port'] 		    = isset($settings['port']) ? $settings['port'] : 3306;

        $creds['compression'] 	= isset($settings['compression']) ? (boolean) $settings['compression'] : false;
        $creds['ssl'] 			= isset($settings['ssl']) ? (boolean) $settings['ssl'] : false;

        return $creds;
    }

    public function setCredentials($settings)
    {
        if(!isset($settings['master']))
            $this->___credentials['master']         = $this->_prepareCredentials($settings);
        elseif(isset($settings['master']))
            $this->___credentials['master']         = $this->_prepareCredentials($settings['master']);

        if(isset($settings['slave']))
            $this->___credentials['slave']          = $this->_prepareCredentials($settings['slave']);

        $this->___credentials['db']                 = @$settings['db'];

        //could be already defined e.g. under model
        if(!$this->___current_db)
        {
            $this->___current_db = @$settings['db'];
        }
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
        return $this->___mysqli[$this->_queryServer]->options(MYSQLI_OPT_CONNECT_TIMEOUT,$time);
    }

    public function setTimezone($timezone,$queryServer=null)
    {
        if(!$this->isConnected())
        {
            $this->connect();
            if(!$this->isConnected()) return false;
        }

        $this->query(new FullyQualifiedQuery('SET time_zone = "'.$this->escape($timezone).'"'),$queryServer);
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

    public function connect()
    {
        if($this->isConnected()) return true;

        $_credentials['db']				= $this->___credentials['db'];

        $_credentials['host']			= $this->___credentials[$this->_queryServer]['host'];
        $_credentials['user']			= $this->___credentials[$this->_queryServer]['user'];
        $_credentials['pass']			= $this->___credentials[$this->_queryServer]['pass'];
        $_credentials['port']			= $this->___credentials[$this->_queryServer]['port'];
        $_credentials['compression']	= $this->___credentials[$this->_queryServer]['compression'];
        $_credentials['ssl']			= $this->___credentials[$this->_queryServer]['ssl'];

        if(!$_credentials['host'])
        {
            throw new \Exception('Please specify connection settings before',111);
        }

        if(($connector = Connection::getOne($this->___connectionName.'/'.$this->_queryServer)))
        {
            $this->___mysqli[$this->_queryServer] 		= &$connector;
            $this->___connected[$this->_queryServer] 	    = true;

            return true;
        }

        $mysqli_class = '\Simpletools\Db\Mysql\Driver';

        if($this->___settings['custom_mysqli_class_name'] != false)
            $mysqli_class = $this->___settings['custom_mysqli_class_name'];

        $this->___mysqli[$this->_queryServer] = new $mysqli_class();
//        $this->___mysqli[$this->_queryServer]->connect();
        $this->setTimeout();

        //$this->___current_db could be already defined under model etc.
        if(!$this->___current_db && isset($_credentials['db']) && $_credentials['db'])
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

        @$this->___mysqli[$this->_queryServer]->real_connect(
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

            $this->___connected[$this->_queryServer] = false;
        }
        else
        {

            if(isset($this->___settings['charset_type']))
            {
                $this->___mysqli[$this->_queryServer]->set_charset($this->___settings['charset_type']);
            }


            $this->___connected[$this->_queryServer] = true;

            if(isset($this->___settings['time_zone']))
            {
                $this->setTimezone($this->___settings['time_zone'],$this->_queryServer);
            }
        }


        Connection::setOne($this->___connectionName.'/'.$this->_queryServer,$this->___mysqli[$this->_queryServer]);

        return $this->___connected[$this->_queryServer];
    }

    public function getServerInfo($queryServer = null)
    {
        $this->_manualSetQueryServer($queryServer);

        if(!$this->isConnected())
        {
            $this->connect();
            if(!$this->isConnected()) return false;
        }

        return $this->___mysqli[$this->_queryServer]->server_info;
    }

    public function getConnectError()
    {
        return $this->___mysqli[$this->_queryServer]->connect_error;
    }

    public function isThreadSafe($queryServer = null)
    {
        $this->_manualSetQueryServer($queryServer);

        if(!$this->isConnected())
        {
            $this->connect();
            if(!$this->isConnected()) return false;
        }

        return $this->___mysqli[$this->_queryServer]->thread_safe();
    }

    public function getConnectErrorNo()
    {
        return $this->___mysqli[$this->_queryServer]->connect_errno;
    }

    public function isTable($table,$db=null,$queryServer=null)
    {
        $this->_manualSetQueryServer($queryServer);
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
    public function setAutoCommit($autoCommit=true,$queryServer=null)
    {
        if($autoCommit)
            $this->query('SET AUTOCOMMIT=1',$queryServer);
        else
            $this->query('SET AUTOCOMMIT=0',$queryServer);
    }

    public function startTransaction($queryServer=null)
    {
        $this->query('START TRANSACTION',$queryServer);
    }

    public function beginTransaction($queryServer=null)
    {
        $this->query('BEGIN',$queryServer);
    }

    public function rollback($queryServer=null)
    {
        $this->query('ROLLBACK',$queryServer);
    }

    public function commit($queryServer=null)
    {
        $this->query('COMMIT',$queryServer);
    }

    public function setUniqueChecks($uniqueChecks=true,$queryServer=null)
    {
        if($uniqueChecks)
            $this->query('SET UNIQUE_CHECKS=1',$queryServer);
        else
            $this->query('SET UNIQUE_CHECKS=0',$queryServer);
    }
    /*
     * END innoDB methods only
     */

    public function getInfo($queryServer=null)
    {
        $this->_manualSetQueryServer($queryServer);

        if(!$this->isConnected())
        {
            $this->connect();
            if(!$this->isConnected()) return false;
        }

        return $this->___mysqli[$this->_queryServer]->info;
    }

    public function getError()
    {
        return $this->___mysqli[$this->_queryServer]->error;
    }

    public function getErrorNo()
    {
        return $this->___mysqli[$this->_queryServer]->errno;
    }

    public function isError()
    {
        return (boolean) $this->___mysqli[$this->_queryServer]->errno;
    }

    public function getCharset($queryServer=null)
    {
        $this->_manualSetQueryServer($queryServer);

        if(!$this->isConnected())
        {
            $this->connect();
            if(!$this->isConnected()) return false;
        }

        return $this->___mysqli[$this->_queryServer]->get_charset();
    }

    public function setCharset($charset,$queryServer=null)
    {
        $this->_manualSetQueryServer($queryServer);

        if(!$this->isConnected())
        {
            $this->connect();
            if(!$this->isConnected()) return false;
        }

        $this->___mysqli[$this->_queryServer]->set_charset($charset);
    }

    //mysql close connection
    public function close()
    {
        if($this->isConnected())
        {
            /* Moved under Driver
            if(!$this->___mysqli[$this->_queryServer]->isClosed())
            {
                $this->___mysqli[$this->_queryServer]->close();
            }
            */

            unset($this->___connected[$this->_queryServer]);
        }
    }

    public function getConnectionStatus($queryServer=null)
    {
        $this->_manualSetQueryServer($queryServer);

        if(isset($this->___connected[$this->_queryServer]) && $this->___connected[$this->_queryServer])
        {
            $status = new \StdClass();
            $status->connected 	= true;
            $status->host		= $this->___credentials[$this->_queryServer]['host'];
            $status->db			= $this->___credentials['db'];
            $status->user		= $this->___credentials[$this->_queryServer]['user'];

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

        return $this->_query($this->_prepareQuery($query,$args));

    }

    public function execute($args=self::_noArgs)
    {
        if($args === self::_noArgs && is_array($this->___args))
            $args = $this->___args;
        else if($args === self::_noArgs && $this->___args !== self::_noArgs)
            $args = array($this->___args);
        else if(!is_array($args) && $args !== self::_noArgs)
            $args = array($args);
        else if($args === self::_noArgs)
            throw new \Exception("Please specify arguments for prepare() and/or execute() methods of \Simpletools\Db\Mysql class.",10001);

        $query = $this->___query;
        $this->_setQueryServer($query);

        if(!$this->isConnected())
        {
            $this->connect();
            if(!$this->isConnected()) return false;
        }

        return $this->_query($this->_prepareQuery($query,$args));
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

    public function forceMasterServer($force=true)
    {
        if($force) {
            $this->_forcedMasterServer = true;
            $this->_manualSetQueryServer('master');
        }
        else{
            $this->_forcedMasterServer = false;
        }

        return $this->_forcedMasterServer;
    }

    protected function _manualSetQueryServer($queryServer)
    {
        if($this->_forcedMasterServer) return $this->_queryServer;

        if(!isset($this->___credentials['slave']))
        {
            return $this->_queryServer = 'master';
        }

        if($queryServer == 'slave' || $queryServer == 'master')
        {
            return $this->_queryServer = $queryServer;
        }

        return false;
    }

    protected function _setQueryServer($query)
    {
        if($this->_forcedMasterServer) return $this->_queryServer;

        if(!isset($this->___credentials['slave']))
        {
            return $this->_queryServer = 'master';
        }

        $query = trim($query);

        if(
            stripos($query,'SELECT')===0 OR
            stripos($query,'SET')===0 OR
            stripos($query,'SHOW')===0
        )
        {
            $this->_queryServer = 'slave';
        }
        else
        {
            $this->_queryServer = 'master';
        }

        return $this->_queryServer;
    }

    public function query($query,$queryServer=null)
    {
        if(!$this->_manualSetQueryServer($queryServer))
        {
            $this->_setQueryServer($query);
        }

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
            $this->___mysqli[$this->_queryServer]->getDb()!=$this->___current_db && $this->___current_db
        )
        {
            //echo "change DB $this->___current_db\n";
            $this->___mysqli[$this->_queryServer]->select_db($this->___current_db);
        }

        $startedAt 		= microtime(true);

        $result = @$this->___mysqli[$this->_queryServer]->query($query);

        $endedAt 		= microtime(true);

        Connection::logQuery($startedAt,$endedAt,$query);

        /*
        * Connection Error - retrying 1 time
        */
        if(!$result && $this->___mysqli[$this->_queryServer]->errno == 2006)
        {
            unset($this->___connected[$this->_queryServer]);
            Connection::cleanOne($this->___connectionName.'/'.$this->_queryServer);

            $this->connect();

            $startedAt 		= microtime(true);

            $result = $this->___mysqli[$this->_queryServer]->query($query);

            $endedAt 		= microtime(true);

            Connection::logQuery($startedAt,$endedAt,$query);
        }

        if(
            !$result
        )
        {
            $errorMsg 	= $this->___mysqli[$this->_queryServer]->error;
            $errNo 		= $this->___mysqli[$this->_queryServer]->errno;

            Connection::logQuery($startedAt,$endedAt,$query,$errorMsg,$errNo);

            throw new \Exception($errorMsg,$errNo);
        }

        $result = new \Simpletools\Db\Mysql\Result($result,$this->___mysqli[$this->_queryServer]);

        if($this->_columnsMap)
            $result->setColumnMap($this->_columnsMap);

        return $result;
    }

    protected $_columnsMap;

    public function columnMap(array $map)
    {
        return $this->setColumnMap($map);
    }

    public function setColumnMap(array $map)
    {
        $this->_columnsMap = $map;

        return $this;
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
//        if($this->___quotes_on)
//        {
//            $string = stripslashes($string);
//        }

        return $this->___mysqli[$this->_queryServer]->real_escape_string($string);
    }

    //returning latest id - after insert
    public function getInsertedId()
    {
        return $this->___mysqli[$this->_queryServer]->insert_id;
    }

    public function getAffectedRows()
    {
        return $this->___mysqli[$this->_queryServer]->affected_rows;
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
        return isset($this->___connected[$this->_queryServer]) && $this->___connected[$this->_queryServer];
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
