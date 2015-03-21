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
require __DIR__ . '/path/to/root/vendor/autoload.php';
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

You can find your API key where it says `View API Key`.

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
$boxView = new Box\View\Client('YOUR_API_KEY');
```

## Tests

First make sure you're running Composer and that you've run `composer install`.

Run the tests:

```bash
./vendor/bin/phpunit --bootstrap tests/bootstrap.php tests

```

## Support

Please use GitHub's issue tracker for API library support.

## Usage

### Fields

All fields are accessed using getters.
You can find a list of these fields below in their respective sections.

### Errors

Errors are handled by throwing exceptions.
We throw instances of `Box\View\Exception`.

Note that any Box View API call can throw an exception.
When making API calls, put them in a try/catch block.
You can see `examples/examples.php` to see working code for each method using try/catch blocks.

### Document

#### Fields

Field     | Getter
--------- | ------
id        | $document->getId()
createdAt | $document->getCreatedAt()
name      | $document->getName()
status    | $document->getStatus()

#### Upload from File

https://developers.box.com/view/#post-documents
To upload a document from a local file, use `$boxView->uploadFile()`.
Pass in a file resource, and also an optional associative array of other params.
This function returns a `Box\View\Document` object.

```php
// without options
$handle     = fopen($filename, 'r');
$document   = $boxView->uploadFile($handle);

// with options
$handle   = fopen($filename, 'r');
$document = $boxView->uploadFile($handle, [
    'name'       => 'Test File',
    'thumbnails' => ['100x100', '200x200'],
    'nonSvg'     => true,
]);
```

The response looks like this:

```php
object(Box\View\Document)#54 (5) {
  ["createdAt":"Box\View\Document":private]=>
  string(25) "2015-03-11T07:48:52+00:00"
  ["id":"Box\View\Document":private]=>
  string(32) "0971e7674469406dba53254fcbb11d05"
  ["name":"Box\View\Document":private]=>
  string(11) "Sample File"
  ["status":"Box\View\Document":private]=>
  string(6) "queued"
}
```

#### Upload by URL

https://developers.box.com/view/#post-documents
To upload a document by a URL, use `$boxView->uploadUrl()`.
Pass in the URL of the file you want to upload, and also an optional associative array of other params.
This function returns a `Box\View\Document` object.

```php
// without options
$document = $boxView->uploadUrl($url);

// with options
$document = $boxView->uploadUrl($url, [
    'name'       => 'Test File',
    'thumbnails' => ['100x100', '200x200'],
    'nonSvg'     => true,
]);
```

The response looks like this:

```php
object(Box\View\Document)#54 (5) {
  ["createdAt":"Box\View\Document":private]=>
  string(25) "2015-03-11T07:48:52+00:00"
  ["id":"Box\View\Document":private]=>
  string(32) "0971e7674469406dba53254fcbb11d05"
  ["name":"Box\View\Document":private]=>
  string(11) "Sample File"
  ["status":"Box\View\Document":private]=>
  string(6) "queued"
}
```

#### Get Document

https://developers.box.com/view/#get-documents-id
To get a document, use `$boxView->get()`.
Pass in the ID of the document you want to get.
This function returns a `Box\View\Document` object.

```php
$document = $boxView->getDocument($documentId);
```

The response looks like this:

```php
object(Box\View\Document)#54 (5) {
  ["createdAt":"Box\View\Document":private]=>
  string(25) "2015-03-11T07:48:52+00:00"
  ["id":"Box\View\Document":private]=>
  string(32) "0971e7674469406dba53254fcbb11d05"
  ["name":"Box\View\Document":private]=>
  string(11) "Sample File"
  ["status":"Box\View\Document":private]=>
  string(6) "queued"
}
```

#### Find

https://developers.box.com/view/#get-documents
To get a list of documents you've uploaded, use `$boxView->findDocuments()`.
Pass an optional associative array of parameters you want to filter by.
This function returns an array of `Box\View\Document` objects matching the request.

```php
// without options
$documents = $boxView->findDocuments();

// with options
$start     = date('c', strtotime('-2 weeks'));
$end       = date('c', strtotime('-1 week'));
$documents = $boxView->findDocuments([
    'limit'         => 10,
    'createdAfter'  => $start,
    'createdBefore' => $end,
]);
```

The response looks like this:

```php
array(2) {
  [0]=>
  object(Box\View\Document)#31 (5) {
    ["createdAt":"Box\View\Document":private]=>
    string(25) "2015-03-11T07:50:41+00:00"
    ["id":"Box\View\Document":private]=>
    string(32) "f2f9be2249e2490da3b0a040d5eaae58"
    ["name":"Box\View\Document":private]=>
    string(14) "Sample File #2"
    ["status":"Box\View\Document":private]=>
    string(10) "processing"
  }
  [1]=>
  object(Box\View\Document)#55 (5) {
    ["createdAt":"Box\View\Document":private]=>
    string(25) "2015-03-11T07:50:40+00:00"
    ["id":"Box\View\Document":private]=>
    string(32) "966f747cb77b4f1b805cc594c9fdd30c"
    ["name":"Box\View\Document":private]=>
    string(11) "Sample File"
    ["status":"Box\View\Document":private]=>
    string(4) "done"
  }
}
```

#### Download

https://developers.box.com/view/#get-documents-id-content
To download a document, use `$document->download()`.
This function returns the contents of the downloaded file.

```php
$contents = $document->download();
$filename = __DIR__ . '/files/new-file.doc';
$handle   = fopen($filename, 'w');

fwrite($handle, $contents);
fclose($handle);
```

The response is just a giant string representing the data of the file.

#### Thumbnail

https://developers.box.com/view/#get-documents-id-thumbnail
To download a document, use `$document->thumbnail()`.
Pass in the width and height in pixels of the thumbnail you want to download.
This function returns the contents of the downloaded thumbnail.

```php
$thumbnailContents = $document->thumbnail(100, 100);
$filename          = __DIR__ . '/files/new-thumbnail.png';
$handle            = fopen($filename, 'w');

fwrite($handle, $thumbnailContents);
fclose($handle);
```

The response is just a giant string representing the data of the file.

#### Update

https://developers.box.com/view/#put-documents-id
To update the metadata of a document, use `$document->update()`.
Pass in the fields you want to update.
Right now, only the `name` field is supported.
This function returns a boolean of whether the file was updated or not.

```php
$updated = $document->update(['name' => 'Updated Name']);

if ($updated) {
    // do something
} else {
    // do something else
}
```

The response looks like this:

```php
bool(true)
```

#### Delete

https://developers.box.com/view/#delete-documents-id
To delete a document, use `$document->delete()`.
This function returns a boolean of whether the file was deleted or not.

```php
$deleted = $document->delete();

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

#### Fields

Field       | Getter
----------- | ------
id          | $session->getId()
document    | $session->getDocument()
expiresAt   | $session->getExpiresAt()
assetsUrl   | $session->getAssetsUrl()
realtimeUrl | $session->getRealtimeUrl()
viewUrl     | $session->getViewUrl()

#### Create

https://developers.box.com/view/#post-sessions
To create a session, use `$document->createSession()`.
Pass in an optional associative array of params.
This function returns a `Box\View\Session` object.

```php
// without options
$session = $document->createSession();

// with options
$session = $document->createSession([
    'expiresAt'        => date('c', strtotime('+10 min')),
    'isDownloadable'   => true,
    'isTextSelectable' => false,
]);
```

The response looks like this:

```php
object(Box\View\Session)#41 (5) {
  ["document":"Box\View\Session":private]=>
  object(Box\View\Document)#27 (5) {
    ...
  }
  ["id":"Box\View\Session":private]=>
  string(32) "d1b8c35a69da43fbb2e978e99589114a"
  ["expiresAt":"Box\View\Session":private]=>
  string(25) "2015-03-11T08:53:23+00:00"
  ["urls":"Box\View\Session":private]=>
  array(3) {
    ["assets"]=>
    string(76) "https://view-api.box.com/1/sessions/d1b8c35a69da43fbb2e978e99589114a/assets/"
    ["realtime"]=>
    string(61) "https://view-api.box.com/sse/d1b8c35a69da43fbb2e978e99589114a"
    ["view"]=>
    string(73) "https://view-api.box.com/1/sessions/d1b8c35a69da43fbb2e978e99589114a/view"
  }
}

```

#### Delete

https://developers.box.com/view/#delete-sessions-id
To delete a session, use `$session->delete()`.
This function returns a boolean of whether the session was deleted or not.

```php
$deleted = $session->delete();

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
