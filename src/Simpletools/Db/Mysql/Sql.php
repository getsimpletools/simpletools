<?php

namespace Simpletools\Db\Mysql;

class Sql
{
    protected $_statement = '';

    public function __construct($statement)
    {
        $this->_statement = $statement;
    }

    public function __toString()
    {
        return $this->_statement;
    }
}