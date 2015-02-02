# php-box-view

## Introduction

php-box-view is a PHP wrapper for the Box View API.
The Box View API lets you upload documents and then generate secure and customized viewing sessions for them.
Our API is based on REST principles and generally returns JSON encoded responses,
and in PHP are converted to associative arrays unless otherwise noted.

For more information about the Box View API, see the [API docs at developers.box.com](https://developers.box.com/view/).

## Installation

### Requirements

* PHP 5.4 or newer

### Install with Composer

We recommend installing `php-box-view` using [Composer](http://getcomposer.org).

If you don't have Composer, you can install it from the command line:

```bash
curl -sS https://getcomposer.org/installer | php
```

Use Composer to install the latest stable version:

```bash
composer require crocodoc/box-view
```

Make sure to add this package to your composer.json file.
And if you aren't doing this already, require Composer's autoloader from your project:

```php
require __DIR__ '/path/to/root/vendor/autoload.php';
```

### Install without Composer

Download the library and put it in your project.
You will also need to download and include [Guzzle](https://github.com/guzzle/guzzle).

From the file you want to include it from, just use the autoload file:

```php
require __DIR__ . '/path/to/box/view/autoload.php';
```

## Getting Started

### Get an API Key

[Create a Box Application](https://app.box.com/developers/services/edit/) to get an API key.
Enter your application name, click the option for `Box View`, and click `Create Application`.
Then click `Configure your application`.

You can find your API key where it says `View APi Key`.

In the future, if you need to return to this page, go to [Box Developers > My Applications](https://app.box.com/developers/services) and click on any of your Box View apps.

### Examples

You can see a number of examples on how to use this library in `examples/examples.php`.
These examples are interactive and you can run this file to see `php-box-view` in action.

To run these examples, open up `examples/examples.php` and change this line to show your API key:

```php
$exampleApiKey = 'YOUR_API_KEY';
```

Save the file, make sure the `examples/files` directory is writeable, and then run `examples/examples.php`:

```php
php examples/examples.php
```

You should see 17 examples run with output in your terminal.
You can inspect the `examples/examples.php` code to see each API call being used.

### Your Code

To start using `php-box-view` in your code, set your API key:

```php
Box\View\Request::setApiKey('YOUR_API_KEY');
```

And now you can start using the methods in `Box\View\Document` and `Box\View\Session`.

Read on to find out more how to use `php-box-view`.

## Usage

### Errors

Errors are handled by throwing exceptions.
We throw instances of Box\View\Exception.

Note that any Box View API call can throw an exception.
When making API calls, put them in a try/catch block.
You can see `examples/examples.php` to see working code for each method using try/catch blocks.

### Document

#### Upload from File

https://developers.box.com/view/#post-documents  
To upload a document from a local file, use Box\View\Document::uploadFile().
Pass in a file resource, and also an optional associative array of other params.
This function returns an associative array representing the metadata of the file.

```php
// without options
$fileHandle = fopen($filePath, 'r');
$metadata = Box\View\Document::uploadFile($fileHandle);

// with options
$fileHandle = fopen($filePath, 'r');
$metadata = Box\View\Document::uploadFile($fileHandle, [
    'name' => 'Test File',
    'thumbnails' => ['100x100', '200x200'],
    'nonSvg' => true,
]);
```

The response looks like this:

```php
array(5) {
  ["type"]=>
  string(8) "document"
  ["id"]=>
  string(32) "32db41a77ca0432fbf88ddd766b4b1e5"
  ["status"]=>
  string(6) "queued"
  ["name"]=>
  string(11) "Sample File"
  ["created_at"]=>
  string(24) "2015-02-02T09:09:47.359Z"
}
```

#### Upload by URL

https://developers.box.com/view/#post-documents  
To upload a document by a URL, use `Box\View\Document::uploadUrl()`.
Pass in the URL of the file you want to upload, and also an optional associative array of other params.
This function returns an associative array representing the metadata of the file.

```php
// without options
$metadata = Box\View\Document::uploadUrl($url);

// with options
$metadata = Box\View\Document::uploadUrl($url, [
    'name' => 'Test File',
    'thumbnails' => ['100x100', '200x200'],
    'nonSvg' => true,
]);
```

The response looks like this:

```php
array(5) {
  ["type"]=>
  string(8) "document"
  ["id"]=>
  string(32) "32db41a77ca0432fbf88ddd766b4b1e5"
  ["status"]=>
  string(6) "queued"
  ["name"]=>
  string(11) "Sample File"
  ["created_at"]=>
  string(24) "2015-02-02T09:09:47.359Z"
}
```

#### Metadata

https://developers.box.com/view/#get-documents-id  
To get a document's metadata, use `Box\View\Document::metadata()`.
Pass in the ID of the file you want to check the metadata of.
This function returns an associative array representing the metadata of the file.

```php
$metadata = Box\View\Document::metadata($file_id);
```

The response looks like this:

```php
array(5) {
  ["type"]=>
  string(8) "document"
  ["id"]=>
  string(32) "32db41a77ca0432fbf88ddd766b4b1e5"
  ["status"]=>
  string(6) "queued"
  ["name"]=>
  string(11) "Sample File"
  ["created_at"]=>
  string(24) "2015-02-02T09:09:47.359Z"
}
```

#### List

https://developers.box.com/view/#get-documents  
To get a list of documents you've uploaded, use `Box\View\Document::listDocuments()`.
Pass an optional associative array of parameters you want to filter by.
This function returns an array of files matching the request.

```php
// without options
$documents = Box\View\Document::listDocuments();

// with options
$start = date('c', strtotime('-2 weeks'));
$end = date('c', strtotime('-1 week'));
$documents = Box\View\Document::listDocuments([
    'limit' => 10,
    'createdAfter' => $start,
    'createdBefore' => $end,
]);
```

The response looks like this:

array(1) {
  ["document_collection"]=>
  array(2) {
    ["total_count"]=>
    int(2)
    ["entries"]=>
    array(2) {
      [0]=>
      array(5) {
        ["type"]=>
        string(8) "document"
        ["id"]=>
        string(32) "8db7bd32e40d48adac24b3c955f49e23"
        ["status"]=>
        string(10) "processing"
        ["name"]=>
        string(14) "Sample File #2"
        ["created_at"]=>
        string(20) "2015-02-02T09:13:20Z"
      }
      [1]=>
      array(5) {
        ["type"]=>
        string(8) "document"
        ["id"]=>
        string(32) "ee7ae7e2ff8d44fca84471d42d74006e"
        ["status"]=>
        string(4) "done"
        ["name"]=>
        string(11) "Sample File"
        ["created_at"]=>
        string(20) "2015-02-02T09:13:19Z"
      }
    }
  }
}


#### Download

https://developers.box.com/view/#get-documents-id-content  
To download a document, use `Box\View\Document::download()`.
Pass in the ID of the file you want to download.
This function returns the contents of the downloaded file.

```php
$contents = Box\View\Document::download($file_id);
$filename = __DIR__ . '/files/new-file.doc';
$handle = fopen($filename, 'w');
fwrite($handle, $contents);
fclose($handle);
```

The response is just a giant string representing the data of the file.

#### Thumbnail

https://developers.box.com/view/#get-documents-id-thumbnail  
To download a document, use `Box\View\Document::thumbnail()`.
Pass in the ID of the file you want to download, and also the width and height in pixels of the thumbnail you want to download.
This function returns the contents of the downloaded thumbnail.

```php
$thumbnailContents = Box\View\Document::thumbnail($file_id, 100, 100);
$filename = __DIR__ . '/files/new-thumbnail.png';
$handle = fopen($filename, 'w');
fwrite($handle, $thumbnailContents);
fclose($handle);
```

The response is just a giant string representing the data of the file.

#### Update

https://developers.box.com/view/#put-documents-id  
To update the metadata of a document, use `Box\View\Document::update()`.
Pass in the ID of the file you want to update, and also the fields you want to update.
Right now, only the name field is supported.
This function returns an associative array representing the metadata of the file.

```php
$metadata = Box\View\Document::update($file_id, [
    'name' => 'Updated Name',
]);
```

The response looks like this:

```php
array(5) {
  ["type"]=>
  string(8) "document"
  ["id"]=>
  string(32) "32db41a77ca0432fbf88ddd766b4b1e5"
  ["status"]=>
  string(6) "queued"
  ["name"]=>
  string(11) "Sample File"
  ["created_at"]=>
  string(24) "2015-02-02T09:09:47.359Z"
}
```

#### Delete

https://developers.box.com/view/#delete-documents-id  
To delete a document, use `Box\View\Document::delete()`.
Pass in the ID of the file you want to delete.
This function returns a boolean of whether the file was deleted or not.

```php
$deleted = Box\View\Document::delete($file_id);

if ($deleted) {
    // do something
} else {
    // do something else
}
```

The response looks like this:

```php
bool(true)
```

### Session

#### Create

https://developers.box.com/view/#post-sessions  
To create a session, use `Box\View\Session::create()`.
Pass in the ID of the file you want to create a session for, and also an optional associative array of other params.
This function returns an associative array representing the metadata of the session.

```php
// without options
$session = Box\View\Session::create($file_id);

// with options
$session = Box\View\Session::create($file_id, [
    'duration' => 10,
    'expiresAt' => date('c', strtotime('+10 min')),
    'isDownloadable' => true,
    'isTextSelectable' => false,
]);
```

The response looks like this:

```php
array(5) {
  ["type"]=>
  string(7) "session"
  ["id"]=>
  string(32) "c3d082985d08425faacb744aa28a8ba3"
  ["document"]=>
  array(5) {
    ["type"]=>
    string(8) "document"
    ["id"]=>
    string(32) "f5f342c440b84dcfa4104eaae49cdead"
    ["status"]=>
    string(4) "done"
    ["name"]=>
    string(12) "Updated Name"
    ["created_at"]=>
    string(20) "2015-02-02T09:16:19Z"
  }
  ["expires_at"]=>
  string(24) "2015-02-02T10:16:39.876Z"
  ["urls"]=>
  array(3) {
    ["view"]=>
    string(73) "https://view-api.box.com/1/sessions/c3d082985d08425faacb744aa28a8ba3/view"
    ["assets"]=>
    string(76) "https://view-api.box.com/1/sessions/c3d082985d08425faacb744aa28a8ba3/assets/"
    ["realtime"]=>
    string(61) "https://view-api.box.com/sse/c3d082985d08425faacb744aa28a8ba3"
  }
}
```

#### Delete

https://developers.box.com/view/#delete-sessions-id  
To delete a session, use `Box\View\Session::delete()`.
Pass in the ID of the session you want to delete.
This function returns a boolean of whether the session was deleted or not.

```php
$deleted = Box\View\Session::delete($session_id);

if ($deleted) {
    // do something
} else {
    // do something else
}
```

The response looks like this:

```php
bool(true)
```

## Tests

COMING SOON

## Support

Please use GitHub's issue tracker for API library support.
