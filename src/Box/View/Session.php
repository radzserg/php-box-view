<?php
namespace Box\View;

/**
 * Provides access to the Box View Session API. The Session API is used to
 * to create sessions for specific documents that can be used to view a
 * document using a specific session-based URL.
 */
class Session extends Request
{
    /**
     * The Download API path relative to the base API path
     * 
     * @var string
     */
    public static $path = '/sessions';
    
    /**
     * Create a session for a specific document by ID that may expire.
     * 
     * @param string $id The id of the file to create a session for.
     * 
     * @return array An array representing the metadata of the session.
     * @throws Box\View\Exception
     */
    public static function create(
        $id,
        $duration = null,
        $expiresAt = null,
        $isDownloadable = null,
        $isTextSelectable = null
    ) {
        $postParams = [
            'document_id' => $id,
        ];
        if (isset($duration)) $postParams['duration'] = $duration;

        if (isset($isDownloadable)) {
            $postParams['is_downloadable'] = $isDownloadable;
        }

        if (isset($isTextSelectable)) {
            $postParams['is_text_selectable'] = $isTextSelectable;
        }

        return static::_request(null, null, $postParams);
    }

    /**
     * Delete a session by ID.
     * 
     * @param string $id The ID of the session to delete.
     * 
     * @return bool Was the session deleted?
     * @throws Box\View\Exception
     */
    public static function delete($id)
    {
        $options = [
            'httpMethod' => 'DELETE',
            'rawResponse' => true,
        ];
        $response = static::_request('/' . $id, null, null, $options);

        // a successful delete returns nothing, so we return true in that case
        return empty($response);
    }
}
