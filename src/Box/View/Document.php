<?php
namespace Box\View;

/**
 * Provide access to the Box View Document API. The Document API is used for
 * uploading, checking status, and deleting documents.
 *
 * Document objects have the following fields:
 *   - string 'id' The document ID.
 *   - string 'createdAt' The date the document was created, formatted as
 *                        RFC 3339.
 *   - string 'name' The document title.
 *   - string 'status' The document status, which can be 'queued', 'processing',
 *                     'done', or 'error'.
 *
 * Only the following fields can be updated:
 *   - string 'name' The document title.
 *
 * When finding documents, the following parameters can be set:
 *   - int|null 'limit' The number of documents to return.
 *   - string|DateTime|null 'createdBefore' Upper date limit to filter by.
 *   - string|DateTime|null 'createdAfter' Lower date limit to filter by.
 *
 * When uploading a file, the following parameters can be set:
 *   - string|null 'name' Override the filename of the file being uploaded.
 *   - string[]|string|null 'thumbnails' An array of dimensions in pixels, with
 *                                       each dimension formatted as
 *                                       [width]x[height], this can also be a
 *                                       comma-separated string.
 *   - bool|null 'nonSvg' Create a second version of the file that doesn't use
 *                        SVG, for users with browsers that don't support SVG?
 *
 */
class Document extends Base
{
    /**
     * Document error-codes
     */
    const INVALID_RESPONSE = 'invalid_response';
    const INVALID_FILE = 'invalid_file';

    /**
     * An alternate hostname that file upload requests are sent to.
     * @const string
     */
    const FILE_UPLOAD_HOST = 'upload.view-api.box.com';

    /**
     * The Document API path relative to the base API path.
     * @var string
     */
    public static $path = '/documents';


    /**
     * The fields that can be updated on a document.
     * @var array
     */
    public static $updateableFields = ['name'];

    /**
     * The date the document was created, formatted as RFC 3339.
     * @var string
     */
    private $createdAt;

    /**
     * The document ID.
     * @var string
     */
    private $id;

    /**
     * The document title.
     * @var string
     */
    private $name;

    /**
     * The document status, which can be 'queued', 'processing', 'done', or
     * 'error'.
     * @var string
     */
    private $status;

    /**
     * Instantiate the document.
     *
     * @param Box\View\Client $client The client instance to make requests from.
     * @param array $data An associative array to instantiate the object with.
     *                    Use the following values:
     *                      - string 'id' The document ID.
     *                      - string 'createdAt' The date the document was
     *                        created, formatted as RFC 3339.
     *                      - string 'name' The document title.
     *                      - string 'status' The document status, which can be
     *                        'queued', 'processing', 'done', or 'error'.
     */
    public function __construct($client, $data)
    {
        $this->client = $client;

        $this->id = $data['id'];
        $this->setValues($data);
    }

    /**
     * Create a session for a specific document.
     *
     * @param array|null $params Optional. An associative array of options
     *                           relating to the new session. None are
     *                           necessary; all are optional. Use the following
     *                           options:
     *                             - int|null 'duration' The number of minutes
     *                               for the session to last.
     *                             - string|DateTime|null 'expiresAt' When the
     *                               session should expire.
     *                             - bool|null 'isDownloadable' Should the user
     *                               be allowed to download the original file?
     *                             - bool|null 'isTextSelectable' Should the
     *                               user be allowed to select text?
     *
     * @return Box\View\Session A new session instance.
     * @throws Box\View\Exception
     */
    public function createSession($params = [])
    {
        return Session::create($this->client, $this->id, $params);
    }

    /**
     * Delete a file.
     *
     * @return bool Was the file deleted?
     * @throws Box\View\Exception
     */
    public function delete()
    {
        $path = '/' . $this->id;
        $response = static::request($this->client, $path, null, null, [
            'httpMethod'  => 'DELETE',
            'rawResponse' => true,
        ]);

        // a successful delete returns nothing, so we return true in that case
        return empty($response);
    }

    /**
     * Download a file using a specific extension or the original extension.
     *
     * @param string|null $extension Optional. The extension to download the
     *                               file in, which can be pdf or zip. If no
     *                               extension is provided, the file will be
     *                               downloaded using the original extension.
     *
     * @return string The contents of the downloaded file.
     * @throws Box\View\Exception
     */
    public function download($extension = null)
    {
        if ($extension) $extension = '.' . $extension;
        $path = '/' . $this->id . '/content' . $extension;
        return static::request($this->client, $path, null, null, [
            'rawResponse' => true,
        ]);
    }

    /**
     * Get the date the document was created, formatted as RFC 3339.
     *
     * @return string The date the document was created, formatted as RFC 3339.
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Get the document ID.
     *
     * @return string The document ID.
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the document title.
     *
     * @return string The document title.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the document status.
     *
     * @return string The document title.
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Download a thumbnail of a specific size for a file.
     *
     * @param int $width The width of the thumbnail in pixels.
     * @param int $height The height of the thumbnail in pixels.
     *
     * @return string The contents of the downloaded thumbnail.
     * @throws Box\View\Exception
     */
    public function thumbnail($width, $height)
    {
        $path      = '/' . $this->id . '/thumbnail';
        $getParams = [
            'height' => $height,
            'width'  => $width,
        ];
        return static::request($this->client, $path, $getParams, null, [
            'rawResponse' => true,
        ]);
    }

    /**
     * Update specific fields for the metadata of a file .
     *
     * @param array $fields An associative array of the fields to update on the
     *                      file. Only the 'name' field is supported at this
     *                      time.
     *
     * @return bool Was the file updated?
     * @throws Box\View\Exception
     */
    public function update($fields)
    {
        $path       = '/' . $this->id;
        $postParams = [];

        foreach (self::$updateableFields as $field) {
            if (isset($fields[$field])) $postParams[$field] = $fields[$field];
        }

        $metadata = static::request($this->client, $path, null, $postParams, [
            'httpMethod' => 'PUT',
        ]);

        $this->setValues($metadata);
        return true;
    }

    /**
     * Get a list of all documents that meet the provided criteria.
     *
     * @param Box\View\Client $client The client instance to make requests from.
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
     * @throws Box\View\Exception
     */
    public static function find($client, $params = [])
    {
        $getParams = [];
        if (!empty($params['limit'])) $getParams['limit'] = $params['limit'];

        if (!empty($params['createdBefore'])) {
            $createdBefore               = $params['createdBefore'];
            $getParams['created_before'] = static::date($createdBefore);
        }

        if (!empty($params['createdAfter'])) {
            $createdAfter               = $params['createdAfter'];
            $getParams['created_after'] = static::date($createdAfter);
        }

        $response = static::request($client, null, $getParams);

        if (empty($response)
            || empty($response['document_collection'])
            || !isset($response['document_collection']['entries'])
        ) {
            $message = '$response is not in a valid format.';
            return static::error(static::INVALID_RESPONSE, $message);
        }

        $documents = [];

        foreach ($response['document_collection']['entries'] as $metadata) {
            $documents[] = new Document($client, $metadata);
        }

        return $documents;
    }

   /**
     * Create a new document instance by ID, and load it with values requested
     * from the API.
     *
     * @param Box\View\Client $client The client instance to make requests from.
     * @param string $id The document ID.
     *
     * @return Box\View\Document A document instance using data from the API.
     * @throws Box\View\Exception
     */
    public static function get($client, $id)
    {
        $fields   = ['id', 'created_at', 'name', 'status'];
        $metadata = static::request($client, '/' . $id, [
            'fields' => implode(',', $fields),
        ]);

        return new self($client, $metadata);
    }

    /**
     * Upload a local file and return a new document instance.
     *
     * @param Box\View\Client $client The client instance to make requests from.
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
     * @throws Box\View\Exception
     */
    public static function uploadFile($client, $file, $params = [])
    {
        if (!is_resource($file)) {
            $message = '$file is not a valid file resource.';
            return static::error(static::INVALID_FILE, $message);
        }

        return static::upload($client, $params, null, [
            'file' => $file,
            'host' => static::FILE_UPLOAD_HOST,
        ]);
    }

    /**
     * Upload a file by URL and return a new document instance.
     *
     * @param Box\View\Client $client The client instance to make requests from.
     * @param string $url The url of the file to upload.
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
     * @throws Box\View\Exception
     */
    public static function uploadUrl($client, $url, $params = [])
    {
        return static::upload($client, $params, ['url' => $url]);
    }

    /**
     * Update the current document instance with new metadata.
     *
     * @param array $data An associative array to instantiate the object with.
     *                    Use the following values:
     *                      - string 'createdAt' The date the document was
     *                        created.
     *                      - string 'name' The document title.
     *                      - string 'status' The document status, which can be
     *                        'queued', 'processing', 'done', or 'error'.
     */
    private function setValues($data)
    {
        if (isset($data['created_at'])) {
            $data['createdAt'] = $data['created_at'];
            unset($data['created_at']);
        }

        if (isset($data['createdAt'])) {
            $this->createdAt = static::date($data['createdAt']);
        }

        if (isset($data['name']))   $this->name   = $data['name'];
        if (isset($data['status'])) $this->status = $data['status'];
    }

    /**
     * Generic upload function used by the two other upload functions, which are
     * more specific than this one, and know how to handle upload by URL and
     * upload from filesystem.
     *
     * @param Box\View\Client $client The client instance to make requests from.
     * @param array|null $params An associative array of options relating to the
     *                           file upload. Pass-thru from the other upload
     *                           functions.
     * @param array|null $postParams An associative array of POST params to be
     *                               sent in the body.
     * @param array|null $options An associative array of request options that
     *                            may modify the way the request is made.
     *
     * @return Box\View\Document A new document instance.
     * @throws Box\View\Exception
     */
    private static function upload(
        $client,
        $params,
        $postParams = [],
        $options = []
    ) {
        if (!empty($params['name'])) $postParams['name'] = $params['name'];

        if (!empty($params['thumbnails'])) {
            if (is_array($params['thumbnails'])) {
                $params['thumbnails'] = implode(',', $params['thumbnails']);
            }

            $postParams['thumbnails'] = $params['thumbnails'];
        }

        if (!empty($params['nonSvg'])) {
            $postParams['non_svg'] = $params['nonSvg'];
        }

        $metadata = static::request($client, null, null, $postParams, $options);
        return new self($client, $metadata);
    }
}
