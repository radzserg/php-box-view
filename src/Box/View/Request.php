<?php
namespace Box\View;

/**
 * Makes a request to the Box View API.
 */
class Request
{
    /**
     * Request error codes.
     * @const string
     */
    const BAD_REQUEST_ERROR            = 'bad_request';
    const GUZZLE_ERROR                 = 'guzzle_error';
    const JSON_RESPONSE_ERROR          = 'server_response_not_valid_json';
    const METHOD_NOT_ALLOWED_ERROR     = 'method_not_allowed';
    const NOT_FOUND_ERROR              = 'not_found';
    const REQUEST_TIMEOUT_ERROR        = 'request_timeout';
    const SERVER_ERROR                 = 'server_error';
    const TOO_MANY_REQUESTS_ERROR      = 'too_many_requests';
    const UNAUTHORIZED_ERROR           = 'unauthorized';
    const UNSUPPORTED_MEDIA_TYPE_ERROR = 'unsupported_media_type';

    /**
     * The default protocol (Box View uses HTTPS).
     * @const string
     */
    const PROTOCOL = 'https';

    /**
     * The default host.
     * @const string
     */
    const HOST = 'view-api.box.com';

    /**
     * The default base path on the server where the API lives.
     * @const string
     */
    const BASE_PATH = '/1';

    /**
     * The number of seconds before timing out when in a retry loop.
     * @const int
     */
    const DEFAULT_RETRY_TIMEOUT = 60;

    /**
     * A good set of default Guzzle options.
     * @var array
     */
    public static $guzzleDefaultOptions = [
        'headers'         => [
            'Accept'        => 'application/json',
            'Authorization' => null,
            'User-Agent'    => 'box-view-php',
        ],
        'connect_timeout' => 10,
        'timeout'         => 60,
    ];

    /**
     * The API key.
     * @var string
     */
    private $apiKey;

    /**
     * The timestamp of the last request.
     * @var string
     */
    private $timestampRequested;

    /**
     * Set the API key.
     *
     * @param string $apiKey The API key.
     */
    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * Send an HTTP request.
     *
     * @param string $path The path to add after the base path.
     * @param array|null $getParams Optional. An associative array of GET params
     *                              to be added to the URL.
     * @param array|null $postParams Optional. An associative array of POST
     *                               params to be sent in the body.
     * @param array|null $requestOptions Optional. An associative array of
     *                                   request options that may modify the way
     *                                   the request is made.
     *
     * @return array|string The response array is usually converted from JSON,
     *                      but sometimes we just return the raw response from
     *                      the server.
     * @throws \Box\View\BoxViewException
     */
    public function send(
        $path,
        $getParams      = [],
        $postParams     = [],
        $requestOptions = []
    ) {
        $host = null;
        if (!empty($requestOptions['host'])) $host = $requestOptions['host'];

        $guzzle = $this->getGuzzleInstance($host);

        $options = ['headers' => []];
        $method  = 'GET';

        if (!empty($requestOptions['file'])) {
            $method                  = 'POST';
            $options['body']         = !empty($postParams) ? $postParams : [];
            $options['body']['file'] = $requestOptions['file'];
        } elseif (!empty($postParams)) {
            $method          = 'POST';
            $options['json'] = $postParams;
        }

        if (!empty($requestOptions['httpMethod'])) {
            $method = $requestOptions['httpMethod'];
        }

        if (!empty($requestOptions['rawResponse'])) {
            $options['headers']['Accept'] = '*/*';
        }

        $url = static::BASE_PATH . $path;
        if (!empty($getParams)) $options['query'] = $getParams;

        try {
            $request = $guzzle->createRequest($method, $url, $options);

            $timeout = !empty($requestOptions['timeout'])
                       ? $requestOptions['timeout']
                       : static::DEFAULT_RETRY_TIMEOUT;
            $this->timestampRequested = time();

            $response = $this->execute($guzzle, $request, $timeout);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            static::handleRequestError($e);
        }

        $rawResponse = !empty($requestOptions['rawResponse']);
        return static::handleResponse($response, $rawResponse, $request);
    }

    /**
     * Handle an error. We handle errors by throwing an exception.
     *
     * @param string $error An error code representing the error
     *                      (use_underscore_separators).
     * @param string|null $message The error message.
     * @param \GuzzleHttp\Message\RequestInterface|null $request Optional. The Guzzle
     *                                                 request object.
     * @param \GuzzleHttp\Message\ResponseInterface|null $response Optional. The Guzzle
     *                                                   response object.
     *
     * @return void
     * @throws \Box\View\BoxViewException
     */
    protected static function error(
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
            $message .= 'Response Body: ' . $response->getBody() . "\n";
        }

        $exception            = new BoxViewException($message);
        $exception->errorCode = $error;
        throw $exception;
    }

   /**
     * Execute a request to the server and return the response, while retrying
     * based on any Retry-After headers that are sent back.
     *
     * @param \GuzzleHttp\Client $guzzle The Guzzle instance to use.
     * @param \GuzzleHttp\Message\RequestInterface $request The request to send, and
     *                                            possibly retry.
     * @param int $timeout The maximum number of seconds to retry for.
     *
     * @return \GuzzleHttp\Message\ResponseInterface The Guzzle response object.
     * @throws \GuzzleHttp\Exception\RequestException
     */
    private function execute($guzzle, $request, $timeout)
    {
        $response = $guzzle->send($request);
        $headers  = $response->getHeaders();

        if (!empty($headers['Retry-After'])) {
            $seconds = round(time() - $this->timestampRequested);

            if ($timeout > 0 && $seconds >= $timeout) {
                $message = 'The request timed out after retrying for '
                           . $seconds . ' seconds.';
                static::error(
                    static::REQUEST_TIMEOUT_ERROR,
                    $message,
                    $request,
                    $response
                );
            }

            sleep($headers['Retry-After'][0]);
            return $this->execute($guzzle, $request, $timeout);
        }

        return $response;
    }

    /**
     * Get a new Guzzle instance using sensible defaults.
     *
     * @param string|null $host Optional. The host to use in the base URL.
     *
     * @return \GuzzleHttp\Client A new Guzzle instance.
     */
    private function getGuzzleInstance($host = null)
    {
        if (!$host) $host = static::HOST;

        $defaults                             = static::$guzzleDefaultOptions;
        $defaults['headers']['Authorization'] = 'Token ' . $this->apiKey;

        return new \GuzzleHttp\Client([
            'base_url' => static::PROTOCOL . '://' . $host,
            'defaults' => $defaults,
        ]);
    }

    /**
     * Check if there is an HTTP error, and returns a brief error description if
     * there is.
     *
     * @param string $httpCode The HTTP code returned by the API server.
     *
     * @return string|null Brief error description.
     */
    private static function handleHttpError($httpCode)
    {
        $http4xxErrorCodes = [
            400 => static::BAD_REQUEST_ERROR,
            401 => static::UNAUTHORIZED_ERROR,
            404 => static::NOT_FOUND_ERROR,
            405 => static::METHOD_NOT_ALLOWED_ERROR,
            415 => static::UNSUPPORTED_MEDIA_TYPE_ERROR,
            429 => static::TOO_MANY_REQUESTS_ERROR,
        ];

        if (isset($http4xxErrorCodes[$httpCode])) {
            return $http4xxErrorCodes[$httpCode];
        }

        if ($httpCode >= 500 && $httpCode < 600) {
            return static::SERVER_ERROR;
        }

        return null;
    }

    /**
     * Handle a request error from Guzzle.
     *
     * @param \GuzzleHttp\Exception\RequestException e The Guzzle request error.
     *
     * @return void
     * @throws \Box\View\BoxViewException
     */
    private static function handleRequestError($e)
    {
        $request  = $e->getRequest();
        $response = $e->getResponse();

        $error   = static::handleHttpError($response->getStatusCode());
        $message = 'Server error';

        if (!$error) {
            $error   = static::GUZZLE_ERROR;
            $message = 'Guzzle error';
        }

        static::error($error, $message, $request, $response);
    }

    /**
     * Handle the response from the server. Raw responses are returned without
     * checking anything. JSON responses are decoded and then checked for
     * any errors.
     *
     * @param \GuzzleHttp\Message\ResponseInterface $response The Guzzle response object.
     * @param bool $isRawResponse Do we want to return the raw response, or
     *                            process as JSON?
     * @param \GuzzleHttp\Message\RequestInterface The Guzzle request object.
     *
     * @return array|string An array decoded from JSON, or the raw response from
     *                      the server.
     * @throws \Box\View\BoxViewException
     */
    private static function handleResponse($response, $isRawResponse, $request)
    {
        $responseBody = (string) $response->getBody();

        // if we want a raw response, then it's not JSON, and we're done
        if (!empty($isRawResponse)) {
            return $responseBody;
        }

        // decode json and handle any potential errors
        $jsonDecoded = json_decode($responseBody, true);

        if ($jsonDecoded === false || $jsonDecoded === null) {
            return static::error(
                static::JSON_RESPONSE_ERROR,
                null,
                $request,
                $response
            );
        }

        if (
            is_array($jsonDecoded)
            && isset($jsonDecoded['status'])
            && $jsonDecoded['status'] == 'error'
        ) {
            $message = !empty($jsonDecoded['error_message'])
                       ? $jsonDecoded['error_message']
                       : 'Server error';
            return static::error(
                static::SERVER_ERROR,
                $message,
                $request,
                $response
            );
        }

        return $jsonDecoded;
    }
}
