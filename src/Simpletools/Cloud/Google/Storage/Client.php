<?php

namespace Simpletools\Cloud\Google\Storage;

class Client
{
    protected static $_client;
    public static function get()
    {
        $settings   = \Simpletools\Cloud\Google\Client::getSettings();
        $client     = @$settings['keyFile']['client_email'];

        if(!isset(self::$_client[$client]) OR !self::$_client[$client])
        {
            self::$_client[$client]  = \Simpletools\Cloud\Google\Client::get()->storage();
        }

        return self::$_client[$client];
    }
}