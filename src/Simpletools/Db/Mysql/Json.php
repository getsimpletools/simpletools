<?php

namespace Simpletools\Db\Mysql;

class Json
{
    protected $_columnName;
    protected $_targetColumn;

    protected $_set = array();
    protected $_remove = array();

    protected $_payload = array();

    protected $_type;
    protected $_client;

    public static function get($dataSource,$dataSourceOut=null)
    {
        $json = new static($dataSource,$dataSourceOut);
        return $json;
    }

    public function __construct($dataSource,$dataSourceOut=null)
    {
        $this->_columnName = $dataSource;
        $this->_targetColumn = $dataSourceOut;
    }

    public function remove($path)
    {
        if(!isset($this->_payload['remove']))
            $this->_payload['remove'] = array();

        $this->_payload['remove'][$path]   = 1;

        return $this;
    }

    public function setDataSourceOut($dataSourceOut)
    {
        $this->_targetColumn = $dataSourceOut;

        return $this;
    }

    public function set($path,$value)
    {
        if(!isset($this->_payload['set']))
            $this->_payload['set'] = array();

        $this->_payload['set'][$path]   = $value;

        return $this;
    }

    public function setClient(Client $client)
    {
        $this->_client = $client;
    }

    public function __toString()
    {
        $queries    = array();

        foreach($this->_payload as $type => $cmd)
        {
            $query = (($this->_targetColumn) ? $this->_targetColumn : $this->_columnName).' = ';

            if ($type == 'set') {
                $query .= " JSON_SET(IFNULL({$this->_columnName},'{}')";

                foreach ($cmd as $path => $value) {
                    $query .= ", '$." . $this->_escape($path) . "', " . $this->_prepareValue($value);
                }
            } elseif ($type == 'remove') {
                $query .= "JSON_REMOVE(IFNULL({$this->_columnName},'{}')";

                foreach ($cmd as $path => $value) {
                    $query .= ", '$." . $this->_escape($path) . "'";
                }
            }

            $query .= ')';

            $queries[] = $query;
        }

        return implode(', ',$queries);
    }

    protected function _prepareValue($value)
    {
        $query = '';

        if(
            is_object($value) OR
            ($value && is_array($value) && !isset($value[0]))
        )
        {
            $query .= " JSON_OBJECT(";
            $args = [];

            foreach($value as $key => $value)
            {
                $args[] = "'".$this->_escape($key)."'";
                $args[] = $this->_prepareValue($value);
            }

            $query .= implode(',', $args).') ';
        }
        elseif(is_array($value))
        {
            $query .= " JSON_ARRAY(";
            $args = [];

            foreach($value as $key => $value)
            {
                $args[] = $this->_prepareValue($value);
            }

            $query .= implode(',', $args).') ';
        }
        elseif(is_integer($value) OR is_float($value))
        {
            $query = $value;
        }
        elseif(is_bool($value))
        {
            $query = var_export($value,true);
        }
        else
        {
            $query = "'".$this->_escape($value)."'";
        }

        return $query;
    }

    protected function _escape($value)
    {
        if($this->_client)
            return $this->_client->escape($value);
        else
            return $value;
    }

}