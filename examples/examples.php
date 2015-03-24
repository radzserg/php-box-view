<?php
/**
 * Bootstrap
 */
error_reporting(E_ALL);
$exampleApiKey = 'YOUR_API_KEY';

// set the content type to plaintext if we're running this from a web browser
if (php_sapi_name() != 'cli') {
    header('Content-Type: text/plain');
}

require_once __DIR__ . '/../vendor/autoload.php';
$boxView = new Box\View\Client($exampleApiKey);

// when did this script start?
date_default_timezone_set('America/Los_Angeles');
$start = date('c');

// set a couple document variables we'll use later
$document  = null;
$document2 = null;

/*
 * Example #1
 *
 * Upload a file. We're uploading a sample file by URL.
 */
echo 'Example #1 - Upload sample file by URL.' . "\n";
echo '  Uploading... ';

$sampleUrl = 'http://crocodoc.github.io/php-box-view/examples/files/sample.doc';

try {
    $document = $boxView->uploadUrl($sampleUrl, ['name' => 'Sample File']);

    echo 'success :)' . "\n";
    echo '  ID is ' . $document->id() . '.' . "\n";
} catch (Box\View\Exception $e) {
    echo 'failed :(' . "\n";
    echo '  Error Code: ' . $e->errorCode . "\n";
    echo '  Error Message: ' . $e->getMessage() . "\n";
}

/*
 * Example #2
 *
 * Check the metadata of the file from Example #1.
 */
echo "\n";
echo 'Example #2 - Check the metadata of the file we just uploaded.' . "\n";
echo '  Checking metadata... ';

try {
    $documentDuplicate = $boxView->getDocument($document->id());

    echo 'success :)' . "\n";
    echo '  File ID is ' . $documentDuplicate->id() . '.' . "\n";
    echo '  File status is ' . $documentDuplicate->status() . '.' . "\n";
    echo '  File name is ' . $documentDuplicate->name() . '.' . "\n";
    echo '  File was created on ' . $documentDuplicate->createdAt() . '.'
         . "\n";
} catch (Box\View\Exception $e) {
    echo 'failed :(' . "\n";
    echo '  Error Code: ' . $e->errorCode . "\n";
    echo '  Error Message: ' . $e->getMessage() . "\n";
}

/*
 * Example #3
 *
 * Upload another file. We're uploading a sample .doc file from the local
 * filesystem using all options.
 */
echo "\n";
echo 'Example #3 - Upload a sample .doc as a file using all options.' . "\n";

$filePath = __DIR__ . '/files/sample.doc';

if (is_file($filePath)) {
    echo '  Uploading... ';

    $handle = fopen($filePath, 'r');

    try {
        $document2 = $boxView->uploadFile($handle, [
            'name'       => 'Sample File #2',
            'thumbnails' => ['100x100', '200x200'],
            'nonSvg'     => true,
        ]);

        echo 'success :)' . "\n";
        echo '  ID is ' . $document2->id() . '.' . "\n";
    } catch (Box\View\Exception $e) {
        echo 'failed :(' . "\n";
        echo '  Error Code: ' . $e->errorCode . "\n";
        echo '  Error Message: ' . $e->getMessage() . "\n";
    }
} else {
    echo '  Skipping because the sample .doc file can\'t be found.' . "\n";
}

/*
 * Example #4
 *
 * Check the metadata of the file from Example #3.
 */
echo "\n";
echo 'Example #4 - Check the metadata of the file we just uploaded.' . "\n";
echo '  Checking metadata... ';

try {
    $documentDuplicate = $boxView->getDocument($document->id());

    echo 'success :)' . "\n";
    echo '  File ID is ' . $documentDuplicate->id() . '.' . "\n";
    echo '  File status is ' . $documentDuplicate->status() . '.' . "\n";
    echo '  File name is ' . $documentDuplicate->name() . '.' . "\n";
    echo '  File was created on ' . $documentDuplicate->createdAt() . '.'
         . "\n";
} catch (Box\View\Exception $e) {
    echo 'failed :(' . "\n";
    echo '  Error Code: ' . $e->errorCode . "\n";
    echo '  Error Message: ' . $e->getMessage() . "\n";
}

/*
 * Example #5
 *
 * List the documents we've uploaded since starting these examples.
 */
echo "\n";
echo 'Example #5 - List the documents we uploaded so far.' . "\n";
echo '  Listing documents... ';

try {
    $documents = $boxView->findDocuments(['createdAfter' => $start]);

    $doc1 = $documents[1];
    $doc2 = $documents[0];

    echo 'success :)' . "\n";
    echo '  File #1 ID is ' . $doc1->id() .  '.' . "\n";
    echo '  File #1 status is ' . $doc1->status() .  '.' . "\n";
    echo '  File #1 name is ' . $doc1->name() . '.' . "\n";
    echo '  File #1 was created on ' . $doc1->createdAt() .  '.' . "\n";
    echo '  File #2 ID is ' . $doc2->id() .  '.' . "\n";
    echo '  File #2 status is ' . $doc2->status() .  '.' . "\n";
    echo '  File #2 name is ' . $doc2->name() . '.' . "\n";
    echo '  File #2 was created on ' . $doc2->createdAt() .  '.' . "\n";
} catch (Box\View\Exception $e) {
    echo 'failed :(' . "\n";
    echo '  Error Code: ' . $e->errorCode . "\n";
    echo '  Error Message: ' . $e->getMessage() . "\n";
}

/*
 * Example #6
 *
 * Wait ten seconds and check the status of both files.
 */
echo "\n";
echo 'Example #6 - Wait ten seconds and check the status of both files.' . "\n";
echo '  Waiting... ';
sleep(10);
echo 'done.' . "\n";
echo '  Checking statuses... ';

try {
    $documents = $boxView->findDocuments(['createdAfter' => $start]);

    $doc1 = $documents[1];
    $doc2 = $documents[0];

    echo 'success :)' . "\n";
    echo '  Status for file #1 (id=' . $doc1->id() .  ') is '
         . $doc1->status() . '.' . "\n";
    echo '  Status for file #2 (id=' . $doc2->id() .  ') is '
         . $doc2->status() . '.' . "\n";
} catch (Box\View\Exception $e) {
    echo 'failed :(' . "\n";
    echo '  Error Code: ' . $e->errorCode . "\n";
    echo '  Error Message: ' . $e->getMessage() . "\n";
}

/*
 * Example #7
 *
 * Delete the file we uploaded from Example #1.
 */
echo "\n";
echo 'Example #7 - Delete the second file we uploaded.' . "\n";
echo '  Deleting... ';

try {
    $deleted = $document2->delete();

    if ($deleted) {
        echo 'success :)' . "\n";
        echo '  File was deleted.' . "\n";
    } else {
        echo 'failed :(' . "\n";
    }
} catch (Box\View\Exception $e) {
    echo 'failed :(' . "\n";
    echo '  Error Code: ' . $e->errorCode . "\n";
    echo '  Error Message: ' . $e->getMessage() . "\n";
}

/*
 * Example #8
 *
 * Update the name of the file from Example #1.
 */
echo "\n";
echo 'Example #8 - Update the name of a file.' . "\n";
echo '  Updating... ';

try {
    $document->update(['name' => 'Updated Name']);

    echo 'success :)' . "\n";
    echo '  File ID is ' . $document->id() .  '.' . "\n";
    echo '  File status is ' . $document->status() .  '.' . "\n";
    echo '  File name is ' . $document->name() . '.' . "\n";
    echo '  File was created on ' . $document->createdAt() .  '.' . "\n";
} catch (Box\View\Exception $e) {
    echo 'failed :(' . "\n";
    echo '  Error Code: ' . $e->errorCode . "\n";
    echo '  Error Message: ' . $e->getMessage() . "\n";
}

/*
 * Example #9
 *
 * Download the file we uploaded from Example #1 in its original file format.
 */
echo "\n";
echo 'Example #9 - Download a file in its original file format.' . "\n";
echo '  Downloading... ';

try {
    $contents = $document->download();
    $filename = __DIR__ . '/files/test-original.doc';
    $handle   = fopen($filename, 'w');

    fwrite($handle, $contents);
    fclose($handle);

    echo 'success :)' . "\n";
    echo '  File was downloaded to ' . $filename . '.' . "\n";
} catch (Box\View\Exception $e) {
    echo 'failed :(' . "\n";
    echo '  Error Code: ' . $e->errorCode . "\n";
    echo '  Error Message: ' . $e->getMessage() . "\n";
}

/*
 * Example #10
 *
 * Download the file we uploaded from Example #1 as a PDF.
 */
echo "\n";
echo 'Example #10 - Download a file as a PDF.' . "\n";
echo '  Downloading... ';

try {
    $contents = $document->download('pdf');
    $filename = __DIR__ . '/files/test.pdf';
    $handle   = fopen($filename, 'w');

    fwrite($handle, $contents);
    fclose($handle);

    echo 'success :)' . "\n";
    echo '  File was downloaded to ' . $filename . '.' . "\n";
} catch (Box\View\Exception $e) {
    echo 'failed :(' . "\n";
    echo '  Error Code: ' . $e->errorCode . "\n";
    echo '  Error Message: ' . $e->getMessage() . "\n";
}

/*
 * Example #11
 *
 * Download the file we uploaded from Example #1 as a zip file.
 */
echo "\n";
echo 'Example #11 - Download a file as a zip.' . "\n";
echo '  Downloading... ';

try {
    $contents = $document->download('zip');
    $filename = __DIR__ . '/files/test.zip';
    $handle   = fopen($filename, 'w');

    fwrite($handle, $contents);
    fclose($handle);

    echo 'success :)' . "\n";
    echo '  File was downloaded to ' . $filename . '.' . "\n";
} catch (Box\View\Exception $e) {
    echo 'failed :(' . "\n";
    echo '  Error Code: ' . $e->errorCode . "\n";
    echo '  Error Message: ' . $e->getMessage() . "\n";
}

/*
 * Example #12
 *
 * Download the file we uploaded from Example #1 as a small thumbnail.
 */
echo "\n";
echo 'Example #12 - Download a small thumbnail from a file.' . "\n";
echo '  Downloading... ';

try {
    $thumbnailContents = $document->thumbnail(16, 16);
    $filename          = __DIR__ . '/files/test-thumbnail.png';
    $handle            = fopen($filename, 'w');

    fwrite($handle, $thumbnailContents);
    fclose($handle);

    echo 'success :)' . "\n";
    echo '  File was downloaded to ' . $filename . '.' . "\n";
} catch (Box\View\Exception $e) {
    echo 'failed :(' . "\n";
    echo '  Error Code: ' . $e->errorCode . "\n";
    echo '  Error Message: ' . $e->getMessage() . "\n";
}

/*
 * Example #13
 *
 * Download the file we uploaded from Example #1 as a large thumbnail.
 */
echo "\n";
echo 'Example #13 - Download a large thumbnail from a file.' . "\n";
echo '  Downloading... ';

try {
    $thumbnailContents = $document->thumbnail(250, 250);
    $filename          = __DIR__ . '/files/test-thumbnail-large.png';
    $handle            = fopen($filename, 'w');

    fwrite($handle, $thumbnailContents);
    fclose($handle);

    echo 'success :)' . "\n";
    echo '  File was downloaded to ' . $filename . '.' . "\n";
} catch (Box\View\Exception $e) {
    echo 'failed :(' . "\n";
    echo '  Error Code: ' . $e->errorCode . "\n";
    echo '  Error Message: ' . $e->getMessage() . "\n";
}

/*
 * Example #14
 *
 * Create a session for the file we uploaded from Example #1 with default
 * options.
 */
echo "\n";
echo 'Example #14 - Create a session for a file with default options.' . "\n";
echo '  Creating... ';

$session = null;

try {
    $session = $document->createSession();

    echo 'success :)' . "\n";
    echo '  Session ID is ' . $session->id() .  '.' . "\n";
    echo '  Session expires on ' . $session->expiresAt() . '.' . "\n";
    echo '  Session view URL is ' . $session->viewUrl() . '.' . "\n";
    echo '  Session assets URL is ' . $session->assetsUrl() . '.' . "\n";
    echo '  Session realtime URL is ' . $session->realtimeUrl() . '.'
         . "\n";
} catch (Box\View\Exception $e) {
    echo 'failed :(' . "\n";
    echo '  Error Code: ' . $e->errorCode . "\n";
    echo '  Error Message: ' . $e->getMessage() . "\n";
}

/*
 * Example #15
 *
 * Create a session for the file we uploaded from Example #1 all of the options.
 */
echo "\n";
echo 'Example #15 - Create a session for a file with more of the options.'
     . "\n";
echo '  Creating... ';

$session2 = null;

try {
    $session2 = $document->createSession([
        'expiresAt'        => date('c', strtotime('+10 min')),
        'isDownloadable'   => true,
        'isTextSelectable' => false,
    ]);

    echo 'success :)' . "\n";
    echo '  Session ID is ' . $session2->id() .  '.' . "\n";
    echo '  Session expires on ' . $session2->expiresAt() . '.' . "\n";
    echo '  Session view URL is ' . $session2->viewUrl() . '.' . "\n";
    echo '  Session assets URL is ' . $session2->assetsUrl() . '.' . "\n";
    echo '  Session realtime URL is ' . $session2->realtimeUrl() . '.'
         . "\n";
} catch (Box\View\Exception $e) {
    echo 'failed :(' . "\n";
    echo '  Error Code: ' . $e->errorCode . "\n";
    echo '  Error Message: ' . $e->getMessage() . "\n";
}

/*
 * Example #16
 *
 * Delete the two sessions.
 */
echo "\n";
echo 'Example #16 - Delete the two sessions.' . "\n";
echo '  Deleting session #1... ';

try {
    $deleted = $session->delete();

    if ($deleted) {
        echo 'success :)' . "\n";
        echo '  Session #1 was deleted.' . "\n";
    } else {
        echo 'failed :(' . "\n";
    }
} catch (Box\View\Exception $e) {
    echo 'failed :(' . "\n";
    echo '  Error Code: ' . $e->errorCode . "\n";
    echo '  Error Message: ' . $e->getMessage() . "\n";
}

echo '  Deleting session #2... ';

try {
    $deleted = $session2->delete();

    if ($deleted) {
        echo 'success :)' . "\n";
        echo '  Session #2 was deleted.' . "\n";
    } else {
        echo 'failed :(' . "\n";
    }
} catch (Box\View\Exception $e) {
    echo 'failed :(' . "\n";
    echo '  Error Code: ' . $e->errorCode . "\n";
    echo '  Error Message: ' . $e->getMessage() . "\n";
}

/*
 * Example #17
 *
 * Delete the file we uploaded from Example #1.
 */
echo "\n";
echo 'Example #17 - Delete the first file we uploaded.' . "\n";
echo '  Deleting... ';

try {
    $deleted = $document->delete();

    if ($deleted) {
        echo 'success :)' . "\n";
        echo '  File was deleted.' . "\n";
    } else {
        echo 'failed :(' . "\n";
    }
} catch (Box\View\Exception $e) {
    echo 'failed :(' . "\n";
    echo '  Error Code: ' . $e->errorCode . "\n";
    echo '  Error Message: ' . $e->getMessage() . "\n";
}
