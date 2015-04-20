<?php
namespace Box\View\Tests;

use \Mockery as m;

class DocumentTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $apiKey       = 'abc123';
        $this->client = new \Box\View\Client($apiKey);

        $this->requestMock = m::mock('\Box\View\Request');
        $this->client->setRequestHandler($this->requestMock);
    }

    public function tearDown()
    {
        m::close();
    }

    public function testConstructor()
    {
        $rawDocument = $this->getRawTestDocument();
        $document    = $this->getTestDocument();

        $response = new \Box\View\Document($this->client, $rawDocument);

        $this->assertEquals($document, $response);
    }

    /**
     * @runInSeparateProcess
     */
    public function testDefaultCreateSession()
    {
        $sessionMock = m::mock('alias:\Box\View\Session');
        $document    = $this->getTestDocument();
        $session     = $this->getTestSession();

        $sessionMock->shouldReceive('create')
                    ->with($this->client, $document->id(), [])
                    ->andReturn($session);

        $response = $document->createSession();

        $this->assertEquals($session, $response);
    }

    /**
     * @runInSeparateProcess
     */
    public function testCreateSessionWithOptions()
    {
        $sessionMock = m::mock('alias:\Box\View\Session');
        $document    = $this->getTestDocument();
        $session     = $this->getTestSession();
        $options     = [
           'duration'         => 10,
           'expiresAt'        => date('c', strtotime('now')),
           'isDownloadable'   => true,
           'isTextSelectable' => false,
        ];

        $sessionMock->shouldReceive('create')
                    ->with($this->client, $document->id(), $options)
                    ->andReturn($session);

        $response = $document->createSession($options);

        $this->assertEquals($session, $response);
    }

    public function testDelete()
    {
        $document = $this->getTestDocument();

        $this->requestMock
             ->shouldReceive('send')
             ->with('/documents/' . $document->id(), null, null, [
                   'httpMethod'  => 'DELETE',
                   'rawResponse' => true,
               ])
             ->andReturnNull();

        $deleted  = $document->delete();

        $this->assertTrue($deleted);
    }

    public function testDeleteWithNonExistantFile()
    {
        $document = new \Box\View\Document($this->client, ['id' => 123]);

        $this->requestMock
             ->shouldReceive('send')
             ->with('/documents/' . $document->id(), null, null, [
                   'httpMethod'  => 'DELETE',
                   'rawResponse' => true,
               ])
             ->andThrow('Box\View\BoxViewException');

        $failed = false;

        try {
            $deleted = $document->delete();
        } catch (\Box\View\BoxViewException $e) {
            $this->assertInstanceOf('Box\View\BoxViewException', $e);

            $failed = true;
        }

        $this->assertTrue($failed);
    }

    public function testDefaultDownload()
    {
        $document = $this->getTestDocument();
        $path     = '/documents/' . $document->id() . '/content';
        $content  = '123456789';

        $this->requestMock
             ->shouldReceive('send')
             ->with($path, null, null, ['rawResponse' => true])
             ->andReturn($content);

        $response = $document->download();

        $this->assertEquals($content, $response);
    }

    public function testDownloadWithExtension()
    {
        $document = $this->getTestDocument();
        $path     = '/documents/' . $document->id() . '/content.pdf';
        $content  = '123456789';

        $this->requestMock
             ->shouldReceive('send')
             ->with($path, null, null, ['rawResponse' => true])
             ->andReturn($content);

        $response = $document->download('pdf');

        $this->assertEquals($content, $response);
    }

    public function testDownloadWithWrongExtension()
    {
        $document = $this->getTestDocument();
        $path     = '/documents/' . $document->id() . '/content.pdf2';

        $this->requestMock
             ->shouldReceive('send')
             ->with($path, null, null, ['rawResponse' => true])
             ->andThrow('Box\View\BoxViewException');

        $failed = false;

        try {
            $response = $document->download('pdf2');
        } catch (\Box\View\BoxViewException $e) {
            $this->assertInstanceOf('Box\View\BoxViewException', $e);

            $failed = true;
        }

        $this->assertTrue($failed);
    }

    public function testCreatedAt()
    {
        $rawDocument = $this->getRawTestDocument();
        $createdAt   = date('c', strtotime($rawDocument['created_at']));
        $document    = $this->getTestDocument();

        $this->assertEquals($createdAt, $document->createdAt());
    }

    public function testId()
    {
        $rawDocument = $this->getRawTestDocument();
        $document    = $this->getTestDocument();

        $this->assertEquals($rawDocument['id'], $document->id());
    }

    public function testName()
    {
        $rawDocument = $this->getRawTestDocument();
        $document    = $this->getTestDocument();

        $this->assertEquals($rawDocument['name'], $document->name());
    }

    public function testStatus()
    {
        $rawDocument = $this->getRawTestDocument();
        $document    = $this->getTestDocument();

        $this->assertEquals($rawDocument['status'], $document->status());
    }

    public function testThumbnail()
    {
        $document = $this->getTestDocument();
        $content  = '123456789';

        $this->requestMock
             ->shouldReceive('send')
             ->with(
                   '/documents/' . $document->id() . '/thumbnail',
                   [
                       'height' => 100,
                       'width'  => 100,
                   ],
                   null,
                   ['rawResponse' => true]
               )
             ->andReturn($content);

        $response = $document->thumbnail(100, 100);

        $this->assertEquals($content, $response);
    }

    public function testThumbnailNonExistantFile()
    {
        $document = new \Box\View\Document($this->client, ['id' => 123]);
        $content  = '123456789';

        $this->requestMock
             ->shouldReceive('send')
             ->with(
                   '/documents/' . $document->id() . '/thumbnail',
                   [
                       'height' => 100,
                       'width'  => 100,
                   ],
                   null,
                   ['rawResponse' => true]
               )
             ->andThrow('Box\View\BoxViewException');

        $failed = false;

        try {
            $response = $document->thumbnail(100, 100);
        } catch (\Box\View\BoxViewException $e) {
            $this->assertInstanceOf('Box\View\BoxViewException', $e);

            $failed = true;
        }

        $this->assertTrue($failed);
    }

    public function testUpdate()
    {
        $document = $this->getTestDocument();
        $newName  = 'Updated Name';

        $updatedRawDocument         = $this->getRawTestDocument();
        $updatedRawDocument['name'] = $newName;

        $this->requestMock
             ->shouldReceive('send')
             ->with(
                   '/documents/' . $document->id(),
                   null,
                   ['name' => $newName],
                   ['httpMethod' => 'PUT']
               )
             ->andReturn($updatedRawDocument);

        $response = $document->update(['name' => $newName]);

        $this->assertEquals($newName, $document->name());
        $this->assertTrue($response);
    }

    public function testDefaultFind()
    {
        $rawDocuments = $this->getRawTestDocuments();
        $documents    = $this->getTestDocuments();

        $this->requestMock
             ->shouldReceive('send')
             ->with('/documents', null, null, null)
             ->andReturn($rawDocuments);

        $response = \Box\View\Document::find($this->client);

        $this->assertEquals($documents, $response);
    }

    public function testFindWithLimit()
    {
        $limit        = 1;
        $rawDocuments = $this->getRawTestDocuments($limit);
        $documents    = $this->getTestDocuments($limit);

        $this->requestMock
             ->shouldReceive('send')
             ->with('/documents', ['limit' => $limit], null, null)
             ->andReturn($rawDocuments);

        $response = \Box\View\Document::find($this->client, [
            'limit' => $limit,
        ]);

        $this->assertEquals($documents, $response);
        $this->assertCount($limit, $response);
    }

    public function testFindWithOptions()
    {
        $limit         = 1;
        $createdAfter  = date('r', strtotime('-2 weeks'));
        $createdBefore = date('r', strtotime('-1 week'));
        $rawDocuments  = $this->getRawTestDocuments($limit);
        $documents     = $this->getTestDocuments($limit);

        $this->requestMock
             ->shouldReceive('send')
             ->with('/documents', [
                   'limit'          => $limit,
                   'created_before' => date('c', strtotime($createdBefore)),
                   'created_after'  => date('c', strtotime($createdAfter)),
               ], null, null)
             ->andReturn($rawDocuments);

        $response = \Box\View\Document::find($this->client, [
            'limit'          => $limit,
             'createdAfter'  => $createdAfter,
             'createdBefore' => $createdBefore,
        ]);

        $this->assertEquals($documents, $response);
        $this->assertCount($limit, $response);
    }

    public function testDefaultGet()
    {
        $rawDocument = $this->getRawTestDocument();
        $document    = $this->getTestDocument();

        $this->requestMock
             ->shouldReceive('send')
             ->with('/documents/' . $document->id(), [
                   'fields' => 'id,created_at,name,status',
               ], null, null)
             ->andReturn($rawDocument);

        $response = \Box\View\Document::get($this->client, $document->id());

        $this->assertEquals($document->id(), $response->id());
        $this->assertEquals($document, $response);
    }

    public function testGetWithNonExistantFile()
    {
        $id = '123';

        $this->requestMock
             ->shouldReceive('send')
             ->with('/documents/' . $id, [
                   'fields' => 'id,created_at,name,status',
               ], null, null)
             ->andThrow('Box\View\BoxViewException');

        $failed = false;

        try {
            $document = \Box\View\Document::get($this->client, $id);
        } catch (\Box\View\BoxViewException $e) {
            $this->assertInstanceOf('Box\View\BoxViewException', $e);

            $failed = true;
        }

        $this->assertTrue($failed);
    }

    public function testDefaultUploadFile()
    {
        $filename    = __DIR__ . '/../examples/files/sample.doc';
        $handle      = fopen($filename, 'r');
        $rawDocument = $this->getRawTestDocument();
        $document    = $this->getTestDocument();

        $this->requestMock
             ->shouldReceive('send')
             ->with('/documents', null, null, [
                   'file' => $handle,
                   'host' => \Box\View\Document::FILE_UPLOAD_HOST,
               ])
             ->andReturn($rawDocument);

        $response = \Box\View\Document::uploadFile($this->client, $handle);

        $this->assertEquals($document, $response);
    }

    public function testUploadFileWithInvalidFile()
    {
        $handle = null;

        $this->requestMock
             ->shouldReceive('send')
             ->with('/documents', null, null, [
                   'file' => $handle,
                   'host' => \Box\View\Document::FILE_UPLOAD_HOST,
               ])
             ->andThrow('Box\View\BoxViewException');

        $failed = false;

        try {
            $response = \Box\View\Document::uploadFile($this->client, $handle);
        } catch (\Box\View\BoxViewException $e) {
            $this->assertInstanceOf('Box\View\BoxViewException', $e);
            $this->assertEquals(
                \Box\View\Document::INVALID_FILE_ERROR,
                $e->errorCode
            );

            $message = '$file is not a valid file resource.';

            $this->assertEquals($message, $e->getMessage());

            $failed = true;
        }

        $this->assertTrue($failed);
    }

    public function testUploadFileWithParams()
    {
        $filename = __DIR__ . '/../examples/files/sample.doc';
        $handle   = fopen($filename, 'r');
        $newName  = 'Updated Name';

        $rawDocument         = $this->getRawTestDocument();
        $rawDocument['name'] = $newName;

        $document = new \Box\View\Document($this->client, [
            'id'        => $rawDocument['id'],
            'createdAt' => $rawDocument['created_at'],
            'name'      => $newName,
            'status'    => $rawDocument['status'],
        ]);

        $this->requestMock
             ->shouldReceive('send')
             ->with(
                   '/documents',
                   null,
                   ['name' => $newName],
                   [
                       'file' => $handle,
                       'host' => \Box\View\Document::FILE_UPLOAD_HOST,
                   ]
               )
             ->andReturn($rawDocument);

        $response = \Box\View\Document::uploadFile($this->client, $handle, [
            'name' => $newName,
        ]);

        $this->assertEquals($document, $response);
        $this->assertEquals($newName, $response->name());
    }

    public function testDefaultUploadUrl()
    {
        $url         = 'http://crocodoc.github.io/php-box-view/examples/files/sample.doc';
        $rawDocument = $this->getRawTestDocument();
        $document    = $this->getTestDocument();

        $this->requestMock
             ->shouldReceive('send')
             ->with('/documents', null, ['url' => $url], null)
             ->andReturn($rawDocument);

        $response = \Box\View\Document::uploadUrl($this->client, $url);

        $this->assertEquals($document, $response);
    }

    public function testUploadUrlWithInvalidFile()
    {
        $url = 'http://foo.bar';

        $this->requestMock
             ->shouldReceive('send')
             ->with('/documents', null, ['url' => $url], null)
             ->andThrow('Box\View\BoxViewException');

        $failed = false;

        try {
            $response = \Box\View\Document::uploadUrl($this->client, $url);
        } catch (\Box\View\BoxViewException $e) {
            $this->assertInstanceOf('Box\View\BoxViewException', $e);

            $failed = true;
        }

        $this->assertTrue($failed);
    }

    public function testUploadUrlWithParams()
    {
        $url     = 'http://crocodoc.github.io/php-box-view/examples/files/sample.doc';
        $newName = 'Updated Name';

        $rawDocument         = $this->getRawTestDocument();
        $rawDocument['name'] = $newName;

        $document = new \Box\View\Document($this->client, [
            'id'        => $rawDocument['id'],
            'createdAt' => $rawDocument['created_at'],
            'name'      => $newName,
            'status'    => $rawDocument['status'],
        ]);

        $this->requestMock
             ->shouldReceive('send')
             ->with('/documents', null, [
                   'name' => $newName,
                   'url'  => $url,
               ], null)
             ->andReturn($rawDocument);

        $response = \Box\View\Document::uploadUrl($this->client, $url, [
            'name' => $newName,
        ]);

        $this->assertEquals($document, $response);
        $this->assertEquals($newName, $response->name());
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

    private function getTestSession()
    {
        $session = $this->getRawTestSession();
        return new \Box\View\Session($this->client, $session);
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
            $entries = $documents['document_collection']['entries'];
            $documents['document_collection']['entries']
                = array_slice($entries, 0, $limit);
        }

        return $documents;
    }

    private function getRawTestSession()
    {
        return [
            'type'       => 'session',
            'id'         => 'c3d082985d08425faacb744aa28a8ba3',
            'document'   => [
                'type'       => 'document',
                'id'         => 'f5f342c440b84dcfa4104eaae49cdead',
                'status'     => 'done',
                'name'       => 'Updated Name',
                'created_at' => '2015-02-02T09:16:19Z',
            ],
            'expires_at' => '2015-02-02T10:16:39.876Z',
            'urls'       => [
                'view'     => 'https://view-api.box.com/1/sessions/c3d082985d08425faacb744aa28a8ba3/view',
                'assets'   => 'https://view-api.box.com/1/sessions/c3d082985d08425faacb744aa28a8ba3/assets/',
                'realtime' => 'https://view-api.box.com/sse/c3d082985d08425faacb744aa28a8ba3',
            ],
        ];
    }
}
