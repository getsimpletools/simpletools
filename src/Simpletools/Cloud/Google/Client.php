<?php

namespace Simpletools\Cloud\Google;

use Google\Cloud\ServiceBuilder;

/*
 * https://github.com/GoogleCloudPlatform/google-cloud-php
 */
class Client
{
    protected static $_clientSettings;
    protected static $_gCloudClient;

    public static function settings($settings)
    {
        self::$_clientSettings = $settings;
        if(self::$_gCloudClient)
        {
            self::$_gCloudClient = null;
        }
        self::$_gCloudClient   = new ServiceBuilder($settings);
    }

    public static function getSettings()
    {
        return self::$_clientSettings;
    }

    public static function get()
    {
        return self::$_gCloudClient;
    }
}