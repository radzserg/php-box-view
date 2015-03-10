<?php
namespace Box\View;

/**
 * Makes a request to the Box View API.
 */
class Request
{
    /**
     * The default protocol (Box View uses HTTPS).
     *
     * @const string
     */
    const PROTOCOL = 'https';

    /**
     * The default host
     *
     * @const string
     */
    const HOST = 'view-api.box.com';

    /**
     * The default base path on the server where the API lives.
     *
     * @const string
     */
    const BASE_PATH = '/1';

    /**
     * The API key.
     *
     * @var string
     */
    private $_apiKey;

    /**
     * The path after the base path before the request path.
     *
     * @var string
     */
    private $_path;

    /**
     * A good set of default Guzzle options.
     *
     * @var array
     */
    public static $guzzleDefaultOptions = [
        'headers' => [
            'Accept'        => 'application/json',
            'Authorization' => null,
            'User-Agent'    => 'box-view-php',
        ],
        'connect_timeout' => 10,
        'timeout' => 60,
    ];

   /**
     * Execute a request to the server and return the response, while retrying
     * based on any Retry-After headers that are sent back.
     *
     * @param GuzzleHttp\Client $guzzle The Guzzle instance to use.
     * @param GuzzleHttp\Message\Request $request The request to send, and
     *                                            possibly retry.
     *
     * @return GuzzleHttp\Message\Response The Guzzle response object.
     * @throws GuzzleHttp\Exception\RequestException
     */
    private function _execute($guzzle, $request)
    {
        $response = $guzzle->send($request);
        $headers  = $response->getHeaders();

        if (!empty($headers['Retry-After'])) {
            sleep($headers['Retry-After'][0]);
            return $this->_execute($guzzle, $request);
        }

        return $response;
    }

    /**
     * Get a new Guzzle instance using sensible defaults.
     *
     * @param string|null $host Optional. The host to use in the base URL.
     *
     * @return GuzzleHttp\Client A new Guzzle instance.
     */
    private function _getGuzzleInstance($host = null)
    {
        if (!$host) $host = static::HOST;

        $defaults                             = static::$guzzleDefaultOptions;
        $defaults['headers']['Authorization'] = 'Token ' . $this->_apiKey;

        return new \GuzzleHttp\Client([
            'base_url' => static::PROTOCOL . '://' . $host,
            'defaults' => $defaults,
        ]);
    }

    /**
     * Check if there is an HTTP error, and returns a brief error description
     * if there is.
     *
     * @param string $httpCode The HTTP code returned by the API server.
     *
     * @return string|null Brief error description.
     */
    private static function _handleHttpError($httpCode)
    {
        $http4xxErrorCodes = [
            400 => 'bad_request',
            401 => 'unauthorized',
            404 => 'not_found',
            405 => 'method_not_allowed',
            415 => 'unsupported_media_type',
            429 => 'too_many_requests',
        ];

        if (isset($http4xxErrorCodes[$httpCode])) {
            return 'server_error_' . $httpCode . '_'
                   . $http4xxErrorCodes[$httpCode];
        }

        if ($httpCode >= 500 && $httpCode < 600) {
            return 'server_error_' . $httpCode . '_unknown';
        }

        return null;
    }

    /**
     * Handle a request error from Guzzle.
     *
     * @param GuzzleHttp\Exception\RequestException e The Guzzle request error.
     *
     * @return void No return value.
     * @throws Box\View\Exception
     */
    private static function _handleRequestError($e)
    {
        $request  = $e->getRequest();
        $response = $e->getResponse();

        $error   = static::_handleHttpError($response->getStatusCode());
        $message = 'Server Error';

        if (!$error) {
            $error   = 'guzzle_error';
            $message = 'Guzzle Error';
        }

        static::_error($error, $message, $request, $response);
    }

    /**
     * Handle the response from the server. Raw responses are returned without
     * checking anything. JSON responses are decoded and then checked for
     * any errors.
     *
     * @param GuzzleHttp\Message\Response $response The Guzzle response object.
     * @param bool $isRawResponse Do we want to return the raw response, or
     *                            process as JSON?
     * @param GuzzleHttp\Message\Request The Guzzle request object.
     *
     * @return array|string An array decoded from JSON, or the raw response from
     *                      the server.
     * @throws Box\View\Exception
     */
    private static function _handleResponse($response, $isRawResponse, $request)
    {
        $responseBody = (string) $response->getBody();

        // if we want a raw response, then it's not JSON, and we're done
        if (!empty($isRawResponse)) {
            return $responseBody;
        }

        // decode json and handle any potential errors
        $jsonDecoded = json_decode($responseBody, true);

        if ($jsonDecoded === false || $jsonDecoded === null) {
            $error = 'server_response_not_valid_json';
            return static::_error($error, null, $request, $response);
        }

        if (
            is_array($jsonDecoded)
            && isset($jsonDecoded['status'])
            && $jsonDecoded['status'] == 'error'
        ) {
            $error   = 'server_error';
            $message = !empty($jsonDecoded['error_message'])
                       ? $jsonDecoded['error_message']
                       : 'Server Error';
            return static::_error($error, $message, $request, $response);
        }

        return $jsonDecoded;
    }

    /**
     * Handle an error. We handle errors by throwing an exception.
     *
     * @param string $error An error code representing the error
     *                      (use_underscore_separators).
     * @param string|null $message The error message.
     * @param GuzzleHttp\Message\Request|null $request Optional. The Guzzle
     *                                                 request object.
     * @param GuzzleHttp\Message\Response|null $response Optional. The Guzzle
     *                                                   response object.
     *
     * @return void No return value.
     * @throws Box\View\Exception
     */
    protected static function _error(
        $error,
        $message  = null,
        $request  = null,
        $response = null
    ) {
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

        $exception            = new Exception($message);
        $exception->errorCode = $error;
        throw $exception;
    }

    /**
     * Set the API key.
     *
     * @param string $apiKey The API key.
     * @param string $path The path after the base path before the request path.
     *
     * @return void No return value.
     */
    public function __construct($apiKey, $path)
    {
        $this->_apiKey = $apiKey;
        $this->_path   = $path;
    }

    /**
     * Send an HTTP request.
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
     * @return array|string The response array is usually converted from JSON,
     *                      but sometimes we just return the raw response from
     *                      the server.
     * @throws Box\View\Exception
     */
    public function send(
        $path,
        $getParams   = [],
        $postParams  = [],
        $requestOpts = []
    ) {
        $host = null;
        if (!empty($requestOpts['host'])) $host = $requestOpts['host'];

        $guzzle = $this->_getGuzzleInstance($host);

        $options = array();
        $method  = 'GET';

        if (!empty($requestOpts['file'])) {
            $method                  = 'POST';
            $options['body']         = !empty($postParams) ? $postParams : [];
            $options['body']['file'] = $requestOpts['file'];
        } elseif (!empty($postParams)) {
            $method          = 'POST';
            $options['json'] = $postParams;
        }

        if (!empty($requestOpts['httpMethod'])) {
            $method = $requestOpts['httpMethod'];
        }

        if (!empty($requestOpts['rawResponse'])) {
            $options['headers']['Accept'] = '*/*';
        }

        $url = static::BASE_PATH . $this->_path . $path;
        if (!empty($getParams)) $options['query'] = $getParams;

        try {
            $request  = $guzzle->createRequest($method, $url, $options);
            $response = $this->_execute($guzzle, $request);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            static::_handleRequestError($e);
        }

        $rawResponse = !empty($requestOpts['rawResponse']);
        return static::_handleResponse($response, $rawResponse, $request);
    }
}
