<?php
// require composer packages
require_once dirname(__FILE__) . '/../vendor/autoload.php';

// require our exception class
require_once dirname(__FILE__) . '/BoxView/Exception.php';

// require the different Box View classes
require_once dirname(__FILE__) . '/BoxView/Document.php';
require_once dirname(__FILE__) . '/BoxView/Session.php';

/**
 * Provides access to the Box View API. This is a base class that can be used
 * standalone with full access to the other Box View API classes (Document,
 * Download, and Session), and is also used internally by the other Box View
 * API classes for generic methods including error and request.
 */
class BoxView {
    /**
     * The developer's BoxView API key.
     * 
     * @var string
     */
    public static $apiKey;
    
    /**
     * A good set of default curl options.
     * 
     * @var array
     */
    public static $curlDefaultOptions = [
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_USERAGENT => 'box-view-php',
    ];

    /**
     * A good set of default Guzzle options.
     * 
     * @var array
     */
    public static $guzzleDefaultOptions = [
        'headers' => [
            'Accept' => 'application/json',
            'Authorization' => null,
            'User-Agent' => 'box-view-php',
        ],
        'connect_timeout' => 10,
        'timeout' => 60,
    ];

    /**
     * The default protocol (Box View uses HTTPS).
     * 
     * @var string
     */
    public static $protocol = 'https';
    
    /**
     * The default host
     * 
     * @var string
     */
    public static $host = 'view-api.box.com';
    
    /**
     * The default base path on the server where the API lives.
     * 
     * @var string
     */
    public static $basePath = '/1';
    
    /**
     * An API path relative to the base API path.
     * 
     * @var string
     */
    public static $path = '/';

    /**
     * Get a new Guzzle instance using sensible defaults.
     * 
     * @return Guzzle A new Guzzle instance.
     */
    private static function _getGuzzleInstance($host = null) {
        if (!$host) $host = static::$host;
        $defaults = static::$guzzleDefaultOptions;
        $defaults['headers']['Authorization'] = 'Token ' . static::$apiKey;
        return new GuzzleHttp\Client([
            'base_url' => static::$protocol . '://' . $host,
            'defaults' => $defaults,
        ]);
    }

    /**
     * Check if there is an HTTP error, and returns a brief error description
     * if there is.
     * 
     * @param string $httpCode The HTTP code returned by the API server.
     * 
     * @return string Brief error description.
     */
    private static function _handleHttpError($httpCode) {
        $http4xxErrorCodes = [
            400 => 'bad_request',
            401 => 'unauthorized',
            404 => 'not_found',
            405 => 'method_not_allowed',
            429 => 'too_many_requests',
        ];
        
        if (isset($http4xxErrorCodes[$httpCode])) {
            return 'server_error_' . $httpCode . '_'
                   . $http4xxErrorCodes[$httpCode];
        }
        
        if ($httpCode >= 500 && $httpCode < 600) {
            return 'server_error_' . $httpCode . '_unknown';
        }
    }

    /**
     * Send a request to the server and return the response, while retrying
     * based on any Retry-After headers that are sent back.
     * 
     * @param Guzzle $guzzle The Guzzle instance to use.
     * @param GuzzleHttp\Message\Request $request The request to send, and
     *                                               possibly retry.
     * 
     * @return GuzzleHttp\Message\Response The Guzzle response object.
     * @throws BoxView_Exception
     */
    private static function _sendRequest($guzzle, $request) {
        $response = $guzzle->send($request);
        $headers = $response->getHeaders();

        if (!empty($headers['Retry-After'])) {
            sleep($headers['Retry-After'][0]);
            return static::_sendRequest($guzzle, $request);
        }

        return $response;
    }

    /**
     * Take a date in almost any format, and return a date string that is
     * formatted as an RFC 3339 timestamp.
     * 
     * @param string|DateTime $date A date string in almost any format, or a
     *                                 DateTime object.
     * 
     * @return string An RFC 3339 timestamp.
     */
    protected static function _date($date) {
        if (is_string($date)) $date = new DateTime($date);
        $date->setTimezone(new DateTimeZone('UTC'));
        return $date->format('c');
    }
    
    /**
     * Handle an error. We handle errors by throwing an exception.
     * 
     * @param string $error An error code representing the error
     *                      (use_underscore_separators).
     * @param string $message The error message.
     * @param GuzzleHttp\Message\Request $request The Guzzle request object.
     * @param GuzzleHttp\Message\Response $response The Guzzle response object.
     * 
     * @return void No return value.
     * @throws BoxView_Exception
     */
    protected static function _error($error, $message = null, $request = null,
                                     $response = null) {
        if (!empty($request)) {
            $message .= "\n";
            $message .= 'Method: ' . $request->getMethod() . "\n";
            $message .= 'URL: ' . $request->getUrl() . "\n";
            $message .= 'Query: ' . json_encode($request->getQuery()->toArray())
                      . "\n";
            $message .= 'Headers: ' . json_encode($request->getHeaders())
                      . "\n";
            $message .= 'Request Body: ' . $request->getBody() . "\n";
        }

        if (!empty($response)) {
            $message .= "\n";
            $message .= 'Response: ' . $response->getBody() . "\n";
        }
        
        $exception = new BoxView_Exception($message);
        $exception->errorCode = $error;
        throw $exception;
    }
    
    /**
     * Make an HTTP request. Some of the params are polymorphic - getParams and
     * postParams. 
     * 
     * @param string $path The path to add after the base path.
     * @param array $getParams An array of GET params to be added to the URL -
     *                            this can also be a string.
     * @param array $postParams An array of GET params to be added to the URL -
     *                             this can also be a string.
     * @param array $requestOpts An array of request options that may modify
     *                              the way the request is made.
     * 
     * @return array|string The response array is usually converted from JSON,
     *                      but sometimes we just return the raw response from
     *                      the server.
     * @throws BoxView_Exception
     */
    protected static function _request($path, $getParams = [], $postParams = [],
                                       $requestOpts = []) {
        $host = null;
        if (!empty($requestOpts['host'])) $host = $requestOpts['host'];
        $guzzle = static::_getGuzzleInstance($host);

        $options = array();

        $method = 'GET';

        if (!empty($requestOpts['file'])) {
            $method = 'POST';

            $options['body'] = !empty($postParams) ? $postParams : [];
            $options['body']['file'] = $requestOpts['file'];
        } elseif (!empty($postParams)) {
            $method = 'POST';
            $options['json'] = $postParams;
        }

        if (!empty($requestOpts['httpMethod'])) {
            $method = $requestOpts['httpMethod'];
        }

        if (!empty($requestOpts['rawResponse'])) {
            $options['headers']['Accept'] = '*/*';
        }

        $url = static::$basePath . static::$path . $path;
        if (!empty($getParams)) $options['query'] = $getParams;

        try {
            $request = $guzzle->createRequest($method, $url, $options);
            $response = static::_sendRequest($guzzle, $request);
        } catch (GuzzleHttp\Exception\RequestException $e) {
            $request = $e->getRequest();
            $response = $e->getResponse();

            $error = static::_handleHttpError($response->getStatusCode());
            $message = 'Server Error';

            if (!$error) {
                $error = 'guzzle_error';
                $message = 'Guzzle Error';
            }

            return static::_error($error, $message, $request, $response);
        }

        $responseBody = (string) $response->getBody();
        
        // if we don't want a raw response, then it's JSON
        if (empty($requestOpts['rawResponse'])) {
            $jsonDecoded = json_decode($responseBody, true);

            if ($jsonDecoded === false || $jsonDecoded === null) {
                return static::_error('server_response_not_valid_json',
                                      $request, $response);
            }
            
            if (is_array($jsonDecoded) && !empty($jsonDecoded['error'])) {
                return static::_error($jsonDecoded['error'], $request,
                                      $response);
            }
            
            $responseBody = $jsonDecoded;
        }
        
        return $responseBody;
    }

    /**
     * Get the API key.
     * 
     * @return string The API key.
     */
    public static function getApiKey() {
        return static::$apiKey;
    }
    
    /**
     * Set the API key.
     * 
     * @param string $apiKey The API key.
     * 
     * @return void No return value.
     */
    public static function setApiKey($apiKey) {
        static::$apiKey = $apiKey;
    }
}
