<?php
require_once dirname(__FILE__) . '/../BoxView.php';

/**
 * Provides access to the Box View Document API. The Document API is used for
 * uploading, checking status, and deleting documents.
 */
class BoxView_Document extends BoxView {
    /**
     * The Document API path relative to the base API path.
     * 
     * @var string
     */
    public static $path = '/documents';
    
    /**
     * Delete a file by ID.
     * 
     * @param string $id The ID of the file to delete.
     * 
     * @return bool Was the file deleted?
     * @throws BoxView_Exception
     */
    public static function delete($id) {
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
     * @param string $extension The extension to download the file in, which can
     *                             be pdf or zip. If no extension is provided,
     *                             the file will be downloaded using the original
     *                             extension.
     * 
     * @return file A file to be downloaded.
     * @throws BoxView_Exception
     */
    public static function download($id, $extension = null) {
        $path = '/' . $id . '/content' . ($extension ? '.' . $extension : '');
        $options = [
            'rawResponse' => true,
        ];
        return static::_request($path, null, null, $options);
    }

    /**
     * Get a list of all documents that meet the provided criteria.
     * 
     * @param integer $limit The number of documents to return.
     * @param string|DateTime $createdBefore Upper date limit to filter by.
     * @param string|DateTime $createdAfter Lower limit to filter by.
     * 
     * @return array An array containing a list of documents.
     * @throws BoxView_Exception
     */
    public static function listDocuments($limit = null, $createdBefore = null,
                                         $createdAfter = null) {
        $getParams = [];
        if ($limit) $getParams['limit'] = $limit;

        if ($createdBefore) {
            $getParams['created_before'] = static::_date($createdBefore);
        }

        if ($createdAfter) {
            $getParams['created_after'] = static::_date($createdAfter);
        }

        return static::_request(null, $getParams);
    }
    
    /**
     * Get specific fields from the metadata of a file.
     * 
     * @param string $id The ID of the file to check.
     * @param string[]|string $fields The fields to return with the metadata,
     *                                   formatted as an array or a comma-separated
     *                                   string. Regardless of which fields are
     *                                   provided, id and type are always returned.
     * 
     * @return array An array of the metadata for the file.
     * @throws BoxView_Exception
     */
    public static function metadata($id, $fields) {
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
     * @return file A thumbnail to be downloaded.
     * @throws BoxView_Exception
     */
    public static function thumbnail($id, $width, $height) {
        $getParams = [
            'height' => $height,
            'width' => $width,
        ];
        $options = [
            'rawResponse' => true,
        ];
        return static::_request('/' . $id . '/thumbnail', $getParams, null,
                                $options);
    }

    /**
     * Update specific fields for the metadata of a file .
     * 
     * @param string $id The ID of the file to check.
     * @param string[] $fields The fields to return with the metadata.
     *                            Regardless of which fields are provided, id and
     *                            type are always returned.
     * 
     * @return array An array of the metadata for the file.
     * @throws BoxView_Exception
     */
    public static function update($id, $fields) {
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
     * Upload a file to Box View with a local file.
     * 
     * @param resource $file The file resource to upload.
     * @param string $name Override the filename of the file being uploaded.
     * @param string[]|string $thumbnails An array of dimensions in pixels, with 
     *                                       each dimension formatted as
     *                                       [width]x[height], this can also be a
     *                                       comma-separated string.
     * 
     * @return array An array representing the metadata of the file.
     * @throws BoxView_Exception
     */
    public static function uploadFile($file, $name = null, $thumbnails = null,
                                      $nonSvg = null) {
        if (!is_resource($file)) {
            $message = '$file is not a valid file resource.';
            return static::_error('invalid_file', $message);
        }

        $postParams = [];
        if (!empty($name)) $postParams['name'] = $name;

        if (!empty($thumbnails)) {
            if (is_array($thumbnails)) {
                $thumbnails = implode(',', $thumbnails);
            }

            $postParams['thumbnails'] = $thumbnails;
        }

        if (isset($nonSvg)) $postParams['non_svg'] = $nonSvg;

        $options = [
            'file' => $file,
            'host' => 'upload.view-api.box.com',
        ];
        return static::_request(null, null, $postParams, $options);
    }

    /**
     * Upload a file to Box View by URL.
     * 
     * @param string $url The url of the file to upload.
     * @param string $name Override the filename of the file being uploaded.
     * @param string[]|string $thumbnails An array of dimensions in pixels, with 
     *                                   each dimension formatted as
     *                                   [width]x[height], this can also be a
     *                                   comma-separated string.
     * 
     * @return array An array representing the metadata of the file.
     * @throws BoxView_Exception
     */
    public static function uploadUrl($url, $name = null, $thumbnails = null,
                                  $nonSvg = null) {
        $postParams = [
            'url' => $url,
        ];
        if (!empty($name)) $postParams['name'] = $name;

        if (!empty($thumbnails)) {
            if (is_array($thumbnails)) {
                $thumbnails = implode(',', $thumbnails);
            }

            $postParams['thumbnails'] = $thumbnails;
        }

        if (isset($nonSvg)) $postParams['non_svg'] = $nonSvg;

        return static::_request(null, ['foo' => 'bar'], $postParams);
    }
}
