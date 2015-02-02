<?php
namespace Box\View;

/**
 * Provides access to the Box View API.
 */
class Client
{
    /**
     * The developer's Box View API key.
     * 
     * @var string
     */
    private static $_apiKey;

    /**
     * Get the API key.
     * 
     * @return string The API key.
     */
    public static function getApiKey()
    {
        return static::$_apiKey;
    }

    /**
     * Set the API key.
     * 
     * @param string $apiKey The API key.
     * 
     * @return void No return value.
     */
    public static function setApiKey($apiKey)
    {
        static::$_apiKey = $apiKey;
    }
}
