<?php

namespace Simpletools\Cloud\Google;

/*

Google\Cloud\ServiceBuilder is depricated as of 0.172.0
Deprecated:

use Google\Cloud\Core\ServiceBuilder instead.

*/

use Google\Cloud\Core\ServiceBuilder;

/*
 * https://github.com/GoogleCloudPlatform/google-cloud-php
 */
class Client
{
    protected static $_clientSettings;
    protected static $_gCloudClient;

    protected static $_activeUser;

    public static function settings($settings)
    {
        $clientEmail = @$settings['keyFile']['client_email'];

        if(!isset(self::$_gCloudClient[$clientEmail])) {
            self::$_clientSettings[$clientEmail] = $settings;
            self::$_gCloudClient[$clientEmail]   = new ServiceBuilder($settings);
        }

        if(!self::$_activeUser)
            self::$_activeUser = $clientEmail;
    }

    public static function activeSettingsByClientEmail($clientEmail)
    {
        if(!isset(self::$_gCloudClient[$clientEmail]))
            throw new \Exception('Access key is missing for: '.$clientEmail,400);

        self::$_activeUser = $clientEmail;
    }

    public static function getSettings()
    {
        return self::$_clientSettings[self::$_activeUser];
    }

    public static function get()
    {
        return self::$_gCloudClient[self::$_activeUser];
    }
}