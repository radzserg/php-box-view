<?php
namespace Box\View;

/**
 * Provides access to the Box View Document API. The Document API is used for
 * uploading, checking status, and deleting documents.
 */
class Document extends Request
{
    /**
     * The Document API path relative to the base API path.
     * 
     * @var string
     */
    public static $path = '/documents';

    /**
     * Generic upload function used by the two other upload functions, which are
     * more specific than this one, and know how to handle upload by URL and
     * upload from filesystem.
     * 
     * @param array|null $params An associative array of options relating to the
     *                           file upload. Pass-thru from the other upload
     *                           functions.
     * @param array|null $postParams An associative array of POST params to be
     *                               sent in the body.
     * @param array|null $options An associative array of request options that
     *                            may modify the way the request is made.
     * 
     * @return array An associative array representing the metadata of the file.
     * @throws Box\View\Exception
     */
    private static function _upload($params, $postParams = [], $options = [])
    {
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

        return static::_request(null, null, $postParams, $options);
    }
    
    /**
     * Delete a file by ID.
     * 
     * @param string $id The ID of the file to delete.
     * 
     * @return bool Was the file deleted?
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

    /**
     * Download a file using a specific extension or the original extension.
     * 
     * @param string $id The ID of the file to download.
     * @param string|null $extension Optional. The extension to download the
     *                               file in, which can be pdf or zip. If no
     *                               extension is provided, the file will be
     *                               downloaded using the original extension.
     * 
     * @return string The contents of the downloaded file.
     * @throws Box\View\Exception
     */
    public static function download($id, $extension = null)
    {
        $path = '/' . $id . '/content' . ($extension ? '.' . $extension : '');
        $options = [
            'rawResponse' => true,
        ];
        return static::_request($path, null, null, $options);
    }

    /**
     * Get a list of all documents that meet the provided criteria.
     * 
     * @param array|null $params Optional. An associative array to filter the
     *                           list of all documents uploaded. None are
     *                           necessary; all are optional. Use the following
     *                           options:
     *                             - integer|null 'limit' The number of
     *                               documents to return.
     *                             - string|DateTime|null 'createdBefore'
     *                               Upper date limit to filter by .
     *                             - string|DateTime|null 'createdAfter'
     *                               Lower limit to filter by.
     * 
     * @return array An array containing a list of documents.
     * @throws Box\View\Exception
     */
    public static function listDocuments($params = [])
    {
        $getParams = [];
        if (!empty($params['limit'])) $getParams['limit'] = $params['limit'];

        if (!empty($params['createdBefore'])) {
            $createdBefore = $params['createdBefore'];
            $getParams['created_before'] = static::_date($createdBefore);
        }

        if (!empty($params['createdAfter'])) {
            $createdAfter = $params['createdAfter'];
            $getParams['created_after'] = static::_date($createdAfter);
        }

        return static::_request(null, $getParams);
    }
    
    /**
     * Get specific fields from the metadata of a file.
     * 
     * @param string $id The ID of the file to check.
     * @param string[]|string $fields The fields to return with the metadata,
     *                                formatted as an array or a comma-separated
     *                                string. Regardless of which fields are
     *                                provided, id and type are always returned.
     * 
     * @return array An associative array representing the metadata of the file.
     * @throws Box\View\Exception
     */
    public static function metadata($id, $fields)
    {
        $getParams = [
            'fields' => $fields,
        ];
        return static::_request('/' . $id, $getParams);
    }

    /**
     * Download a thumbnail of a specific size for a file.
     * 
     * @param string $id The ID of the file to download a thumbnail for.
     * @param width The width of the thumbnail in pixels.
     * @param height The height of the thumbnail in pixels.
     * 
     * @return string The contents of the downloaded thumbnail.
     * @throws Box\View\Exception
     */
    public static function thumbnail($id, $width, $height)
    {
        $getParams = [
            'height' => $height,
            'width' => $width,
        ];
        $options = [
            'rawResponse' => true,
        ];
        return static::_request(
            '/' . $id . '/thumbnail',
            $getParams, null,
            $options
        );
    }

    /**
     * Update specific fields for the metadata of a file .
     * 
     * @param string $id The ID of the file to check.
     * @param string[] $fields The fields to return with the metadata.
     *                         Regardless of which fields are provided, id and
     *                         type are always returned.
     * 
     * @return array An associative array representing the metadata of the file.
     * @throws Box\View\Exception
     */
    public static function update($id, $fields)
    {
        $postParams = [];

        $supportedFields = ['name'];

        foreach ($supportedFields as $field) {
            if (isset($fields[$field])) $postParams[$field] = $fields[$field];
        }

        $options = [
            'httpMethod' => 'PUT',
        ];
        return static::_request('/' . $id, null, $postParams, $options);
    }

    /**
     * Upload a local file.
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
     * @return array An associative array representing the metadata of the file.
     * @throws Box\View\Exception
     */
    public static function uploadFile($file, $params = [])
    {
        if (!is_resource($file)) {
            $message = '$file is not a valid file resource.';
            return static::_error('invalid_file', $message);
        }

        $options = [
            'file' => $file,
            'host' => 'upload.view-api.box.com',
        ];
        return static::_upload($params, null, $options);
    }

    /**
     * Upload a file by URL.
     * 
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
     * @return array An associative array representing the metadata of the file.
     * @throws Box\View\Exception
     */
    public static function uploadUrl($url, $params = [])
    {
        $postParams = [
            'url' => $url,
        ];
        return static::_upload($params, $postParams);
    }
}
