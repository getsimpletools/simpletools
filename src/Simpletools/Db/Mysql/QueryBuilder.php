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


class QueryBuilder implements \Iterator
{
    protected $_query 	= '';
    protected $_mysql 	= '';

    protected $_result   = null;

    public function __construct($table,$mysql,$columns=array())
    {
        if (!($mysql instanceof Client))
        {
            throw new \Exception("2nd construct argument is not an instance of \Simpletools\Db\Mysql\Client", 404);
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

    protected $_currentJoinIndex = 0;

    public function &join($tableName,$direction='left')
    {
        $tableType = 'table';

        if($tableName instanceof QueryBuilder)
        {
            $tableName = '('.$tableName->getQuery().')';
            $tableType = 'query';
        }

        $this->_query['join'][$this->_currentJoinIndex] = [
            'tableType'		=> $tableType,
            'table'			=> $tableName,
            'direction'		=> $direction
        ];

        return $this;
    }

    public function &leftJoin($tableName)
    {
        return $this->join($tableName,'left');
    }

    public function &rightJoin($tableName)
    {
        return $this->join($tableName,'right');
    }

    public function &innerJoin($tableName)
    {
        return $this->join($tableName,'inner');
    }

    protected function _on($args,$glue='')
    {
        if(
            $args instanceof Sql OR
            $args instanceof Json
        )
        {
            $this->_query['join'][$this->_currentJoinIndex]['on'] = (string) $args;
        }
        else
        {
            $operand 	= '=';
            $left 		= $args[0];

            if(count($args)>2)
            {
                $operand 	= $args[1];
                $right 		= $args[2];
            }
            else
            {
                $right 		= $args[1];
            }

            if($glue)
            {
                $this->_currentJoinIndex--;
                $glue = ' '.$glue.' ';
            }
            else
            {
                $this->_query['join'][$this->_currentJoinIndex]['on'] = '';
            }

            $this->_query['join'][$this->_currentJoinIndex]['on'] .= $glue.$left.' '.$operand.' '.$right;
        }

        $this->_currentJoinIndex++;

        return $this;
    }

    public function &on()
    {
        $args = func_get_args();
        if(count($args)==1) $args = $args[0];

        $this->_on($args,'');

        return $this;
    }

    public function &orOn()
    {
        $args = func_get_args();
        if(count($args)==1) $args = $args[0];

        $this->_on($args,'OR');

        return $this;
    }

    public function &andOn()
    {
        $args = func_get_args();
        if(count($args)==1) $args = $args[0];

        $this->_on($args,'AND');

        return $this;
    }

    public function &using()
    {
        $this->_currentJoinIndex++;

        return $this;
    }

    public function &db($db)
    {
        if(isset($this->_query['db']) OR isset($this->_query['join']))
        {
            $join = @$this->_query['join'][$this->_currentJoinIndex];
            if($join && $join['tableType']=='table')
            {
                $this->_query['join'][$this->_currentJoinIndex]['db'] = $db;
            }
        }
        else
        {
            $this->_query['db'] = $db;
        }

        return $this;
    }

    public function &inDb($db)
    {
        return $this->db($db);
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

    public function &insertDelayed($data)
    {
        $this->_query['type'] = "INSERT DELAYED";
        $this->_query['data'] = $data;

        return $this;
    }

    public function &insertLowPriority($data)
    {
        $this->_query['type'] = "INSERT LOW_PRIORITY";
        $this->_query['data'] = $data;

        return $this;
    }

    public function &insertHighPriority($data)
    {
        $this->_query['type'] = "INSERT HIGH_PRIORITY";
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

    public function insert($data)
    {
        $this->_query['type'] = "INSERT";
        $this->_query['data'] = $data;

        return $this;
    }

    public function onDuplicate($data)
    {
        $this->_query['onDuplicateData'] = $data;

        return $this;
    }

    public function update($data)
    {
        $this->_query['type'] = "UPDATE";
        $this->_query['data'] = $data;

        return $this;
    }

    public function replace($data)
    {
        $this->_query['type'] = "REPLACE";
        $this->_query['data'] = $data;

        return $this;
    }

    public function replaceDelayed($data)
    {
        $this->_query['type'] = "REPLACE DELAYED";
        $this->_query['data'] = $data;

        return $this;
    }

    public function replaceLowPriority($data)
    {
        $this->_query['type'] = "REPLACE LOW_PRIORITY";
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

        return $this;
        //return $this->run();
    }

    public function _escape($value)
    {
        if($value instanceof Sql)
        {
            return (string) $value;
        }
        elseif(is_float($value) || is_integer($value))
        {
            return $value;
        }
        elseif($value instanceof Json)
        {
            $value->setClient($this->_mysql);
            return (string) $value;
        }
        elseif(is_bool($value))
        {
            return (int) $value;
        }
        elseif(is_null($value))
        {
            return null;
        }
        else
        {
            return '"'.$this->_mysql->escape($value).'"';
        }
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

                $arg = $this->_escape($arg);
            }
            elseif(
                $arg instanceof Sql OR
                $arg instanceof Json
            )
            {
                $arg = (string) $arg;
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
        {
            $this->_query['columns']		= "*";
        }

        if(!is_array($this->_query['columns']) && !(
                $this->_query['columns'] instanceof Sql OR
                $this->_query['columns'] instanceof Json
            ))
        {
            $this->_query['columns'] = explode(',',$this->_query['columns']);
        }
        elseif(is_array($this->_query['columns']))
        {
            foreach($this->_query['columns'] as $idx => $column)
            {
                if($column instanceof Json)
                {
                    $this->_query['columns'][$idx] = $column->toArray();
                }

                $this->_query['columns'][$idx] = $this->escapeKey($column);
            }
        }

        $query 		= array();
        $query[] 	= $this->_query['type'];

        if($this->_query['type']=='SELECT')
        {
            $query[] = is_array($this->_query['columns']) ? implode(', ',$this->_query['columns']) : $this->_query['columns'];
            $query[] = 'FROM';
        }
        elseif(
            $this->_query['type']=='INSERT' OR
            $this->_query['type']=='INSERT IGNORE' OR $this->_query['type']=='INSERT LOW_PRIORITY' OR
            $this->_query['type']=='INSERT HIGH_PRIORITY' OR $this->_query['type']=='INSERT DELAYED'
        )
        {
            $query[] = 'INTO';
        }

        if(strpos($this->_query['table'],'.')===false)
        {
            if(!isset($this->_query['db']))
            {
                $this->_query['db'] = $this->_mysql->getCurrentDb();
                if(!$this->_query['db'])
                {
                    throw new \Exception("Please set your Database name under connect settings or using ->setDb", 1);
                }
            }

            $query[] = $this->escapeKey($this->_query['db']).'.'.$this->escapeKey($this->_query['table']);
        }
        else
        {
            $query[] = $this->escapeKey($this->_query['table']);
        }


        if(isset($this->_query['as']))
        {
            $query[] = 'as '.$this->escapeKey($this->_query['as']);
        }

        if(isset($this->_query['join']))
        {
            foreach($this->_query['join'] as $join)
            {
                $db = isset($join['db']) ? $join['db'] : $this->_mysql->getCurrentDb();

                if(strpos($join['table'],'.')===false)
                {
                    $syntax 	= strtoupper($join['direction']).' JOIN '.$this->escapeKey($db.'.'.$join['table']);
                }
                else
                {
                    $syntax 	= strtoupper($join['direction']).' JOIN '.$this->escapeKey($join['table']);
                }

                if(isset($join['as']))
                {
                    $syntax .= ' as '.$join['as'];
                }

                if(isset($join['on']))
                {
                    $syntax .= ' ON ('.$join['on'].')';
                }
                elseif(isset($join['using']))
                {
                    $syntax .= ' USING ('.$join['using'].')';
                }

                $query[] 	= $syntax;
            }
        }

        $setTypes = array(
            'INSERT' 				=> 1,
            'UPDATE' 				=> 1,
            'REPLACE' 				=> 1,
            'REPLACE DELAYED'		=> 1,
            'REPLACE LOW_PRIORITY'	=> 1,
            'INSERT IGNORE'			=> 1,
            'INSERT LOW_PRIORITY'	=> 1,
            'INSERT HIGH_PRIORITY'	=> 1,
            'INSERT DELAYED'		=> 1
        );

        if(isset($setTypes[$this->_query['type']]))
        {
            $query[] = 'SET';

            $set = array();

            foreach($this->_query['data'] as $key => $value)
            {
                if(is_null($value))
                {
                    $set[] = $this->escapeKey($key).' = NULL';
                }
                elseif($value instanceof Json)
                {
                    $value->setDataSourceOut($key);
                    $set[] = $this->_escape($value);
                }
                else
                {
                    $set[] = $this->escapeKey($key).' = '.$this->_escape($value);
                }
            }

            $query[] = implode(', ',$set);
        }

        if(isset($this->_query['onDuplicateData']))
        {
            $query[] = 'ON DUPLICATE KEY UPDATE';

            $set = array();

            foreach($this->_query['onDuplicateData'] as $key => $value)
            {
                if(is_null($value))
                {
                    $set[] = $this->escapeKey($key) . ' = NULL';
                }
                elseif($value instanceof Json)
                {
                    $value->setDataSourceOut($key);
                    $set[] = $this->_escape($value);
                }
                else
                {
                    $set[] = $this->escapeKey($key) . ' = ' . $this->_escape($value);
                }

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
                    {
                        if($operands[1]===null) {
                            $query[] = @$operands[-1] . ' ' . $this->escapeKey($operands[0]) . " IS NULL";
                        }
                        else{
                            $query[] = @$operands[-1] . ' ' . $this->escapeKey($operands[0]) . " = " . $this->_escape($operands[1]);
                        }

                    }
                    else
                    {
                        $operands[1] = strtoupper($operands[1]);

                        if($operands[1] == "IN" AND is_array($operands[2]))
                        {
                            $operands_ = array();

                            foreach($operands[2] as $op)
                            {
                                $operands_[] = $this->_escape($op);
                            }

                            $query[] = @$operands[-1].' '.$this->escapeKey($operands[0])." ".$operands[1]." (".implode(",",$operands_).')';
                        }
                        else
                        {
                            if($operands[2]===null) {
                                $query[] = @$operands[-1] . ' ' . $this->escapeKey($operands[0]) . " " . $operands[1] . " NULL";
                            }
                            else
                            {
                                $query[] = @$operands[-1] . ' ' . $this->escapeKey($operands[0]) . " " . $operands[1] . " " . $this->_escape($operands[2]);
                            }

                        }
                    }
                }
            }
            else
            {
                $query[] = 'id = '.$this->_escape($this->_query['where']);
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

                $query[] = implode(', ',$groupBy);
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

                $query[] = implode(', ',$sort);
            }
        }

        if(isset($this->_query['limit']))
        {
            $query[] = 'LIMIT '.$this->_query['limit'];
        }

        if(isset($this->_query['offset']))
        {
            $query[] = 'OFFSET '.$this->_query['offset'];
        }

        $this->_query = array();
        return new FullyQualifiedQuery(implode(' ',$query));
    }

    /*
    * Prevent SQL Injection on database name, table name, field names
    */
    public function escapeKey($key)
    {
        if(
            $key instanceof Sql OR
            $key instanceof Json
        )
        {
            return (string) $key;
        }
        elseif(trim($key)=='*')
        {
            return '*';
        }
        elseif(strpos($key,'.')===false)
        {
            return "`".trim(str_replace("`","",$key))."`";
        }
        else
        {
            $keys = explode('.',$key);
            foreach($keys as $index => $key)
            {
                $keys[$index] = "`".trim(str_replace("`","",$key))."`";
            }

            return implode('.',$keys);
        }
    }

    public function &whereSql($statement,$vars=null)
    {
        $this->_query['whereSql'] = array('statement'=>$statement,'vars'=>$vars);

        return $this;
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

    public function &find()
    {
        $args = func_get_args();
        if(count($args)==1) $args = $args[0];

        $this->_query['where'][] 	= $args;

        return $this;
    }

    public function &where()
    {
        $args = func_get_args();
        if(count($args)==1) $args = $args[0];

        $this->_query['where'][] 	= $args;

        return $this;
    }

    public function &alternatively()
    {
        $args = func_get_args();

        $args[-1] 	= 'OR';
        $args[0] 	= $args[0];

        $this->_query['where'][] 	= $args;

        return $this;
    }

    public function &also()
    {
        $args = func_get_args();

        $args[-1] 	= 'AND';
        $args[0] 	= $args[0];

        $this->_query['where'][] 	= $args;

        return $this;
    }

    public function &setTable($table)
    {
        $this->_query['table'] 		= $table;

        return $this;
    }

    public function &aka($as)
    {
        if(!isset($this->_query['join']))
            $this->_query['as'] 									= $as;
        else
            $this->_query['join'][$this->_currentJoinIndex]['as'] 	= $as;

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

    public function getFirstRow()
    {
        $this->run();
        return $this->_result->getFirstRow();
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