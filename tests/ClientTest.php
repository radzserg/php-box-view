<?php
namespace Box\View\Tests;

use \Mockery as m;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Box\View\Client
     */
    private $client;

    public function setUp()
    {
        $apiKey       = 'abc123';
        $this->client = new \Box\View\Client($apiKey);
    }

    public function tearDown()
    {
        m::close();
    }

    public function testConstructor()
    {
        $client = new \Box\View\Client(null);

        $this->assertNull($client->getApiKey());
    }

    /**
     * @runInSeparateProcess
     */
    public function testDefaultFindDocuments()
    {
        $documentMock = m::mock('alias:\Box\View\Document');
        $documents    = [new \Box\View\Document(), new \Box\View\Document()];

        $documentMock->shouldReceive('find')
                     ->with($this->client, [])
                     ->andReturn($documents);

        $response = $this->client->findDocuments();

        $this->assertEquals($documents, $response);
    }

    /**
     * @runInSeparateProcess
     */
    public function testFindDocumentsWithOptions()
    {
        $limit         = 1;
        $createdAfter  = date('r', strtotime('-2 weeks'));
        $createdBefore = date('r', strtotime('-1 week'));

        $documentMock = m::mock('alias:\Box\View\Document');
        $documents    = [new \Box\View\Document(), new \Box\View\Document()];
        $options      = [
            'limit'         => $limit,
            'createdAfter'  => $createdAfter,
            'createdBefore' => $createdBefore,
        ];

        $documentMock->shouldReceive('find')
                     ->with($this->client, $options)
                     ->andReturn($documents);

        $response = $this->client->findDocuments($options);

        $this->assertEquals($documents, $response);
    }

    public function testGetApiKey()
    {
        $apiKey = 'abc123';
        $client = new \Box\View\Client($apiKey);

        $this->assertEquals($apiKey, $client->getApiKey());
    }

    /**
     * @runInSeparateProcess
     */
    public function testGetDocument()
    {
        $id           = 123;
        $documentMock = m::mock('alias:\Box\View\Document')->makePartial();
        $document     = new \Box\View\Document(['id' => $id]);

        $documentMock->shouldReceive('get')
                     ->with($this->client, $id)
                     ->andReturn($document);

        $response = $this->client->getDocument($id);

        $this->assertEquals($document, $response);
    }

    public function testGetRequestHandler()
    {
        $handler = $this->client->getRequestHandler();

        $this->assertInstanceOf('\Box\View\Request', $handler);
    }

    public function testSetApiKey()
    {
        $apiKey = 'abc123';
        $this->client->setApiKey($apiKey);

        $this->assertEquals($apiKey, $this->client->getApiKey());
    }

    public function testSetRequestHandler()
    {
        $type        = '\Box\View\RequestFoo';
        $requestMock = m::mock($type);

        $this->client->setRequestHandler($requestMock);
        $handler = $this->client->getRequestHandler();

        $this->assertInstanceOf($type, $handler);
    }

    /**
     * @runInSeparateProcess
     */
    public function testDefaultUploadFile()
    {
        $documentMock = m::mock('alias:\Box\View\Document');
        $filename     = __DIR__ . '/../examples/files/sample.doc';
        $handle       = fopen($filename, 'r');
        $document     = new \Box\View\Document(['id' => 123]);

        $documentMock->shouldReceive('uploadFile')
                     ->with($this->client, $handle, [])
                     ->andReturn($document);

        $response = $this->client->uploadFile($handle);

        $this->assertEquals($document, $response);
    }

    /**
     * @runInSeparateProcess
     */
    public function testUploadFileWithParams()
    {
        $documentMock = m::mock('alias:\Box\View\Document');
        $filename     = __DIR__ . '/../examples/files/sample.doc';
        $handle       = fopen($filename, 'r');
        $document     = new \Box\View\Document(['id' => 123]);
        $newName      = 'Updated Name';

        $documentMock->shouldReceive('uploadFile')
                     ->with($this->client, $handle, ['name' => $newName])
                     ->andReturn($document);

        $response = $this->client->uploadFile($handle, ['name' => $newName]);

        $this->assertEquals($document, $response);
    }

    /**
     * @runInSeparateProcess
     */
    public function testDefaultUploadUrl()
    {
        $documentMock = m::mock('alias:\Box\View\Document');
        $url          = 'http://crocodoc.github.io/php-box-view/examples/files/sample.doc';
        $document     = new \Box\View\Document(['id' => 123]);

        $documentMock->shouldReceive('uploadUrl')
                     ->with($this->client, $url, [])
                     ->andReturn($document);

        $response = $this->client->uploadUrl($url);

        $this->assertEquals($document, $response);
    }

    /**
     * @runInSeparateProcess
     */
    public function testUploadFUrlWithParams()
    {
        $documentMock = m::mock('alias:\Box\View\Document');
        $url          = 'http://crocodoc.github.io/php-box-view/examples/files/sample.doc';
        $document     = new \Box\View\Document(['id' => 123]);
        $newName      = 'Updated Name';

        $documentMock->shouldReceive('uploadUrl')
                     ->with($this->client, $url, ['name' => $newName])
                     ->andReturn($document);

        $response = $this->client->uploadUrl($url, ['name' => $newName]);

        $this->assertEquals($document, $response);
    }

    private function getTestDocument()
    {
        $document = $this->getRawTestDocument();
        return new \Box\View\Document($this->client, $document);
    }

    private function getTestDocuments($limit = null)
    {
        $documents    = [];
        $rawDocuments = $this->getRawTestDocuments($limit);
        $entries      = $rawDocuments['document_collection']['entries'];

        foreach ($entries as $rawDocument) {
            $documents[] = new \Box\View\Document($this->client, $rawDocument);
        }

        return $documents;
    }

    private function getRawTestDocument()
    {
        $documents = $this->getRawTestDocuments(1);
        return $documents['document_collection']['entries'][0];
    }

    private function getRawTestDocuments($limit = null)
    {
        $documents = [
            'document_collection' => [
                'total_count' => 2,
                'entries'     => [
                    [
                        'type'       => 'document',
                        'id'         => '8db7bd32e40d48adac24b3c955f49e23',
                        'status'     => 'processing',
                        'name'       => 'Sample File #2',
                        'created_at' => '2015-02-02T09:13:20Z',
                    ],
                    [
                        'type'       => 'document',
                        'id'         => 'ee7ae7e2ff8d44fca84471d42d74006e',
                        'status'     => 'done',
                        'name'       => 'Sample File',
                        'created_at' => '2015-02-02T09:13:19Z',
                    ],
                ],
            ],
        ];

        if ($limit) {
            $documents = array_slice($documents, 0, $limit);
        }

        return $documents;
    }
}
