<?php
namespace Box\View;

/**
 * Acts as a base class for the different Box View APIs.
 */
class Base
{
    /**
     * The request handler.
     *
     * @var Request|null
     */
    protected static $_requestHandler;

    /**
     * The API path relative to the base API path.
     *
     * @var string
     */
    public static $path = '/';

    /**
     * Take a date in almost any format, and return a date string that is
     * formatted as an RFC 3339 timestamp.
     *
     * @param string|DateTime $date A date string in almost any format, or a
     *                              DateTime object.
     *
     * @return string An RFC 3339 timestamp.
     */
    protected static function _date($date)
    {
        if (is_string($date)) $date = new \DateTime($date);
        $date->setTimezone(new \DateTimeZone('UTC'));
        return $date->format('c');
    }

    /**
     * Handle an error. We handle errors by throwing an exception.
     *
     * @param string $error An error code representing the error
     *                      (use_underscore_separators).
     * @param string|null $message The error message.
     *
     * @return void No return value.
     * @throws Box\View\Exception
     */
    protected static function _error($error, $message = null)
    {
        $exception            = new Exception($message);
        $exception->errorCode = $error;
        throw $exception;
    }

    /**
     * Send a new request to the API.
     *
     * @param string $path The path to add after the base path.
     * @param array|null $getParams Optional. An associative array of GET params
     *                              to be added to the URL.
     * @param array|null $postParams Optional. An associative array of POST
     *                               params to be sent in the body.
     * @param array|null $requestOpts Optional. An associative array of request
     *                                options that may modify the way the
     *                                request is made.
     *
     * @return array|string The response is pass-thru from Box\View\Request.
     * @throws Box\View\Exception
     */
    protected static function _request(
        $path,
        $getParams   = [],
        $postParams  = [],
        $requestOpts = []
    ) {
        $requestHandler = static::getRequestHandler();
        return $requestHandler->send(
            $path,
            $getParams,
            $postParams,
            $requestOpts
        );
    }

    /**
     * Return the request handler.
     *
     * @return Request The request handler.
     */
    public static function getRequestHandler()
    {
        if (!isset(static::$_requestHandler)) {
            $requestHandler = new Request(Client::getApiKey(), static::$path);
            static::setRequestHandler($requestHandler);
        }

        return static::$_requestHandler;
    }

    /**
     * Set the request handler.
     *
     * @param Request $requestHandler The request handler.
     *
     * @return void No return value.
     */
    public static function setRequestHandler($requestHandler)
    {
        static::$_requestHandler = $requestHandler;
    }
}
