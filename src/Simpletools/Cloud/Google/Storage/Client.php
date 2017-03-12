<?php

namespace Simpletools\Cloud\Google\Storage;

class Client
{
    protected static $_client;
    public static function get()
    {
        if(!self::$_client)
        {
            self::$_client  = \Simpletools\Cloud\Google\Client::get()->storage();
        }

        return self::$_client;
    }
}