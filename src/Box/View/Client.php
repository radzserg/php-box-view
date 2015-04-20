<?php
namespace Box\View;

/**
 * Provides access to the Box View API.
 */
class Client
{
    /**
     * The developer's Box View API key.
     * @var string
     */
    private $apiKey;

    /**
     * The request handler.
     * @var \Box\View\Request
     */
    private $requestHandler;

    /**
     * Instantiate the client.
     *
     * @param string $apiKey The API key to use.
     */
    public function __construct($apiKey)
    {
        $this->setApiKey($apiKey);
    }

    /**
     * Get a list of all documents that meet the provided criteria.
     *
     * @param array|null $params Optional. An associative array to filter the
     *                           list of all documents uploaded. None are
     *                           necessary; all are optional. Use the following
     *                           options:
     *                             - int|null 'limit' The number of documents to
     *                               return.
     *                             - string|DateTime|null 'createdBefore' Upper
     *                               date limit to filter by.
     *                             - string|DateTime|null 'createdAfter' Lower
     *                               date limit to filter by.
     *
     * @return array An array containing document instances matching the
     *               request.
     * @throws Box\View\BoxViewException
     */
    public function findDocuments($params = [])
    {
        return Document::find($this, $params);
    }

    /**
     * Get the API key.
     *
     * @return string The API key.
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * Create a new document instance by ID, and load it with values requested
     * from the API.
     *
     * @param string $id The document ID.
     *
     * @return Box\View\Document A document instance using data from the API.
     * @throws Box\View\BoxViewException
     */
    public function getDocument($id)
    {
        return Document::get($this, $id);
    }

    /**
     * Return the request handler.
     *
     * @return Request The request handler.
     */
    public function getRequestHandler()
    {
        if (!isset($this->requestHandler)) {
            $this->setRequestHandler(new Request($this->getApiKey()));
        }

        return $this->requestHandler;
    }

    /**
     * Set the API key.
     *
     * @param string $apiKey The API key.
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * Set the request handler.
     *
     * @param Request $requestHandler The request handler.
     *
     * @return void
     */
    public function setRequestHandler($requestHandler)
    {
        $this->requestHandler = $requestHandler;
    }

    /**
     * Upload a local file and return a new document instance.
     *
     * @param resource $file The file resource to upload.
     * @param array|null $params Optional. An associative array of options
     *                           relating to the file upload. None are
     *                           necessary; all are optional. Use the following
     *                           options:
     *                             - string|null 'name' Override the filename of
     *                               the file being uploaded.
     *                             - string[]|string|null 'thumbnails' An array
     *                               of dimensions in pixels, with each
     *                               dimension formatted as [width]x[height],
     *                               this can also be a comma-separated string.
     *                             - bool|null 'nonSvg' Create a second version
     *                               of the file that doesn't use SVG, for users
     *                               with browsers that don't support SVG?
     *
     * @return Box\View\Document A new document instance.
     * @throws Box\View\BoxViewException
     */
    public function uploadFile($file, $params = [])
    {
        return Document::uploadFile($this, $file, $params);
    }

    /**
     * Upload a file by URL and return a new document instance.
     *
     * @param string $url The URL of the file to upload.
     * @param array|null $params Optional. An associative array of options
     *                           relating to the file upload. None are
     *                           necessary; all are optional. Use the following
     *                           options:
     *                             - string|null 'name' Override the filename of
     *                               the file being uploaded.
     *                             - string[]|string|null 'thumbnails' An array
     *                               of dimensions in pixels, with each
     *                               dimension formatted as [width]x[height],
     *                               this can also be a comma-separated string.
     *                             - bool|null 'nonSvg' Create a second version
     *                               of the file that doesn't use SVG, for users
     *                               with browsers that don't support SVG?
     *
     * @return Box\View\Document A new document instance.
     * @throws Box\View\BoxViewException
     */
    public function uploadUrl($url, $params = [])
    {
        return Document::uploadUrl($this, $url, $params);
    }
}
