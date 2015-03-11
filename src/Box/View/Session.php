<?php
namespace Box\View;

/**
 * Provide access to the Box View Session API. The Session API is used to create
 * sessions for specific documents that can be used to view a document using a
 * specific session-based URL.
 *
 * Session objects have the following fields:
 *   - string 'id' The session ID.
 *   - string 'expiresAt' The date te session was created.
 *   - array 'urls' An associative array of URLs for 'assets', 'realtime', and
 *                  'view'.
 *
 * When creating a session, the following parameters can be set:
 *   - int|null 'duration' The number of minutes for the session to last.
 *   - string|DateTime|null 'expiresAt' When the session should expire.
 *   - bool|null 'isDownloadable' Should the user be allowed to download the
 *                                original file?
 *   - bool|null 'isTextSelectable' Should the user be allowed to select text?
 */
class Session extends Base
{
    /**
     * The document that created this session.
     * @var Box\View\Document
     */
    public $document;

    /**
     * The session ID.
     * @var string
     */
    public $id;

    /**
     * The date the session expires, formatted as RFC 3339.
     * @var string
     */
    public $expiresAt;

    public $urls = [
        'assets'   => null,
        'realtime' => null,
        'view'     => null,
    ];

    /**
     * The Session API path relative to the base API path.
     *
     * @var string
     */
    public static $path = '/sessions';

    /**
     * Instantiate the document.
     *
     * @param Box\View\Client $client The client instance to make requests from.
     * @param array $data An associative array to instantiate the object with.
     *                    Use the following values:
     *                      - string 'id' The session ID.
     *                      - string 'expiresAt' The date te session was
     *                        created.
     *                      - array 'urls' An associative array of URLs for
     *                        assets', 'realtime', and 'view'.
     */
    public function __construct($client, $data)
    {
        $this->client = $client;

        $this->id = $data['id'];
        $this->setValues($data);
    }

    /**
     * Delete a session.
     *
     * @return bool Was the session deleted?
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
     * Create a session for a specific document by ID.
     *
     * @param Box\View\Client $client The client instance to make requests from.
     * @param string $id The id of the document to create a session for.
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
    public static function create($client, $id, $params = [])
    {
        $postParams = [
            'document_id' => $id,
        ];

        if (isset($params['duration'])) {
            $postParams['duration'] = $params['duration'];
        }
        if (isset($params['expiresAt'])) {
            $postParams['expires_at'] = static::date($params['expiresAt']);
        }

        if (isset($params['isDownloadable'])) {
            $postParams['is_downloadable'] = $params['isDownloadable'];
        }

        if (isset($params['isTextSelectable'])) {
            $postParams['is_text_selectable'] = $params['isTextSelectable'];
        }

        $metadata = static::request($client, null, null, $postParams);
        return new Session($client, $metadata);
    }

    /**
     * Update the current document instance with new metadata.
     *
     * @param array $data An associative array to instantiate the object with.
     *                    Use the following values:
     *                      - string 'expiresAt' The date te session was
     *                        created.
     *                      - array 'urls' An associative array of URLs for
     *                        'assets', 'realtime', and 'view'.
     */
    private function setValues($data)
    {
        if (isset($data['expires_at'])) {
            $data['expiresAt'] = $data['expires_at'];
            unset($data['expires_at']);
        }

        if (isset($data['expiresAt'])) {
            $this->expiresAt = static::date($data['expiresAt']);
        }

        if (isset($data['urls']['assets'])) {
            $this->urls['assets'] = $data['urls']['assets'];
        }

        if (isset($data['urls']['realtime'])) {
            $this->urls['realtime'] = $data['urls']['realtime'];
        }

        if (isset($data['urls']['view'])) {
            $this->urls['view'] = $data['urls']['view'];
        }

        if (isset($data['document'])) {
            $this->document = new Document($this->client, $data['document']);
        }
    }
}
